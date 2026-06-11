<?php
/**
 * SoulMD Hub Internal API
 * POST /api/save-preset - Save generated AI soul to session memory
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

loadTranslations('api');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ Phase 2 修復：session based mutating 補 CSRF（低風險但一致性）
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

$input = json_decode(file_get_contents('php://input'), true);

$_SESSION['preset_title'] = $input['title'] ?? '';
$_SESSION['preset_content'] = $input['content'] ?? '';
$_SESSION['preset_role'] = $input['role'] ?? '';

echo json_encode(['success' => true]);