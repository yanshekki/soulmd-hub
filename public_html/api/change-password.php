<?php
/**
 * SoulMD Hub Public API
 * POST /api/change-password - Update user password (i18n Fixed)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';

loadTranslations('api');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$security = ApiSecurity::initialize(true);  // supports api_key (skips CSRF) or session (enforces CSRF)
$userId = $security['user_id'];
$pdo = $security['pdo'];

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$current_password = $input['current_password'] ?? '';
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('All fields required')], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($current_password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Incorrect current password')], JSON_UNESCAPED_UNICODE);
    exit;
} 

if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Password min chars')], JSON_UNESCAPED_UNICODE);
    exit;
} 

if ($new_password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Passwords do not match')], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);
    echo json_encode(['success' => true, 'message' => __('Password successfully updated')], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
}
?>