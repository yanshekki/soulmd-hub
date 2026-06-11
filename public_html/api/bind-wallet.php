<?php
/**
 * SoulMD Hub API - Web3 Wallet Binding
 * 🚀 V6 SECURE: Error Catching & NEP-0413 Integration
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../private/config.php';
    require_once __DIR__ . '/../../private/src/Database.php';
    require_once __DIR__ . '/../../private/src/NearAuthService.php';

    session_start();
    loadTranslations('api');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (!function_exists('sodium_crypto_sign_verify_detached')) {
        throw new Exception("伺服器缺少 'libsodium' 擴充模組，無法進行 Ed25519 密碼學驗證！");
    }

    $authService = new NearAuthService();
    $authResult = $authService->verifyAuthPayload($input);

    if (!$authResult['success']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => $authResult['error'] ?? __('Security validation failed')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $wallet = trim($input['account_id'] ?? '');

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ? AND id != ?");
    $stmt->execute([$wallet, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => __('Wallet already bound')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET near_wallet_address = ? WHERE id = ?");
    $stmt->execute([$wallet, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);

} catch (\Throwable $e) {
    // 🚨 無敵捕捉器：將 PHP 500 致命錯誤轉為 JSON 輸出給前端！
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => '內部崩潰 (Fatal Error): ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ' on line ' . $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}
?>