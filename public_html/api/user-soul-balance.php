<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';
require_once __DIR__ . '/../../private/src/SoulCorpHub.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $security = ApiSecurity::initialize(true);
    $userId = (int)$security['user_id'];
    $tier = SoulCorpHub::getUserTier($security['pdo'], $userId);
    $walletStmt = $security['pdo']->prepare('SELECT near_wallet_address FROM users WHERE id = ? LIMIT 1');
    $walletStmt->execute([$userId]);
    $walletRow = $walletStmt->fetch();

    echo json_encode([
        'success' => true,
        'soul_balance' => (float)($tier['soul_balance'] ?? 0),
        'soul_staked' => (float)($tier['soul_staked'] ?? 0),
        'tier' => $tier['tier'] ?? 'free',
        'near_wallet_address' => $walletRow['near_wallet_address'] ?? null,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}