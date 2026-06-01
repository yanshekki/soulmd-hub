<?php
/**
 * SoulMD Hub - Global Logout Handler
 * 🚀 Patched: Deep Web3 LocalStorage Wipe (Including Auth Keys) to break infinite Auto-Login loop
 */
session_start();

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

// 1. 清除資料庫中的 Remember Token
if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {}
}

// 2. 徹底銷毀 PHP Session 與 Cookie
session_unset();
session_destroy();
setcookie('remember_token', '', time() - 3600, '/');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging out...</title>
    <style>
        body { background-color: #09090b; color: #a1a1aa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; font-family: sans-serif; }
    </style>
</head>
<body>
    <div>Securely logging you out of Web2 and Web3...</div>
    <script>
        try {
            // 🚨 終極修復：除了 keystore，必須同時刪除 '_wallet_auth_key' 才能徹底讓 NEAR Wallet 判定為登出！
            // 否則 isSignedIn() 依舊回傳 true，導致 Global Sync 自動將你登入！
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && (key.startsWith('near-api-js:keystore:') || key.includes('_wallet_auth_key'))) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(k => localStorage.removeItem(k));
        } catch(e) {}
        
        // 🚨 完美轉導：使用 location.replace 避免污染歷史紀錄
        window.location.replace('<?= url("/login") ?>');
    </script>
</body>
</html>