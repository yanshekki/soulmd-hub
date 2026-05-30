<?php
/**
 * SoulMD Hub API
 * POST /api/paypal - Capture PayPal Order & Entitlement Engine (i18n Fixed)
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

session_start();
loadTranslations('api'); // 🚨 載入語言包

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Auth required for transaction')], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['orderID'] ?? '';
$purchasedTier = $input['tier'] ?? ''; 

if (empty($orderId) || !in_array($purchasedTier, ['vip', 'pro'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Malformed transaction')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

$uStmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$currentUser = $uStmt->fetch();

$currentTier = $currentUser['tier'];
$currentExpiry = $currentUser['vip_expires_at'] ? strtotime($currentUser['vip_expires_at']) : 0;
$now = time();
$isActivePremium = ($currentTier !== 'free' && $currentExpiry > $now);

if ($isActivePremium && $currentTier === 'pro' && $purchasedTier === 'vip') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Downgrade Guard')], JSON_UNESCAPED_UNICODE);
    exit;
}

$checkStmt = $pdo->prepare("SELECT id, status FROM payments WHERE paypal_order_id = ?");
$checkStmt->execute([$orderId]);
if ($checkStmt->fetch()) {
    echo json_encode(['success' => true, 'message' => __('Transaction already processed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$paypalBaseUrl = (PAYPAL_MODE === 'sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypalBaseUrl . '/v1/oauth2/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Gateway auth failure')], JSON_UNESCAPED_UNICODE);
    exit;
}

$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? '';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypalBaseUrl . "/v2/checkout/orders/{$orderId}/capture");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);

$captureResponse = curl_exec($ch);
$captureHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$captureData = json_decode($captureResponse, true);
$paypalStatus = $captureData['status'] ?? '';

if ($captureHttpCode !== 200 && $captureHttpCode !== 201) {
    $errorDesc = $captureData['details'][0]['description'] ?? 'Payment authorization declined by issuer.';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Gateway Error', ['error' => $errorDesc])], JSON_UNESCAPED_UNICODE);
    exit;
}

$paidAmount = $captureData['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? '0.00';
$expectedAmount = ($purchasedTier === 'pro') ? PRICE_PRO_MONTHLY : PRICE_VIP_MONTHLY;

if ((float)$paidAmount < (float)$expectedAmount && $paypalStatus === 'COMPLETED') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Gross amount mismatch')], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo->beginTransaction();

    $insStmt = $pdo->prepare("INSERT INTO payments (user_id, paypal_order_id, amount, currency, tier_purchased, status) VALUES (?, ?, ?, ?, ?, ?)");
    $insStmt->execute([$userId, $orderId, $paidAmount, 'USD', $purchasedTier, $paypalStatus]);

    $purchasedSeconds = 30 * 24 * 60 * 60; 

    if ($paypalStatus === 'COMPLETED') {
        if ($currentTier === 'vip' && $purchasedTier === 'pro' && $currentExpiry > $now) {
            $remainingVipSeconds = $currentExpiry - $now;
            $conversionRatio = (PRICE_VIP_MONTHLY / 30) / (PRICE_PRO_MONTHLY / 30);
            $convertedProSeconds = $remainingVipSeconds * $conversionRatio;
            $newExpiry = $now + $purchasedSeconds + $convertedProSeconds;
        } else {
            $newExpiry = max($currentExpiry, $now) + $purchasedSeconds;
        }

        $newExpiryStr = date('Y-m-d H:i:s', $newExpiry);
        $pdo->prepare("UPDATE users SET tier = ?, vip_expires_at = ? WHERE id = ?")
            ->execute([$purchasedTier, $newExpiryStr, $userId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => __('Transaction COMPLETED'), 'status' => 'COMPLETED', 'new_tier' => $purchasedTier, 'expires_at' => $newExpiryStr], JSON_UNESCAPED_UNICODE);
    } elseif ($paypalStatus === 'PENDING') {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => __('Transaction PENDING'), 'status' => 'PENDING', 'new_tier' => 'free'], JSON_UNESCAPED_UNICODE);
    } else {
        $pdo->commit();
        echo json_encode(['success' => false, 'error' => __('Auth returned status', ['status' => $paypalStatus]), 'status' => $paypalStatus], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Entitlement error')], JSON_UNESCAPED_UNICODE);
}
?>