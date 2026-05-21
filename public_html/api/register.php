<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// 🚨 完美修復：嚴格限定 JSON，杜絕 CSRF Form 提交
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (strlen($username) < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username must be at least 3 characters']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username can only contain alphanumeric characters, underscores, and dashes.']);
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

    session_start();
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Account created successfully', 'api_key' => $apiKey], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'Username already taken']);
}