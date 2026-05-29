<?php
/**
 * SoulMD Hub - My API Controller
 * (Clean, Modular, Web2.5 Stateless BYOK Proxy & One-Time Wallet Binding Edition)
 * 🚀 Fixed: Pure MyNearWallet Redirect & Emerald Contrast Button
 */

$isPublicApiPage = $isPublicApiPage ?? false;

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

$apiKey = 'YOUR_API_KEY';
$isPremiumActive = false;
$userTier = 'free';
$isExpired = false;
$isAdmin = false;
$nearWallet = null;

if (!$isPublicApiPage) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT username, api_key, tier, vip_expires_at, near_wallet_address FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userRow = $stmt->fetch();
    
    if ($userRow) {
        $apiKey = $userRow['api_key'];
        $userTier = $userRow['tier'];
        $nearWallet = $userRow['near_wallet_address'];
        $expiry = $userRow['vip_expires_at'] ? strtotime($userRow['vip_expires_at']) : 0;
        
        if (in_array(strtolower($userRow['username']), ['yanshekki', 'ysk', 'ysklimited', 'ki'])) {
            $isAdmin = true;
        }
        
        if ($userTier !== 'free') {
            if ($expiry > time()) {
                $isPremiumActive = true;
            } else {
                $isExpired = true; 
            }
        }
    }

    if (!$apiKey) {
        $apiKey = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$apiKey, $userId]);
    }
}

$baseUrl = defined('BASE_URL') ? BASE_URL : ("https://" . $_SERVER['HTTP_HOST']);
loadTranslations('my-api');

