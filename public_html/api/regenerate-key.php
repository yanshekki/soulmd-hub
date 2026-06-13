<?php
/**
 * SoulMD Hub API - Regenerate API Key
 * 🚀 Patched: Standardized HTTP Codes & i18n
 */

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

loadTranslations('api');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$security = ApiSecurity::initialize(true);  // requires auth + enforces CSRF for session
$userId = $security['user_id'];
$pdo = $security['pdo'];

// 生成全新的 64 字元高強度金鑰
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