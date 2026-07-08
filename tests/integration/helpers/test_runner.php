<?php
/**
 * Minimal assertion helpers for SoulMD Hub integration tests (no PHPUnit required).
 */

function hub_test_pass(string $name): void
{
    echo "  PASS: {$name}\n";
}

function hub_test_fail(string $name, string $reason): void
{
    fwrite(STDERR, "  FAIL: {$name} — {$reason}\n");
    exit(1);
}

function hub_test_assert_true(bool $condition, string $name, string $reason = 'expected true'): void
{
    if ($condition) {
        hub_test_pass($name);
        return;
    }
    hub_test_fail($name, $reason);
}

function hub_test_assert_false(bool $condition, string $name, string $reason = 'expected false'): void
{
    hub_test_assert_true(!$condition, $name, $reason);
}

function hub_test_assert_eq($expected, $actual, string $name): void
{
    if ($expected === $actual) {
        hub_test_pass($name);
        return;
    }
    hub_test_fail(
        $name,
        'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
    );
}

function hub_test_section(string $title): void
{
    echo "\n== {$title} ==\n";
}