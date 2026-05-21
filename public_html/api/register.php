<?php
/**
 * SoulMD Hub Public API
 * POST /api/register - Register a new user and generate an API key
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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (strlen($username) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username must be at least 3 characters']);
    exit;
}

// 🚨 核心安全防護：限制用戶名只能使用英數字、底線與橫線，確保百分之百網址安全，防止髒數據弄爛外部路由！
if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username can only contain alphanumeric characters, underscores, and dashes (No spaces or special symbols allowed).']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$hash = password_hash($password, PASSWORD_DEFAULT);
$apiKey = bin2hex(random_bytes(32));

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, api_key) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hash, $apiKey]);
    
    $userId = $pdo->lastInsertId();

    // Set session for website users
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'api_key' => $apiKey
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'Username already taken']);
}