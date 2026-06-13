<?php
/**
 * SoulMD Hub API - Web3 Wallet Cryptographic Auth
 * 🚀 V6 SECURE: Error Catching & NEP-0413 Integration
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../private/config.php';
    require_once __DIR__ . '/../../private/src/Database.php';
    require_once __DIR__ . '/../../private/src/NearAuthService.php';
    require_once __DIR__ . '/../../private/src/ApiSecurity.php';

    loadTranslations('api');

    $security = ApiSecurity::initialize(false);  // wallet login creates the session
    $pdo = $security['pdo'];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // 🚨 檢查伺服器是否支援 libsodium (Ed25519 加密必須)
    if (!function_exists('sodium_crypto_sign_verify_detached')) {
        throw new Exception("伺服器缺少 'libsodium' 擴充模組，無法進行 Ed25519 密碼學驗證！");
    }

    // 呼叫全新的 NEP-0413 驗證器
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

    $stmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ?");
    $stmt->execute([$wallet]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('User Not Found')], JSON_UNESCAPED_UNICODE);
    }

} catch (\Throwable $e) {
    // ✅ Phase 1 修復：移除詳細 file/line 洩漏（安全考量）
    error_log('Wallet login fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => __('Internal authentication error. Please try again later.')
    ], JSON_UNESCAPED_UNICODE);
}
?>