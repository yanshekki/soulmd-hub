<?php
/**
 * SoulMD Hub - Mini Apps API
 * GET  /api/apps           — list curated mini apps (theme catalog)
 * GET  /api/apps?slug=...  — form schema + souls from keyword search
 * POST /api/apps           — validate fields + soul_id, return chat prefill content
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/src/AppBootstrap.php';
require_once __DIR__ . '/../../private/src/MiniAppsCatalog.php';

// Load config + i18n first (never call loadTranslations before bootstrap)
$boot = AppBootstrap::forApi([
    'require_user' => false,
    'enforce_csrf' => true,
    'translations' => ['api', 'apps'],
    'json_header' => false,
]);
$userId = $boot['user_id'];
$pdo = $boot['pdo'];
$isApiKey = !empty($boot['is_api_key']);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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

$catalogApp = MiniAppsCatalog::getBySlug($slug);
if (!$catalogApp) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => __('App not found')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!MiniAppsCatalog::isSoulAllowed($pdo, $slug, $soulId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Invalid app soul')], JSON_UNESCAPED_UNICODE);
    exit;
}

[$ok, $fieldErr, $sanitized] = MiniAppsCatalog::validateFields($catalogApp, $fieldsIn);
if (!$ok) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $fieldErr], JSON_UNESCAPED_UNICODE);
    exit;
}

$content = MiniAppsCatalog::formatUserMessage($catalogApp, $sanitized);

echo json_encode([
    'success' => true,
    'slug' => $slug,
    'soul_id' => $soulId,
    'content' => $content,
    'chat_path' => '/chat/' . $soulId,
], JSON_UNESCAPED_UNICODE);
