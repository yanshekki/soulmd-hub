<?php
/**
 * SoulMD Hub API
 * POST /api/paypal - Capture PayPal Order & Entitlement Engine
 * Entitlement writes go through PremiumEntitlement (shared with NEAR).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';
require_once __DIR__ . '/../../private/src/SoulCorpHub.php';
require_once __DIR__ . '/../../private/src/PremiumEntitlement.php';

loadTranslations('api');

$security = ApiSecurity::initialize(true);  // requires session + enforces CSRF
$userId = $security['user_id'];
$pdo = $security['pdo'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($userId)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = trim((string)($input['orderID'] ?? ''));
$purchasedTier = strtolower(trim((string)($input['tier'] ?? '')));

// PayPal order IDs are alphanumeric; reject obvious garbage early
if ($orderId === '' || !preg_match('/^[A-Z0-9_-]{8,64}$/i', $orderId) || !in_array($purchasedTier, ['vip', 'pro'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Malformed transaction')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = (int)$_SESSION['user_id'];

// Idempotency: already captured/processed
$checkStmt = $pdo->prepare("SELECT id, status, tier_purchased FROM payments WHERE paypal_order_id = ? LIMIT 1");
$checkStmt->execute([$orderId]);
$existingPay = $checkStmt->fetch();
if ($existingPay) {
    echo json_encode([
        'success' => true,
        'message' => __('Transaction already processed'),
        'status' => $existingPay['status'] ?? 'COMPLETED',
        'new_tier' => $existingPay['tier_purchased'] ?? $purchasedTier,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$paypalBaseUrl = (PAYPAL_MODE === 'sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypalBaseUrl . '/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Gateway auth failure')], JSON_UNESCAPED_UNICODE);
    exit;
}

$tokenData = json_decode((string)$response, true);
$accessToken = $tokenData['access_token'] ?? '';
if ($accessToken === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Gateway auth failure')], JSON_UNESCAPED_UNICODE);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypalBaseUrl . "/v2/checkout/orders/{$orderId}/capture");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_TIMEOUT, 45);

$captureResponse = curl_exec($ch);
$captureHttpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$captureData = json_decode((string)$captureResponse, true) ?? [];
$paypalStatus = (string)($captureData['status'] ?? '');

if ($captureHttpCode !== 200 && $captureHttpCode !== 201) {
    $errorDesc = $captureData['details'][0]['description'] ?? 'Payment authorization declined by issuer.';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Gateway Error', ['error' => $errorDesc])], JSON_UNESCAPED_UNICODE);
    exit;
}

// Only COMPLETED captures grant entitlement (PENDING must not succeed or block forever)
if ($paypalStatus !== 'COMPLETED') {
    http_response_code(402);
    echo json_encode([
        'success' => false,
        'error' => __('Auth returned status', ['status' => $paypalStatus ?: 'UNKNOWN']),
        'status' => $paypalStatus,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$capture = $captureData['purchase_units'][0]['payments']['captures'][0] ?? null;
if (!is_array($capture)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Gross amount mismatch')], JSON_UNESCAPED_UNICODE);
    exit;
}

$paidAmount = (string)($capture['amount']['value'] ?? '0.00');
$paidCurrency = strtoupper((string)($capture['amount']['currency_code'] ?? ''));
$captureStatus = (string)($capture['status'] ?? '');
$expectedAmount = ($purchasedTier === 'pro') ? (string)PRICE_PRO_MONTHLY : (string)PRICE_VIP_MONTHLY;

// Capture-level COMPLETED + USD + exact amount (not merely "not less than")
if ($captureStatus !== '' && $captureStatus !== 'COMPLETED') {
    http_response_code(402);
    echo json_encode(['success' => false, 'error' => __('Auth returned status', ['status' => $captureStatus])], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($paidCurrency !== 'USD') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Gross amount mismatch')], JSON_UNESCAPED_UNICODE);
    exit;
}

$amountOk = false;
if (function_exists('bccomp')) {
    $amountOk = (bccomp($paidAmount, $expectedAmount, 2) === 0);
} else {
    $amountOk = (abs((float)$paidAmount - (float)$expectedAmount) < 0.005);
}
if (!$amountOk) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Gross amount mismatch')], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $result = PremiumEntitlement::applyPurchase(
        $pdo,
        $userId,
        $purchasedTier,
        $orderId,
        $paidAmount,
        'USD',
        true // duplicate claim → success (idempotent)
    );

    if (!empty($result['downgrade'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('Downgrade Guard')], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!empty($result['user_missing'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Entitlement error')], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!empty($result['already'])) {
        echo json_encode(['success' => true, 'message' => __('Transaction already processed')], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Hard require success flag — never report COMPLETED without ok
    if (empty($result['ok']) || empty($result['expires_at'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Entitlement error')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => __('Transaction COMPLETED'),
        'status' => 'COMPLETED',
        'new_tier' => $result['new_tier'] ?? $purchasedTier,
        'expires_at' => $result['expires_at'],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (PremiumEntitlement::isDuplicateKeyException($e)) {
        echo json_encode(['success' => true, 'message' => __('Transaction already processed')], JSON_UNESCAPED_UNICODE);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Entitlement error')], JSON_UNESCAPED_UNICODE);
}
?>
