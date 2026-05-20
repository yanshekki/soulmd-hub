<?php
/**
 * SoulMD Hub Public API
 * GET /api/souls - List souls
 * POST /api/souls - Create soul (simple)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List souls
    $limit = min((int)($_GET['limit'] ?? 20), 50);
    $offset = (int)($_GET['offset'] ?? 0);

    $sql = "SELECT id, title, description, role, domain, compatibility, file_type, like_count, fork_count, created_at 
            FROM souls WHERE is_public = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $souls = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($souls),
        'data' => $souls
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} elseif ($method === 'POST') {
    // Simple create (for AI/tools)
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['title']) || empty($input['content'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'title and content are required']);
        exit;
    }

    $userId = 1; // Demo: default user (later use API key)

    $stmt = $pdo->prepare("INSERT INTO souls (user_id, title, description, content, file_type, role, domain, compatibility) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $input['title'],
        $input['description'] ?? '',
        $input['content'],
        $input['file_type'] ?? 'single_md',
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