<?php
/**
 * SoulMD Hub API - Regenerate API Key
 * 🚀 Patched: Standardized HTTP Codes & i18n
 */

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
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

// ✅ Phase 2 修復：純 session mutating endpoint 補 CSRF
$userCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($userCsrfToken) && function_exists('getallheaders')) {
    $headers = getallheaders();
    $userCsrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
}
$serverCsrfToken = $_SESSION['chat_csrf_token'] ?? '';
if (empty($serverCsrfToken) || empty($userCsrfToken) || !hash_equals($serverCsrfToken, $userCsrfToken)) {
    http_response_code(403); 
    echo json_encode(['success' => false, 'error' => __('Security validation failed')], JSON_UNESCAPED_UNICODE); 
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

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