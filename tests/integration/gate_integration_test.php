<?php
/**
 * Integration tests for shared token-gate helpers (enforceSoulAccess building blocks).
 *
 * Run: php tests/integration/gate_integration_test.php
 */

require_once __DIR__ . '/helpers/test_runner.php';
require_once __DIR__ . '/../../private/includes/token-gate.php';

hub_test_section('Non-NFT soul access');

hub_test_assert_true(
    evaluateNonNftSoulAccess(['is_nft' => 0, 'is_public' => 1, 'user_id' => 9], null),
    'public soul allows guests'
);

hub_test_assert_true(
    evaluateNonNftSoulAccess(['is_nft' => 0, 'is_public' => 0, 'user_id' => 42], 42),
    'private soul allows owner'
);

hub_test_assert_false(
    evaluateNonNftSoulAccess(['is_nft' => 0, 'is_public' => 0, 'user_id' => 42], 7),
    'private soul denies other users'
);

hub_test_assert_false(
    evaluateNonNftSoulAccess(['is_nft' => 0, 'is_public' => 0, 'user_id' => 42], null),
    'private soul denies guests'
);

hub_test_assert_true(
    evaluateNonNftSoulAccess(['is_nft' => 1, 'is_public' => 0, 'user_id' => 1], null),
    'nft souls defer to chain gate'
);

hub_test_section('NFT hash integrity');

$soul = [
    'content' => 'persona body',
    'nft_salt' => 'salt-123',
];
$hash = 'sha256:' . hash('sha256', $soul['content'] . $soul['nft_salt']);

hub_test_assert_true(
    nftContentHashMatches($soul, ['metadata' => ['extra' => $hash]]),
    'matching metadata hash accepted'
);

hub_test_assert_false(
    nftContentHashMatches($soul, ['metadata' => ['extra' => 'sha256:tampered']]),
    'tampered metadata hash rejected'
);

hub_test_assert_false(
    nftContentHashMatches(['content' => 'x', 'nft_salt' => ''], ['metadata' => ['extra' => $hash]]),
    'missing salt rejected'
);

hub_test_section('NFT wallet access');

$futureNano = (time() + 3600) * 1000000000;
$pastNano = (time() - 3600) * 1000000000;

hub_test_assert_true(
    evaluateNftWalletAccess(['owner_id' => 'owner.near', 'renters' => []], 'owner.near'),
    'owner wallet allowed'
);

hub_test_assert_true(
    evaluateNftWalletAccess(
        ['owner_id' => 'owner.near', 'renters' => ['renter.near' => $futureNano]],
        'renter.near'
    ),
    'active renter allowed'
);

hub_test_assert_false(
    evaluateNftWalletAccess(
        ['owner_id' => 'owner.near', 'renters' => ['renter.near' => $pastNano]],
        'renter.near'
    ),
    'expired renter denied'
);

hub_test_assert_false(
    evaluateNftWalletAccess(['owner_id' => 'owner.near', 'renters' => []], 'intruder.near'),
    'unrelated wallet denied'
);

echo "\nAll gate integration tests passed.\n";