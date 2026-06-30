<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';
require_once __DIR__ . '/../../private/src/SoulCorpHub.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $security = ApiSecurity::initialize(false);
        $gigs = SoulCorpHub::listGigs($security['pdo'], $_GET['status'] ?? 'open');
        echo json_encode(['success' => true, 'gigs' => $gigs]);
        exit;
    }

    if ($method === 'POST') {
        $security = ApiSecurity::initialize(true);
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $result = SoulCorpHub::createGig($security['pdo'], (int)$security['user_id'], $input);
        echo json_encode(['success' => true] + $result);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
} catch (RuntimeException $e) {
    $code = $e->getCode();
    http_response_code(is_int($code) && $code >= 400 ? $code : 400);
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'code' => $code ?: 400]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}