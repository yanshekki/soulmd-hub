<?php
/**
 * SoulMD Hub Public API
 * POST /api/like - Increment the like count of a soul (Enforced per-user constraint)
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

$db = Database::getInstance();
$pdo = $db->getConnection();

// 1. 驗證身份 (支援 API Key 或 Session)
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

// 2. 獲取請求數據
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['soul_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'soul_id is required']);
    exit;
}

$soulId = (int)$input['soul_id'];

// 3. 確認 Soul 存在且公開（或屬於自己）
$stmt = $pdo->prepare("SELECT id FROM souls WHERE id = ? AND (is_public = 1 OR user_id = ?)");
$stmt->execute([$soulId, $userId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Soul not found or access denied']);
    exit;
}

// 4. 🚨 核心防護：檢查此用戶是否已經 Like 過
$checkLike = $pdo->prepare("SELECT id FROM soul_likes WHERE soul_id = ? AND user_id = ?");
$checkLike->execute([$soulId, $userId]);
if ($checkLike->fetch()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'You have already liked this soul']);
    exit;
}

// 5. 使用 Transaction 確保數據一致性
try {
    $pdo->beginTransaction();

    // 插入 per-user 記錄 (若併發漏過上面的檢查，UNIQUE KEY 也會在此處觸發報錯防禦)
    $pdo->prepare("INSERT INTO soul_likes (soul_id, user_id) VALUES (?, ?)")->execute([$soulId, $userId]);

    // 增加該 Soul 總讚數
    $pdo->prepare("UPDATE souls SET like_count = like_count + 1 WHERE id = ?")->execute([$soulId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Soul liked successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to like soul due to server error']);
}