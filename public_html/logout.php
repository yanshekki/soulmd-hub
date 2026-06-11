<?php
/**
 * SoulMD Hub - Logout Page
 * 🚀 V7 FINAL: Sync Web2 PHP Logout with Web3 LocalStorage Nuke
 */

require_once __DIR__ . '/../private/config.php';

loadTranslations('logout');

session_start();

// 1. 徹底摧毀 Web2 的 PHP Session
$_SESSION = [];
session_destroy();

// 2. 清除可能存在的 Remember Me Cookie (保持極致安全)
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// 3. 輸出一個極簡的過渡畫面，讓瀏覽器有時間執行 Web3 核彈清理腳本
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('title') ?></title>
    <style>
        body {
            background-color: #09090b; /* Zinc-950 */
            color: #10b981; /* Emerald-500 */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: ui-sans-serif, system-ui, sans-serif;
        }
        .loader {
            border: 3px solid rgba(16, 185, 129, 0.2);
            border-top-color: #10b981;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div style="text-align: center;">
        <div class="loader"></div>
        <div style="font-size: 14px; font-weight: bold; letter-spacing: 1px;"><?= __('securing') ?></div>
    </div>

    <script>
        // 🚀 核彈級清除機制：只要經過這個頁面，強制清空 NEAR 相關的所有 LocalStorage 狀態！
        try {
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && (key.startsWith('near-wallet-selector') || key.startsWith('near-api-js:keystore:'))) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(k => localStorage.removeItem(k));
            console.log("Web3 Wallet State Nuked on Logout.");
        } catch(e) {
            console.error("Nuke error:", e);
        }

        // 清理完成後，極速跳轉回登入頁面 (用戶幾乎只會看到一閃而過)
        window.location.replace('<?= url("/login") ?>');
    </script>
</body>
</html>