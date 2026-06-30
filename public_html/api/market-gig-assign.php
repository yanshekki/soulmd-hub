<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';
require_once __DIR__ . '/../../private/src/SoulCorpHub.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $security = ApiSecurity::initialize(true);
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $gigId = (int)($input['gig_id'] ?? $_GET['id'] ?? 0);
    $result = SoulCorpHub::assignGig($security['pdo'], (int)$security['user_id'], $gigId);
    echo json_encode(['success' => true] + $result);
} catch (RuntimeException $e) {
    $code = $e->getCode();
    http_response_code(is_int($code) && $code >= 400 ? $code : 400);
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'code' => $code ?: 400]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}