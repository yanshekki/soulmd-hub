<?php
/**
 * SoulMD Hub - Mini Apps API
 * GET  /api/apps           — list curated mini apps
 * GET  /api/apps?slug=...  — app detail + form schema
 * POST /api/apps           — run app (body: { slug, fields })
 */

set_time_limit(180);

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
require_once __DIR__ . '/../../private/includes/token-gate.php';

loadTranslations('api');
loadTranslations('apps');

$security = ApiSecurity::initialize(false);
$userId   = $security['user_id'];
$pdo      = $security['pdo'];
$isApiKey = $security['is_api_key'];
$method   = $_SERVER['REQUEST_METHOD'];

function miniAppsGetCurrentUser($pdo, $apiUserId = null): array
{
    $uid = $apiUserId ?? ($_SESSION['user_id'] ?? null);
    $today = date('Y-m-d');

    if (!$uid) {
        if (($_SESSION['guest_last_chat_date'] ?? '') !== $today) {
            $_SESSION['guest_daily_count'] = 0;
            $_SESSION['guest_last_chat_date'] = $today;
        }
        return [
            'id' => null,
            'username' => null,
            'tier' => 'free',
            'daily_count' => (int)($_SESSION['guest_daily_count'] ?? 0),
            'is_expired' => false,
            'near_wallet' => null,
        ];
    }

    $stmt = $pdo->prepare("SELECT id, username, tier, daily_chat_count, last_chat_date, vip_expires_at, near_wallet_address FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['id' => null, 'username' => null, 'tier' => 'free', 'daily_count' => 0, 'is_expired' => false, 'near_wallet' => null];
    }

    $isExpired = false;
    if ($user['tier'] !== 'free' && $user['vip_expires_at'] && strtotime($user['vip_expires_at']) < time()) {
        $pdo->prepare("UPDATE users SET tier = 'free' WHERE id = ?")->execute([$uid]);
        $user['tier'] = 'free';
        $isExpired = true;
    }

    if ($user['last_chat_date'] !== $today) {
        $pdo->prepare("UPDATE users SET daily_chat_count = 0, last_chat_date = ? WHERE id = ? AND last_chat_date != ?")
            ->execute([$today, $uid, $today]);
        $user['daily_chat_count'] = 0;
    }

    return [
        'id' => $user['id'],
        'username' => $user['username'],
        'tier' => $user['tier'],
        'daily_count' => (int)$user['daily_chat_count'],
        'is_expired' => $isExpired,
        'near_wallet' => $user['near_wallet_address'],
    ];
}

function miniAppsTierConfig(string $tier): array
{
    $prefix = strtoupper($tier);
    return [
        'model' => constant("{$prefix}_MODEL"),
        'max_turns' => constant("{$prefix}_MAX_TURNS"),
        'daily_limit' => constant("{$prefix}_DAILY_LIMIT"),
        'max_input' => constant("{$prefix}_MAX_INPUT_CHARS"),
        'max_tokens' => constant("{$prefix}_MAX_AI_TOKENS"),
        'memory_threshold' => constant("{$prefix}_MEMORY_THRESHOLD"),
        'allow_image' => constant("{$prefix}_ALLOW_IMAGE"),
    ];
}

if ($method === 'GET') {
    $slug = trim((string)($_GET['slug'] ?? ''));
    if ($slug !== '') {
        $detail = MiniAppsCatalog::getPublicDetail($slug);
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

// --- POST run ---
$currentUser = miniAppsGetCurrentUser($pdo, $userId);

if ($isApiKey && $currentUser['tier'] === 'free') {
    http_response_code(403);
    $msg = $currentUser['is_expired'] ? __('API restricted expired') : __('API restricted free');
    echo json_encode(['success' => false, 'error' => $msg, 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$isApiKey) {
    $now = time();
    if (($now - (int)($_SESSION['last_chat_time'] ?? 0)) < 3) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => __('Sending too fast')], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Invalid JSON payload')], JSON_UNESCAPED_UNICODE);
    exit;
}

$slug = trim((string)($input['slug'] ?? ''));
$fieldsIn = $input['fields'] ?? null;
if ($slug === '' || !is_array($fieldsIn)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
    exit;
}

$app = MiniAppsCatalog::getBySlug($slug);
if (!$app) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => __('App not found')], JSON_UNESCAPED_UNICODE);
    exit;
}

[$ok, $fieldErr, $sanitized] = MiniAppsCatalog::validateFields($app, $fieldsIn);
if (!$ok) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $fieldErr], JSON_UNESCAPED_UNICODE);
    exit;
}

$userMessageText = MiniAppsCatalog::formatUserMessage($app, $sanitized);
$tierConfig = miniAppsTierConfig($currentUser['tier']);

