<?php
/**
 * SoulMD Hub Public API
 * GET /api/categories - List all available categories / roles
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // 撈取資料庫中設定的所有分類，供開發者白名單對照
    $stmt = $pdo->query("SELECT id, name, slug, icon FROM categories ORDER BY id ASC");
    $categories = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'count' => count($categories),
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database query failed']);
}