<?php
/**
 * SoulMD Hub Public API
 * GET  /api/souls          - List public souls (Optimized Search & Sorting)
 * POST /api/souls          - Create soul (Requires API Key)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
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

if ($method === 'GET') {
    // ==========================================
    // GET: List public souls (Optimized Search)
    // ==========================================
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    $q = trim($_GET['q'] ?? '');
    $role = $_GET['role'] ?? '';
    $fileType = $_GET['file_type'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';

    $sql = "SELECT id, title, description, role, domain, compatibility, file_type, like_count, fork_count, created_at 
            FROM souls WHERE is_public = 1";
    $params = [];

    // Performance Optimization: Excluded 'description' from LIKE search to prevent heavy full table scans.
    // Now searching only in Title, Role, Domain, and Compatibility.
    if ($q) {
        $sql .= " AND (title LIKE ? OR role LIKE ? OR domain LIKE ? OR compatibility LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    
    if ($role) {
        $sql .= " AND role = ?";
        $params[] = $role;
    }
    
    if ($fileType) {
        $sql .= " AND file_type = ?";
        $params[] = $fileType;
    }

    if ($sort === 'popular') {
        $sql .= " ORDER BY like_count DESC, created_at DESC";
    } elseif ($sort === 'forks') {
        $sql .= " ORDER BY fork_count DESC, created_at DESC";
    } else {
        $sql .= " ORDER BY created_at DESC";
    }

    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
    // ==========================================
    // POST: Create a new soul (API Key Required)
    // ==========================================
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $apiKey = trim(str_replace('Bearer', '', $authHeader));

    if (empty($apiKey)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'API Key is required in Authorization header']);
        exit;
    }

    $keyStmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
    $keyStmt->execute([$apiKey]);
    $user = $keyStmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid API Key']);
        exit;
    }

    $userId = $user['id'];

    if (empty($input['title']) || empty($input['content'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Fields "title" and "content" are required']);
        exit;
    }

    $fileType = strpos(trim($input['content']), '{') === 0 ? 'full_soul_folder' : 'single_md';

    try {
        $stmt = $pdo->prepare("INSERT INTO souls 
            (user_id, title, description, content, file_type, role, domain, compatibility, is_public) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $userId,
            $input['title'],
            $input['description'] ?? '',
            $input['content'],
            $fileType,
            $input['role'] ?? '',
            $input['domain'] ?? '',
            $input['compatibility'] ?? ''
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Soul created successfully via API',
            'id' => $newId,
            'url' => "https://" . $_SERVER['HTTP_HOST'] . "/soul/" . $newId
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save soul via API']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}