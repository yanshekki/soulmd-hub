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
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['user_id'])) $userId = $_SESSION['user_id'];
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Login or valid API Key required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

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

// 🚨 完美修復 1：加入標籤統計增長函數，確保 Fork 出來的文章也能被首頁 Trending Tags 計算
function incrementTags($pdo, $table, $tagsString) {
    $tags = array_filter(array_map('trim', explode(',', $tagsString)));
    foreach ($tags as $tag) {
        if (empty($tag)) continue;
        $stmt = $pdo->prepare("INSERT INTO {$table} (name, usage_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
        $stmt->execute([$tag]);
    }
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO souls (user_id, title, description, content, file_type, role, domain, compatibility, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $userId,
        $original['title'] . ' (Forked)',
        $original['description'],
        $original['content'],
        $original['file_type'],
        $original['role'],
        $original['domain'],
        $original['compatibility']
    ]);

    $newId = $pdo->lastInsertId();
    
    // 更新原作品的 Fork 數量
    $pdo->prepare("UPDATE souls SET fork_count = fork_count + 1 WHERE id = ?")->execute([$soulId]);

    // 🚨 完美修復 2：執行標籤數據庫同步
    incrementTags($pdo, 'tags_domain', $original['domain']);
    incrementTags($pdo, 'tags_compatibility', $original['compatibility']);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'new_soul_id' => $newId,
        'url' => "https://" . $_SERVER['HTTP_HOST'] . "/soul/" . $newId,
        'message' => 'Soul forked successfully!'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fork soul due to server error']);
}