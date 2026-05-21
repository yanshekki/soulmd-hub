<?php
session_start();

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

// 如果用戶有登入，清空資料庫中的 remember_token
if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // 忽略連線錯誤
    }
}

// 清除 Session
session_unset();
session_destroy();

// 刪除 Browser 上的 Remember Me Cookie
setcookie('remember_token', '', time() - 3600, '/');

header('Location: /');
exit;