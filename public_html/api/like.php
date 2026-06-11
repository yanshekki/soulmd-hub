<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

loadTranslations('api');

$db = Database::getInstance();
$pdo = $db->getConnection();

$userId = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$apiKey = trim(str_replace('Bearer', '', $authHeader));

if ($apiKey) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    if ($user = $stmt->fetch()) $userId = $user['id'];
} else {
    session_start();
    if (isset($_SESSION['user_id'])) $userId = $_SESSION['user_id'];
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Login or valid API Key required')], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ Phase 2 修復：browser session 路徑補 CSRF（API key 跳過）
if (empty($apiKey)) {
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

// ✅ Phase 2 業務邏輯修復：簡單 rate limit 防 spam like (session based, 3秒)
if (!empty($_SESSION['last_like_time']) && (time() - $_SESSION['last_like_time']) < 3) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => __('Too many likes, please wait')], JSON_UNESCAPED_UNICODE);
    exit;
}
$_SESSION['last_like_time'] = time();

// 🚨 完美修復：防止 PHP 8 Array Offset 報錯
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['soul_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('soul_id is required')], JSON_UNESCAPED_UNICODE);
    exit;
}

$soulId = (int)$input['soul_id'];

$stmt = $pdo->prepare("SELECT id FROM souls WHERE id = ? AND (is_public = 1 OR user_id = ?)");
$stmt->execute([$soulId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => __('Soul not found or access denied')], JSON_UNESCAPED_UNICODE);
    exit;
}

$checkLike = $pdo->prepare("SELECT id FROM soul_likes WHERE soul_id = ? AND user_id = ?");
$checkLike->execute([$soulId, $userId]);
$likeRow = $checkLike->fetch();

try {
    $pdo->beginTransaction();

    if ($likeRow) {
        $pdo->prepare("DELETE FROM soul_likes WHERE soul_id = ? AND user_id = ?")->execute([$soulId, $userId]);
        $pdo->prepare("UPDATE souls SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?")->execute([$soulId]);
        $liked = false;
        $message = __('Soul unliked successfully');
    } else {
        $pdo->prepare("INSERT INTO soul_likes (soul_id, user_id) VALUES (?, ?)")->execute([$soulId, $userId]);
        $pdo->prepare("UPDATE souls SET like_count = like_count + 1 WHERE id = ?")->execute([$soulId]);
        $liked = true;
        $message = __('Soul liked successfully');
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'liked' => $liked, 'message' => $message], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Failed to process like toggle due to server error')], JSON_UNESCAPED_UNICODE);
}