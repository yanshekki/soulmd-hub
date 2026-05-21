<?php
/**
 * SoulMD Hub Public API
 * GET /api/profile?username={username} - Get public profile data & public souls
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$username = trim($_GET['username'] ?? '');

if (empty($username)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username parameter is required']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// 1. 撈取用戶基礎公開資料
$userStmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE username = ?");
$userStmt->execute([$username]);
$user = $userStmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$userId = $user['id'];

// 2. 彙整並統計該用戶的社交數據 (總公開數、總點讚數、總 Fork 數)
$statsStmt = $pdo->prepare("
    SELECT COUNT(*) as total_souls, 
           COALESCE(SUM(like_count), 0) as total_likes, 
           COALESCE(SUM(fork_count), 0) as total_forks 
    FROM souls 
    WHERE user_id = ? AND is_public = 1
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

// 3. 獲取該用戶所有的公開 Souls 列表
$soulsStmt = $pdo->prepare("
    SELECT id, title, description, role, domain, compatibility, file_type, like_count, fork_count, created_at 
    FROM souls 
    WHERE user_id = ? AND is_public = 1 
    ORDER BY created_at DESC
");
$soulsStmt->execute([$userId]);
$publicSouls = $soulsStmt->fetchAll();

// 4. 回傳精準架構的 JSON 數據
echo json_encode([
    'success' => true,
    'user' => [
        'username' => $user['username'],
        'joined_at' => $user['created_at']
    ],
    'stats' => [
        'total_souls' => (int)$stats['total_souls'],
        'total_likes' => (int)$stats['total_likes'],
        'total_forks' => (int)$stats['total_forks']
    ],
    'souls' => $publicSouls
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);