<?php
/**
 * SoulMD Hub API
 * POST /api/paypal - Capture PayPal Order & Prorated Tier Upgrade
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
    echo json_encode(['success' => false, 'error' => 'Please log in to upgrade.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['orderID'] ?? '';
$purchasedTier = $input['tier'] ?? ''; // 'vip' or 'pro'

if (empty($orderId) || !in_array($purchasedTier, ['vip', 'pro'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid order details.']);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// ==========================================
// 🛡️ 1. 向 PayPal 獲取 Access Token
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
    echo json_encode(['success' => false, 'error' => 'Failed to authenticate with PayPal.']);
    exit;
}

$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'] ?? '';

// ==========================================
// 🛡️ 2. 扣款並驗證訂單 (Capture Order)
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

if ($captureHttpCode !== 201 || !isset($captureData['status']) || $captureData['status'] !== 'COMPLETED') {
    http_response_code(400);
    $errMsg = $captureData['details'][0]['description'] ?? 'Payment capture failed or was not completed.';
    echo json_encode(['success' => false, 'error' => $errMsg]);
    exit;
}

// ==========================================
// 🛡️ 3. 安全驗證金額
// ==========================================
$paidAmount = $captureData['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? '0.00';
$expectedAmount = ($purchasedTier === 'pro') ? PRICE_PRO_MONTHLY : PRICE_VIP_MONTHLY;

if ((float)$paidAmount < (float)$expectedAmount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payment amount mismatch. Possible tampering detected.']);
    exit;
}

// ==========================================
// 🛡️ 4. 公平折算升級邏輯 (Prorated Upgrade) 與更新 DB
// ==========================================
try {
    $pdo->beginTransaction();

    // 檢查訂單是否已存在
    $checkStmt = $pdo->prepare("SELECT id FROM payments WHERE paypal_order_id = ?");
    $checkStmt->execute([$orderId]);
    if ($checkStmt->fetch()) {
        $pdo->rollBack();
        echo json_encode(['success' => true, 'message' => 'Order already processed.']);
        exit;
    }

    $insStmt = $pdo->prepare("INSERT INTO payments (user_id, paypal_order_id, amount, currency, tier_purchased, status) VALUES (?, ?, ?, ?, ?, ?)");
    $insStmt->execute([$userId, $orderId, $paidAmount, 'USD', $purchasedTier, 'COMPLETED']);

    $uStmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
    $uStmt->execute([$userId]);
    $currentUser = $uStmt->fetch();

    $currentTier = $currentUser['tier'];
    $currentExpiry = $currentUser['vip_expires_at'] ? strtotime($currentUser['vip_expires_at']) : time();
    $now = time();
    
    $purchasedSeconds = 30 * 24 * 60 * 60; // 買 30 日
    $newExpiry = 0;

    // 如果是用戶由 VIP 升級 PRO，計算剩餘價值的折算日數
    if ($currentTier === 'vip' && $purchasedTier === 'pro' && $currentExpiry > $now) {
        $remainingVipSeconds = $currentExpiry - $now;
        
        $vipDailyCost = PRICE_VIP_MONTHLY / 30;
        $proDailyCost = PRICE_PRO_MONTHLY / 30;
        $conversionRatio = $vipDailyCost / $proDailyCost; // 折算比例
        
        $convertedProSeconds = $remainingVipSeconds * $conversionRatio;
        
        $newExpiry = $now + $purchasedSeconds + $convertedProSeconds;
    } else {
        // 同級續費，或者 Free 升級 (直接累加 30 日)
        $newExpiry = max($currentExpiry, $now) + $purchasedSeconds;
    }

    $newExpiryStr = date('Y-m-d H:i:s', $newExpiry);

    $updStmt = $pdo->prepare("UPDATE users SET tier = ?, vip_expires_at = ? WHERE id = ?");
    $updStmt->execute([$purchasedTier, $newExpiryStr, $userId]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Payment successful! Your account has been upgraded.',
        'new_tier' => $purchasedTier,
        'expires_at' => $newExpiryStr
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error while upgrading account. Please contact support with Order ID: ' . $orderId]);
}