<?php
/**
 * AppBootstrap contract smoke tests (no live DB required for session helpers).
 * Run: php tests/integration/bootstrap_contract_test.php
 */

$root = dirname(__DIR__, 2);
require_once $root . '/private/src/AppBootstrap.php';

$failed = 0;
function assert_true(bool $cond, string $msg): void
{
    global $failed;
    if ($cond) {
        echo "OK  {$msg}\n";
    } else {
        echo "FAIL {$msg}\n";
        $failed++;
    }
}

// sessionStart when idle should not throw
$before = session_status();
$ok = AppBootstrap::sessionStart();
assert_true($ok || session_status() === PHP_SESSION_ACTIVE || headers_sent(), 'sessionStart callable');

// second call is no-op success when active
if (session_status() === PHP_SESSION_ACTIVE) {
    assert_true(AppBootstrap::sessionStart() === true, 'sessionStart idempotent when active');
}

// sessionClose
AppBootstrap::sessionClose();
assert_true(session_status() !== PHP_SESSION_ACTIVE, 'sessionClose releases active session');

// sessionWritable false after close
assert_true(AppBootstrap::sessionWritable() === false, 'sessionWritable false after close');

// loadConfig — may use config.example in CI without secrets
try {
    AppBootstrap::loadConfig(false);
    assert_true(defined('DB_HOST'), 'loadConfig defines DB_HOST');
    assert_true(function_exists('loadTranslations'), 'loadConfig provides loadTranslations');
} catch (Throwable $e) {
    assert_true(false, 'loadConfig threw: ' . $e->getMessage());
}

// forPage never sets JSON content-type (check via headers_list if possible)
// Soft check: method exists and returns expected keys when config present
if (defined('DB_HOST')) {
    $page = AppBootstrap::forPage([
        'translations' => '404',
        'csrf' => false,
        'db' => false,
        'seo' => false,
    ]);
    assert_true(array_key_exists('pdo', $page) && array_key_exists('user_id', $page), 'forPage returns shape');
    assert_true($page['pdo'] === null, 'forPage db=false leaves pdo null');
}

echo $failed === 0 ? "\nAll bootstrap contract checks passed.\n" : "\n{$failed} check(s) failed.\n";
exit($failed === 0 ? 0 : 1);
