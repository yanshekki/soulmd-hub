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

// 🚨 完美修復：防止 PHP 8 Array Offset 報錯
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['soul_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'soul_id is required']);
    exit;
}

$soulId = (int)$input['soul_id'];

$stmt = $pdo->prepare("SELECT id FROM souls WHERE id = ? AND (is_public = 1 OR user_id = ?)");
$stmt->execute([$soulId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Soul not found or access denied']);
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
        $message = 'Soul unliked successfully';
    } else {
        $pdo->prepare("INSERT INTO soul_likes (soul_id, user_id) VALUES (?, ?)")->execute([$soulId, $userId]);
        $pdo->prepare("UPDATE souls SET like_count = like_count + 1 WHERE id = ?")->execute([$soulId]);
        $liked = true;
        $message = 'Soul liked successfully';
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'liked' => $liked, 'message' => $message], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to process like toggle due to server error']);
}