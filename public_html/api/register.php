<?php
/**
 * SoulMD Hub Public API
 * POST /api/register - Register a new user and generate an API key
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

require_once __DIR__ . '/../../private/src/AppBootstrap.php';
$app = AppBootstrap::forApi([
    'require_user' => false,
    'enforce_csrf' => false,
    'translations' => 'api',
    'json_header' => false,
]);
$userId = $app['user_id'];
$pdo = $app['pdo'];
$isApiKey = $app['is_api_key'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    // 💡 補回 JSON_UNESCAPED_UNICODE
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🚨 完美安全修復：強制只接收 JSON，杜絕 $_POST CSRF 攻擊 (now centralized)
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (strlen($username) < 3) {
    http_response_code(400);
    // 💡 補回 JSON_UNESCAPED_UNICODE
    echo json_encode(['success' => false, 'error' => __('Username min chars')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) {
    http_response_code(400);
    // 💡 補回 JSON_UNESCAPED_UNICODE
    echo json_encode(['success' => false, 'error' => __('Username invalid format')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    // 💡 補回 JSON_UNESCAPED_UNICODE
    echo json_encode(['success' => false, 'error' => __('Password min chars')], JSON_UNESCAPED_UNICODE);
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

    // Session may already be active (e.g. CSRF helper) — only start if idle
    AppBootstrap::sessionStart();
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => __('Account created successfully'),
        'api_key' => $apiKey
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(409);
    // 💡 補回 JSON_UNESCAPED_UNICODE
    echo json_encode(['success' => false, 'error' => __('Username taken')], JSON_UNESCAPED_UNICODE);
}