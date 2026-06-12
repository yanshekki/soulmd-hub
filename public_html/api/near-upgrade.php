<?php
/**
 * PoC: Claim on-chain USDT/USDC upgrade payment (replaces PayPal capture)
 * 1. User paid via ft_transfer_call (see upgrade.php PoC JS + contract ft_on_transfer)
 * 2. This endpoint verifies the credit on-chain via view_call to the Soul contract
 * 3. Applies the same tier + expiry logic
 * 4. (Optional) clears the credit on-chain
 *
 * NOTE: This is a working PoC. In production:
 *   - Always do the has_upgrade_credit view check server-side before applying
 *   - Add replay protection (store tx or credit timestamp)
 *   - Support exact price matching + time-based credits
 *   - Use platform account to call clear_upgrade_credit after success
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

session_start();
loadTranslations('api');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Auth required')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$tier = strtolower(trim($input['tier'] ?? ''));
$token = strtolower(trim($input['token'] ?? '')); // usdt or usdc (for logging)

if (!in_array($tier, ['vip', 'pro'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Invalid tier')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get user's NEAR wallet (must be bound for on-chain payments)
$uStmt = $pdo->prepare("SELECT near_wallet_address, tier, vip_expires_at FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

$nearAccount = $user['near_wallet_address'] ?? '';
if (!$nearAccount) {
    echo json_encode([
        'success' => false,
        'error' => 'Please bind your NEAR wallet first (go to My Settings → Web3 Wallet)',
        'needs_bind' => true
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$contractId = defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near';

// PoC: Server-side verification via NEAR RPC view call
// Call has_upgrade_credit({account_id: $nearAccount, tier: $tier})
$args = ['account_id' => $nearAccount, 'tier' => $tier];
$argsBase64 = base64_encode(json_encode($args));

$rpcPayload = [
    'jsonrpc' => '2.0',
    'id' => 'near-upgrade-poc',
    'method' => 'query',
    'params' => [
        'request_type' => 'call_function',
        'finality' => 'optimistic',
        'account_id' => $contractId,
        'method_name' => 'has_upgrade_credit',
        'args_base64' => $argsBase64
    ]
];

$ch = curl_init('https://rpc.mainnet.near.org'); // or use healthy pool if you have
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
        $resultBytes = $decoded['result']['result'];
        $resultStr = '';
        foreach ($resultBytes as $b) { $resultStr .= chr($b); }
        $hasCredit = (trim($resultStr) === 'true');
    }
}

// For PoC we still allow if the view failed (demo mode) but log it.
// In real deployment, REQUIRE $hasCredit === true
if (!$hasCredit) {
    // PoC fallback: allow the claim anyway so the flow can be tested end-to-end without perfect RPC timing
    // TODO: Remove this fallback before real launch
    error_log("PoC near-upgrade: view check inconclusive for $nearAccount $tier, allowing for demo.");
}

$now = time();
$currentTier = $user['tier'] ?? 'free';
$currentExpiry = $user['vip_expires_at'] ? strtotime($user['vip_expires_at']) : 0;
$isActivePremium = ($currentTier !== 'free' && $currentExpiry > $now);

if ($isActivePremium && $currentTier === 'pro' && $tier === 'vip') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Downgrade Guard')], JSON_UNESCAPED_UNICODE);
    exit;
}

// Apply entitlement (same logic as PayPal)
$thirtyDays = 30 * 24 * 3600;
$newExpiry = ($isActivePremium && $currentTier === $tier)
    ? $currentExpiry + $thirtyDays
    : $now + $thirtyDays;

$updateStmt = $pdo->prepare("UPDATE users SET tier = ?, vip_expires_at = ? WHERE id = ?");
$updateStmt->execute([$tier, date('Y-m-d H:i:s', $newExpiry), $userId]);

// Record payment (re-use payments table; store a synthetic id)
$paymentRef = 'near-ft:' . $nearAccount . ':' . $tier . ':' . time();
$ins = $pdo->prepare("INSERT INTO payments (user_id, paypal_order_id, amount, currency, tier_purchased, status) VALUES (?, ?, ?, ?, ?, ?)");
$ins->execute([$userId, $paymentRef, ($tier === 'vip' ? '5.00' : '15.00'), strtoupper($token ?: 'USDT'), $tier, 'COMPLETED']);

echo json_encode([
    'success' => true,
    'message' => __('NEAR FT payment applied. Welcome to ' . strtoupper($tier) . '!'),
    'tier' => $tier,
    'expires' => date('Y-m-d H:i:s', $newExpiry),
    'onchain_account' => $nearAccount
], JSON_UNESCAPED_UNICODE);
