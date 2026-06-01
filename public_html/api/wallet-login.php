<?php
/**
 * SoulMD Hub API - Web3 Wallet Silent Login Sync
 * 🚀 Patched: Added HTTP Codes and Multi-Language handling
 */

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

header('Content-Type: application/json; charset=utf-8');
session_start();

loadTranslations('api');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$wallet = trim($input['account_id'] ?? '');

if (empty($wallet)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // 檢查該 Web3 錢包是否已經綁定至某個 Web2 帳號
    $stmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ?");
    $stmt->execute([$wallet]);
    $user = $stmt->fetch();

    if ($user) {
        // 同步登入狀態
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(['success' => true]);
    } else {
        // 尚未綁定
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('User Not Found')], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
}
?>