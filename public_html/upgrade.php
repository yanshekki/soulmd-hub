<?php
/**
 * SoulMD Hub Premium Upgrade Page
 * Fully parameterized with PayPal SDK and Prorated Tier detection
 * (Dynamic i18n Internationalization & Mobile UX Responsive Fixed Edition)
 * 🚀 V5 SEO Optimized: Semantic Pricing Sections, a11y Enhancements, and SaaS Keywords
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

loadTranslations('upgrade');

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT tier, vip_expires_at, username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$currentTier = $user['tier'] ?? 'free';
$expiresAt = $user['vip_expires_at'] ? strtotime($user['vip_expires_at']) : 0;

$isActivePremium = ($currentTier !== 'free' && $expiresAt > time());
$isVip = ($currentTier === 'vip' && $isActivePremium);
$isPro = ($currentTier === 'pro' && $isActivePremium);

// SEO Meta
$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');

require_once __DIR__ . '/../private/includes/header.php';
<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; // PoC: for USDT/USDC on-chain payments via existing wallet bridge ?>
?>

<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=USD&disable-funding=credit,card"></script>

<main class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12 flex-grow flex flex-col">
    
    <header class="text-center mb-10 sm:mb-16">
        <div class="inline-flex items-center gap-2 bg-emerald-950/40 text-emerald-400 border border-emerald-500/20 px-3 sm:px-4 py-1.5 rounded-full text-[10px] sm:text-xs font-semibold mb-4 shadow-sm">
            <i class="fas fa-crown text-amber-400" aria-hidden="true"></i> <?= __('SoulMD Premium SaaS Ecosystem') ?>
        </div>
        <h1 id="pricing-heading" class="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tighter mb-4 leading-tight"><?= __('Upgrade Your') ?> <span class="gradient-text"><?= __('AI Brain') ?></span></h1>
        <p class="text-sm sm:text-base text-zinc-400 max-w-2xl mx-auto leading-relaxed px-2">
            <?= __('Upgrade Subtitle') ?>
        </p>
        
        <?php if ($isActivePremium): ?>
            <div class="mt-6 sm:mt-8 inline-flex flex-col items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 px-5 sm:px-6 py-4 sm:py-3 rounded-2xl shadow-xl shadow-emerald-500/5 animate-fade-in w-full sm:w-auto">
                <span class="text-xs sm:text-sm font-bold text-emerald-400 flex items-center justify-center gap-2 text-center leading-tight">
                    <i class="fas fa-shield-check" aria-hidden="true"></i> 
                    <?= __('Active Tier', ['username' => htmlspecialchars($user['username']), 'tier' => strtoupper($currentTier)]) ?>
                </span>
                <span class="text-[10px] sm:text-xs text-zinc-400 font-medium text-center">
                    <?= __('Subscription active until', ['date' => date('Y-m-d H:i', $expiresAt)]) ?>
                </span>
            </div>
            
            <?php if ($isVip): ?>
                <p class="text-[10px] sm:text-xs text-amber-400/80 mt-4 max-w-md mx-auto leading-relaxed px-4">
                    <i class="fas fa-info-circle" aria-hidden="true"></i> <?= __('Prorated Upgrade Active') ?>
                </p>
            <?php endif; ?>

            <div class="mt-5">
                <a href="<?= url('/billing') ?>" class="text-xs text-emerald-400 hover:text-emerald-300 underline transition flex items-center justify-center gap-1.5">
                    <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> <?= __('View Billing History') ?>
                </a>
            </div>
        <?php endif; ?>
    </header>

    <div id="payment-status" aria-live="assertive" class="hidden max-w-2xl mx-auto mb-10 p-5 rounded-2xl text-center border font-bold text-sm shadow-xl transition-all duration-300 mx-4 sm:mx-auto"></div>

    <section aria-labelledby="pricing-heading" class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8 max-w-4xl mx-auto items-stretch mb-12 w-full">
        
        <!-- Standard Plan (VIP) -->
        <article class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 flex flex-col hover:border-emerald-500/30 transition-all duration-300 shadow-xl backdrop-blur-sm justify-between">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="text-emerald-400 text-[10px] sm:text-xs font-bold tracking-widest uppercase mb-1"><?= __('Standard Plan') ?></div>
                        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight"><?= __('VIP Member') ?></h2>
                    </div>
                    <?php if ($isVip): ?>
                        <span class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-lg text-[10px] font-bold uppercase tracking-wider"><?= __('Current') ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="text-4xl sm:text-5xl font-black text-white mb-3 tracking-tight">$<?= PRICE_VIP_MONTHLY ?> <span class="text-xs sm:text-sm text-zinc-500 font-normal tracking-normal"><?= __('/ 30 Days') ?></span></div>
                <p class="text-xs sm:text-sm text-zinc-400 mb-6 pb-6 border-b border-white/5 leading-relaxed"><?= __('VIP Desc') ?></p>
                
                <ul class="space-y-4 mb-8 text-xs sm:text-sm text-zinc-300" role="list">
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Unlimited turns per session') ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Up to chars input', ['chars' => number_format(VIP_MAX_INPUT_CHARS)]) ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Vision AI') ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Smart sliding memory retention') ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Private Mode switch lock') ?></span></li>
                    <li class="flex items-start gap-3 opacity-40"><i class="fas fa-times text-zinc-600 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span class="line-through"><?= __('Elite Reasoning Engine') ?></span></li>
                </ul>
            </div>
            
            <div class="pt-4 border-t border-white/5 mt-auto">
                <?php if ($isVip || $isPro): ?>
                    <button disabled aria-label="Already Subscribed" class="w-full py-3 bg-zinc-800/50 text-zinc-500 font-bold rounded-xl cursor-not-allowed border border-white/5 transition flex items-center justify-center gap-2 text-sm">
                        <i class="fas fa-check-circle" aria-hidden="true"></i> <?= __('Included in plan') ?>
                    </button>
                <?php else: ?>
                    <div id="paypal-button-container-vip" class="relative z-10 w-full min-h-[45px]"></div>
                <?php endif; ?>
            </div>
        </article>

        <!-- Advanced Plan (PRO) -->
        <article class="bg-gradient-to-b from-emerald-950/20 to-zinc-900/90 border border-emerald-500/40 rounded-3xl p-6 sm:p-8 flex flex-col hover:border-emerald-400/70 transition-all duration-300 shadow-2xl shadow-emerald-500/5 relative justify-between transform md:-translate-y-4 mt-5 sm:mt-0">
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-emerald-500 to-teal-500 text-zinc-950 text-[9px] sm:text-[10px] font-black px-3 sm:px-4 py-1 rounded-full uppercase tracking-widest shadow-md shadow-emerald-500/10 border border-emerald-400 whitespace-nowrap"><?= __('Ultimate Brain') ?></div>
            
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="text-amber-400 text-[10px] sm:text-xs font-bold tracking-widest uppercase mb-1"><?= __('Advanced Plan') ?></div>
                        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight flex items-center gap-1.5 sm:gap-2"><i class="fas fa-fire text-amber-500 text-sm animate-pulse" aria-hidden="true"></i> <?= __('PRO Member') ?></h2>
                    </div>
                    <?php if ($isPro): ?>
                        <span class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-lg text-[10px] font-bold uppercase tracking-wider"><?= __('Current') ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="text-4xl sm:text-5xl font-black text-white mb-3 tracking-tight">$<?= PRICE_PRO_MONTHLY ?> <span class="text-xs sm:text-sm text-emerald-500/40 font-normal tracking-normal"><?= __('/ 30 Days') ?></span></div>
                <p class="text-xs sm:text-sm text-emerald-100/70 mb-6 pb-6 border-b border-emerald-500/10 leading-relaxed"><?= __('PRO Desc') ?></p>
                
                <ul class="space-y-4 mb-8 text-xs sm:text-sm text-zinc-200" role="list">
                    <li class="flex items-start gap-3"><i class="fas fa-star text-amber-400 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Elite Reasoning Engine Access') ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Unlimited advanced messages') ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Massive chars chars', ['chars' => number_format(PRO_MAX_INPUT_CHARS)]) ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Extended tokens output', ['tokens' => number_format(PRO_MAX_AI_TOKENS)]) ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Multi-Modal Vision at highest quality') ?></span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center" aria-hidden="true"></i> <span><?= __('Max memory retention (30 layers)') ?></span></li>
                </ul>
            </div>
            
            <div class="pt-4 border-t border-emerald-500/10 mt-auto">
                <?php if ($isPro): ?>
                    <button disabled aria-label="Highest Tier Reached" class="w-full py-3 bg-zinc-800/50 text-zinc-500 font-bold rounded-xl cursor-not-allowed border border-white/5 transition flex items-center justify-center gap-2 text-sm">
                        <i class="fas fa-check-circle" aria-hidden="true"></i> <?= __('Highest Tier Reached') ?>
                    </button>
                <?php else: ?>
                    <div id="paypal-button-container-pro" class="relative z-10 w-full min-h-[45px]"></div>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <!-- =====================================================
         PoC: On-chain USDT / USDC payments via NEAR (replacing PayPal)
         Uses the shared near-wallet-scripts (wallet connect + ft_transfer_call)
         Hardcoded demo pricing: VIP=5 USDT/USDC, PRO=15 (6 decimals)
         Flow: user selects token → signs ft_transfer_call to Soul contract with msg="upgrade:vip"
         Contract grants credit (ft_on_transfer) → frontend claims → updates DB (same as PayPal)
    ===================================================== -->
    <section class="max-w-4xl mx-auto mt-8 mb-12 border border-amber-500/30 bg-amber-950/10 rounded-3xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-4">
            <i class="fas fa-link text-amber-400 text-xl"></i>
            <h3 class="text-xl font-bold text-white">Pay with USDT / USDC on NEAR <span class="text-[10px] px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded font-mono">PoC</span></h3>
        </div>
        <p class="text-sm text-zinc-400 mb-6">Connect your NEAR wallet (already used for Marketplace) and pay directly on-chain. Lower fees, instant, no PayPal. <strong>This is a working Proof-of-Concept</strong> — amounts are demo USD equivalents.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- VIP NEAR PoC -->
            <div class="bg-zinc-900/70 border border-white/10 rounded-2xl p-5">
                <div class="flex justify-between mb-3">
                    <div>
                        <div class="text-emerald-400 text-xs font-bold">STANDARD</div>
                        <div class="font-bold">VIP — 5 USDT or USDC</div>
                    </div>
                    <?php if ($isVip): ?><span class="text-xs text-emerald-400">Current</span><?php endif; ?>
                </div>
                <div class="flex gap-2 mb-3">
                    <button onclick="payWithNearFt('vip', 'usdt')" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition">Pay 5 USDT</button>
                    <button onclick="payWithNearFt('vip', 'usdc')" class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition">Pay 5 USDC</button>
                </div>
                <div class="text-[10px] text-zinc-500">Uses ft_transfer_call to contract. Credit verified on-chain.</div>
            </div>

            <!-- PRO NEAR PoC -->
            <div class="bg-zinc-900/70 border border-white/10 rounded-2xl p-5">
                <div class="flex justify-between mb-3">
                    <div>
                        <div class="text-amber-400 text-xs font-bold">ADVANCED</div>
                        <div class="font-bold">PRO — 15 USDT or USDC</div>
                    </div>
                    <?php if ($isPro): ?><span class="text-xs text-emerald-400">Current</span><?php endif; ?>
                </div>
                <div class="flex gap-2 mb-3">
                    <button onclick="payWithNearFt('pro', 'usdt')" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl transition">Pay 15 USDT</button>
                    <button onclick="payWithNearFt('pro', 'usdc')" class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition">Pay 15 USDC</button>
                </div>
                <div class="text-[10px] text-zinc-500">Same on-chain flow. Platform receives the stablecoins.</div>
            </div>
        </div>

        <div id="near-poc-status" class="mt-4 hidden p-3 rounded-xl text-sm font-medium"></div>
    </section>

    <footer class="max-w-3xl mx-auto mt-auto border-t border-white/5 pt-6 sm:pt-8 text-center">
        <p class="text-[10px] sm:text-[11px] text-zinc-500 leading-relaxed px-2">
            <strong class="text-zinc-400"><?= __('Terms of Purchase & No Refund Policy:') ?></strong><br>
            <?= __('Legal Text') ?>
        </p>
    </footer>
</main>

<script>
    function renderPayPalButton(containerId, tierName, priceStr) {
        if (!document.getElementById(containerId)) return;

        paypal.Buttons({
            style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'paypal' },
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        description: `SoulMD Hub - ${tierName.toUpperCase()} License Pass (30 Days)`,
                        amount: { currency_code: 'USD', value: priceStr }
                    }]
                });
            },
            onApprove: function(data, actions) {
                const statusBox = document.getElementById('payment-status');
                statusBox.classList.remove('hidden', 'bg-red-900/50', 'text-red-200', 'border-red-500', 'bg-emerald-900/50', 'text-emerald-400', 'border-emerald-500');
                statusBox.classList.add('bg-blue-900/50', 'text-blue-200', 'border', 'border-blue-500', 'block');
                
                // Loading State
                statusBox.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> <?= addslashes(__('Verifying transaction...')) ?>';
                statusBox.scrollIntoView({ behavior: 'smooth', block: 'center' });

                return fetch('/api/paypal', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ orderID: data.orderID, tier: tierName })
                }).then(function(res) {
                    return res.json();
                }).then(function(orderData) {
                    if (orderData.success) {
                        statusBox.classList.replace('bg-blue-900/50', 'bg-emerald-900/50');
                        statusBox.classList.replace('text-blue-200', 'text-emerald-400');
                        statusBox.classList.replace('border-blue-500', 'border-emerald-500');
                        
                        // Success State
                        statusBox.innerHTML = '<i class="fas fa-check-circle mr-2"></i> ' + orderData.message + '<br><span class="text-xs sm:text-sm mt-2 block text-emerald-200/70"><i class="fas fa-sync fa-spin mr-1"></i><?= addslashes(__('Syncing subscription...')) ?></span>';
                        
                        setTimeout(() => { window.location.href = '<?= url("/billing") ?>'; }, 2500);
                    } else {
                        statusBox.classList.replace('bg-blue-900/50', 'bg-red-900/50');
                        statusBox.classList.replace('text-blue-200', 'text-red-300');
                        statusBox.classList.replace('border-blue-500', 'border-red-500');
                        statusBox.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> <?= addslashes(__('Integrity Verification Failed:')) ?> ' + (orderData.error || '<?= addslashes(__('Unknown error.')) ?>');
                    }
                }).catch(function(err) {
                    statusBox.classList.replace('bg-blue-900/50', 'bg-red-900/50');
                    statusBox.classList.replace('text-blue-200', 'text-red-300');
                    statusBox.classList.replace('border-blue-500', 'border-red-500');
                    statusBox.innerHTML = '<i class="fas fa-wifi mr-2"></i> <?= addslashes(__('Connection timeout.')) ?>';
                });
            }
        }).render('#' + containerId);
    }

    renderPayPalButton('paypal-button-container-vip', 'vip', '<?= PRICE_VIP_MONTHLY ?>');
    renderPayPalButton('paypal-button-container-pro', 'pro', '<?= PRICE_PRO_MONTHLY ?>');

    // =====================================================
    // PoC JS: NEAR FT (USDT/USDC) payment for upgrades
    // Requires near-wallet-scripts.php (initNearWallet, window.nearApi, getErrorMessage)
    // =====================================================
    const NEAR_USDT = 'usdt.tether-token.near';
    const NEAR_USDC = '17208628f84f5d6ad33f0da3bbbeb27ffcb398eac501a31bd6ad2011e36133a1'; // VERIFY on explorer.near.org

    async function payWithNearFt(tier, tokenKey) {
        const status = document.getElementById('near-poc-status');
        status.className = 'mt-4 p-3 rounded-xl text-sm font-medium bg-blue-900/40 text-blue-200 border border-blue-500/30';
        status.style.display = 'block';
        status.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Connecting NEAR wallet and preparing FT transfer...';

        try {
            const wrapper = await window.initNearWallet();
            if (!wrapper || !wrapper.isSignedIn()) {
                await window.connectOrBindWallet();
                return; // will reload or user signs in
            }

            const tokenContract = (tokenKey === 'usdt') ? NEAR_USDT : NEAR_USDC;
            const amount = (tier === 'vip') ? '5000000' : '15000000'; // 5.000000 or 15.000000 (6 decimals)
            const msg = `upgrade:${tier}`;

            status.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Requesting signature for ${amount} ${tokenKey.toUpperCase()} → contract (msg: ${msg}) ...`;

            // Use the existing robust wrapper to call the TOKEN contract's ft_transfer_call
            // (exactly like how marketplace does storage + ft_transfer_call for swaps)
            await wrapper.account().functionCall({
                contractId: tokenContract,
                methodName: 'ft_transfer_call',
                args: {
                    receiver_id: '<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>',
                    amount: amount,
                    msg: msg
                },
                gas: '300000000000000',   // higher gas for ft cross-contract
                attachedDeposit: '1'      // required 1 yoctoNEAR for ft_transfer_call
            });

            status.className = 'mt-4 p-3 rounded-xl text-sm font-medium bg-emerald-900/40 text-emerald-200 border border-emerald-500/30';
            status.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Transaction signed! Checking on-chain credit...';

            // For PoC: immediately call claim (in prod: you can poll tx result or use callbackUrl + backend verification)
            await claimNearUpgrade(tier, tokenKey, status);

        } catch (e) {
            console.error('NEAR FT PoC payment error:', e);
            status.className = 'mt-4 p-3 rounded-xl text-sm font-medium bg-red-900/40 text-red-200 border border-red-500/30';
            status.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> ' + (window.getErrorMessage ? window.getErrorMessage(e) : (e.message || 'Payment failed'));
        }
    }

    async function claimNearUpgrade(tier, tokenKey, statusEl) {
        try {
            const res = await fetch('/api/near-upgrade', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tier: tier, token: tokenKey })
            });
            const data = await res.json();

            if (data.success) {
                statusEl.className = 'mt-4 p-3 rounded-xl text-sm font-medium bg-emerald-900/50 text-emerald-300 border border-emerald-400';
                statusEl.innerHTML = '<i class="fas fa-check-double mr-2"></i> On-chain payment verified! ' + (data.message || 'Upgraded.') + '<br><span class="text-xs">Redirecting to billing...</span>';
                setTimeout(() => { window.location.href = '<?= url("/billing") ?>'; }, 2200);
            } else {
                statusEl.className = 'mt-4 p-3 rounded-xl text-sm font-medium bg-amber-900/40 text-amber-200 border border-amber-500/30';
                statusEl.innerHTML = '<i class="fas fa-info-circle mr-2"></i> Payment received on-chain but claim needs review: ' + (data.error || 'Please contact support with your NEAR tx.');
            }
        } catch (err) {
            statusEl.innerHTML = 'On-chain payment succeeded. Please refresh or go to Billing to see updated status. (PoC claim API may need manual verification)';
        }
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>