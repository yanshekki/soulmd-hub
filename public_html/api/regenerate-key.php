<?php
/**
 * SoulMD Hub API - Regenerate API Key
 * 🚀 Patched: Standardized HTTP Codes & i18n
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

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

$newApiKey = bin2hex(random_bytes(32));

try {
    $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
    $stmt->execute([$newApiKey, $userId]);
    
    echo json_encode(['success' => true, 'new_api_key' => $newApiKey]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
}
?>