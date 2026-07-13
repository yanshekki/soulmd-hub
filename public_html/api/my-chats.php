<?php
/**
 * SoulMD Hub Public API
 * GET  /api/my-chats - Get paginated personal chat sessions (Requires Auth)
 * POST /api/my-chats - Fetch public guest chat sessions via LocalStorage tokens
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require_once __DIR__ . '/../../private/src/AppBootstrap.php';
$app = AppBootstrap::forApi([
    'require_user' => false,
    'enforce_csrf' => true,
    'translations' => 'api',
    'json_header' => false,
]);
$userId = $app['user_id'];
$pdo = $app['pdo'];
$isApiKey = !empty($app['is_api_key']);
$apiKey = $app['api_key'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 12; 
    $offset = ($page - 1) * $limit;

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_sessions WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $totalCount = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalCount / $limit));

    $stmt = $pdo->prepare("
        SELECT cs.session_token, cs.soul_id, cs.is_private, cs.created_at, s.title, s.role
        FROM chat_sessions cs
        JOIN souls s ON cs.soul_id = s.id
        WHERE cs.user_id = ?
        ORDER BY cs.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $chats = $stmt->fetchAll();

    // 撈取圖標
    $catStmt = $pdo->query("SELECT slug, icon FROM categories");
    $icons = [];
    while ($row = $catStmt->fetch()) { $icons[$row['slug']] = $row['icon']; }

    foreach ($chats as &$chat) {
        $chat['role_icon'] = $icons[$chat['role']] ?? '✨';
    }

    echo json_encode([
        'success' => true,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'data' => $chats
    ], JSON_UNESCAPED_UNICODE);
    exit;

} elseif ($method === 'POST') {
    // CSRF for session users already enforced by ApiSecurity at top (guest token paths are allowed)
    $input = json_decode(file_get_contents('php://input'), true);
    $tokens = $input['tokens'] ?? [];

    if (empty($tokens) || !is_array($tokens)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $tokens = array_slice($tokens, 0, 50); 
    $inQuery = implode(',', array_fill(0, count($tokens), '?'));

    // 🚨 完美過濾：絕對不拉取屬於當前登入者自己的 Token
    $sql = "
        SELECT cs.session_token, cs.soul_id, cs.created_at, s.title, s.role, u.username as owner_username
        FROM chat_sessions cs
        JOIN souls s ON cs.soul_id = s.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE cs.session_token IN ($inQuery)
        AND (cs.user_id IS NULL OR cs.user_id != ?)
        ORDER BY cs.created_at DESC
    ";
    
    $params = $tokens;
    $params[] = $userId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $guestChats = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $guestChats], JSON_UNESCAPED_UNICODE);
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
}
?>