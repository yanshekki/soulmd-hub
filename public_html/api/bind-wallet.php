<?php
/**
 * SoulMD Hub Public API
 * POST /api/bind-wallet - Link Web3 Wallet to User Account (One-Time Only)
 * (Strict Security Enforced Edition)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized Session'], JSON_UNESCAPED_UNICODE);
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
            echo json_encode(['success' => false, 'error' => 'Wallet address missing'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 🚨 嚴格防護：檢查該用戶是否已經綁定了錢包
        $checkStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
        $checkStmt->execute([$userId]);
        $currentUser = $checkStmt->fetch();
        
        if (!empty($currentUser['near_wallet_address'])) {
            echo json_encode(['success' => false, 'error' => 'Wallet is already permanently bound to this account and cannot be modified.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET near_wallet_address = ? WHERE id = ?");
        $stmt->execute([$wallet, $userId]);
        echo json_encode(['success' => true, 'message' => 'Wallet bound successfully!'], JSON_UNESCAPED_UNICODE);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action or action not permitted.'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    // 捕獲 Unique Constraint 錯誤 (若該錢包已被其他帳號綁定)
    echo json_encode(['success' => false, 'error' => 'This wallet address is already in use by another account.'], JSON_UNESCAPED_UNICODE);
}