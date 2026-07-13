<?php
/**
 * SoulMD Hub API - Web3 Wallet Binding
 * 🚀 V6 SECURE: Error Catching & NEP-0413 Integration
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../private/src/NearAuthService.php';
    require_once __DIR__ . '/../../private/src/AppBootstrap.php';
    $app = AppBootstrap::forApi([
    'require_user' => true,
    'enforce_csrf' => true,
    'translations' => 'api',
    'json_header' => false,
]);
$userId = $app['user_id'];
$pdo = $app['pdo'];
$isApiKey = !empty($app['is_api_key']);
$apiKey = $app['api_key'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
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
    // ✅ Phase 1 修復：移除詳細 file/line 洩漏（安全考量）
    error_log('Wallet bind fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => __('Internal authentication error. Please try again later.')
    ], JSON_UNESCAPED_UNICODE);
}
?>