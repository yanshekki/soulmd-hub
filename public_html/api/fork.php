<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';

loadTranslations('api');

$security = ApiSecurity::initialize(true);  // api_key skips CSRF + rate limited; session enforces CSRF
$userId = $security['user_id'];
$pdo = $security['pdo'];
$apiKey = $security['api_key'];  // may be null

// For compatibility with later code in this file
$username = 'anonymous';
if ($userId) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    if ($u = $stmt->fetch()) $username = $u['username'];
}

// ✅ Phase 2 業務邏輯修復：簡單 rate limit 防 spam fork (session based, 5秒)
if (!empty($_SESSION['last_fork_time']) && (time() - $_SESSION['last_fork_time']) < 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => __('Too many forks, please wait')], JSON_UNESCAPED_UNICODE);
    exit;
}
$_SESSION['last_fork_time'] = time();

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if (empty($input['soul_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('soul_id is required')], JSON_UNESCAPED_UNICODE);
    exit;
}

$soulId = (int)$input['soul_id'];

$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND is_public = 1");
$stmt->execute([$soulId]);
$original = $stmt->fetch();

if (!$original) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => __('Public soul not found')], JSON_UNESCAPED_UNICODE);
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
        'message' => __('Soul forked successfully!')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Failed to fork soul due to server error')], JSON_UNESCAPED_UNICODE);
}