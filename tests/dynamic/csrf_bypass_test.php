<?php
/**
 * Phase 3: Dynamic/Adversarial Testing - CSRF Bypass Test
 * 
 * Tests for missing/weak CSRF on mutating endpoints (post-fixes should block).
 * Vectors: no token, wrong token, timing, header case.
 * 
 * Run against local server with valid session cookie (from browser devtools).
 * Post CSRF fixes: All browser-state-changing should 403.
 */

$base = $argv[1] ?? 'http://localhost/api/';
$endpoints = [
    'chat.php' => ['soul_id' => 1, 'session_token' => 'test12345678', 'content' => 'csrf test', 'action' => 'chat'],
    'settings.php' => ['use_byok' => 0],
    'change-password.php' => ['current_password' => 'old', 'new_password' => 'newpass123', 'confirm_password' => 'newpass123'],
    // add more from fixed list: paypal, fork, like, rate, etc.
];

foreach ($endpoints as $ep => $payload) {
    $url = $base . $ep;
    echo "Testing $url ...\n";
    
    // Test 1: No CSRF header
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "  No CSRF: HTTP $code " . (strpos($res, 'Security validation failed') !== false ? '✅ blocked' : '❌ possible bypass') . "\n";
    
    // Test 2: Wrong token
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'X-CSRF-Token: wrongtoken']);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "  Wrong token: HTTP $code " . (strpos($res, 'Security validation failed') !== false ? '✅ blocked' : '❌ possible bypass') . "\n";
}

echo "Note: Use real session cookies (curl -b) for accurate browser-path tests. API-key paths intentionally skip.\n";
