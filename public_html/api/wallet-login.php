<?php
/**
 * SoulMD Hub Public API
 * POST /api/wallet-login - Authenticate user via NEAR Wallet
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

loadTranslations('api');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$accountId = trim($input['account_id'] ?? '');

if (empty($accountId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Wallet account ID is required.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// 尋找是否已有帳號綁定了這個 Web3 錢包
$stmt = $pdo->prepare("SELECT id, username, api_key FROM users WHERE near_wallet_address = ?");
$stmt->execute([$accountId]);
$user = $stmt->fetch();

if ($user) {
    session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    echo json_encode([
        'success' => true,
        'message' => __('Login successful'),
        'api_key' => $user['api_key']
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(401);
    // 傳回特定錯誤碼，由前端語言包處理翻譯
    echo json_encode(['success' => false, 'error' => __('Wallet not bound')], JSON_UNESCAPED_UNICODE);
}