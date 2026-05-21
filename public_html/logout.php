<?php
// Fallback for direct URL access
session_start();

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?")->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {}
}

session_unset();
session_destroy();
setcookie('remember_token', '', time() - 3600, '/');

header('Location: /login');
exit;