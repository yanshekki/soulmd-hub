<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$userId = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$apiKey = trim(str_replace('Bearer', '', $authHeader));

if ($apiKey) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    if ($user = $stmt->fetch()) $userId = $user['id'];
} else {
    session_start();
    if (isset($_SESSION['user_id'])) $userId = $_SESSION['user_id'];
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Login or valid API Key required']);
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

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Rating must be 1-5']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO soul_ratings (soul_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
$stmt->execute([$soulId, $userId, $rating]);
echo json_encode(['success' => true]);