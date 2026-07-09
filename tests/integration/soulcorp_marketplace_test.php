<?php
/**
 * SoulCorp marketplace lifecycle test (SQLite in-memory, no MySQL required).
 *
 * Run: php tests/integration/soulcorp_marketplace_test.php
 */

require_once __DIR__ . '/helpers/test_runner.php';
require_once __DIR__ . '/../../private/src/SoulCorpHub.php';

if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "WARN: pdo_sqlite unavailable — skipping DB lifecycle test.\n");
    require __DIR__ . '/soulcorp_marketplace_contract_test.php';
    exit(0);
}

function marketplace_test_pdo(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec(
        'CREATE TABLE gigs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            poster_user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            budget_usdt REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT "open",
            required_skills TEXT,
            deadline TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $pdo->exec(
        'CREATE TABLE gig_assignments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            gig_id INTEGER NOT NULL,
            assignee_user_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT "assigned",
            deliverable_url TEXT,
            qc_score TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $pdo->exec(
        'CREATE TABLE platform_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            gig_id INTEGER,
            from_user_id INTEGER,
            to_user_id INTEGER,
            amount_usdt REAL DEFAULT 0,
            fee_usdt REAL DEFAULT 0,
            fee_soul REAL DEFAULT 0,
            tx_hash TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $pdo->exec(
        'CREATE TABLE user_tiers (
            user_id INTEGER PRIMARY KEY,
            tier TEXT DEFAULT "free",
            soul_staked REAL DEFAULT 0,
            soul_balance REAL DEFAULT 0,
            expires_at TEXT,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $pdo->exec(
        'CREATE TABLE sync_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            direction TEXT NOT NULL,
            payload_json TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )'
    );

    return $pdo;
}

hub_test_section('Create and list gigs');

$pdo = marketplace_test_pdo();
$posterId = 10;
$workerId = 20;

$created = SoulCorpHub::createGig($pdo, $posterId, [
    'title' => 'Landing page',
    'description' => 'Build a static site',
    'budget_usdt' => 6000,
    'required_skills' => ['react', 'css'],
]);
hub_test_assert_eq('open', $created['status'], 'new gig is open');
$gigId = (int)$created['gig_id'];
hub_test_assert_true($gigId > 0, 'gig id assigned');

$listed = SoulCorpHub::listGigs($pdo, 'open');
hub_test_assert_eq(1, count($listed), 'one open gig listed');
hub_test_assert_true($listed[0]['executive_lounge'], 'high-budget gig flagged executive lounge');

hub_test_section('Assign → start → QC → complete');

$assigned = SoulCorpHub::assignGig($pdo, $workerId, $gigId);
hub_test_assert_eq('assigned', $assigned['status'], 'gig assigned');

$started = SoulCorpHub::startGig($pdo, $workerId, $gigId);
hub_test_assert_eq('in_progress', $started['status'], 'gig started');

$submitted = SoulCorpHub::submitGigForQc($pdo, $workerId, $gigId, [
    'qc_score' => ['overall' => 0.92],
]);
hub_test_assert_eq('in_qc', $submitted['status'], 'gig in QC');

$completed = SoulCorpHub::completeGig($pdo, $workerId, $gigId);
hub_test_assert_eq('completed', $completed['status'], 'gig completed');
hub_test_assert_true((float)$completed['payout_usdt'] > 0, 'payout recorded');
hub_test_assert_true((float)$completed['fee_usdt'] > 0, 'platform fee recorded');

hub_test_section('Cancel open gig');

$cancelled = SoulCorpHub::createGig($pdo, $posterId, [
    'title' => 'Draft brief',
    'description' => 'Will cancel',
    'budget_usdt' => 300,
    'required_skills' => ['copy'],
]);
$cancelGigId = (int)$cancelled['gig_id'];
$cancelResult = SoulCorpHub::cancelGig($pdo, $posterId, $cancelGigId);
hub_test_assert_eq('cancelled', $cancelResult['status'], 'open gig cancelled');

hub_test_section('Sync pull');

$pdo->prepare('INSERT INTO user_tiers (user_id, tier, soul_balance) VALUES (?, "pro", 12.5)')
    ->execute([$workerId]);
$pull = SoulCorpHub::pullSync($pdo, $workerId);
hub_test_assert_eq(12.5, (float)$pull['soul_balance'], 'pull sync soul balance');
hub_test_assert_true(is_array($pull['open_gigs']), 'pull sync includes gigs array');

echo "\nAll SoulCorp marketplace integration checks passed.\n";