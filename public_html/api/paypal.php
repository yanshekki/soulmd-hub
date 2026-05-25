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
// 🛡️ 2. 執行 PayPal 訂單捕獲扣款 (Capture Order)
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

// 🚨 釋放寫死限制：不再卡死 COMPLETED，允許接收 PENDING 狀態
$paypalStatus = $captureData['status'] ?? '';
if ($captureHttpCode !== 200 && $captureHttpCode !== 201) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'PayPal Gateway communication failure.']);
    exit;
}

// ==========================================
// 🛡️ 3. 安全驗證金額 (防止竄改 Frontend Payload)
// ==========================================
$paidAmount = $captureData['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? '0.00';
$expectedAmount = ($purchasedTier === 'pro') ? PRICE_PRO_MONTHLY : PRICE_VIP_MONTHLY;

if ((float)$paidAmount < (float)$expectedAmount && $paypalStatus === 'COMPLETED') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payment amount mismatch. Possible tampering detected.']);
    exit;
}

// ==========================================
// 🛡️ 4. 全自動狀態機與權限聯動引擎
// ==========================================
try {
    $pdo->beginTransaction();

    // 檢查訂單防重入
    $checkStmt = $pdo->prepare("SELECT id, status FROM payments WHERE paypal_order_id = ?");
    $checkStmt->execute([$orderId]);
    $existingOrder = $checkStmt->fetch();

    if ($existingOrder) {
        $pdo->rollBack();
        echo json_encode(['success' => true, 'message' => 'Order already logged inside cluster system.']);
        exit;
    }

    // 將 PayPal 原生回傳狀態原封不動寫入資料庫
    $insStmt = $pdo->prepare("INSERT INTO payments (user_id, paypal_order_id, amount, currency, tier_purchased, status) VALUES (?, ?, ?, ?, ?, ?)");
    $insStmt->execute([$userId, $orderId, $paidAmount, 'USD', $purchasedTier, $paypalStatus]);

    $uStmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
    $uStmt->execute([$userId]);
    $currentUser = $uStmt->fetch();

    $currentTier = $currentUser['tier'];
    $currentExpiry = $currentUser['vip_expires_at'] ? strtotime($currentUser['vip_expires_at']) : time();
    $now = time();
    
    $purchasedSeconds = 30 * 24 * 60 * 60; // 標準 30 天 Pass

    // 🚨 核心權益動作分流
    if ($paypalStatus === 'COMPLETED') {
        // 動作 A：只有付款成功才發放/延長會員階級
        if ($currentTier === 'vip' && $purchasedTier === 'pro' && $currentExpiry > $now) {
            $remainingVipSeconds = $currentExpiry - $now;
            $vipDailyCost = PRICE_VIP_MONTHLY / 30;
            $proDailyCost = PRICE_PRO_MONTHLY / 30;
            $conversionRatio = $vipDailyCost / $proDailyCost;
            $convertedProSeconds = $remainingVipSeconds * $conversionRatio;
            
            $newExpiry = $now + $purchasedSeconds + $convertedProSeconds;
        } else {
            $newExpiry = max($currentExpiry, $now) + $purchasedSeconds;
        }

        $newExpiryStr = date('Y-m-d H:i:s', $newExpiry);
        $updStmt = $pdo->prepare("UPDATE users SET tier = ?, vip_expires_at = ? WHERE id = ?");
        $updStmt->execute([$purchasedTier, $newExpiryStr, $userId]);

        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Payment successful! Your account architecture has been upgraded.',
            'status' => 'COMPLETED',
            'new_tier' => $purchasedTier,
            'expires_at' => $newExpiryStr
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } elseif ($paypalStatus === 'PENDING') {
        // 動作 B：處理 PayPal 審查掛起，寫入紀錄但不升級用戶
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment captured but is currently PENDING review by PayPal. Your premium features will unlock automatically once cleared.',
            'status' => 'PENDING',
            'new_tier' => 'free'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        // 動作 C：交易失敗或遭拒絕
        $pdo->commit();
        echo json_encode([
            'success' => false,
            'error' => "Transaction status: {$paypalStatus}. Premium activation halted.",
            'status' => $paypalStatus
        ]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database exception during tier handshake signature sync.']);
}

// =========================================================================
// 🚨 企業級非同步回收模組 (用於處理日後 PayPal Webhook 傳來的 REFUNDED / REVERSED)
// =========================================================================
function handleAsynchronousRevocation($pdo, $paypalOrderId, $targetStatus) {
    try {
        $pdo->beginTransaction();
        
        // 1. 先把該訂單改為退款或爭議拒付狀態
        $updPay = $pdo->prepare("UPDATE payments SET status = ? WHERE paypal_order_id = ?");
        $updPay->execute([$targetStatus, $paypalOrderId]);
        
        // 2. 撈出受害者用戶 ID
        $getUserId = $pdo->prepare("SELECT user_id FROM payments WHERE paypal_order_id = ?");
        $getUserId->execute([$paypalOrderId]);
        $uid = $getUserId->fetchColumn();
        
        if ($uid) {
            // 3. 🚨 實時對會員採取懲罰/回滾動作：立刻打回免費用戶原型，到期日清空！
            $revocation = $pdo->prepare("UPDATE users SET tier = 'free', vip_expires_at = NULL WHERE id = ?");
            $revocation->execute([$uid]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return false;
    }
}