<?php
/**
 * SoulMD Hub - Global Logout Handler
 * 🚀 Patched: Atomic Logout for both Web2 (PHP) and Web3 (LocalStorage)
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

// 2. 銷毀 PHP Session 與 Cookie
session_unset();
session_destroy();
setcookie('remember_token', '', time() - 3600, '/');

// 3. 輸出 HTML 與 JavaScript 執行 Web3 清理
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
            // 🚨 清除所有以 near-api-js 開頭的 LocalStorage (Web3 錢包狀態)
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('near-api-js:keystore:')) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(k => localStorage.removeItem(k));
        } catch(e) {}
        
        // 轉導回登入頁
        window.location.href = '/login';
    </script>
</body>
</html>