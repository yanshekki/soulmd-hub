<?php
/**
 * SoulMD Hub Public API
 * GET /api/profile?username={username}&sort={sort}&page={page}&limit={limit}
 * 🚀 Patched: Pagination added to prevent memory exhaustion and DDoS vectors
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
$sort = $_GET['sort'] ?? 'newest';

// 🚀 加入分頁參數與安全邊界限制 (預設每頁 12 筆，最高 100 筆)
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min((int)($_GET['limit'] ?? 12), 100);

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

// 2. 彙整並統計該用戶的社交數據 (包含 Web2 與 Web3 的公開資產)
$statsStmt = $pdo->prepare("
    SELECT COUNT(*) as total_souls, 
           COALESCE(SUM(like_count), 0) as total_likes, 
           COALESCE(SUM(fork_count), 0) as total_forks 
    FROM souls 
    WHERE user_id = ? AND is_public = 1 AND (is_nft = 0 OR is_nft IS NULL)
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

$totalSouls = (int)$stats['total_souls'];
$totalPages = max(1, ceil($totalSouls / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// 3. 處理排序條件
$orderSql = "ORDER BY created_at DESC";
if ($sort === 'popular') {
    $orderSql = "ORDER BY like_count DESC, created_at DESC";
} elseif ($sort === 'forks') {
    $orderSql = "ORDER BY fork_count DESC, created_at DESC";
} elseif ($sort === 'oldest') {
    $orderSql = "ORDER BY created_at ASC";
} elseif ($sort === 'az') {
    $orderSql = "ORDER BY title ASC, created_at DESC";
} elseif ($sort === 'za') {
    $orderSql = "ORDER BY title DESC, created_at DESC";
}

// 4. 獲取該用戶所有的公開 Souls 列表 (套用分頁與排序)
$soulsStmt = $pdo->prepare("
    SELECT id, title, description, role, domain, compatibility, file_type, like_count, fork_count, created_at 
    FROM souls 
    WHERE user_id = ? AND is_public = 1 AND (is_nft = 0 OR is_nft IS NULL)
    $orderSql
    LIMIT ? OFFSET ?
");

$soulsStmt->bindValue(1, $userId, PDO::PARAM_INT);
$soulsStmt->bindValue(2, $limit, PDO::PARAM_INT);
$soulsStmt->bindValue(3, $offset, PDO::PARAM_INT);
$soulsStmt->execute();
$publicSouls = $soulsStmt->fetchAll();

// 5. 回傳精準架構的 JSON 數據 (加入分頁指標)
echo json_encode([
    'success' => true,
    'current_page' => $page,
    'total_pages' => $totalPages,
    'user' => [
        'username' => $user['username'],
        'joined_at' => $user['created_at']
    ],
    'stats' => [
        'total_souls' => $totalSouls,
        'total_likes' => (int)$stats['total_likes'],
        'total_forks' => (int)$stats['total_forks']
    ],
    'souls' => $publicSouls
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);