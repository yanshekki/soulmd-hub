<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/src/SoulCorpHub.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../../private/src/AppBootstrap.php';
$app = AppBootstrap::forApi([
    'require_user' => true,
    'enforce_csrf' => true,
    'translations' => 'api',
    'json_header' => false,
]);
$userId = $app['user_id'];
$pdo = $app['pdo'];
$isApiKey = $app['is_api_key'];
    $userId = (int)$security['user_id'];
    $pdo = $security['pdo'];

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $tier = strtolower(trim((string)($input['tier'] ?? '')));
    $amount = (float)($input['amount'] ?? 0);

    if (!in_array($tier, ['pro', 'vip'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid tier. Use pro or vip.']);
        exit;
    }

    $required = $tier === 'pro' ? 100.0 : 500.0;
    if ($amount <= 0) {
        $amount = $required;
    }
    if (abs($amount - $required) > 0.01) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Stake amount must be {$required} SOUL for {$tier}."]);
        exit;
    }

    $current = SoulCorpHub::getUserTier($pdo, $userId);
    $balance = (float)($current['soul_balance'] ?? 0);
    $currentTier = strtolower((string)($current['tier'] ?? 'free'));

    $rank = ['free' => 1, 'pro' => 2, 'vip' => 3];
    if (($rank[$currentTier] ?? 1) >= ($rank[$tier] ?? 1)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'You are already on this tier or higher.']);
        exit;
    }

    if ($balance < $required) {
        http_response_code(402);
        echo json_encode([
            'success' => false,
            'error' => "Insufficient SOUL balance. Need {$required}, have {$balance}.",
            'soul_balance' => $balance,
        ]);
        exit;
    }

    $newBalance = $balance - $required;
    $newStaked = (float)($current['soul_staked'] ?? 0) + $required;

    $stmt = $pdo->prepare(
        'UPDATE user_tiers SET tier = ?, soul_balance = ?, soul_staked = ?, updated_at = NOW() WHERE user_id = ?'
    );
    $stmt->execute([$tier, $newBalance, $newStaked, $userId]);

    echo json_encode([
        'success' => true,
        'tier' => $tier,
        'soul_balance' => $newBalance,
        'soul_staked' => $newStaked,
        'message' => "Upgraded to {$tier} by staking {$required} SOUL.",
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}