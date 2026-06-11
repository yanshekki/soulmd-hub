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
    if (session_status() === PHP_SESSION_NONE) session_start();
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

// ✅ Phase 2 業務邏輯修復：簡單 rate limit 防 spam rate (session based, 5秒)
if (!empty($_SESSION['last_rate_time']) && (time() - $_SESSION['last_rate_time']) < 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => __('Too many ratings, please wait')], JSON_UNESCAPED_UNICODE);
    exit;
}
$_SESSION['last_rate_time'] = time();

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['soul_id']) || empty($input['rating'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('soul_id and rating are required')], JSON_UNESCAPED_UNICODE);
    exit;
}

$soulId = (int)$input['soul_id'];
$rating = (int)$input['rating'];

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Rating must be 1-5')], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 🚨 完美修復 1：檢查該 Soul 是否存在，並且是否公開 (或屬於自己)，防止惡意越權評分
    $checkStmt = $pdo->prepare("SELECT id FROM souls WHERE id = ? AND (is_public = 1 OR user_id = ?)");
    $checkStmt->execute([$soulId, $userId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Soul not found or access denied')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    // 寫入或更新評分
    $stmt = $pdo->prepare("INSERT INTO soul_ratings (soul_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
    $stmt->execute([$soulId, $userId, $rating]);

    // 即時撈出最新統計數據
    $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_ratings FROM soul_ratings WHERE soul_id = ?");
    $avgStmt->execute([$soulId]);
    $ratingData = $avgStmt->fetch();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => __('Rating submitted successfully'),
        'avg_rating' => (float)($ratingData['avg_rating'] ?? 0),
        'total_ratings' => (int)($ratingData['total_ratings'] ?? 0)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // 🚨 完美修復 2：捕獲資料庫異常，避免噴出 HTTP 500 及 Stack Trace
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Internal Server Error while rating')], JSON_UNESCAPED_UNICODE);
}