<?php
/**
 * SoulMD Hub Internal API
 * POST /api/save-preset - Save generated AI soul to session memory
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';

loadTranslations('api');

$security = ApiSecurity::initialize(true);  // enforces session + CSRF
$userId = $security['user_id'];
$pdo = $security['pdo'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$_SESSION['preset_title'] = $input['title'] ?? '';
$_SESSION['preset_content'] = $input['content'] ?? '';
$_SESSION['preset_role'] = $input['role'] ?? '';

echo json_encode(['success' => true]);