$pageTitle = $isPublicApiPage ? __('API Reference') : __('Developer API Access');
$pageDesc = 'Manage your API key and read integration docs for SoulMD Hub.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-8">
        <div>
            <?php if ($isPublicApiPage): ?>
                <a href="<?= url('/browse') ?>" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                    <i class="fas fa-arrow-left"></i> Back to Hub
                </a>
            <?php else: ?>
                <a href="<?= url('/my-souls') ?>" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                    <i class="fas fa-arrow-left"></i> Back to My Souls
                </a>
            <?php endif; ?>
            
            <h1 class="text-4xl font-bold tracking-tighter"><?= $isPublicApiPage ? __('API Reference') : __('Developer API Access') ?></h1>
            <p class="text-zinc-400 mt-2">Integrate SoulMD Hub programmatically. 100% API-Driven Architecture.</p>
        </div>
    </div>

    <?php if (!$isPublicApiPage): ?>
        <?php if (!$isPremiumActive): ?>
            <div class="bg-gradient-to-r from-red-900/40 to-amber-900/40 border border-red-500/50 p-6 rounded-3xl mb-8 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
                <div class="flex items-start gap-4 z-10">
                    <div class="text-red-400 text-3xl mt-1"><i class="fas fa-lock"></i></div>
                    <div>
                        <h3 class="text-xl font-bold text-white mb-1">
                            <?= $isExpired ? 'Your Premium Subscription has Expired!' : 'Headless Chat API is Locked (Free Tier)' ?>
                        </h3>
                        <p class="text-sm text-zinc-300 leading-relaxed">
                            <?= $isExpired 
                                ? "Your VIP/PRO access has lapsed. Direct headless access to the <code>/api/chat</code> endpoint has been restricted. Please renew your pass to restore full API integration capabilities." 
                                : "Direct headless access to the core Chat Engine (<code>/api/chat</code>) is exclusively reserved for VIP and PRO members. Upgrade now to build automated agents." ?>
                        </p>
                    </div>
                </div>
                <div class="shrink-0 z-10 w-full md:w-auto">
                    <a href="<?= url('/upgrade') ?>" class="w-full md:w-auto px-6 py-3 bg-red-500 hover:bg-red-400 text-zinc-950 font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-red-500/20">
                        <?= $isExpired ? '<i class="fas fa-sync-alt"></i> Renew Subscription' : '<i class="fas fa-crown"></i> Upgrade to Unlock' ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div id="success-box" class="hidden bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-8 text-sm text-emerald-100 shadow-lg flex items-center gap-2 transition-all">
            <i class="fas fa-check-circle"></i> <span id="success-msg"></span>
        </div>
        <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-red-200 shadow-lg flex items-center gap-2 transition-all">
            <i class="fas fa-exclamation-circle"></i> <span id="error-msg"></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <?php if (!$isPublicApiPage): ?>
        <div class="xl:col-span-4 space-y-6">
            
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 backdrop-blur-sm shadow-xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                <h3 class="text-lg font-bold mb-1"><?= __('Your Secret API Key') ?></h3>
                <p class="text-xs text-zinc-400 mb-6 leading-relaxed"><?= __('API Key Warning') ?></p>
                
                <div class="bg-zinc-950 border border-white/10 p-4 rounded-2xl flex items-center justify-between gap-3 mb-6">
                    <code id="key-display" class="text-sm text-emerald-400 font-mono truncate select-all"><?= htmlspecialchars($apiKey) ?></code>
                    <button onclick="copyKey(this)" class="text-zinc-400 hover:text-white transition shrink-0 bg-white/5 hover:bg-white/10 w-8 h-8 rounded-lg flex items-center justify-center">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>

                <button type="button" id="roll-btn" onclick="rollApiKey()" class="mb-3 w-full py-3 bg-zinc-800 hover:bg-red-500/20 text-zinc-300 hover:text-red-400 border border-white/5 hover:border-red-500/30 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                    <span id="roll-text"><i class="fas fa-redo text-xs"></i> Roll API Key</span>
                    <span id="roll-loading" class="hidden animate-spin h-4 w-4 border-2 border-current border-t-transparent rounded-full"></span>
                </button>
                
                <button onclick="downloadPostmanCollection()" class="w-full py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/10">
                    <i class="fas fa-file-download"></i> Download Postman Collection
                </button>
            </div>

            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 backdrop-blur-sm shadow-xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-emerald-500"></div>
                <h3 class="text-lg font-bold mb-1 text-white flex items-center gap-2"><i class="fas fa-wallet text-blue-400"></i> <?= __('Web3 Wallet Binding') ?></h3>
                <p class="text-xs text-zinc-400 mb-6 leading-relaxed"><?= __('Wallet Binding Desc') ?></p>
                
                <?php if ($nearWallet): ?>
                    <div class="bg-zinc-950 border border-emerald-500/30 p-4 rounded-2xl flex items-center justify-between gap-3 mb-2 shadow-inner">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" class="w-6 h-6 shrink-0 opacity-80" alt="NEAR">
                            <code class="text-sm text-emerald-400 font-mono truncate"><?= htmlspecialchars($nearWallet) ?></code>
                        </div>
                        <span class="text-[10px] text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded font-bold uppercase tracking-wider border border-emerald-500/20 shrink-0 shadow-sm"><i class="fas fa-lock mr-1"></i><?= __('Bound') ?></span>
                    </div>
                    <p class="text-[10px] text-zinc-500 mt-2 leading-relaxed"><i class="fas fa-shield-alt mr-1 text-zinc-600"></i> <?= __('Wallet cannot be changed') ?></p>
                <?php else: ?>
                    <div class="bg-blue-900/10 border border-blue-500/30 p-4 rounded-2xl mb-4 text-[11px] sm:text-xs text-blue-300 leading-relaxed flex items-start gap-2 shadow-inner">
                        <i class="fas fa-exclamation-triangle text-blue-400 mt-0.5 shrink-0"></i>
                        <div>
                            <strong class="text-blue-400 font-bold uppercase tracking-wide block mb-1"><?= __('Important Warning:') ?></strong> <?= __('Wallet one-time warning') ?>
                        </div>
                    </div>
                    
                    <button type="button" onclick="bindNearWallet()" id="bind-wallet-btn" class="w-full py-4 bg-gradient-to-r from-emerald-400 to-teal-500 text-zinc-950 font-black text-base rounded-2xl hover:brightness-110 transition flex items-center justify-center gap-3 shadow-[0_0_25px_rgba(52,211,153,0.25)] border-none group transform hover:-translate-y-0.5 duration-200 relative overflow-hidden">
                        <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" class="w-5 h-5 opacity-90 group-hover:scale-105 transition shrink-0" alt="NEAR"> 
                        <span id="bind-wallet-text"><?= __('Connect & Bind Wallet') ?></span>
                    </button>
                <?php endif; ?>
            </div>

            <?php require_once __DIR__ . '/../private/includes/my-api-byok.php'; ?>

            <?php if ($isAdmin): ?>
            <div class="bg-zinc-900/60 border border-amber-500/30 rounded-3xl p-6 backdrop-blur-sm shadow-xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-400 to-orange-500"></div>
                <h3 class="text-lg font-bold mb-1 text-white"><i class="fas fa-university text-amber-400 mr-2"></i><?= __('Platform Treasury') ?></h3>
                <p class="text-xs text-zinc-400 mb-6 leading-relaxed"><?= __('Treasury Desc') ?></p>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-amber-400 uppercase tracking-wider mb-1.5"><?= __('Amount to Swap (NEAR)') ?></label>
                        <input type="number" id="buyback-amount" placeholder="e.g. 50" step="0.1" class="w-full bg-zinc-950 border border-amber-500/20 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 text-white shadow-inner">
                    </div>
                    
                    <button type="button" onclick="triggerAutoBuyback()" class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-zinc-950 font-black rounded-xl transition shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2 transform hover:-translate-y-0.5 duration-200">
                        <i class="fas fa-fire"></i> <?= __('Trigger Buyback & Burn') ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <?php require_once __DIR__ . '/../private/includes/api-docs.php'; ?>

    </div>
