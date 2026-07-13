<?php
/**
 * On-chain claim endpoint for NEAR USDT/USDC upgrade payments.
 *
 * Flow:
 * 1. User paid via ft_transfer_call (msg = "upgrade:vip" or "upgrade:pro") to the Soul contract.
 * 2. Contract recorded a credit in upgrade_credits (see contract.ts ft_on_transfer).
 * 3. This API does a REAL on-chain view_call to has_upgrade_credit(account, tier).
 * 4. If the credit exists → apply the exact same prorated tier/expiry logic as /api/paypal.
 * 5. Record the payment and return success.
 *
 * The claim requires on-chain proof via view call. If the credit is missing the claim will fail cleanly.
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
require_once __DIR__ . '/../../private/src/ApiSecurity.php';
require_once __DIR__ . '/../../private/src/SoulCorpHub.php';
require_once __DIR__ . '/../../private/src/PremiumEntitlement.php';

loadTranslations('api');

$security = ApiSecurity::initialize(true);  // session + CSRF enforced (no api_key expected for this claim usually)
$userId = $security['user_id'];
$pdo = $security['pdo'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$tier = strtolower(trim((string)($input['tier'] ?? '')));
$token = strtolower(trim((string)($input['token'] ?? 'usdt'))); // for logging/amount display only
$payTx = $input['tx'] ?? null; // optional tx outcome from frontend for audit / future verification

if (!in_array($tier, ['vip', 'pro'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Invalid tier')], JSON_UNESCAPED_UNICODE);
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
    exit;
}

// Must have a bound NEAR wallet
$uStmt = $pdo->prepare("SELECT near_wallet_address, tier, vip_expires_at FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$user = $uStmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
    exit;
}

$nearAccount = trim((string)($user['near_wallet_address'] ?? ''));
if ($nearAccount === '') {
    echo json_encode([
        'success' => false,
        'error' => 'Please bind your NEAR wallet in My Settings → Web3 Wallet before using on-chain payments.',
        'needs_bind' => true
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$contractId = defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near';

// === STRICT on-chain verification using NEAR_RPC_NODES, pick the fastest RPC ===
// All RPC in this NEAR payment upgrade now use the project's NEAR_RPC_NODES config.
// We ping/measure the actual has_upgrade_credit query on each and pick the fastest successful one.
$rpcNodes = defined('NEAR_RPC_NODES') ? NEAR_RPC_NODES : ["https://rpc.mainnet.near.org", "https://free.rpc.fastnear.com"];
$args = ['account_id' => $nearAccount, 'tier' => $tier];
$argsBase64 = base64_encode(json_encode($args));
$creditTsStr = "0";
$bestTime = PHP_FLOAT_MAX;

foreach ($rpcNodes as $rpcUrl) {
    $start = microtime(true);
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
        CURLOPT_TIMEOUT => 5,
    ]);
    $rpcResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $elapsed = microtime(true) - $start;
    curl_close($ch);

    if ($httpCode === 200 && $rpcResponse) {
        $decoded = json_decode($rpcResponse, true);
        if (isset($decoded['result']['result'])) {
            $bytes = $decoded['result']['result'];
            $resultStr = '';
            foreach ($bytes as $b) $resultStr .= chr($b);
            $trimmed = trim($resultStr);
            if ($trimmed !== "0" && $elapsed < $bestTime) {
                $bestTime = $elapsed;
                $creditTsStr = $trimmed;
            }
        }
    }
}

if ($creditTsStr === "0") {
    http_response_code(402);
    echo json_encode([
        'success' => false,
        'error' => 'No valid on-chain upgrade credit found for your NEAR account. Please make sure the ft_transfer_call transaction succeeded and try again in a moment.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Prevent double-claim using exact credit timestamp binding (ties claim to this specific payment instance)
$exactRef = 'near-ft:' . $nearAccount . ':' . $tier . ':' . $creditTsStr;
$checkStmt = $pdo->prepare("SELECT id FROM payments WHERE paypal_order_id = ? LIMIT 1");
$checkStmt->execute([$exactRef]);
if ($checkStmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'This on-chain upgrade payment has already been claimed.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Simple rate limit (session-based). Do NOT stamp time until after we know claim will proceed past validation.
if (isset($_SESSION['last_near_claim_time']) && (time() - $_SESSION['last_near_claim_time'] < 5)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many claims, please wait a moment.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// For manual claims (no payTx proof of a just-completed ft_transfer_call), require the credit to be very recent.
// This prevents abuse where users bind a wallet that had an old/test/admin-set credit and claim free time
// without ever performing a real on-chain payment.
$isManualClaim = empty($payTx);
if ($isManualClaim) {
    $creditNs = (int) $creditTsStr;
    $nowNs = time() * 1_000_000_000; // current wall time in ns (good enough approximation)
    $maxAgeNs = 3600 * 1_000_000_000; // 1 hour
    if (($nowNs - $creditNs) > $maxAgeNs) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'This on-chain credit is too old for manual claim. Please use the automatic claim right after a successful payment, or contact support if your wallet had confirmation issues after sending USDT/USDC.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// === Apply entitlement via shared PremiumEntitlement (same as PayPal) ===
$amountStr = ($tier === 'vip')
    ? number_format((float)NEAR_UPGRADE_VIP_USD_AMOUNT, 2, '.', '')
    : number_format((float)NEAR_UPGRADE_PRO_USD_AMOUNT, 2, '.', '');
$currency = in_array($token, ['usdt', 'usdc'], true) ? strtoupper($token) : 'USDT';

try {
    $result = PremiumEntitlement::applyPurchase(
        $pdo,
        $userId,
        $tier,
        $exactRef,
        $amountStr,
        $currency,
        false // duplicate claim → error (NEAR already-claimed semantics)
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
        echo json_encode(['success' => false, 'error' => 'This on-chain upgrade payment has already been claimed.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (empty($result['ok'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Entitlement error')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Only throttle after a successful grant (failed claims should be retryable immediately)
    $_SESSION['last_near_claim_time'] = time();

    echo json_encode([
        'success' => true,
        'message' => __('Transaction COMPLETED'),
        'status' => 'COMPLETED',
        'new_tier' => $result['new_tier'] ?? $tier,
        'expires_at' => $result['expires_at'] ?? null,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (PremiumEntitlement::isDuplicateKeyException($e)) {
        echo json_encode(['success' => false, 'error' => 'This on-chain upgrade payment has already been claimed.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => __('Entitlement error')], JSON_UNESCAPED_UNICODE);
}
?>