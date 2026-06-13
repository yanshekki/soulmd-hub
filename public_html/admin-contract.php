<?php
/**
 * SoulMD Hub - CONTRACT ADMIN CONTROL PAGE
 * 
 * RESTRICTED: This page is ONLY for the contract owner (NEAR_CONTRACT_ID = soulmd-hub.near).
 * Server-side check + JS enforcement.
 * 
 * Purpose: Give the platform owner (soulmd-hub.near) a web UI to:
 * - Inspect any token (via RPC get_soul)
 * - Fully edit / create tokens (owner, prices, renters JSON, full replace)
 * - Remove individual tokens or wipe all test data
 * - Use raw storage write/remove for ultimate repair of any key (use after debug)
 * - Manage upgrade credits for testing the USDT/USDC flow
 * - One-time FT receiver registration (storage_deposit) so soulmd-hub.near can accept USDT/USDC via ft_transfer_call (fixes "account not registered" panic)
 *
 * All actions call the on-chain admin_* god-mode methods (only callable by platform_wallet).
 * Fresh start on prefix 't': old test data cleared by user (DB + on-chain zero start).
 *
 * Security: 
 * - PHP checks the logged-in user's bound near_wallet_address === NEAR_CONTRACT_ID
 * - JS also verifies the connected wallet is exactly the contract account before enabling controls.
 * - All calls use the audited wallet scripts + improved error handling.
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
loadTranslations('admin-contract'); // will fall back gracefully if missing

$db = Database::getInstance();
$pdo = $db->getConnection();

$userId = $_SESSION['user_id'] ?? 0;
$currentUserWallet = null;

if ($userId > 0) {
    $wStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
    $wStmt->execute([$userId]);
    $currentUserWallet = $wStmt->fetchColumn();
}

$contractOwner = defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near';

// Enforce FT contract defines (same pattern as upgrade.php + other Web3 pages).
// All contract addresses must come from private/config.php.
if (!defined('NEAR_USDT_CONTRACT') || !defined('NEAR_USDC_CONTRACT')) {
    die('FATAL CONFIG ERROR: NEAR_USDT_CONTRACT and NEAR_USDC_CONTRACT must be defined in private/config.php (see config.example.php).');
}
$usdtContract = NEAR_USDT_CONTRACT;
$usdcContract = NEAR_USDC_CONTRACT;

$isOwner = ($currentUserWallet === $contractOwner);

if (!$isOwner) {
    // Hard server-side restriction as requested
    http_response_code(403);
    include __DIR__ . '/404.php';
    exit;
}

// Preload recent on-chain upgrade payments (USDT/USDC buys) + recent marketplace NFT souls for full buy/sell records
$recentUpgrades = [];
$recentSouls = [];
if ($isOwner) {
    $upStmt = $pdo->prepare("
        SELECT p.id, p.paypal_order_id, p.amount, p.currency, p.tier_purchased, p.created_at, u.username 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.paypal_order_id LIKE 'near-ft:%' 
        ORDER BY p.created_at DESC LIMIT 20
    ");
    $upStmt->execute();
    $recentUpgrades = $upStmt->fetchAll();

    // Marketplace buy/sell/rent records: souls that are on-chain NFTs (is_nft or have nft_owner_wallet)
    $sStmt = $pdo->prepare("
        SELECT id, title, user_id, is_nft, nft_owner_wallet, sale_price, rent_price, created_at 
        FROM souls 
        WHERE is_nft = 1 OR nft_owner_wallet IS NOT NULL 
        ORDER BY created_at DESC LIMIT 8
    ");
    $sStmt->execute();
    $recentSouls = $sStmt->fetchAll();
}

$pageTitle = __('SEO Title');
$pageDesc  = __('SEO Desc');

require_once __DIR__ . '/../private/includes/header.php';
?>
<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<main class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow">
    <!-- Dashboard Header -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between mb-8 border-b border-white/10 pb-6">
        <div>
            <div class="inline-flex items-center gap-2 bg-red-950/40 text-red-400 border border-red-500/30 px-3 py-1 rounded-full text-[10px] font-bold tracking-widest uppercase mb-2">
                <i class="fas fa-shield-alt" aria-hidden="true"></i> <?= __('Platform Owner Only') ?>
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tighter text-white"><?= __('Contract Admin Dashboard') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-1"><?= __('Contract') ?>: <span class="font-mono text-red-400"><?= htmlspecialchars($contractOwner) ?></span> • <?= __('All on-chain state & operations in one place') ?></p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-3">
            <div id="connected-wallet" class="px-4 py-2 bg-zinc-900 border border-white/10 rounded-2xl text-xs font-mono text-emerald-400 hidden"></div>
            <button onclick="refreshAllData()" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white text-sm font-bold rounded-2xl border border-white/10 transition flex items-center gap-2">
                <i class="fas fa-sync-alt" aria-hidden="true"></i> <?= __('Refresh All') ?>
            </button>
        </div>
    </div>

    <!-- Quick Stats / On-Chain Overview (Complete live data) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-5">
            <div class="text-xs text-zinc-400 uppercase tracking-widest mb-1"><?= __('Platform NEAR Balance') ?></div>
            <div id="near-balance" class="text-3xl font-black text-white font-mono">Loading...</div>
            <a href="https://nearblocks.io/address/<?= htmlspecialchars($contractOwner) ?>" target="_blank" class="text-[10px] text-emerald-400 hover:underline"><?= __('View on Explorer') ?></a>
        </div>
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-5">
            <div class="text-xs text-zinc-400 uppercase tracking-widest mb-1"><?= __('USDT Balance (Platform)') ?></div>
            <div id="usdt-balance" class="text-3xl font-black text-white font-mono">Loading...</div>
            <div class="text-[10px] text-emerald-400 font-mono truncate max-w-[150px]" title="<?= htmlspecialchars($usdtContract) ?>"><?= htmlspecialchars($usdtContract) ?></div>
        </div>
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-5">
            <div class="text-xs text-zinc-400 uppercase tracking-widest mb-1"><?= __('USDC Balance (Platform)') ?></div>
            <div id="usdc-balance" class="text-3xl font-black text-white font-mono">Loading...</div>
            <div class="text-[10px] text-emerald-400 font-mono truncate max-w-[150px]" title="<?= htmlspecialchars($usdcContract) ?>"><?= htmlspecialchars($usdcContract) ?></div>
        </div>
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-5">
            <div class="text-xs text-zinc-400 uppercase tracking-widest mb-1"><?= __('Quick Links') ?></div>
            <div class="space-y-1 text-sm">
                <a href="https://nearblocks.io/address/<?= htmlspecialchars($contractOwner) ?>?tab=txns" target="_blank" class="block text-emerald-400 hover:underline"><?= __('Contract Transactions') ?></a>
                <a href="https://nearblocks.io/address/<?= htmlspecialchars($contractOwner) ?>?tab=contract" target="_blank" class="block text-emerald-400 hover:underline"><?= __('Contract State') ?></a>
                <button onclick="checkFtRegistrationStatus()" class="text-xs text-amber-400 hover:underline"><?= __('Check FT Registration Status') ?></button>
            </div>
            <div class="mt-2 text-[10px] text-emerald-400/80"><?= __('Revenue from USDT/USDC upgrades after costs is 100% allocated to $SOUL buy & burn per tokenomics.') ?></div>
        </div>
    </div>

    <!-- Token Management: Explorer + Automated Form (No JSON ever) -->
    <div id="token-section" class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-cube text-purple-400"></i> <?= __('Token Management') ?>
            </h2>
            <button onclick="clearTokenForm()" class="text-xs px-3 py-1 bg-zinc-800 hover:bg-zinc-700 rounded-lg border border-white/10"><?= __('Clear Form') ?></button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Load / Search -->
            <div class="lg:col-span-1 bg-zinc-900/60 border border-white/10 rounded-3xl p-6">
                <h3 class="font-bold mb-3 text-sm text-zinc-400"><?= __('Load or Create') ?></h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-zinc-400"><?= __('Token ID') ?></label>
                        <input id="token-id" type="text" class="w-full bg-black border border-white/10 rounded-xl px-4 py-2.5 text-sm font-mono focus:border-purple-500" placeholder="soul_3956" value="soul_3956">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="loadAndDisplayToken()" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 rounded-xl text-sm font-bold transition"><?= __('Load from Chain') ?></button>
                        <button onclick="enterCreateMode()" class="px-4 py-2.5 bg-zinc-700 hover:bg-zinc-600 rounded-xl text-sm font-bold transition"><?= __('New Token') ?></button>
                    </div>
                    <div class="text-[10px] text-zinc-500 mt-1"><?= __('Enter values in NEAR (e.g. 2.5). Will auto-convert to yoctoNEAR for on-chain.') ?></div>
                </div>
            </div>

            <!-- Current State Display (pretty, full data) -->
            <div id="token-state-card" class="lg:col-span-2 bg-zinc-900/60 border border-white/10 rounded-3xl p-6 hidden">
                <h3 class="font-bold mb-3 text-sm text-zinc-400"><?= __('Current On-Chain State') ?></h3>
                <div id="token-state-content" class="text-sm space-y-3"></div>
            </div>

            <!-- Edit Form - Fully Automated Friendly UI, NO JSON input -->
            <div class="lg:col-span-3 bg-zinc-900/60 border border-white/10 rounded-3xl p-6">
                <h3 class="font-bold mb-3 text-sm text-zinc-400"><?= __('Edit / Create Token (Automated - No JSON)') ?></h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <label class="text-xs text-zinc-400"><?= __('Owner ID') ?></label>
                        <input id="edit-owner" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 font-mono text-xs">
                    </div>
                    <div>
                        <label class="text-xs text-zinc-400"><?= __('Title') ?></label>
                        <input id="edit-title" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs text-zinc-400"><?= __('Description') ?></label>
                        <textarea id="edit-description" rows="2" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 text-xs"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-zinc-400"><?= __('Hash (extra / IPFS)') ?></label>
                        <input id="edit-hash" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 font-mono text-xs">
                    </div>
                    <div>
                        <label class="text-xs text-zinc-400"><?= __('Reference') ?></label>
                        <input id="edit-reference" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 font-mono text-xs">
                    </div>
                    <div>
                        <label class="text-xs text-zinc-400"><?= __('Sale Price (NEAR)') ?></label>
                        <input id="edit-sale" type="number" step="0.0001" min="0" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2" placeholder="0.5">
                    </div>
                    <div>
                        <label class="text-xs text-zinc-400"><?= __('Rent Price (NEAR / 30 Days)') ?></label>
                        <input id="edit-rent" type="number" step="0.0001" min="0" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2" placeholder="0.1">
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-3 items-center">
                    <button onclick="saveTokenFromForm()" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 rounded-2xl text-sm font-bold transition"><?= __('Save / Update on Chain') ?></button>
                    <button onclick="removeCurrentToken()" class="px-6 py-2.5 bg-red-600 hover:bg-red-500 rounded-2xl text-sm font-bold transition"><?= __('Remove Token') ?></button>
                    <span class="text-[10px] text-amber-400"><?= __('Note: Admin Save always resets renters list to empty. Use for corrections only. Normal marketplace use owner list_for_rent / rent_soul flows.') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Upgrade Credits Management (full automated for testing USDT/USDC flow) -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
            <i class="fas fa-credit-card text-cyan-400"></i> <?= __('Upgrade Credits Management') ?>
        </h2>
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <label class="text-xs text-zinc-400"><?= __('Account ID') ?></label>
                    <input id="credit-account" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 text-xs font-mono" value="testuser.near">
                </div>
                <div>
                    <label class="text-xs text-zinc-400"><?= __('Tier') ?></label>
                    <select id="credit-tier" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 text-xs">
                        <option value="vip">VIP</option>
                        <option value="pro">PRO</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-zinc-400"><?= __('Timestamp (ns, optional)') ?></label>
                    <input id="credit-ts" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 text-xs font-mono" placeholder="<?= __('Leave empty for current time') ?>">
                </div>
                <div class="flex items-end gap-2">
                    <button onclick="adminSetCreditFromForm()" class="flex-1 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 rounded-xl text-sm font-bold"><?= __('Set Credit') ?></button>
                    <button onclick="adminRemoveCreditFromForm()" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-xl text-sm"><?= __('Remove') ?></button>
                    <button onclick="queryCredit()" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-xl text-sm"><?= __('Query') ?></button>
                </div>
            </div>
            <div id="credit-result" class="mt-3 text-xs p-3 bg-black/40 rounded-xl border border-white/10 hidden"></div>
            <div class="mt-2 text-[10px] text-zinc-500"><?= __('These credits power the on-chain upgrade flow. Normal flow uses ft_on_transfer from real USDT/USDC payments. Use for testing/recovery only.') ?></div>
        </div>
    </div>

    <!-- FT Receivers & Revenue (complete on-chain FT data + reg automation) -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
            <i class="fas fa-link text-emerald-400"></i> <?= __('FT Receivers & Revenue') ?>
        </h2>
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="font-bold mb-2 text-sm"><?= __('USDT Receiver Status') ?></div>
                    <div id="usdt-status" class="text-xs p-3 bg-black/40 rounded-xl border border-white/10 mb-2"><?= __('Click Check to load...') ?></div>
                    <div class="flex gap-2">
                        <button onclick="checkFtRegistrationStatus()" class="px-4 py-1.5 bg-zinc-800 hover:bg-zinc-700 rounded-lg text-xs"><?= __('Check Status') ?></button>
                        <button onclick="registerFtReceiver('usdt')" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 rounded-lg text-xs text-white"><?= __('Register USDT') ?></button>
                    </div>
                </div>
                <div>
                    <div class="font-bold mb-2 text-sm"><?= __('USDC Receiver Status') ?></div>
                    <div id="usdc-status" class="text-xs p-3 bg-black/40 rounded-xl border border-white/10 mb-2"><?= __('Click Check to load...') ?></div>
                    <div class="flex gap-2">
                        <button onclick="checkFtRegistrationStatus()" class="px-4 py-1.5 bg-zinc-800 hover:bg-zinc-700 rounded-lg text-xs"><?= __('Check Status') ?></button>
                        <button onclick="registerFtReceiver('usdc')" class="px-4 py-1.5 bg-violet-600 hover:bg-violet-500 rounded-lg text-xs text-white"><?= __('Register USDC') ?></button>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-[10px] text-emerald-400"><?= __('USDT/USDC upgrade revenue (after costs) is 100% used to buy & burn $SOUL per the tokenomics.') ?></div>
        </div>
    </div>

    <!-- Buy/Sell Records: Upgrade Payments + Marketplace Souls (all displayed, automated live) -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
            <i class="fas fa-history text-amber-400"></i> <?= __('Recent On-Chain Upgrade Payments (Buy Records)') ?>
        </h2>
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl overflow-hidden mb-4">
            <?php if (empty($recentUpgrades)): ?>
                <div class="p-6 text-sm text-zinc-500"><?= __('No recent on-chain upgrade payments found') ?></div>
            <?php else: ?>
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-black/30 text-zinc-400 text-xs uppercase">
                        <th class="p-4"><?= __('Date') ?></th>
                        <th class="p-4"><?= __('User') ?></th>
                        <th class="p-4"><?= __('Reference') ?></th>
                        <th class="p-4"><?= __('Amount') ?></th>
                        <th class="p-4"><?= __('Tier') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($recentUpgrades as $p): ?>
                    <tr class="hover:bg-white/5">
                        <td class="p-4 text-xs font-mono text-zinc-400"><?= date('Y-m-d H:i', strtotime($p['created_at'])) ?></td>
                        <td class="p-4 text-xs font-mono"><?= htmlspecialchars($p['username']) ?></td>
                        <td class="p-4 text-xs font-mono select-all text-emerald-400"><?= htmlspecialchars($p['paypal_order_id']) ?></td>
                        <td class="p-4 text-xs font-bold"><?= htmlspecialchars($p['currency']) ?> $<?= number_format($p['amount'], 2) ?></td>
                        <td class="p-4"><span class="px-2 py-0.5 text-[10px] rounded bg-emerald-500/10 text-emerald-400 uppercase"><?= htmlspecialchars($p['tier_purchased']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <p class="mb-6 text-[10px] text-zinc-500"><?= __('For full soul marketplace buy/sell/rent history, view contract transactions on') ?> <a href="https://nearblocks.io/address/<?= htmlspecialchars($contractOwner) ?>?tab=txns" target="_blank" class="text-emerald-400 underline"><?= __('View Full Tx History') ?></a> (filter by buy_soul, rent_soul, etc.).</p>

        <!-- Marketplace buy/sell/rent records - live on-chain data for souls -->
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-3">
            <i class="fas fa-store text-amber-400"></i> <?= __('Recent Marketplace Souls (Live On-Chain Status)') ?>
        </h2>
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl overflow-hidden">
            <?php if (empty($recentSouls)): ?>
                <div class="p-6 text-sm text-zinc-500"><?= __('No recent NFT souls in DB yet') ?></div>
            <?php else: ?>
            <table class="w-full text-left text-sm" id="marketplace-records-table">
                <thead>
                    <tr class="bg-black/30 text-zinc-400 text-xs uppercase">
                        <th class="p-4"><?= __('Soul ID') ?></th>
                        <th class="p-4"><?= __('Title') ?></th>
                        <th class="p-4"><?= __('On-Chain Owner') ?></th>
                        <th class="p-4"><?= __('Live Sale') ?></th>
                        <th class="p-4"><?= __('Live Rent') ?></th>
                        <th class="p-4"><?= __('Renters') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5" id="marketplace-records-body">
                    <?php foreach ($recentSouls as $s): 
                        $sid = 'soul_' . (int)$s['id'];
                    ?>
                    <tr class="hover:bg-white/5" data-soul-id="<?= htmlspecialchars($sid) ?>">
                        <td class="p-4 text-xs font-mono text-emerald-400"><?= htmlspecialchars($sid) ?></td>
                        <td class="p-4 text-xs"><?= htmlspecialchars($s['title'] ?: 'Untitled') ?></td>
                        <td class="p-4 text-xs font-mono text-zinc-400 onchain-owner">Loading...</td>
                        <td class="p-4 text-xs font-mono onchain-sale">-</td>
                        <td class="p-4 text-xs font-mono onchain-rent">-</td>
                        <td class="p-4 text-xs onchain-renters">-</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <p class="mt-2 text-[10px] text-zinc-500"><?= __('Synced from DB + verified live via get_soul RPC. Buy/sell/rent actions are executed fully on-chain with platform fees auto-split.') ?></p>
    </div>

    <!-- Advanced / Raw (kept for experts, danger) -->
    <div class="mb-8">
        <details class="bg-zinc-900/60 border border-red-500/20 rounded-3xl p-6">
            <summary class="cursor-pointer text-lg font-bold text-red-400 flex items-center gap-2"><?= __('Advanced / Raw Storage (Expert Only - Dangerous)') ?></summary>
            <div class="mt-4 space-y-3 text-sm">
                <div>
                    <label class="text-xs text-zinc-400"><?= __('Storage Key') ?></label>
                    <input id="raw-key" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 text-xs font-mono mb-1" placeholder="<?= __('e.g. t:soul_123 or uc:account:vip') ?>">
                    <textarea id="raw-value" rows="2" class="w-full bg-black border border-white/10 rounded-xl p-2 text-xs font-mono mb-1" placeholder="<?= __('Value (string) or empty to remove') ?>"></textarea>
                    <div class="flex gap-2">
                        <button onclick="adminRawWrite()" class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-500 rounded-xl text-xs font-bold"><?= __('Write') ?></button>
                        <button onclick="adminRawRemove()" class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-500 rounded-xl text-xs font-bold"><?= __('Remove') ?></button>
                    </div>
                    <div class="text-[10px] text-red-400/70 mt-1"><?= __('Raw storage bypasses normal methods. Only for recovery after bugs or migration artifacts on this account.') ?></div>
                </div>
            </div>
        </details>
    </div>

    <div>
        <div class="text-xs uppercase tracking-widest text-zinc-500 mb-1"><?= __('Activity Log') ?></div>
        <div id="admin-log" class="bg-black/60 border border-white/10 rounded-3xl p-4 text-xs font-mono whitespace-pre-wrap min-h-[140px] text-emerald-300 overflow-auto"></div>
    </div>
</main>

<script>
    // All contract IDs come from private/config.php defines (centralized, same as upgrade.php + other Web3 pages).
    // near-wallet-scripts.php already provides initNearWallet, nearRpcQuery (hub), nearContractView (any contract),
    // getErrorMessage, requestSignTransactions, account().functionCall etc.
    const CONTRACT_ID = "<?= defined('NEAR_CONTRACT_ID') ? addslashes(NEAR_CONTRACT_ID) : 'soulmd-hub.near' ?>";
    const NEAR_USDT_CONTRACT = "<?= defined('NEAR_USDT_CONTRACT') ? NEAR_USDT_CONTRACT : 'usdt.tether-token.near' ?>";
    const NEAR_USDC_CONTRACT = "<?= defined('NEAR_USDC_CONTRACT') ? NEAR_USDC_CONTRACT : '17208628f84f5d6ad33f0da3bbbeb27ffcb398eac501a31bd6ad2011e36133a1' ?>";
    const logEl = document.getElementById('admin-log');
    let currentLoadedTokenId = null;

    function log(msg) {
        const ts = new Date().toLocaleTimeString();
        if (logEl) {
            logEl.textContent = `[${ts}] ${msg}\n` + logEl.textContent;
        }
        console.log(msg);
    }

    async function ensurePlatformWallet() {
        const wrapper = await initNearWallet();
        if (!wrapper || !wrapper.getAccountId()) {
            await window.connectOrBindWallet();
            return null;
        }
        const acc = wrapper.getAccountId();
        if (acc !== CONTRACT_ID) {
            const msg = '<?= addslashes(__('You must be connected as exactly')) ?> ' + CONTRACT_ID + ' <?= addslashes(__('to use admin functions.')) ?>';
            alert(msg);
            return null;
        }
        return wrapper;
    }

    // === Complete automated Token load + form (no JSON) ===
    async function loadAndDisplayToken() {
        const idInput = document.getElementById('token-id');
        const id = (idInput ? idInput.value.trim() : '').replace(/^soul_/i, 'soul_');
        if (!id) return;
        currentLoadedTokenId = id;

        const stateCard = document.getElementById('token-state-card');
        const stateContent = document.getElementById('token-state-content');

        try {
            const res = await window.nearRpcQuery('get_soul', { token_id: id });
            if (stateCard) stateCard.classList.remove('hidden');
            if (!res || !res.success || !res.data) {
                if (stateContent) stateContent.innerHTML = `<div class="text-amber-400">No on-chain token found for <span class="font-mono">${id}</span>. Use "New Token" to create via admin.</div>`;
                log(`No on-chain token or error for ${id}`);
                return;
            }
            const t = res.data;
            currentLoadedTokenId = id;

            // Pretty state card (full data, renters table)
            let html = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2">
                    <div><span class="text-zinc-400"><?= addslashes(__('Owner')) ?>:</span> <span class="font-mono text-emerald-300">${t.owner_id || ''}</span></div>
                    <div><span class="text-zinc-400"><?= addslashes(__('Title')) ?>:</span> ${t.metadata?.title || ''}</div>
                    <div class="md:col-span-2"><span class="text-zinc-400"><?= addslashes(__('Description')) ?>:</span> ${t.metadata?.description || ''}</div>
                    <div><span class="text-zinc-400"><?= addslashes(__('Hash (extra / IPFS)')) ?>:</span> <span class="font-mono text-xs break-all">${t.metadata?.extra || ''}</span></div>
                    <div><span class="text-zinc-400"><?= addslashes(__('Reference')) ?>:</span> <span class="font-mono text-xs">${t.metadata?.reference || ''}</span></div>
                    <div><span class="text-zinc-400"><?= addslashes(__('Sale Price (NEAR)')) ?>:</span> <span class="font-bold">${t.sale_price && t.sale_price !== '0' ? (window.nearApi ? nearApi.utils.format.formatNearAmount(t.sale_price) : (parseInt(t.sale_price)/1e24).toFixed(4)) : '<?= addslashes(__('Not listed')) ?>'}</span></div>
                    <div><span class="text-zinc-400"><?= addslashes(__('Rent Price (NEAR / 30 Days)')) ?>:</span> <span class="font-bold">${t.rent_price && t.rent_price !== '0' ? (window.nearApi ? nearApi.utils.format.formatNearAmount(t.rent_price) : (parseInt(t.rent_price)/1e24).toFixed(4)) : '<?= addslashes(__('Not listed')) ?>'}</span></div>
                </div>
            `;
            // Renters table (professional)
            const renters = t.renters || {};
            const now = Date.now() * 1_000_000; // ns approx
            const renterEntries = Object.entries(renters).filter(([_, exp]) => Number(exp) > now);
            html += `<div class="mt-3"><div class="text-xs text-zinc-400 mb-1"><?= addslashes(__('Active Renters')) ?> (${renterEntries.length}):</div>`;
            if (renterEntries.length === 0) {
                html += `<div class="text-xs text-zinc-500"><?= addslashes(__('No active renters')) ?></div>`;
            } else {
                html += `<table class="w-full text-[11px]"><thead><tr class="text-zinc-500"><th class="text-left py-0.5"><?= addslashes(__('Renter')) ?></th><th class="text-left"><?= addslashes(__('Expiry')) ?></th></tr></thead><tbody>`;
                for (const [acc, expNs] of renterEntries) {
                    const expMs = Math.floor(Number(expNs) / 1_000_000);
                    const d = new Date(expMs).toLocaleString();
                    html += `<tr><td class="font-mono text-emerald-300 py-px">${acc}</td><td class="text-zinc-400">${d}</td></tr>`;
                }
                html += `</tbody></table>`;
            }
            html += `</div>`;
            if (stateContent) stateContent.innerHTML = html;

            // Auto-populate the friendly edit form (automated, no JSON)
            document.getElementById('edit-owner').value = t.owner_id || '';
            document.getElementById('edit-title').value = t.metadata?.title || '';
            document.getElementById('edit-description').value = t.metadata?.description || '';
            document.getElementById('edit-hash').value = t.metadata?.extra || '';
            document.getElementById('edit-reference').value = t.metadata?.reference || '';
            document.getElementById('edit-sale').value = (t.sale_price && t.sale_price !== '0') ? (window.nearApi ? nearApi.utils.format.formatNearAmount(t.sale_price) : (parseInt(t.sale_price)/1e24)) : '';
            document.getElementById('edit-rent').value = (t.rent_price && t.rent_price !== '0') ? (window.nearApi ? nearApi.utils.format.formatNearAmount(t.rent_price) : (parseInt(t.rent_price)/1e24)) : '';

            log(`Loaded ${id} from chain (state + form populated)`);
        } catch (e) {
            log('Load error: ' + (window.getErrorMessage ? window.getErrorMessage(e) : e));
            if (stateContent) stateContent.innerHTML = `<div class="text-red-400">Error loading: ${e.message || e}</div>`;
        }
    }

    function enterCreateMode() {
        const stateCard = document.getElementById('token-state-card');
        const stateContent = document.getElementById('token-state-content');
        if (stateCard) stateCard.classList.remove('hidden');
        if (stateContent) stateContent.innerHTML = `<div class="text-emerald-400 text-sm">Creating new token. Fill the form below and press Save. Token ID is taken from the top input.</div>`;
        // Clear form for fresh create
        clearTokenForm();
        const idInput = document.getElementById('token-id');
        if (idInput && !idInput.value) idInput.value = 'soul_' + Math.floor(Math.random()*9000+1000);
        currentLoadedTokenId = null;
    }

    function clearTokenForm() {
        const fields = ['edit-owner','edit-title','edit-description','edit-hash','edit-reference','edit-sale','edit-rent'];
        fields.forEach(f => { const el = document.getElementById(f); if (el) el.value = ''; });
        const stateCard = document.getElementById('token-state-card');
        if (stateCard) stateCard.classList.add('hidden');
    }

    // Core: call admin methods (guarded + auto refresh where useful)
    async function callAdmin(method, args) {
        const wrapper = await ensurePlatformWallet();
        if (!wrapper) return;

        const btns = document.querySelectorAll('button');
        btns.forEach(b => b.disabled = true);

        try {
            await wrapper.account().functionCall({
                contractId: CONTRACT_ID,
                methodName: method,
                args: args,
                gas: "30000000000000",
                attachedDeposit: "0"
            });
            log(`${method} submitted successfully (check explorer for result)`);

            // Auto refresh relevant UI (no user JSON work)
            if (method.includes('token') || method === 'admin_set_token' || method.includes('remove')) {
                setTimeout(async () => {
                    try {
                        if (currentLoadedTokenId) {
                            await loadAndDisplayToken();
                        }
                        log('<?= addslashes(__('Token state refreshed from chain')) ?>');
                    } catch (_) {}
                }, 1600);
            } else if (method.includes('credit')) {
                log('<?= addslashes(__('Credit action done')) ?>');
            }
        } catch (e) {
            const err = window.getErrorMessage ? window.getErrorMessage(e) : String(e);
            log(`${method} failed: ${err}`);
            if (method.includes('token') || method.includes('set') || method.includes('remove')) {
                try { if (currentLoadedTokenId) await loadAndDisplayToken(); log('<?= addslashes(__('State re-loaded from chain after admin call (may have succeeded)')) ?>'); } catch(_) {}
            }
            const adminFailMsg = '<?= addslashes(__('Admin call failed')) ?>'.replace('{err}', err);
            alert(adminFailMsg);
        } finally {
            btns.forEach(b => b.disabled = false);
        }
    }

    // Save from the friendly form (automated, converts NEAR->yocto, sends flat fields to match contract admin_set_token)
    async function saveTokenFromForm() {
        const id = (document.getElementById('token-id').value || '').trim();
        if (!id) { alert('<?= addslashes(__('token_id and all fields required for save')) ?>'); return; }

        const owner = document.getElementById('edit-owner').value.trim();
        const title = document.getElementById('edit-title').value.trim() || 'Untitled';
        const desc = document.getElementById('edit-description').value.trim() || '';
        const hash = document.getElementById('edit-hash').value.trim() || '';
        const ref = document.getElementById('edit-reference').value.trim() || '';

        let sale = document.getElementById('edit-sale').value.trim();
        let rent = document.getElementById('edit-rent').value.trim();

        // Convert human NEAR to yocto strings (or null)
        const saleY = sale ? (window.nearApi ? window.nearApi.utils.format.parseNearAmount(sale) : (parseFloat(sale) * 1e24).toFixed(0)) : null;
        const rentY = rent ? (window.nearApi ? window.nearApi.utils.format.parseNearAmount(rent) : (parseFloat(rent) * 1e24).toFixed(0)) : null;

        const args = {
            token_id: id,
            owner_id: owner || CONTRACT_ID,
            title: title,
            description: desc,
            hash: hash,
            reference: ref,
            sale_price: saleY,
            rent_price: rentY
        };

        await callAdmin('admin_set_token', args);
    }

    async function removeCurrentToken() {
        const id = (document.getElementById('token-id').value || '').trim();
        if (!id) return;
        if (!confirm(`<?= addslashes(__('Really remove')) ?> ${id}?`)) return;
        await callAdmin('admin_remove_token', { token_id: id });
    }

    // Credits - fully wired to real admin_* methods
    async function adminSetCreditFromForm() {
        const acc = document.getElementById('credit-account').value.trim();
        const tier = document.getElementById('credit-tier').value;
        if (!acc || !tier) return;
        let ts = document.getElementById('credit-ts').value.trim();
        if (!ts) {
            ts = (Date.now() * 1_000_000).toString() + '000000'; // ns
        }
        await callAdmin('admin_set_upgrade_credit', { account_id: acc, tier, ts });
        // auto query after set
        setTimeout(() => queryCredit(), 800);
    }

    async function adminRemoveCreditFromForm() {
        const acc = document.getElementById('credit-account').value.trim();
        const tier = document.getElementById('credit-tier').value;
        if (!acc || !tier) return;
        await callAdmin('admin_remove_upgrade_credit', { account_id: acc, tier });
    }

    async function queryCredit() {
        const acc = document.getElementById('credit-account').value.trim();
        const tier = document.getElementById('credit-tier').value;
        const resBox = document.getElementById('credit-result');
        if (!acc || !tier || !resBox) return;

        const r = await window.nearRpcQuery('has_upgrade_credit', { account_id: acc, tier });
        resBox.classList.remove('hidden');
        if (r && r.success && r.data) {
            const val = String(r.data);
            resBox.innerHTML = `<div><b><?= addslashes(__('Credit Result')) ?>:</b> <span class="font-mono">${val}</span> ${val === '0' ? ' (<?= addslashes(__('No credit found')) ?>)' : ''}</div>`;
            log(`has_upgrade_credit(${acc}, ${tier}) = ${val}`);
        } else {
            resBox.innerHTML = `<div class="text-amber-400"><?= addslashes(__('No credit found')) ?></div>`;
        }
    }

    // FT registration + status using nearView (any contract)
    async function checkFtRegistrationStatus() {
        const usdtBox = document.getElementById('usdt-status');
        const usdcBox = document.getElementById('usdc-status');

        // USDT
        if (usdtBox) usdtBox.textContent = 'Checking...';
        const usdtBal = await window.nearContractView(NEAR_USDT_CONTRACT, 'ft_balance_of', { account_id: CONTRACT_ID });
        const usdtStor = await window.nearContractView(NEAR_USDT_CONTRACT, 'storage_balance_of', { account_id: CONTRACT_ID });
        if (usdtBox) {
            const bal = (usdtBal.success && usdtBal.data) ? (parseInt(usdtBal.data) / 1e6).toFixed(2) + ' USDT' : '0';
            const reg = (usdtStor.success && usdtStor.data) ? '✅ <?= addslashes(__('Registered')) ?>' : '❌ <?= addslashes(__('Not Registered')) ?>';
            usdtBox.innerHTML = `${reg} <span class="text-emerald-400">(${bal})</span>`;
        }

        // USDC
        if (usdcBox) usdcBox.textContent = 'Checking...';
        const usdcBal = await window.nearContractView(NEAR_USDC_CONTRACT, 'ft_balance_of', { account_id: CONTRACT_ID });
        const usdcStor = await window.nearContractView(NEAR_USDC_CONTRACT, 'storage_balance_of', { account_id: CONTRACT_ID });
        if (usdcBox) {
            const bal = (usdcBal.success && usdcBal.data) ? (parseInt(usdcBal.data) / 1e6).toFixed(2) + ' USDC' : '0';
            const reg = (usdcStor.success && usdcStor.data) ? '✅ <?= addslashes(__('Registered')) ?>' : '❌ <?= addslashes(__('Not Registered')) ?>';
            usdcBox.innerHTML = `${reg} <span class="text-emerald-400">(${bal})</span>`;
        }
        log('FT status + platform balances refreshed.');
    }

    async function registerFtReceiver(token) {
        const wrapper = await ensurePlatformWallet();
        if (!wrapper || !wrapper.getAccountId()) {
            log('<?= addslashes(__('Please connect as platform owner first.')) ?>');
            return;
        }
        if (wrapper.getAccountId() !== CONTRACT_ID) {
            log('<?= addslashes(__('WARNING: Connected wallet is not the contract owner. Admin buttons will be limited.')) ?> ' + CONTRACT_ID + '. <?= addslashes(__('Registration must be done by the owner account.')) ?>');
        }

        const tokenContract = (token === 'usdt') ? NEAR_USDT_CONTRACT : NEAR_USDC_CONTRACT;
        log(`<?= addslashes(__('Registering as receiver on')) ?> ${tokenContract} (storage_deposit, registration_only=true, ~0.00125 NEAR)...`);

        try {
            await wrapper.requestSignTransactions({
                transactions: [{
                    receiverId: tokenContract,
                    actions: [{
                        methodName: 'storage_deposit',
                        args: { account_id: CONTRACT_ID, registration_only: true },
                        gas: '30000000000000',
                        deposit: window.nearApi.utils.format.parseNearAmount('0.00125')
                    }]
                }]
            });
            log(`✅ <?= addslashes(__('Success. is now registered')) ?> ${token.toUpperCase()}.`);
            setTimeout(checkFtRegistrationStatus, 1400);
        } catch (e) {
            const errMsg = (window.getErrorMessage ? window.getErrorMessage(e) : (e && e.message) || String(e));
            log(`<?= addslashes(__('Registration failed')) ?>: ${errMsg}`);
        }
    }

    // === Full live on-chain data refresh (balances + auto marketplace rows) ===
    async function refreshAllData() {
        log('Refreshing all dashboard data...');

        // 1. Platform NEAR balance (account query)
        try {
            const balPayload = { jsonrpc:"2.0", id:"bal", method:"query", params:{ request_type:"view_account", finality:"final", account_id: CONTRACT_ID } };
            const r = await fetch((window.activeNearRpcUrl || 'https://free.rpc.fastnear.com'), {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(balPayload)});
            const j = await r.json();
            const amt = j && j.result && j.result.amount ? j.result.amount : '0';
            // Always produce a short, layout-safe number (max 4 decimals) so the stat card never overflows
            let nearDisplay = '0';
            try {
                if (window.nearApi && nearApi.utils && typeof nearApi.utils.format.formatNearAmount === 'function') {
                    const formatted = nearApi.utils.format.formatNearAmount(amt);
                    nearDisplay = parseFloat(formatted || '0').toFixed(4);
                } else {
                    nearDisplay = (parseFloat(amt) / 1e24).toFixed(4);
                }
            } catch (_) {
                nearDisplay = (parseFloat(amt) / 1e24).toFixed(4);
            }
            const el = document.getElementById('near-balance');
            if (el) el.textContent = nearDisplay + ' Ⓝ';
        } catch (e) { const el = document.getElementById('near-balance'); if (el) el.textContent = 'Error'; }

        // 2. USDT + USDC balances (via nearView)
        const usdtB = await window.nearContractView(NEAR_USDT_CONTRACT, 'ft_balance_of', { account_id: CONTRACT_ID });
        const usdcB = await window.nearContractView(NEAR_USDC_CONTRACT, 'ft_balance_of', { account_id: CONTRACT_ID });
        const uEl = document.getElementById('usdt-balance');
        const cEl = document.getElementById('usdc-balance');
        if (uEl) uEl.textContent = (usdtB.success && usdtB.data) ? (parseInt(usdtB.data)/1e6).toFixed(2) + ' USDT' : '0';
        if (cEl) cEl.textContent = (usdcB.success && usdcB.data) ? (parseInt(usdcB.data)/1e6).toFixed(2) + ' USDC' : '0';

        // 3. Also refresh FT registration statuses
        checkFtRegistrationStatus();

        // 4. Re-enrich live marketplace rows (if any)
        await refreshMarketplaceRows();

        log('All data refreshed.');
    }

    async function refreshMarketplaceRows() {
        const rows = document.querySelectorAll('#marketplace-records-body tr[data-soul-id]');
        for (const row of rows) {
            const sid = row.getAttribute('data-soul-id');
            if (!sid) continue;
            try {
                const rpc = await window.nearRpcQuery('get_soul', { token_id: sid });
                if (rpc && rpc.success && rpc.data) {
                    const t = rpc.data;
                    const ownerTd = row.querySelector('.onchain-owner');
                    const saleTd = row.querySelector('.onchain-sale');
                    const rentTd = row.querySelector('.onchain-rent');
                    const rentCntTd = row.querySelector('.onchain-renters');
                    if (ownerTd) ownerTd.textContent = t.owner_id || '-';
                    if (saleTd) saleTd.textContent = (t.sale_price && t.sale_price !== '0') ? (window.nearApi ? nearApi.utils.format.formatNearAmount(t.sale_price) : (parseInt(t.sale_price)/1e24).toFixed(3)) : '<?= addslashes(__('Not listed')) ?>';
                    if (rentTd) rentTd.textContent = (t.rent_price && t.rent_price !== '0') ? (window.nearApi ? nearApi.utils.format.formatNearAmount(t.rent_price) : (parseInt(t.rent_price)/1e24).toFixed(3)) : '<?= addslashes(__('Not listed')) ?>';
                    if (rentCntTd) {
                        const renters = t.renters || {};
                        const active = Object.keys(renters).filter(k => Number(renters[k]) > Date.now()*1000000).length;
                        rentCntTd.textContent = active;
                    }
                } else {
                    const o = row.querySelector('.onchain-owner'); if (o) o.textContent = 'not on-chain';
                }
            } catch (e) {
                console.warn('market row refresh', sid, e);
            }
        }
    }

    // Raw storage (expert)
    async function adminRawWrite() {
        const key = document.getElementById('raw-key').value.trim();
        const val = document.getElementById('raw-value').value || '';
        if (!key) return;
        await callAdmin('admin_raw_storage_write', { key, value: val });
    }
    async function adminRawRemove() {
        const key = document.getElementById('raw-key').value.trim();
        if (!key) return;
        await callAdmin('admin_raw_storage_remove', { key });
    }

    // Initial: connect check + auto load demo token + full refresh + enrich marketplace
    window.addEventListener('DOMContentLoaded', async () => {
        const wrapper = await initNearWallet();
        const connEl = document.getElementById('connected-wallet');
        if (wrapper && wrapper.getAccountId()) {
            const acc = wrapper.getAccountId();
            if (connEl) {
                connEl.textContent = '<?= addslashes(__('Connected as')) ?> ' + acc;
                connEl.classList.remove('hidden');
            }
            if (acc !== CONTRACT_ID) {
                log('<?= addslashes(__('WARNING: Connected wallet is not the contract owner. Admin buttons will be limited.')) ?>');
            } else {
                log('<?= addslashes(__('Platform owner connected. All admin functions enabled.')) ?>');
            }
        }

        // Auto-load a common token for instant usefulness (no user effort)
        setTimeout(() => {
            const idEl = document.getElementById('token-id');
            if (idEl && idEl.value) {
                loadAndDisplayToken().catch(()=>{});
            }
        }, 650);

        // Full live stats + records
        setTimeout(() => {
            refreshAllData().catch(()=>{});
        }, 900);

        // Also enrich marketplace rows (DB rows + live get_soul)
        setTimeout(refreshMarketplaceRows, 1200);
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>