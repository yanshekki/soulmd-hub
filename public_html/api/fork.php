<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$userId = null;
$username = 'anonymous';
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$apiKey = trim(str_replace('Bearer', '', $authHeader));

if ($apiKey) {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    if ($user = $stmt->fetch()) {
        $userId = $user['id'];
        $username = $user['username'];
    }
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $username = $_SESSION['username'];
    }
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Login or valid API Key required']);
    exit;
}

// ✅ Phase 2 修復：browser session 路徑補 CSRF（API key 路徑跳過，與其他 endpoint 一致）
if (! $apiKey) {  // 只有純 session 時檢查 CSRF
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
}

// ✅ Phase 2 業務邏輯修復：簡單 rate limit 防 spam fork (session based, 5秒)
if (!empty($_SESSION['last_fork_time']) && (time() - $_SESSION['last_fork_time']) < 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many forks, please wait']);
    exit;
}
$_SESSION['last_fork_time'] = time();

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['soul_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'soul_id is required']);
    exit;
}

$soulId = (int)$input['soul_id'];

$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND is_public = 1");
$stmt->execute([$soulId]);
$original = $stmt->fetch();

if (!$original) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Public soul not found']);
    exit;
}

function incrementTags($pdo, $table, $tagsString) {
    $tags = array_filter(array_map('trim', explode(',', $tagsString)));
    foreach ($tags as $tag) {
        if (empty($tag)) continue;
        $stmt = $pdo->prepare("INSERT INTO {$table} (name, usage_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
        $stmt->execute([$tag]);
    }
}

// 🚨 SEO 友善化助手
function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

try {
    $pdo->beginTransaction();

    $newTitle = $original['title'] . ' (Forked)';

    $stmt = $pdo->prepare("INSERT INTO souls (user_id, title, description, content, file_type, role, domain, compatibility, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $userId,
        $newTitle,
        $original['description'],
        $original['content'],
        $original['file_type'],
        $original['role'],
        $original['domain'],
        $original['compatibility']
    ]);

    $newId = $pdo->lastInsertId();
    
    $pdo->prepare("UPDATE souls SET fork_count = fork_count + 1 WHERE id = ?")->execute([$soulId]);

    incrementTags($pdo, 'tags_domain', $original['domain']);
    incrementTags($pdo, 'tags_compatibility', $original['compatibility']);

    $pdo->commit();

    // 🚨 構建完美 SEO 網址回傳
    $seoUrl = "https://" . $_SERVER['HTTP_HOST'] . "/soul/" . rawurlencode($username) . "/" . $newId . "/" . makeSlug($original['role']) . "/" . makeSlug($newTitle);

    echo json_encode([
        'success' => true,
        'new_soul_id' => $newId,
        'url' => $seoUrl,
        'message' => 'Soul forked successfully!'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fork soul due to server error']);
}