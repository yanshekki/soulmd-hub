<?php
/**
 * SoulMD Hub Public API
 * POST /api/login - Authenticate a user and create a session / return API key
 * (100% Dynamic i18n Internationalized Error Stack Edition)
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

// 🌍 載入後端 API 全域專屬語言包（自動依據 Cookie 語系切換）
loadTranslations('api');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    // 💡 補回 JSON_UNESCAPED_UNICODE
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🚨 完美安全機制：強制只接收 JSON，杜絕 $_POST CSRF 攻擊
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$remember = isset($input['remember']) && ($input['remember'] === true || $input['remember'] === 'true' || $input['remember'] === '1' || $input['remember'] === 1);

if (empty($username) || empty($password)) {
    http_response_code(400);
    // 💡 補回 JSON_UNESCAPED_UNICODE
    echo json_encode(['success' => false, 'error' => __('All fields required')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT id, username, password, api_key FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    
    // 防禦 Session Fixation 攻擊
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

    echo json_encode([
        'success' => true,
        'message' => __('Login successful'),
        'api_key' => $user['api_key']
    ], JSON_UNESCAPED_UNICODE);

} else {
    http_response_code(401);
    // 💡 補回 JSON_UNESCAPED_UNICODE
    echo json_encode(['success' => false, 'error' => __('Incorrect credentials')], JSON_UNESCAPED_UNICODE);
}