<?php
/**
 * API: Like / Unlike a soul
 * POST /api/like.php
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

// Simple like system: increment like_count
// (If you want per-user like tracking later, add a soul_likes table)
$stmt = $pdo->prepare("UPDATE souls SET like_count = like_count + 1 WHERE id = ?");
$stmt->execute([$soulId]);

echo json_encode(['success' => true]);