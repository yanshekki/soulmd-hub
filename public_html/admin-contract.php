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
$isOwner = ($currentUserWallet === $contractOwner);

if (!$isOwner) {
    // Hard server-side restriction as requested
    http_response_code(403);
    include __DIR__ . '/404.php';
    exit;
}

$pageTitle = 'Contract Admin Control';
$pageDesc  = 'Platform-only administration for the soulmd-hub.near smart contract.';

require_once __DIR__ . '/../private/includes/header.php';
?>
<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<main class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow">
    <div class="mb-8">
        <div class="inline-flex items-center gap-2 bg-red-950/40 text-red-400 border border-red-500/30 px-3 py-1 rounded-full text-[10px] font-bold tracking-widest uppercase mb-3">
            <i class="fas fa-shield-alt" aria-hidden="true"></i> PLATFORM ONLY
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tighter text-white">Contract Admin Control</h1>
        <p class="text-sm sm:text-base text-zinc-400 mt-2">Connected as <span class="font-mono text-red-400"><?= htmlspecialchars($currentUserWallet) ?></span>. All actions are irreversible on-chain.</p>
        <div class="mt-3 text-xs text-amber-400 bg-amber-950/30 border border-amber-500/30 px-3 py-2 rounded-xl">
            Fresh start 't'. Records are new. Use admin_set_token for B prices (3 NEAR sale / 0.1 NEAR rent) or raw only if recovery needed.
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Token Inspector / Editor -->
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-database text-purple-400"></i> Token Record Editor
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="text-xs text-zinc-400">token_id (e.g. soul_123)</label>
                    <input id="token-id" type="text" class="w-full bg-black border border-white/10 rounded-xl px-4 py-2.5 text-sm font-mono focus:border-purple-500" placeholder="soul_3956" value="soul_3956">
                </div>

                <div class="flex gap-2">
                    <button onclick="loadToken()" class="flex-1 px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-xl text-sm font-bold">Load from Chain (get_soul)</button>
                    <button onclick="clearForm()" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-xl text-sm">Clear</button>
                </div>

                <div>
                    <label class="text-xs text-zinc-400">Full Token JSON (owner, metadata, sale_price, rent_price, renters)</label>
                    <textarea id="token-json" rows="8" class="w-full bg-black border border-white/10 rounded-xl p-3 text-xs font-mono focus:border-purple-500" placeholder='{"owner_id":"...","metadata":{...},"sale_price":null,"rent_price":null,"renters":{}}'></textarea>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button onclick="adminSetToken()" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 rounded-xl text-sm font-bold">admin_set_token (full replace)</button>
                    <button onclick="adminRemoveToken()" class="px-4 py-2.5 bg-red-600 hover:bg-red-500 rounded-xl text-sm font-bold">admin_remove_token</button>
                </div>

                <div class="pt-2 border-t border-white/10">
                    <div class="text-xs text-zinc-400 mb-1">Quick renters edit (JSON object)</div>
                    <textarea id="renters-json" rows="3" class="w-full bg-black border border-white/10 rounded-xl p-2 text-xs font-mono" placeholder='{"alice.near":"1725...ns","bob.near":"..."}'></textarea>
                    <button onclick="adminUpdateRenters()" class="mt-1 w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-xl text-sm font-bold">admin_update_renters</button>
                </div>
            </div>
        </div>

        <!-- Bulk / Dangerous Actions -->
        <div class="bg-zinc-900/60 border border-red-500/30 rounded-3xl p-6">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2 text-red-400">
                <i class="fas fa-exclamation-triangle"></i> Test Data Reset &amp; Raw Power
            </h2>

            <div class="space-y-3 text-sm">
                <button onclick="adminClearAll()" class="w-full px-4 py-3 bg-red-600 hover:bg-red-500 rounded-xl font-bold">admin_clear_all_tokens (wipe everything under live prefix)</button>
                <div class="text-[10px] text-red-400">Wipes ALL under live prefix 't'. For zero-start resets (pair with your DB clear).</div>

                <div class="pt-4 border-t border-white/10">
                    <div class="font-bold text-amber-400 mb-1">Advanced: Raw Storage (use debug first to discover keys)</div>
                    <input id="raw-key" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 text-xs font-mono mb-1" placeholder="t-v2soul_123 or any internal key">
                    <textarea id="raw-value" rows="2" class="w-full bg-black border border-white/10 rounded-xl p-2 text-xs font-mono mb-1" placeholder="value (string) or leave empty for remove"></textarea>
                    <div class="flex gap-2">
                        <button onclick="adminRawWrite()" class="flex-1 px-3 py-2 bg-amber-600 hover:bg-amber-500 rounded-xl text-xs font-bold">admin_raw_storage_write</button>
                        <button onclick="adminRawRemove()" class="flex-1 px-3 py-2 bg-amber-600 hover:bg-amber-500 rounded-xl text-xs font-bold">admin_raw_storage_remove</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upgrade Credits -->
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 lg:col-span-2">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-credit-card text-cyan-400"></i> Upgrade Credits (USDT/USDC flow testing)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div>
                    <label class="text-xs text-zinc-400">account_id</label>
                    <input id="credit-account" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 text-xs font-mono" value="testuser.near">
                </div>
                <div>
                    <label class="text-xs text-zinc-400">tier (vip / pro)</label>
                    <input id="credit-tier" type="text" class="w-full bg-black border border-white/10 rounded-xl px-3 py-2 text-xs font-mono" value="vip">
                </div>
                <div class="flex items-end gap-2">
                    <button onclick="adminSetCredit()" class="flex-1 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 rounded-xl text-sm font-bold">admin_set_upgrade_credit</button>
                    <button onclick="adminRemoveCredit()" class="flex-1 px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-xl text-sm font-bold">remove</button>
                </div>
            </div>
            <div class="mt-2 text-[10px] text-zinc-500">These credits are what the PHP near-upgrade.php checks via has_upgrade_credit before applying DB tier.</div>
        </div>
    </div>

    <div id="admin-log" class="mt-6 bg-black/60 border border-white/10 rounded-3xl p-4 text-xs font-mono whitespace-pre-wrap min-h-[120px] text-emerald-300"></div>
