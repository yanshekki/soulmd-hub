<?php
/**
 * Integration tests for ApiSecurity CSRF helpers.
 *
 * Run: php tests/integration/api_security_test.php
 */

require_once __DIR__ . '/helpers/test_runner.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
session_id('hub-security-test-' . bin2hex(random_bytes(8)));
session_start();
$_SESSION = [];

hub_test_section('CSRF token lifecycle');

$tokenA = ApiSecurity::ensureCsrfToken();
$tokenB = ApiSecurity::ensureCsrfToken();

hub_test_assert_true(is_string($tokenA) && strlen($tokenA) === 64, 'csrf token is 64 hex chars');
hub_test_assert_eq($tokenA, $tokenB, 'csrf token is stable within session');

hub_test_section('CSRF token matching');

hub_test_assert_true(
    ApiSecurity::csrfTokensMatch($tokenA, $tokenA),
    'matching csrf tokens accepted'
);

hub_test_assert_false(
    ApiSecurity::csrfTokensMatch($tokenA, 'invalid-token'),
    'mismatched csrf tokens rejected'
);

hub_test_assert_false(
    ApiSecurity::csrfTokensMatch($tokenA, ''),
    'empty user csrf token rejected'
);

hub_test_assert_false(
    ApiSecurity::csrfTokensMatch('', $tokenA),
    'empty server csrf token rejected'
);

hub_test_section('Rate limit constant');

hub_test_assert_eq(10, ApiSecurity::RATE_LIMIT_PER_MINUTE, 'api key rate limit is 10/min');

echo "\nAll ApiSecurity integration tests passed.\n";