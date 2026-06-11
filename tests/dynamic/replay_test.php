<?php
/**
 * Phase 3: Dynamic/Adversarial Testing - Signature Replay Test
 * 
 * Tests NEP-0413 auth replay within timestamp window.
 * Post-fix (nonce table): Should be rejected with "Replay attack detected".
 * 
 * Usage: php tests/dynamic/replay_test.php [base_url] [sample_payload_json]
 * Requires: valid recent auth payload (from browser console or generate).
 * For testnet: run against local LAMP with test DB, or modify to use testnet wallets.
 * 
 * Expected: After nonce protection, replay returns 401 with replay error.
 * Pre-fix: Would succeed if timestamp valid.
 */

$baseUrl = $argv[1] ?? 'http://localhost/api/wallet-login.php';
$payloadFile = $argv[2] ?? null;

echo "=== Phase 3: Replay Test ===\n";
echo "Target: $baseUrl\n";

if ($payloadFile && file_exists($payloadFile)) {
    $payload = json_decode(file_get_contents($payloadFile), true);
} else {
    // Sample (replace with real from generateNearAuthPayload or browser)
    $payload = [
        'account_id' => 'test.near',
        'message' => 'soulmd_auth:' . (time() * 1000),
        'public_key' => 'ed25519:example',
        'signature' => base64_encode(random_bytes(64)),
        'nonce' => array_fill(0, 32, 0),
        'recipient' => 'localhost',
        'is_nep0413' => true
    ];
    echo "Using sample payload (update with real for test).\n";
}

$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP: $httpCode\n";
echo "Response: $response\n";

$data = json_decode($response, true);
if ($httpCode === 401 && isset($data['error']) && strpos($data['error'], 'Replay') !== false) {
    echo "✅ PASS: Replay blocked (post-fix).\n";
} else {
    echo "❌ FAIL or pre-fix behavior: $response\n";
    echo "Note: Run with real recent payload from frontend for accurate test.\n";
}
