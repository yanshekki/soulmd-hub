<?php
/**
 * SoulMD Hub Public API
 * POST /api/bind-wallet - Link Web3 Wallet to User Account
 * (100% Dynamic i18n Internationalized Edition)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

session_start();
loadTranslations('api'); // 🚨 載入語言包

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$wallet = trim($input['wallet'] ?? '');

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

try {
    if ($action === 'bind') {
        if (empty($wallet)) {
            echo json_encode(['success' => false, 'error' => __('Wallet address missing')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $checkStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
        $checkStmt->execute([$userId]);
        $currentUser = $checkStmt->fetch();
        
        if (!empty($currentUser['near_wallet_address'])) {
            echo json_encode(['success' => false, 'error' => __('Wallet already bound')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET near_wallet_address = ? WHERE id = ?");
        $stmt->execute([$wallet, $userId]);
        echo json_encode(['success' => true, 'message' => __('Wallet bound successfully')], JSON_UNESCAPED_UNICODE);
        
    } else {
        echo json_encode(['success' => false, 'error' => __('Invalid action')], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Wallet in use')], JSON_UNESCAPED_UNICODE);
}
?>