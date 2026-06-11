<?php
/**
 * Phase 3: Dynamic/Adversarial Testing - Token-Gating Bypass Test
 * 
 * Tests NFT access control bypass vectors:
 * 1. Tampered content (hash mismatch) -> Security Interception
 * 2. Expired renter -> Access Denied Web3
 * 3. RPC timeout fallback abuse (only cached owner should pass)
 * 4. Ownership transfer desync (chain vs DB)
 * 
 * Run: php tests/dynamic/gating_bypass_test.php [base_chat_url] [test_soul_id] [test_wallet]
 * Requires: Test NFT soul with known renter/owner in test DB/chain.
 * For real: Use NEAR testnet + local server with test data.
 * 
 * Post-fixes (shared gate, nonce, etc.): All should fail as expected.
 */

$baseUrl = $argv[1] ?? 'http://localhost/api/chat.php';
$soulId = $argv[2] ?? 999; // test NFT soul
$testWallet = $argv[3] ?? 'attacker.near';

echo "=== Phase 3: Gating Bypass Test ===\n";
echo "Target: $baseUrl soul=$soulId wallet=$testWallet\n";

// Simulate tampered payload (would trigger hash check in gate)
$tamperedPayload = [
    'soul_id' => $soulId,
    'session_token' => 'testtoken12345678',
    'content' => 'tampered prompt that changes hash',
    'is_private' => false
];

$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($tamperedPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-CSRF-Token: test-csrf' // assume valid in test env
]);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Tamper test HTTP $code: " . substr($res, 0, 100) . "...\n";
if (strpos($res, 'Security Interception') !== false || $code === 403) {
    echo "✅ PASS: Tamper blocked.\n";
} else {
    echo "❌ Potential bypass.\n";
}

// Note: For expired renter / timeout tests, use real test data + modify soul DB temporarily.
// Run against testnet for full forgery/expiry: use near-api-js in JS harness to craft bad sigs/renters.
// See plan for full PoC matrix.
echo "Full expiry/RPC tests require live test env + chain data. See plan doc.\n";
