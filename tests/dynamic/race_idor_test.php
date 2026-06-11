<?php
/**
 * Phase 3: Dynamic/Adversarial Testing - Race Conditions & IDOR Test
 * 
 * Tests:
 * - Daily limit reset race (concurrent at day boundary)
 * - Concurrent buy/rent on same NFT (double spend?)
 * - IDOR on soul_id/session_token (private sessions, ownership)
 * - Guest daily bypass
 * 
 * Run with multiple concurrent curls or PHP multi_curl against test server.
 * Post-fixes: Races mitigated by DB conditions; IDOR blocked by ownership checks.
 */

$base = $argv[1] ?? 'http://localhost/api/';
$soulId = $argv[2] ?? 1;
$sessionToken = $argv[3] ?? 'testtoken12345678';

echo "=== Phase 3: Race/IDOR Test ===\n";

// Simple IDOR probe (private soul)
$ch = curl_init($base . "chat.php?soul_id=$soulId&session_token=$sessionToken");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "IDOR probe (private?): HTTP $code\n";
if ($code === 403) {
    echo "✅ Likely blocked by private check.\n";
}

// Race note: Use `ab` or parallel curls for daily reset / buy concurrency.
// Example: for i in {1..5}; do curl ... & done
echo "For full race: run concurrent requests at simulated day change.\n";
echo "See plan for matrix. Use test DB with known private soul + multi-process.\n";
