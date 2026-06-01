<?php
/**
 * SoulMD Hub API - Bind NEAR Wallet
 * 🚀 Patched: Added HTTP Status Codes, i18n, and cross-account wallet duplication check.
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

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$wallet = trim($input['wallet'] ?? '');

if ($action !== 'bind' || empty($wallet)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

try {
    // 1. 檢查當前用戶是否已經綁定過錢包
    $stmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentWallet = $stmt->fetchColumn();

    if (!empty($currentWallet)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Wallet already bound')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 🚨 2. 安全攔截：檢查該錢包是否已經被「其他帳號」綁定 (防雙胞胎漏洞)
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ? AND id != ?");
    $checkStmt->execute([$wallet, $userId]);
    if ($checkStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => __('Wallet already exists')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. 執行綁定
    $updStmt = $pdo->prepare("UPDATE users SET near_wallet_address = ? WHERE id = ?");
    $updStmt->execute([$wallet, $userId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
}
?>