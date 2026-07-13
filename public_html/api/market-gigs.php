<?php
/**
 * SoulMD Hub API - Marketplace gigs list / create
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/src/AppBootstrap.php';
require_once __DIR__ . '/../../private/src/SoulCorpHub.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $app = AppBootstrap::forApi([
            'require_user' => false,
            'enforce_csrf' => true,
            'translations' => 'api',
            'json_header' => false,
        ]);
        $gigs = SoulCorpHub::listGigs($app['pdo'], $_GET['status'] ?? 'open');
        echo json_encode(['success' => true, 'gigs' => $gigs]);
        exit;
    }

    if ($method === 'POST') {
        $app = AppBootstrap::forApi([
            'require_user' => true,
            'enforce_csrf' => true,
            'translations' => 'api',
            'json_header' => false,
        ]);
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $result = SoulCorpHub::createGig($app['pdo'], (int)$app['user_id'], $input);
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