</div>

<script>
    <?php if (!$isPublicApiPage): ?>
    window.addEventListener('DOMContentLoaded', async () => {
        <?php if (!$nearWallet): ?>
            // 🚀 採用與 login.php 完美一致的 URL 監聽回調機制，徹底移走舊版 Selector
            const wallet = await initNearWallet();
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('account_id') || urlParams.has('all_keys')) {
                setTimeout(async () => {
                    if (wallet.isSignedIn()) {
                        await executeWalletBind(wallet.getAccountId());
                    }
                }, 500);
            }
        <?php endif; ?>
    });

    async function bindNearWallet() {
        const btn = document.getElementById('bind-wallet-btn');
        const text = document.getElementById('bind-wallet-text');
        const originalText = text.innerText;
        text.innerText = '<?= addslashes(__('Connecting...')) ?>';
        btn.classList.add('opacity-50', 'pointer-events-none');

        try {
            const wallet = await initNearWallet();
            if (!wallet.isSignedIn()) {
                wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
            } else {
                await executeWalletBind(wallet.getAccountId());
            }
        } catch(e) {
            text.innerText = originalText;
            btn.classList.remove('opacity-50', 'pointer-events-none');
        }
    }

    async function executeWalletBind(accountId) {
        const text = document.getElementById('bind-wallet-text');
        if(text) text.innerText = '<?= addslashes(__('Binding...')) ?>';
        
        try {
            const res = await fetch('/api/bind-wallet', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'bind', wallet: accountId })
            });
            const data = await res.json();
            if (data.success) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
                window.location.reload();
            } else {
                showFeedbackNotification(false, data.error || 'Failed to bind wallet.');
                const wallet = await initNearWallet();
                wallet.signOut();
                if(text) text.innerText = '<?= addslashes(__('Connect & Bind Wallet')) ?>';
                const btn = document.getElementById('bind-wallet-btn');
                if(btn) btn.classList.remove('opacity-50', 'pointer-events-none');
            }
        } catch(e) {
            showFeedbackNotification(false, 'Network error while binding.');
            if(text) text.innerText = '<?= addslashes(__('Connect & Bind Wallet')) ?>';
            const btn = document.getElementById('bind-wallet-btn');
            if(btn) btn.classList.remove('opacity-50', 'pointer-events-none');
        }
    }

    function copyKey(btn) {
        const key = document.getElementById('key-display').innerText;
        navigator.clipboard.writeText(key).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i>';
            setTimeout(() => btn.innerHTML = original, 2000);
        });
    }

    async function rollApiKey() {
        if (!confirm('Are you sure you want to roll your API Key? All applications using the old key will lose access immediately.')) return;
        const btn = document.getElementById('roll-btn');
        const text = document.getElementById('roll-text');
        const loading = document.getElementById('roll-loading');
        const successBox = document.getElementById('success-box');
        const errorBox = document.getElementById('error-box');

        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        successBox.classList.add('hidden');
        errorBox.classList.add('hidden');

        try {
            const res = await fetch('/api/regenerate-key', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                document.getElementById('key-display').innerText = data.new_api_key;
                showFeedbackNotification(true, data.message);
            } else {
                showFeedbackNotification(false, data.error || 'Operation failed');
            }
        } catch(e) {
            showFeedbackNotification(false, 'Network error. Please try again.');
        } finally {
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    <?php if ($isAdmin): ?>
    async function triggerAutoBuyback() {
        const amount = document.getElementById('buyback-amount').value;
        if (!amount || amount <= 0) return alert('Please enter a valid NEAR amount.');
        if (!confirm(`Are you absolutely sure you want to trigger the Deflationary Spiral? \n\nThis will take ${amount} NEAR from the treasury and swap it into $SOUL to burn forever!`)) return;

        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) {
            alert("<?= addslashes(__('Please connect NEAR wallet first')) ?>");
            wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
            return;
        }

        try {
            const amountInYocto = nearApi.utils.format.parseNearAmount(amount.toString());
            await wallet.account().functionCall({
                contractId: "<?= NEAR_CONTRACT_ID; ?>",
                methodName: "auto_buyback_and_burn",
                args: { amount_in_near: amountInYocto },
                gas: "100000000000000",
                attachedDeposit: "0", 
                walletCallbackUrl: window.location.href
            });
        } catch(e) {
            alert("Blockchain transaction failed. Make sure you are logged in with the official Treasury account (<?= NEAR_CONTRACT_ID; ?>).");
        }
    }
    <?php endif; ?>
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../private/includes/api-postman.php'; ?>
<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>