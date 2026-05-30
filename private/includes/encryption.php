<?php
/**
 * SoulMD Hub - AES-256-CBC Encryption Module
 */

// 確保有引入 config.php 中的 APP_ENCRYPTION_KEY
if (!defined('APP_ENCRYPTION_KEY')) {
    define('APP_ENCRYPTION_KEY', 'fallback-insecure-key-change-it-now');
}

function encryptData($plainText) {
    if(empty($plainText)) return '';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($plainText, 'aes-256-cbc', APP_ENCRYPTION_KEY, 0, $iv);
    // 將密文與 IV 組合並 Base64 編碼儲存
    return base64_encode($encrypted . '::' . base64_encode($iv));
}

function decryptData($encryptedText) {
    if(empty($encryptedText)) return '';
    $decoded = base64_decode($encryptedText);
    
    // 兼容舊有資料若無 '::' 則直接回傳
    if (strpos($decoded, '::') === false) return $encryptedText; 
    
    list($encrypted_data, $iv_base64) = explode('::', $decoded, 2);
    $iv = base64_decode($iv_base64);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', APP_ENCRYPTION_KEY, 0, $iv);
}
?>