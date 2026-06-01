<?php
/**
 * SoulMD Hub API - Web3 Wallet Cryptographic Auth & Login Sync
 * (V5 Web2.5 AgentFi Architecture: Hardened Signature Verification Edition)
 * 🚀 Patched: Enforced Ed25519 Cryptographic verification via NearAuthService to prevent spoofing.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/NearAuthService.php'; // 🚀 引入密碼學驗證服務

session_start();
loadTranslations('api');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$wallet    = trim($input['account_id'] ?? '');
$publicKey = trim($input['public_key'] ?? '');
$signature = trim($input['signature'] ?? '');
$message   = trim($input['message'] ?? '');

// 🚨 嚴格攔截：密碼學驗證所需之四大金鑰指紋，缺一不可！
if (empty($wallet) || empty($publicKey) || empty($signature) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🚨 密碼學核心熔斷：利用 libsodium 物理校驗 Ed25519 簽章 + RPC 鏈上 AccessKey 確權
if (!NearAuthService::verifySignature($wallet, $publicKey, $signature, $message)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Security validation failed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    // 檢查該通過密碼學確權的 Web3 錢包，在平台資料庫中是否已經綁定過 Web2 帳號
    $stmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ?");
    $stmt->execute([$wallet]);
    $user = $stmt->fetch();

    if ($user) {
        // 安全登入：密碼學核實無誤，准予核發 Session 狀態！
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(['success' => true]);
    } else {
        // 錢包正確，但此錢包在 SoulMD 平台尚未綁定任何 Web2 帳號
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('User Not Found')], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
}
?>