</main>

<script>
    const CONTRACT_ID = "<?= addslashes($contractOwner) ?>";
    const logEl = document.getElementById('admin-log');

    function log(msg) {
        const ts = new Date().toLocaleTimeString();
        logEl.textContent = `[${ts}] ${msg}\n` + logEl.textContent;
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
            alert("You must be connected as exactly " + CONTRACT_ID + " to use admin functions.");
            return null;
        }
        return wrapper;
    }

    async function loadToken() {
        const id = document.getElementById('token-id').value.trim();
        if (!id) return;
        try {
            const res = await window.nearRpcQuery('get_soul', { token_id: id });
            if (res.success && res.data) {
                document.getElementById('token-json').value = JSON.stringify(res.data, null, 2);
                log(`Loaded ${id} from chain`);
            } else {
                log(`No on-chain token or error: ${res.error || 'not found'}`);
            }
        } catch (e) {
            log('Load error: ' + (window.getErrorMessage ? window.getErrorMessage(e) : e));
        }
    }

    function clearForm() {
        document.getElementById('token-json').value = '';
        document.getElementById('renters-json').value = '';
    }

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
            // Auto-verify state for token/credit actions so admin sees the result without manual reload
            if (method.includes('token') || method === 'admin_set_token' || method.includes('credit')) {
                setTimeout(async () => {
                    try {
                        if (method.includes('token') || method === 'admin_set_token' || method.includes('remove')) {
                            await loadToken();
                            log('Token state refreshed from chain.');
                        } else if (method.includes('credit')) {
                            log('Credit action done. Use has_upgrade_credit view or re-test in upgrade flow.');
                        }
                    } catch (_) {}
                }, 1500);
            }
        } catch (e) {
            const err = window.getErrorMessage ? window.getErrorMessage(e) : String(e);
            log(`${method} failed: ${err}`);
            // For admin, still attempt state verify so false "fail" doesn't block (tx may have landed)
            if (method.includes('token') || method.includes('set') || method.includes('remove')) {
                try { await loadToken(); log('State re-loaded from chain after admin call (may have succeeded)'); } catch(_) {}
            }
            const adminFailMsg = '<?= addslashes(__('Admin call failed')) ?>'.replace('{err}', err);
            alert(adminFailMsg);
        } finally {
            btns.forEach(b => b.disabled = false);
        }
    }

    async function adminSetToken() {
        const id = document.getElementById('token-id').value.trim();
        const json = document.getElementById('token-json').value.trim();
        if (!id || !json) { alert("token_id and JSON required"); return; }
        await callAdmin('admin_set_token', { token_id: id, token_json: json });
    }

    async function adminRemoveToken() {
        const id = document.getElementById('token-id').value.trim();
        if (!id) return;
        if (!confirm(`Really remove ${id}?`)) return;
        await callAdmin('admin_remove_token', { token_id: id });
    }

    async function adminUpdateRenters() {
        const id = document.getElementById('token-id').value.trim();
        const j = document.getElementById('renters-json').value.trim() || '{}';
        if (!id) return;
        await callAdmin('admin_update_renters', { token_id: id, renters_json: j });
    }

    async function adminClearAll() {
        if (!confirm("WIPE ALL TOKENS under the live prefix? This is for test data only.")) return;
        await callAdmin('admin_clear_all_tokens', {});
    }

    async function adminRawWrite() {
        const key = document.getElementById('raw-key').value.trim();
        const val = document.getElementById('raw-value').value;
        if (!key) return;
        await callAdmin('admin_raw_storage_write', { key, value: val });
    }

    async function adminRawRemove() {
        const key = document.getElementById('raw-key').value.trim();
        if (!key) return;
        await callAdmin('admin_raw_storage_remove', { key });
    }

    async function adminSetCredit() {
        const acc = document.getElementById('credit-account').value.trim();
        const tier = document.getElementById('credit-tier').value.trim();
        if (!acc || !tier) return;
        const ts = Date.now() * 1_000_000 + "000000"; // rough ns
        await callAdmin('admin_set_upgrade_credit', { account_id: acc, tier, ts });
    }

    async function adminRemoveCredit() {
        const acc = document.getElementById('credit-account').value.trim();
        const tier = document.getElementById('credit-tier').value.trim();
        if (!acc || !tier) return;
        await callAdmin('admin_remove_upgrade_credit', { account_id: acc, tier });
    }

    // Auto-enforce owner on connect
    window.addEventListener('DOMContentLoaded', async () => {
        const wrapper = await initNearWallet();
        if (wrapper && wrapper.getAccountId()) {
            const acc = wrapper.getAccountId();
            if (acc !== CONTRACT_ID) {
                log("WARNING: Connected wallet is not the contract owner. Admin buttons will be limited.");
            } else {
                log("Platform owner connected. All admin functions enabled.");
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>