<?php
/**
 * SoulMD Hub Public API
 * POST /api/change-password - Update user password
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// ==========================================
// 1. 驗證身份 (支援 API Key 或 Session)
// ==========================================
$userId = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$apiKey = trim(str_replace('Bearer', '', $authHeader));

if (!empty($apiKey)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    if ($user = $stmt->fetch()) {
        $userId = $user['id'];
    }
} else {
    session_start();
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Valid Session or API Key required.']);
    exit;
}

// ==========================================
// 2. 處理修改密碼邏輯
// ==========================================
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$current_password = $input['current_password'] ?? '';
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
    exit;
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($current_password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Incorrect current password.']);
    exit;
} 

if (strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters.']);
    exit;
} 

if ($new_password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'New passwords do not match.']);
    exit;
}

// 更新密碼
try {
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->execute([$hash, $userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password successfully updated!'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error while updating password.']);
}