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

// 🚨 完美修復：嚴格限定 JSON，杜絕 CSRF Form 提交，並防止 null 警告
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$remember = isset($input['remember']) && ($input['remember'] === true || $input['remember'] === 'true' || $input['remember'] === '1' || $input['remember'] === 1);

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT id, username, password, api_key FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    
    session_start();
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        try {
            $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token, $user['id']]);
            setcookie('remember_token', $user['id'] . ':' . $token, time() + 86400 * 30, '/');
        } catch(PDOException $e) {}
    }

    echo json_encode(['success' => true, 'message' => 'Login successful', 'api_key' => $user['api_key']], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Incorrect username or password. Please try again.']);
}