<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

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
    echo json_encode(['success' => false, 'error' => 'Login or valid API Key required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['soul_id']) || empty($input['rating'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'soul_id and rating are required']);
    exit;
}

$soulId = (int)$input['soul_id'];
$rating = (int)$input['rating'];

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Rating must be 1-5']);
    exit;
}

// 寫入或更新評分
$stmt = $pdo->prepare("INSERT INTO soul_ratings (soul_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
$stmt->execute([$soulId, $userId, $rating]);

// 🚨 核心優化：即時撈出最新統計數據
$avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_ratings FROM soul_ratings WHERE soul_id = ?");
$avgStmt->execute([$soulId]);
$ratingData = $avgStmt->fetch();

// 回傳給前端，等前端可以直接局部刷新畫面
echo json_encode([
    'success' => true,
    'message' => 'Rating submitted successfully',
    'avg_rating' => (float)($ratingData['avg_rating'] ?? 0),
    'total_ratings' => (int)($ratingData['total_ratings'] ?? 0)
], JSON_UNESCAPED_UNICODE);