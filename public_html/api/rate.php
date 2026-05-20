<?php
/**
 * API: Rate a soul (1-5 stars)
 * POST /api/rate.php
 * Body: { "soul_id": 123, "rating": 5 }
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

if (empty($input['soul_id']) || empty($input['rating'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'soul_id and rating are required']);
    exit;
}

$soulId = (int)$input['soul_id'];
$rating = (int)$input['rating'];
$userId = $_SESSION['user_id'];

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Rating must be 1-5']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Insert or update rating
$stmt = $pdo->prepare("
    INSERT INTO soul_ratings (soul_id, user_id, rating) 
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE rating = VALUES(rating)
");
$stmt->execute([$soulId, $userId, $rating]);

echo json_encode(['success' => true]);