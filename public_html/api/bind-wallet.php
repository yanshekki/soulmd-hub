<?php
/**
 * SoulMD Hub Public API
 * POST /api/bind-wallet - Link Web3 Wallet to User Account
 * (100% Dynamic i18n Internationalized Edition)
 * 🚨 Patched: Proactive duplicate wallet check to enforce 1-to-1 strict binding rule
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

session_start();
loadTranslations('api'); // 載入語言包

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
        
        // 1. 檢查當前 Web2 用戶是否已經綁定過錢包
        $checkStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
        $checkStmt->execute([$userId]);
        $currentUser = $checkStmt->fetch();
        
        if (!empty($currentUser['near_wallet_address'])) {
            echo json_encode(['success' => false, 'error' => __('Wallet already bound')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 🚨 2. 核心安全補漏：主動攔截！檢查該 NEAR 錢包地址是否已被系統內「其他用戶」佔用
        // 落實「一個 NEAR 錢包只能登入/對應一個 System User」的鐵律，封殺多帳號冒充漏洞！
        $duplicateCheck = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ? AND id != ?");
        $duplicateCheck->execute([$wallet, $userId]);
        if ($duplicateCheck->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'error' => __('Wallet in use')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 3. 寫入資料庫
        $stmt = $pdo->prepare("UPDATE users SET near_wallet_address = ? WHERE id = ?");
        $stmt->execute([$wallet, $userId]);
        echo json_encode(['success' => true, 'message' => __('Wallet bound successfully')], JSON_UNESCAPED_UNICODE);
        
    } else {
        echo json_encode(['success' => false, 'error' => __('Invalid action')], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
}
?>