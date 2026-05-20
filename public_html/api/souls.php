<?php
/**
 * SoulMD Hub Public API
 * GET  /api/souls.php          - List public souls
 * POST /api/souls.php          - Create soul (with API Key)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List public souls
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
    // Create soul
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    // Optional API Key check
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $apiKey = str_replace('Bearer ', '', $authHeader);

    if ($apiKey) {
        $keyStmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
        $keyStmt->execute([$apiKey]);
        if (!$keyStmt->fetch()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid API Key']);
            exit;
        }
    }

    if (empty($input['title']) || empty($input['content'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'title and content are required']);
        exit;
    }

    $userId = 1; // Default for API (or use from key)

    $fileType = strpos($input['content'], '{') === 0 ? 'full_soul_folder' : 'single_md';

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
        'message' => 'Soul created successfully',
        'id' => $newId
    ], JSON_UNESCAPED_UNICODE);

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}