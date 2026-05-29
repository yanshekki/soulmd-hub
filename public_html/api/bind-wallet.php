<?php
/**
 * SoulMD Hub Public API
 * POST /api/bind-wallet - Link Web3 Wallet to User Account
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized Session']);
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
            echo json_encode(['success' => false, 'error' => 'Wallet address missing']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE users SET near_wallet_address = ? WHERE id = ?");
        $stmt->execute([$wallet, $userId]);
        echo json_encode(['success' => true, 'message' => 'Wallet bound successfully']);
        
    } elseif ($action === 'unbind') {
        $stmt = $pdo->prepare("UPDATE users SET near_wallet_address = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'message' => 'Wallet unbound successfully']);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Wallet address may already be in use by another account.']);
}