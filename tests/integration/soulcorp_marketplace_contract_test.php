<?php
/**
 * SoulCorp hub contract checks (no database required).
 *
 * Run: php tests/integration/soulcorp_marketplace_contract_test.php
 */

require_once __DIR__ . '/helpers/test_runner.php';
require_once __DIR__ . '/../../private/src/SoulCorpHub.php';

$root = realpath(__DIR__ . '/../..');

hub_test_section('Hub API surface');

$requiredApis = [
    'public_html/api/market-gigs.php',
    'public_html/api/market-gig-assign.php',
    'public_html/api/market-gig-start.php',
    'public_html/api/market-gig-submit-qc.php',
    'public_html/api/market-gig-reject-qc.php',
    'public_html/api/market-gig-dispute.php',
    'public_html/api/market-gig-complete.php',
    'public_html/api/market-gig-cancel.php',
    'public_html/api/sync-pull.php',
    'public_html/api/sync-push.php',
    'public_html/api/user-soul-balance.php',
    'public_html/api/user-stake-soul.php',
    'public_html/api/near-upgrade.php',
];

foreach ($requiredApis as $relativePath) {
    hub_test_assert_true(
        is_file($root . '/' . $relativePath),
        "API file exists: {$relativePath}"
    );
}

hub_test_section('Executive lounge mapping');

hub_test_assert_false(
    SoulCorpHub::executiveLoungeForBudget(1200.0),
    'standard gig is not executive lounge'
);
hub_test_assert_true(
    SoulCorpHub::executiveLoungeForBudget(6000.0),
    'high-budget gig is executive lounge'
);

echo "\nSoulCorp marketplace contract checks passed.\n";