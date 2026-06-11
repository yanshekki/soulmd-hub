<?php
/**
 * SoulMD Hub - AES-256-GCM Encryption Module (Authenticated)
 * ✅ Phase 2 修復：從 CBC 升級到 GCM 提供完整性保護，防 tampering。
 * 兼容舊 CBC 資料（fallback）。
 */

// 確保有引入 config.php 中的 APP_ENCRYPTION_KEY
if (!defined('APP_ENCRYPTION_KEY')) {
    define('APP_ENCRYPTION_KEY', 'fallback-insecure-key-change-it-now');
}

function encryptData($plainText) {
    if(empty($plainText)) return '';
    // GCM IV 通常 12 bytes
    $iv = openssl_random_pseudo_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt($plainText, 'aes-256-gcm', APP_ENCRYPTION_KEY, 0, $iv, $tag);
    if ($encrypted === false) return '';
    // 格式: base64( iv :: tag :: ciphertext )
    return base64_encode($iv . '::' . $tag . '::' . $encrypted);
}

function decryptData($encryptedText) {
    if(empty($encryptedText)) return '';
    $decoded = base64_decode($encryptedText);
    
    // 兼容舊有 CBC 資料 (無 '::' 或舊格式)
    if (strpos($decoded, '::') === false) {
        // 舊 CBC fallback (不安全，僅為兼容)
        // 嘗試舊格式 base64(encrypted::base64(iv))
        if (strpos($encryptedText, '::') !== false) {
            // 舊 base64 格式
            list($enc_b64, $iv_b64) = explode('::', $encryptedText, 2);
            $iv = base64_decode($iv_b64);
            return openssl_decrypt(base64_decode($enc_b64), 'aes-256-cbc', APP_ENCRYPTION_KEY, 0, $iv);
        }
        return $encryptedText; 
    } 
    
    $parts = explode('::', $decoded, 3);
    if (count($parts) !== 3) {
        // 舊 CBC 格式
        list($encrypted_data, $iv_base64) = explode('::', $decoded, 2);
        $iv = base64_decode($iv_base64);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', APP_ENCRYPTION_KEY, 0, $iv);
    }
    
    list($iv, $tag, $encrypted_data) = $parts;
    return openssl_decrypt($encrypted_data, 'aes-256-gcm', APP_ENCRYPTION_KEY, 0, $iv, $tag);
}
?>