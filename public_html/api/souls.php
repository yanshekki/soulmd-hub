<?php
/**
 * SoulMD Hub Public API
 * GET  /api/souls          - List public souls (Optimized Search & Sorting)
 * POST /api/souls          - Create soul (Auth: Session or API Key)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// ==========================================
// 權限助手函數 (支援 Session 或 API Key)
// ==========================================
function getAuthUserId($pdo) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $apiKey = trim(str_replace('Bearer', '', $authHeader));
    if ($apiKey) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        if ($user = $stmt->fetch()) return $user['id'];
    } else {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_id'])) return $_SESSION['user_id'];
    }
    return null;
}

// ==========================================
// 標籤統計函數
// ==========================================
function incrementTags($pdo, $table, $tagsString) {
    $tags = array_filter(array_map('trim', explode(',', $tagsString)));
    foreach ($tags as $tag) {
        if (empty($tag)) continue;
        $stmt = $pdo->prepare("INSERT INTO {$table} (name, usage_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
        $stmt->execute([$tag]);
    }
}

// ==========================================
// 路由處理
// ==========================================
if ($method === 'GET') {
    // 列表與搜尋
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    $q = trim($_GET['q'] ?? '');
    $role = $_GET['role'] ?? '';
    $fileType = $_GET['file_type'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';

    $sql = "SELECT id, title, description, role, domain, compatibility, file_type, like_count, fork_count, created_at 
            FROM souls WHERE is_public = 1";
            
    $binds = [];

    if ($q) {
        $sql .= " AND (title LIKE ? OR role LIKE ? OR domain LIKE ? OR compatibility LIKE ?)";
        $binds[] = ["%$q%", PDO::PARAM_STR];
        $binds[] = ["%$q%", PDO::PARAM_STR];
        $binds[] = ["%$q%", PDO::PARAM_STR];
        $binds[] = ["%$q%", PDO::PARAM_STR];
    }
    
    if ($role) {
        $sql .= " AND role = ?";
        $binds[] = [$role, PDO::PARAM_STR];
    }
    
    if ($fileType) {
        $sql .= " AND file_type = ?";
        $binds[] = [$fileType, PDO::PARAM_STR];
    }

    if ($sort === 'popular') {
        $sql .= " ORDER BY like_count DESC, created_at DESC";
    } elseif ($sort === 'forks') {
        $sql .= " ORDER BY fork_count DESC, created_at DESC";
    } else {
        $sql .= " ORDER BY created_at DESC";
    }

    $sql .= " LIMIT ? OFFSET ?";
    
    // 🚨 完美安全修復：使用顯式綁定 (Explicit Binding) 來取代 execute([])
    // 確保 LIMIT 和 OFFSET 被當作 Integer 傳遞，避免 MySQL 1064 Syntax Error
    try {
        $stmt = $pdo->prepare($sql);
        
        $paramIndex = 1;
        foreach ($binds as $bind) {
            $stmt->bindValue($paramIndex++, $bind[0], $bind[1]);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $souls = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'count' => count($souls),
            'data' => $souls
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database query failed']);
    }

} elseif ($method === 'POST') {
    // 建立 Soul$userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Valid Session or API Key required.']);
        exit;
    }

    // 🚨 完美修復：嚴格限定 JSON，杜絕 CSRF Form 創建垃圾大腦
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $title = trim($input['title'] ?? '');
    $content = $input['content'] ?? '';
    $description = trim($input['description'] ?? '');
    $role = $input['role'] ?? '';
    $domain = trim($input['domain'] ?? '');
    $compatibility = trim($input['compatibility'] ?? '');
    
    $is_public = isset($input['is_public']) ? (int)$input['is_public'] : 1;

    if (empty($title) || empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Fields "title" and "content" are required']);
        exit;
    }

    $fileType = strpos(trim($content), '{') === 0 ? 'full_soul_folder' : 'single_md';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO souls 
            (user_id, title, description, content, file_type, role, domain, compatibility, is_public) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId,
            $title,
            $description,
            $content,
            $fileType,
            $role,
            $domain,
            $compatibility,
            $is_public
        ]);

        $newId = $pdo->lastInsertId();

        // 更新標籤統計
        incrementTags($pdo, 'tags_domain', $domain);
        incrementTags($pdo, 'tags_compatibility', $compatibility);

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Soul created successfully',
            'id' => $newId,
            'url' => "https://" . $_SERVER['HTTP_HOST'] . "/soul/" . $newId
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save soul via API']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}