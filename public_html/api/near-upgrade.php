<?php
/**
 * STRICT PoC claim endpoint for NEAR USDT/USDC upgrade payments.
 *
 * Flow:
 * 1. User paid via ft_transfer_call (msg = "upgrade:vip" or "upgrade:pro") to the Soul contract.
 * 2. Contract recorded a credit in upgrade_credits (see contract.ts ft_on_transfer).
 * 3. This API does a REAL on-chain view_call to has_upgrade_credit(account, tier).
 * 4. If the credit exists → apply the exact same prorated tier/expiry logic as /api/paypal.
 * 5. Record the payment and return success.
 *
 * This version has NO bypass/fallback. If the on-chain proof is missing the claim will fail cleanly.
 * Security follows the same patterns as the PayPal endpoint (session + CSRF).
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
loadTranslations('api');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Auth required for transaction')], JSON_UNESCAPED_UNICODE);
    exit;
}

// CSRF (same pattern as paypal.php)
$userCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($userCsrfToken) && function_exists('getallheaders')) {
    $headers = getallheaders();
    $userCsrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
}
$serverCsrfToken = $_SESSION['chat_csrf_token'] ?? '';
if (empty($serverCsrfToken) || empty($userCsrfToken) || !hash_equals($serverCsrfToken, $userCsrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => __('Security validation failed')], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$tier = strtolower(trim($input['tier'] ?? ''));
$token = strtolower(trim($input['token'] ?? 'usdt')); // for logging/amount display only

if (!in_array($tier, ['vip', 'pro'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Invalid tier')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Must have a bound NEAR wallet
$uStmt = $pdo->prepare("SELECT near_wallet_address, tier, vip_expires_at FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

$nearAccount = trim($user['near_wallet_address'] ?? '');
if (!$nearAccount) {
    echo json_encode([
        'success' => false,
        'error' => 'Please bind your NEAR wallet in My Settings → Web3 Wallet before using on-chain payments.',
        'needs_bind' => true
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$contractId = defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near';

// === STRICT on-chain verification ===
$args = ['account_id' => $nearAccount, 'tier' => $tier];
$argsBase64 = base64_encode(json_encode($args));

$rpcUrl = 'https://rpc.mainnet.near.org'; // TODO: use the project's rpcNodesPool for production
$rpcPayload = [
    'jsonrpc' => '2.0',
    'id' => 'near-upgrade-claim',
    'method' => 'query',
    'params' => [
        'request_type' => 'call_function',
        'finality' => 'optimistic',
        'account_id' => $contractId,
        'method_name' => 'has_upgrade_credit',
        'args_base64' => $argsBase64
    ]
];

$ch = curl_init($rpcUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($rpcPayload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 8,
]);
$rpcResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$hasCredit = false;
if ($httpCode === 200 && $rpcResponse) {
    $decoded = json_decode($rpcResponse, true);
    if (isset($decoded['result']['result'])) {
        $bytes = $decoded['result']['result'];
        $resultStr = '';
        foreach ($bytes as $b) $resultStr .= chr($b);
        $hasCredit = (strtolower(trim($resultStr)) === 'true');
    }
}

if (!$hasCredit) {
    http_response_code(402);
    echo json_encode([
        'success' => false,
        'error' => 'No valid on-chain upgrade credit found for your NEAR account. Please make sure the ft_transfer_call transaction succeeded and try again in a moment.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// === Apply entitlement (exact same logic as PayPal path) ===
$now = time();
$currentTier = $user['tier'] ?? 'free';
$currentExpiry = $user['vip_expires_at'] ? strtotime($user['vip_expires_at']) : 0;
$isActivePremium = ($currentTier !== 'free' && $currentExpiry > $now);

if ($isActivePremium && $currentTier === 'pro' && $tier === 'vip') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Downgrade Guard')], JSON_UNESCAPED_UNICODE);
    exit;
}

$purchasedSeconds = 30 * 24 * 60 * 60;

if ($isActivePremium && $currentTier === $tier) {
    $newExpiry = $currentExpiry + $purchasedSeconds;
} else {
    $newExpiry = max($currentExpiry, $now) + $purchasedSeconds;
}

try {
    $pdo->beginTransaction();

    $newExpiryStr = date('Y-m-d H:i:s', $newExpiry);
    $pdo->prepare("UPDATE users SET tier = ?, vip_expires_at = ? WHERE id = ?")
        ->execute([$tier, $newExpiryStr, $userId]);

    $amountStr = ($tier === 'vip') ? '5.00' : '15.00';
    $paymentRef = 'near-ft:' . $nearAccount . ':' . $tier . ':' . time();

    $ins = $pdo->prepare("INSERT INTO payments (user_id, paypal_order_id, amount, currency, tier_purchased, status) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->execute([$userId, $paymentRef, $amountStr, strtoupper($token), $tier, 'COMPLETED']);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => __('Transaction COMPLETED'),
        'status' => 'COMPLETED',
        'new_tier' => $tier,
        'expires_at' => $newExpiryStr
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Entitlement error')], JSON_UNESCAPED_UNICODE);
}
?>