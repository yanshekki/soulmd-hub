<?php
/**
 * SoulMD Hub - NEAR Authentication Service
 * 🚀 V13 INDESTRUCTIBLE: Flattened Byte Extraction (Immune to all JS formatting bugs)
 */

require_once __DIR__ . '/NearRpcService.php';

class NearAuthService {
    
    private $rpc;

    public function __construct() {
        $this->rpc = NearRpcService::getInstance();
    }

    public function verifyAuthPayload($payload) {
        $accountId = $payload['account_id'] ?? '';
        $message = $payload['message'] ?? '';

        if (!$accountId || !$message) { return ['success' => false, 'error' => '[V13] Missing account_id or message.']; }

        // 1. 驗證時間戳
        $parts = explode(':', $message);
        $timestamp = (int)($parts[1] ?? 0);
        if (abs((time() * 1000) - $timestamp) > 300000) { 
            return ['success' => false, 'error' => '[V13] Authentication signature has expired.'];
        }

        // ==========================================
        // 🚀 無敵公鑰提取器 (不管 JS 傳什麼鬼東西來，自動抽出 32 Bytes)
        // ==========================================
        $pubKeyBytes = $this->extract32Bytes($payload['public_key'] ?? '');
        if (!$pubKeyBytes) { 
            return ['success' => false, 'error' => '[V13] Failed to extract 32-byte Public Key. Received: ' . json_encode($payload['public_key'])]; 
        }

        // ==========================================
        // 🚀 無敵簽章提取器 (自動抽出 64 Bytes)
        // ==========================================
        $sigBytes = $this->extract64Bytes($payload['signature'] ?? '');
        if (!$sigBytes) { 
            return ['success' => false, 'error' => '[V13] Failed to extract 64-byte Signature.']; 
        }

        // 驗證 Nonce
        $nonceArray = $payload['nonce'] ?? [];
        if (isset($nonceArray['data'])) $nonceArray = $nonceArray['data'];
        if (!is_array($nonceArray) || count($nonceArray) !== 32) { 
            return ['success' => false, 'error' => '[V13] Invalid nonce length.']; 
        }

        $recipient = $payload['recipient'] ?? '';

        // ✅ Phase 1 修復：嚴格 recipient 驗證，防止跨站簽名重放攻擊
        // frontend 會用 hostname，後端必須對照預期主機名
        $expectedHost = '';
        if (defined('BASE_URL')) {
            $expectedHost = parse_url(BASE_URL, PHP_URL_HOST) ?: '';
        }
        if (!$expectedHost) {
            $expectedHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        }
        if ($recipient && $expectedHost && $recipient !== $expectedHost) {
            return ['success' => false, 'error' => '[V13] Invalid recipient for this site.'];
        }

        // ✅ Phase 1 修復：持久化 nonce 重放保護
        // 防止 5 分鐘窗口內重放同一個簽名
        $pdo = null;
        $nonceHash = null;
        if (class_exists('Database') || file_exists(__DIR__ . '/Database.php')) {
            if (!class_exists('Database')) {
                require_once __DIR__ . '/Database.php';
            }
            try {
                $db = Database::getInstance();
                $pdo = $db->getConnection();

                // 計算 nonce hash (account + nonce bytes)
                $nonceBytes = '';
                foreach ($nonceArray as $b) { $nonceBytes .= chr((int)$b); }
                $nonceHash = hash('sha256', $accountId . '|' . $nonceBytes);

                // 輕量清理舊 nonce（5分鐘窗口 + buffer）
                $pdo->prepare("DELETE FROM used_auth_nonces WHERE created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)")->execute();

                // 檢查是否已使用（replay）
                $stmt = $pdo->prepare("SELECT 1 FROM used_auth_nonces WHERE nonce_hash = ? LIMIT 1");
                $stmt->execute([$nonceHash]);
                if ($stmt->fetchColumn()) {
                    return ['success' => false, 'error' => '[V13] Replay attack detected (nonce already used).'];
                }
            } catch (\Throwable $e) {
                error_log('Nonce replay protection DB error (continuing without): ' . $e->getMessage());
                $pdo = null;
                $nonceHash = null;
            }
        }

        // NEP-0413 驗證程序
        $buffer = $this->serializeNep413($message, $nonceArray, $recipient);
        $messageToVerify = hash('sha256', $buffer, true);

        if (!sodium_crypto_sign_verify_detached($sigBytes, $messageToVerify, $pubKeyBytes)) {
            return ['success' => false, 'error' => '[V13] Cryptographic signature verification failed!'];
        }

        // 鏈上金鑰比對
        $nodeUrl = $this->rpc->getHealthyNode();
        $rpcPayload = json_encode([
            "jsonrpc" => "2.0", "id" => "verify_key", "method" => "query",
            "params" => [ "request_type" => "view_access_key_list", "finality" => "final", "account_id" => $accountId ]
        ]);

        $ch = curl_init($nodeUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $rpcPayload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 5
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        $keys = [];
        if ($res) {
            // ✅ B 修復：加強 RPC 回應驗證（schema + id）
            $data = json_decode($res, true);
            if (json_last_error() === JSON_ERROR_NONE 
                && isset($data['jsonrpc']) && $data['jsonrpc'] === '2.0'
                && isset($data['id']) && $data['id'] === 'verify_key'
                && isset($data['result']['keys'])) {
                $keys = $data['result']['keys'];
            } else {
                error_log('[NearAuth] Invalid RPC response for view_access_key_list');
            }
        }

        if (empty($keys)) { return ['success' => false, 'error' => '[V13] Account not found on the blockchain.']; }

        $keyValid = false;
        foreach ($keys as $k) {
            $rpcKeyStr = $k['public_key'] ?? '';
            if (strpos($rpcKeyStr, 'ed25519:') === 0) {
                $rpcKeyBytes = self::base58_decode(substr($rpcKeyStr, 8));
                if ($rpcKeyBytes === $pubKeyBytes) {
                    $keyValid = true;
                    break;
                }
            }
        }

        if (!$keyValid) { return ['success' => false, 'error' => '[V13] The provided public key does not belong to the account.']; }

        // ✅ 儲存 nonce（只有完整驗證成功後才記錄，防止 replay）
        if ($pdo && $nonceHash) {
            try {
                $ins = $pdo->prepare("INSERT INTO used_auth_nonces (nonce_hash, account_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE created_at = NOW()");
                $ins->execute([$nonceHash, $accountId]);
            } catch (\Throwable $e) {
                error_log('Failed to store used nonce: ' . $e->getMessage());
            }
        }

        return ['success' => true];
    }

    /**
     * 🚀 壓平並提取精準 32 Bytes
     */
    private function extract32Bytes($input) {
        if (is_string($input)) {
            $str = str_replace('ed25519:', '', trim($input));
            if ($str !== '[object Object]') {
                $dec = self::base58_decode($str);
                if (strlen($dec) === 32) return $dec;
            }
        }
        if (is_array($input)) {
            $flat = [];
            array_walk_recursive($input, function($a) use (&$flat) {
                if (is_numeric($a)) { $flat[] = (int)$a; }
            });
            if (count($flat) === 32) return pack('C*', ...$flat);
            // 處理 {keyType: 0, data: [...]} 的情況，壓平後長度為 33，第一個元素是 0
            if (count($flat) === 33 && $flat[0] === 0) return pack('C*', ...array_slice($flat, 1));
        }
        return false;
    }

    /**
     * 🚀 壓平並提取精準 64 Bytes
     */
    private function extract64Bytes($input) {
        if (is_string($input)) {
            $dec = base64_decode($input);
            if (strlen($dec) === 64) return $dec;
        }
        if (is_array($input)) {
            $flat = [];
            array_walk_recursive($input, function($a) use (&$flat) {
                if (is_numeric($a)) { $flat[] = (int)$a; }
            });
            if (count($flat) === 64) return pack('C*', ...$flat);
            // 處理 Buffer 對象被序列化的情況
            if (count($flat) === 65 && $flat[0] === 0) return pack('C*', ...array_slice($flat, 1));
        }
        return false;
    }

    private function serializeNep413($message, $nonceArray, $recipient) {
        $buffer = pack('V', 2147484061) . pack('V', strlen($message)) . $message;
        $nonceBytes = '';
        foreach ($nonceArray as $byte) { $nonceBytes .= chr($byte); }
        $buffer .= $nonceBytes . pack('V', strlen($recipient)) . $recipient . "\x00"; 
        return $buffer;
    }

    public static function base58_decode($base58) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        if (is_string($base58) === false) { return false; }
        if (strlen($base58) === 0) { return ''; }
        $indexes = array_flip(str_split($alphabet));
        $chars = str_split($base58);
        foreach ($chars as $char) { if (isset($indexes[$char]) === false) { return false; } }
        $decimal = '0';
        foreach ($chars as $char) {
            $decimal = bcmul($decimal, $base);
            $decimal = bcadd($decimal, $indexes[$char]);
        }
        $return = '';
        while (bccomp($decimal, 0) > 0) {
            $mod = bcmod($decimal, 256);
            $return .= chr($mod);
            $decimal = bcdiv($decimal, 256);
        }
        $return = strrev($return);
        $leading_zeros = 0;
        for ($i = 0; $i < strlen($base58); $i++) {
            if ($base58[$i] === '1') { $leading_zeros++; } else { break; }
        }
        return str_repeat("\x00", $leading_zeros) . $return;
    }
}
?>