<?php
/**
 * API: Fork a soul (create a copy under current user)
 * POST /api/fork.php
 * Body: { "soul_id": 123 }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['soul_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'soul_id is required']);
    exit;
}

$soulId = (int)$input['soul_id'];
$userId = $_SESSION['user_id'];

$db = Database::getInstance();
$pdo = $db->getConnection();

// Get original soul
$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND is_public = 1");
$stmt->execute([$soulId]);
$original = $stmt->fetch();

if (!$original) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Soul not found']);
    exit;
}

// Create forked copy
$stmt = $pdo->prepare("
    INSERT INTO souls 
    (user_id, title, description, content, file_type, role, domain, compatibility, is_public) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
");
$stmt->execute([
    $userId,
    $original['title'] . ' (Forked)',
    $original['description'],
    $original['content'],
    $original['file_type'],
    $original['role'],
    $original['domain'],
    $original['compatibility']
]);

$newId = $pdo->lastInsertId();

// Increment fork count on original
$pdo->prepare("UPDATE souls SET fork_count = fork_count + 1 WHERE id = ?")
    ->execute([$soulId]);

echo json_encode([
    'success' => true,
    'new_soul_id' => $newId,
    'message' => 'Soul forked successfully!'
]);