if ($currentUser['daily_count'] >= $tierConfig['daily_limit']) {
    http_response_code(403);
    $upgradeMsg = $currentUser['tier'] === 'free' ? __('Upgrade suffix') : '';
    echo json_encode([
        'success' => false,
        'error' => __('Daily limit reached', ['limit' => $tierConfig['daily_limit'], 'upgrade' => $upgradeMsg]),
        'needs_upgrade' => true,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (mb_strlen($userMessageText, 'UTF-8') > $tierConfig['max_input']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => __('Message exceeds chars', ['limit' => $tierConfig['max_input']]),
        'needs_upgrade' => $currentUser['tier'] !== 'pro',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$soulId = MiniAppsCatalog::resolveSoulId($slug);
if ($soulId <= 0) {
    // Unmapped apps are hidden from list/detail; block direct run too
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => __('App not found')], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ?");
$stmt->execute([$soulId]);
$soul = $stmt->fetch();
if (!$soul) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE);
    exit;
}
// Catalog apps should use public non-NFT souls to avoid rent bypass
if (!empty($soul['is_nft'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => __('App soul not available')], JSON_UNESCAPED_UNICODE);
    exit;
}
if (empty($soul['is_public'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => __('App soul not available')], JSON_UNESCAPED_UNICODE);
    exit;
}
$chatUserWallet = $currentUser['near_wallet'] ?? '';
enforceSoulAccess($pdo, $soul, $chatUserWallet, $currentUser);

$systemPrompt = MiniAppsCatalog::buildSystemPrompt($app, $soul, $tierConfig);
$finalPayloadMessages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userMessageText],
];

if (!$isApiKey) {
    $_SESSION['last_chat_time'] = time();
}
session_write_close();

$targetApiUrl = DEEPSEEK_API_URL;
$targetApiKey = DEEPSEEK_API_KEY;
$targetModel = $tierConfig['model'];

$maxRetries = 3;
$retryDelay = 2;
$response = '';
$httpCode = 0;

for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
    $ch = curl_init();
    $payload = json_encode([
        'model' => $targetModel,
        'messages' => $finalPayloadMessages,
        'max_tokens' => $tierConfig['max_tokens'],
        'temperature' => 0.7,
        'stream' => false,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    curl_setopt_array($ch, [
        CURLOPT_URL => $targetApiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $targetApiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch);
    curl_close($ch);

    if ($curlError) {
        http_response_code(504);
        echo json_encode(['success' => false, 'error' => __('AI Service timeout')], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($httpCode !== 429) {
        break;
    }
    if ($attempt < $maxRetries - 1) {
        sleep($retryDelay);
        $retryDelay *= 2;
    }
}

$responseData = json_decode($response, true);
if ($httpCode !== 200 || !empty($responseData['error'])) {
    http_response_code(400);
    $errorDetail = $responseData['error']['message'] ?? __('Unknown Connection Failure');
    echo json_encode(['success' => false, 'error' => __('Engine Error', ['error' => $errorDetail])], JSON_UNESCAPED_UNICODE);
    exit;
}

$aiReply = $responseData['choices'][0]['message']['content'] ?? '';
$finishReason = (string)($responseData['choices'][0]['finish_reason'] ?? '');
$isTruncated = in_array($finishReason, ['length', 'max_tokens'], true);
$needsUpgradeForTruncation = $isTruncated && strtolower((string)($currentUser['tier'] ?? 'free')) !== 'pro';

$sessionToken = 'app_' . preg_replace('/[^a-z0-9_-]/i', '', $slug) . '_' . bin2hex(random_bytes(8));
if (strlen($sessionToken) > 128) {
    $sessionToken = substr($sessionToken, 0, 128);
}

$senderName = '';
if ($currentUser['id']) {
    $senderName = $currentUser['username'];
} else {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (empty($_SESSION['guest_id'])) {
        $_SESSION['guest_id'] = bin2hex(random_bytes(8));
    }
    $shortId = strtoupper(substr($_SESSION['guest_id'], 0, 4));
    $senderName = __('Anonymous') . ' #' . $shortId;
    session_write_close();
}

try {
    $freshPdo = Database::getFreshConnection();
    $freshPdo->beginTransaction();

    $persistSoulId = $soulId > 0 ? $soulId : 0;
    // chat_sessions.soul_id may be NOT NULL — use configured soul or skip DB if no soul
    if ($persistSoulId > 0) {
        $freshPdo->prepare("INSERT INTO chat_sessions (session_token, soul_id, user_id, is_private) VALUES (?, ?, ?, 1)")
            ->execute([$sessionToken, $persistSoulId, $currentUser['id']]);

        $ins = $freshPdo->prepare("INSERT INTO chat_messages (soul_id, session_token, role, sender_name, content) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$persistSoulId, $sessionToken, 'user', $senderName, $userMessageText]);
        $ins->execute([$persistSoulId, $sessionToken, 'assistant', __('AI Assistant'), $aiReply]);
    }

    if ($currentUser['id']) {
        $freshPdo->prepare("UPDATE users SET daily_chat_count = daily_chat_count + 1 WHERE id = ?")
            ->execute([$currentUser['id']]);
    } else {
        if (!$isApiKey) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $_SESSION['guest_daily_count'] = ($_SESSION['guest_daily_count'] ?? 0) + 1;
            session_write_close();
        }
    }

    $freshPdo->commit();

    echo json_encode([
        'success' => true,
        'reply' => $aiReply,
        'sender_name' => __('AI Assistant'),
        'truncated' => $isTruncated,
        'needs_upgrade' => $needsUpgradeForTruncation,
        'finish_reason' => $finishReason,
        'session_token' => $persistSoulId > 0 ? $sessionToken : null,
        'slug' => $slug,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($freshPdo) && $freshPdo->inTransaction()) {
        $freshPdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('DB Sync Error', ['error' => $e->getMessage()])], JSON_UNESCAPED_UNICODE);
}
