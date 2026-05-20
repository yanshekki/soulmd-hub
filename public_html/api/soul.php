<?php
/**
 * SoulMD Hub Public API
 * GET /api/soul.php?id=XX - Get single soul
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'id is required']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND is_public = 1");
$stmt->execute([$id]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Soul not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => $soul
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);