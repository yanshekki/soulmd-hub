<?php
/**
 * SoulMD Hub API
 * POST /api/paypal - Capture PayPal Order & Automated Tier Entitlement Engine
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required to process transaction.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['orderID'] ?? '';
$purchasedTier = $input['tier'] ?? ''; 

if (empty($orderId) || !in_array($purchasedTier, ['vip', 'pro'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Malformed transaction payload detected.']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// ==========================================
// 🛡️ 1. Acquire PayPal Access Token
// ==========================================
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
    echo json_encode(['success' => false, 'error' => 'Gateway authentication failure.']);
    exit;
}

$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? '';

// ==========================================
// 🛡️ 2. Execute Order Capture
// ==========================================
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypalBaseUrl . "/v2/checkout/orders/{$orderId}/capture");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken
]);

$captureResponse = curl_exec($ch);
$captureHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$captureData = json_decode($captureResponse, true);
$paypalStatus = $captureData['status'] ?? '';

if ($captureHttpCode !== 200 && $captureHttpCode !== 201) {
    // PayPal 回傳 422 或是 INSTRUMENT_DECLINED 時的精準捕捉
    $errorDesc = $captureData['details'][0]['description'] ?? 'Payment authorization declined by issuer.';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => "Gateway Error: " . $errorDesc]);
    exit;
}

// ==========================================
// 🛡️ 3. Payment Integrity & Amount Validation
// ==========================================
$paidAmount = $captureData['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? '0.00';
$expectedAmount = ($purchasedTier === 'pro') ? PRICE_PRO_MONTHLY : PRICE_VIP_MONTHLY;

if ((float)$paidAmount < (float)$expectedAmount && $paypalStatus === 'COMPLETED') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Gross amount mismatch. Transaction halted for security.']);
    exit;
}

// ==========================================
// 🛡️ 4. Automated Entitlement Engine
// ==========================================
try {
    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare("SELECT id, status FROM payments WHERE paypal_order_id = ?");
    $checkStmt->execute([$orderId]);
    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => true, 'message' => 'Transaction already processed and logged.']);
        exit;
    }

    $insStmt = $pdo->prepare("INSERT INTO payments (user_id, paypal_order_id, amount, currency, tier_purchased, status) VALUES (?, ?, ?, ?, ?, ?)");
    $insStmt->execute([$userId, $orderId, $paidAmount, 'USD', $purchasedTier, $paypalStatus]);

    $uStmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
    $uStmt->execute([$userId]);
    $currentUser = $uStmt->fetch();

    $currentTier = $currentUser['tier'];
    $currentExpiry = $currentUser['vip_expires_at'] ? strtotime($currentUser['vip_expires_at']) : time();
    $now = time();
    $purchasedSeconds = 30 * 24 * 60 * 60; 

    // 🚨 狀態分流機制
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
        echo json_encode([
            'success' => true, 
            'message' => 'Transaction COMPLETED. Premium cluster assets successfully provisioned.',
            'status' => 'COMPLETED',
            'new_tier' => $purchasedTier,
            'expires_at' => $newExpiryStr
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } elseif ($paypalStatus === 'PENDING') {
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Transaction PENDING. Funds are clearing via PayPal. Assets will provision automatically upon successful settlement.',
            'status' => 'PENDING',
            'new_tier' => 'free'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        $pdo->commit();
        echo json_encode([
            'success' => false,
            'error' => "Authorization returned status: {$paypalStatus}. No charges were made.",
            'status' => $paypalStatus
        ]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal cluster sync error during entitlement allocation.']);
}

// 預留給 Webhook 使用的非同步回收引擎 (Asynchronous Revocation Engine)
function handleAsynchronousRevocation($pdo, $paypalOrderId, $targetStatus) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE payments SET status = ? WHERE paypal_order_id = ?")->execute([$targetStatus, $paypalOrderId]);
        $uid = $pdo->prepare("SELECT user_id FROM payments WHERE paypal_order_id = ?");
        $uid->execute([$paypalOrderId]);
        $targetUserId = $uid->fetchColumn();
        if ($targetUserId) {
            $pdo->prepare("UPDATE users SET tier = 'free', vip_expires_at = NULL WHERE id = ?")->execute([$targetUserId]);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return false;
    }
}