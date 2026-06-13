<?php
/**
 * SoulMD Hub Public API
 * POST /api/logout - Clear session and tokens
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';

$security = ApiSecurity::initialize(false);  // logout can work with or without prior full auth check
$pdo = $security['pdo'];

session_start();  // ensure after central (central also starts if needed)

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

echo json_encode(['success' => true, 'message' => 'Logged out successfully']);