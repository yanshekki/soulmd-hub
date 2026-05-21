<?php
/**
 * SoulMD Hub Public API
 * GET  /api/souls          - List public souls
 * POST /api/souls          - Create soul (Requires API Key)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
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
    // GET: List public souls
    // ==========================================
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    $q = trim($_GET['q'] ?? '');
    $role = $_GET['role'] ?? '';
    $fileType = $_GET['file_type'] ?? '';

    $sql = "SELECT id, title, description, role, domain, compatibility, file_type, like_count, fork_count, created_at 
            FROM souls WHERE is_public = 1";
    $params = [];

    if ($q) {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
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

    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $souls = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($souls),
        'data' => $souls
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} elseif ($method === 'POST') {
    // ==========================================
    // POST: Create a new soul (API Key Required)
    // ==========================================
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    // Extract API Key from Bearer token
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $apiKey = trim(str_replace('Bearer', '', $authHeader));

    if (empty($apiKey)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'API Key is required in Authorization header']);
        exit;
    }

    // 嚴格校驗 API Key 並獲取真實的 User ID
    $keyStmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
    $keyStmt->execute([$apiKey]);
    $user = $keyStmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid API Key']);
        exit;
    }

    $userId = $user['id']; // 動態綁定 API 擁有者的 ID！

    // 必填欄位檢查
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