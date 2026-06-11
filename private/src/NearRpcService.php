<?php
/**
 * SoulMD Hub - Enterprise NEAR RPC Service
 * Centralized, highly available RPC failover and smart contract querying service.
 * (V5 AgentFi Architecture)
 */

class NearRpcService {
    private static $instance = null;
    private $activeNode = null;
    private $rpcNodes = [];

    private function __construct() {
        // 從 config 載入全域 RPC 節點池
        $this->rpcNodes = defined('NEAR_RPC_NODES') ? NEAR_RPC_NODES : [
            "https://free.rpc.fastnear.com",
            "https://near.lava.build",
            "https://rpc.mainnet.pagoda.co",
            "https://rpc.mainnet.near.org"
        ];
    }

    /**
     * 取得單例實例 (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 動態掃描並取得目前最快的健康 RPC 節點
     */
    public function getHealthyNode() {
        // 如果已經找到健康節點，直接使用緩存
        if ($this->activeNode !== null) {
            return $this->activeNode;
        }

        $payload = json_encode([
            "jsonrpc" => "2.0",
            "id" => "ping",
            "method" => "status",
            "params" => []
        ]);

        foreach ($this->rpcNodes as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 2 // 極速 2 秒超時切換
            ]);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // ✅ B 修復：加強 RPC 回應驗證
            $parse = $this->validateAndParseResponse($res, 'ping');
            if ($httpCode === 200 && $parse['valid']) {
                $this->activeNode = $url;
                return $this->activeNode;
            }
        }
        
        // 萬一全部 Ping 失敗，強制回退至第一個預設節點
        $this->activeNode = $this->rpcNodes[0];
        return $this->activeNode;
    }

    /**
     * ✅ B 修復：統一 RPC 回應驗證（schema + id match + 基本結構）
     * 防止惡意/損壞 RPC 回應導致邏輯錯誤或注入
     */
    private function validateAndParseResponse($rawResponse, $expectedId = null) {
        if (empty($rawResponse)) {
            return ['valid' => false, 'error' => 'empty_response'];
        }

        $data = json_decode($rawResponse, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['valid' => false, 'error' => 'invalid_json'];
        }

        // 必須是 JSON-RPC 2.0
        if (!isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
            return ['valid' => false, 'error' => 'invalid_jsonrpc_version'];
        }

        // 如果指定 expectedId，必須匹配（防止 replay / mismatch 攻擊）
        if ($expectedId !== null) {
            if (!isset($data['id']) || $data['id'] !== $expectedId) {
                return ['valid' => false, 'error' => 'id_mismatch'];
            }
        }

        return ['valid' => true, 'data' => $data];
    }

    /**
     * 執行 Smart Contract 的 View Call (去中心化查詢)
     * * @param string $contractId 智能合約地址
     * @param string $methodName 要呼叫的合約方法名稱
     * @param array  $args       傳入的參數陣列 (會自動轉 JSON + Base64)
     * @param string $finality   預設 'optimistic' 以獲取極速響應，避免 Read-Replica Lag
     * @return array ['status' => 'success'|'not_found'|'timeout'|'error', 'data' => mixed]
     */
    public function viewCall($contractId, $methodName, $args = [], $finality = "optimistic") {
        $nodeUrl = $this->getHealthyNode();
        
        $payload = json_encode([
            "jsonrpc" => "2.0", 
            "id" => "soulmd_query", 
            "method" => "query",
            "params" => [
                "request_type" => "call_function", 
                "finality" => $finality,
                "account_id" => $contractId, 
                "method_name" => $methodName, 
                "args_base64" => base64_encode(json_encode($args))
            ]
        ]);

        $ch = curl_init($nodeUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, 
            CURLOPT_POST => true, 
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'], 
            CURLOPT_TIMEOUT => 5 // 查詢容忍度設為 5 秒
        ]);
        
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $res) {
            // ✅ B 修復：使用統一驗證器
            $parse = $this->validateAndParseResponse($res, 'soulmd_query');
            if (!$parse['valid']) {
                $this->activeNode = null;
                return ['status' => 'error', 'data' => null, 'error' => $parse['error']];
            }
            $data = $parse['data'];
            
            // 成功取得合約回傳數據
            if (isset($data['result']['result'])) {
                $resString = implode(array_map('chr', $data['result']['result']));
                if (trim($resString) === 'null') {
                    return ['status' => 'not_found', 'data' => null]; 
                }
                return ['status' => 'success', 'data' => json_decode($resString, true)];
            }
            
            // 處理 RPC 節點回報的錯誤 (如合約不存在)
            if (isset($data['error'])) {
                return ['status' => 'error', 'data' => null, 'error' => $data['error']];
            }
        }

        // 如果當前節點連線失敗或超時，清空緩存，讓下次請求重新尋找健康節點
        $this->activeNode = null;
        return ['status' => 'timeout', 'data' => null];
    }
}
?>