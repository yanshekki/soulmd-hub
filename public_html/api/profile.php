<?php
/**
 * SoulMD Hub Public API
 * GET /api/profile?username={username}
 * 🚀 Patched: Price-aware visibility sync
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

loadTranslations('api');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE); exit;
}

$username = trim($_GET['username'] ?? '');
if (empty($username)) { http_response_code(400); echo json_encode(['success' => false, 'error' => __('Username parameter is required')], JSON_UNESCAPED_UNICODE); exit; }

$db = Database::getInstance();
$pdo = $db->getConnection();

$userStmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE username = ?");
$userStmt->execute([$username]);
$user = $userStmt->fetch();

if (!$user) { http_response_code(404); echo json_encode(['success' => false, 'error' => __('User not found')], JSON_UNESCAPED_UNICODE); exit; }

$userId = $user['id'];

// 🚨 完美統計修復：只有 Web2公開 或 Web3有價錢 的資產才算入公共作品集！
$statsStmt = $pdo->prepare("
    SELECT COUNT(*) as total_souls, 
           COALESCE(SUM(like_count), 0) as total_likes, 
           COALESCE(SUM(fork_count), 0) as total_forks 
    FROM souls 
    WHERE user_id = ? AND ((is_public = 1 AND (is_nft = 0 OR is_nft IS NULL)) OR (is_nft = 1 AND (sale_price IS NOT NULL OR rent_price IS NOT NULL)))
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

echo json_encode([
    'success' => true,
    'user' => ['username' => $user['username'], 'joined_at' => $user['created_at']],
    'stats' => [
        'total_souls' => (int)$stats['total_souls'],
        'total_likes' => (int)$stats['total_likes'],
        'total_forks' => (int)$stats['total_forks']
    ]
], JSON_UNESCAPED_UNICODE);