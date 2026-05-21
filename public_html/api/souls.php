<?php
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

function incrementTags($pdo, $table, $tagsString) {
    $tags = array_filter(array_map('trim', explode(',', $tagsString)));
    foreach ($tags as $tag) {
        if (empty($tag)) continue;
        $stmt = $pdo->prepare("INSERT INTO {$table} (name, usage_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
        $stmt->execute([$tag]);
    }
}

// 🚨 SEO URL 友善化助手
function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

if ($method === 'GET') {
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    $q = trim($_GET['q'] ?? '');
    $role = $_GET['role'] ?? '';
    $fileType = $_GET['file_type'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';

    // 🚨 完美修復：JOIN users 表，獲取 username 供前端組合 SEO URL
    $sql = "SELECT s.id, s.title, s.description, s.role, s.domain, s.compatibility, s.file_type, s.like_count, s.fork_count, s.created_at, u.username 
            FROM souls s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.is_public = 1";
            
    $binds = [];

    if ($q) {
        $sql .= " AND (s.title LIKE ? OR s.role LIKE ? OR s.domain LIKE ? OR s.compatibility LIKE ?)";
        $binds[] = ["%$q%", PDO::PARAM_STR];
        $binds[] = ["%$q%", PDO::PARAM_STR];
        $binds[] = ["%$q%", PDO::PARAM_STR];
        $binds[] = ["%$q%", PDO::PARAM_STR];
    }
    
    if ($role) {
        $sql .= " AND s.role = ?";
        $binds[] = [$role, PDO::PARAM_STR];
    }
    
    if ($fileType) {
        $sql .= " AND s.file_type = ?";
        $binds[] = [$fileType, PDO::PARAM_STR];
    }

    if ($sort === 'popular') {
        $sql .= " ORDER BY s.like_count DESC, s.created_at DESC";
    } elseif ($sort === 'forks') {
        $sql .= " ORDER BY s.fork_count DESC, s.created_at DESC";
    } else {
        $sql .= " ORDER BY s.created_at DESC";
    }

    $sql .= " LIMIT ? OFFSET ?";
    
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
    $userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Valid Session or API Key required.']);
        exit;
    }

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

    if (!empty($role) && $role !== 'Other') {
        $roleCheckStmt = $pdo->prepare("SELECT slug FROM categories WHERE slug = ?");
        $roleCheckStmt->execute([$role]);
        if (!$roleCheckStmt->fetch()) {
            $role = 'Other'; 
        }
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

        incrementTags($pdo, 'tags_domain', $domain);
        incrementTags($pdo, 'tags_compatibility', $compatibility);

        $pdo->commit();

        // 🚨 取得目前使用者名稱以拼湊 SEO 網址
        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $username = $uStmt->fetchColumn() ?: 'anonymous';
        
        $seoUrl = "https://" . $_SERVER['HTTP_HOST'] . "/soul/" . rawurlencode($username) . "/" . $newId . "/" . makeSlug($role) . "/" . makeSlug($title);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Soul created successfully',
            'id' => $newId,
            'url' => $seoUrl
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