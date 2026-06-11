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
            $data = json_decode($res, true);
            if (isset($data['result']['keys'])) { $keys = $data['result']['keys']; }
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