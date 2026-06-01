<?php
/**
 * SoulMD Hub - Enterprise NEAR Authentication Service
 * 🚀 Patched: Handles Base58 decoding and Ed25519 cryptographic signature verification to prevent spoofing.
 */

require_once __DIR__ . '/NearRpcService.php';

class NearAuthService {

    /**
     * Decode Base58 string to raw bytes
     * (Because PHP doesn't have a native base58_decode function)
     */
    public static function base58_decode($base58) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = '58';
        $decoded = '0';
        
        for ($i = 0; $i < strlen($base58); $i++) {
            $current = (string) strpos($alphabet, $base58[$i]);
            $decoded = bcadd(bcmul($decoded, $base), $current);
        }
        
        $hex = '';
        while (bccomp($decoded, '0') > 0) {
            $rem = bcmod($decoded, '256');
            $decoded = bcdiv($decoded, '256');
            $hex = str_pad(dechex((int)$rem), 2, '0', STR_PAD_LEFT) . $hex;
        }
        
        $res = hex2bin($hex);
        
        // Handle leading zeros
        for ($i = 0; $i < strlen($base58) && $base58[$i] === '1'; $i++) {
            $res = "\x00" . $res;
        }
        
        return $res;
    }

    /**
     * Verify the cryptographic signature and ensure the public key belongs to the account
     */
    public static function verifySignature($accountId, $publicKeyB58, $signatureB64, $message) {
        // 1. 檢查系統是否支援 Sodium 加密庫
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            error_log('Critical: Libsodium extension is missing in PHP.');
            return false;
        }

        // 2. 驗證時間戳防重放攻擊 (Replay Attack)
        // Message format expects: "soulmd_auth:<timestamp_ms>"
        $parts = explode(':', $message);
        if (count($parts) !== 2 || $parts[0] !== 'soulmd_auth') {
            return false;
        }
        $timestampSec = (int)($parts[1] / 1000);
        if (abs(time() - $timestampSec) > 300) {
            return false; // 簽章超過 5 分鐘即失效
        }

        // 3. 解碼公鑰與簽名
        $signatureBytes = base64_decode($signatureB64);
        $pubKeyStr = str_replace('ed25519:', '', $publicKeyB58);
        $pubKeyBytes = self::base58_decode($pubKeyStr);

        if (!$pubKeyBytes || strlen($pubKeyBytes) !== 32) {
            return false;
        }

        // 4. 驗證物理密碼學簽章 (Ed25519)
        if (!sodium_crypto_sign_verify_detached($signatureBytes, $message, $pubKeyBytes)) {
            return false; // 偽造的簽名！
        }

        // 5. 透過 RPC 確認該 Public Key 的確屬於該 Account ID
        $rpc = NearRpcService::getInstance();
        $nodeUrl = $rpc->getHealthyNode();

        $payload = json_encode([
            "jsonrpc" => "2.0",
            "id" => "dontcare",
            "method" => "query",
            "params" => [
                "request_type" => "view_access_key_list",
                "finality" => "final",
                "account_id" => $accountId
            ]
        ]);

        $ch = curl_init($nodeUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 5
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $res) {
            $data = json_decode($res, true);
            if (isset($data['result']['keys'])) {
                foreach ($data['result']['keys'] as $keyObj) {
                    if ($keyObj['public_key'] === $publicKeyB58) {
                        return true; // 完美匹配！該公鑰的確屬於該錢包
                    }
                }
            }
        }

        return false;
    }
}
?>