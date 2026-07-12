<?php
/**
 * SoulMD Hub - Mini Apps API
 * GET  /api/apps           — list curated mini apps
 * GET  /api/apps?slug=...  — app detail + form schema + soul choices (intros only)
 * POST /api/apps           — validate fields + soul_id, return formatted content for chat prefill
 *                            (does NOT call the LLM — client redirects to /chat)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';
require_once __DIR__ . '/../../private/src/MiniAppsCatalog.php';

loadTranslations('api');
loadTranslations('apps');

$security = ApiSecurity::initialize(false);
$pdo      = $security['pdo'];
$method   = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $slug = trim((string)($_GET['slug'] ?? ''));
    if ($slug !== '') {
        $detail = MiniAppsCatalog::getPublicDetail($slug, $pdo);
        if (!$detail) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => __('App not found')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $detail], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $category = isset($_GET['category']) ? trim((string)$_GET['category']) : null;
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
    $list = MiniAppsCatalog::listPublic($category, $q);
    echo json_encode([
        'success' => true,
        'count' => count($list),
        'data' => $list,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- POST: validate only, return content for chat redirect ---
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Invalid JSON payload')], JSON_UNESCAPED_UNICODE);
    exit;
}

$slug = trim((string)($input['slug'] ?? ''));
$soulId = (int)($input['soul_id'] ?? 0);
$fieldsIn = $input['fields'] ?? null;

if ($slug === '' || !is_array($fieldsIn)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
    exit;
}

$app = MiniAppsCatalog::getBySlug($slug);
if (!$app || MiniAppsCatalog::resolveSoulIds($slug) === []) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => __('App not found')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!MiniAppsCatalog::isSoulAllowed($slug, $soulId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Invalid app soul')], JSON_UNESCAPED_UNICODE);
    exit;
}

// Confirm soul still public / non-NFT
$metas = MiniAppsCatalog::loadSoulMetas($pdo, [$soulId]);
if ($metas === []) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('App soul not available')], JSON_UNESCAPED_UNICODE);
    exit;
}

[$ok, $fieldErr, $sanitized] = MiniAppsCatalog::validateFields($app, $fieldsIn);
if (!$ok) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $fieldErr], JSON_UNESCAPED_UNICODE);
    exit;
}

$content = MiniAppsCatalog::formatUserMessage($app, $sanitized);

echo json_encode([
    'success' => true,
    'slug' => $slug,
    'soul_id' => $soulId,
    'content' => $content,
    'chat_path' => '/chat/' . $soulId,
], JSON_UNESCAPED_UNICODE);
