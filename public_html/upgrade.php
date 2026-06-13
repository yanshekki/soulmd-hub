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
require_once __DIR__ . '/../private/src/ApiSecurity.php';

// STRICT: All NEAR FT token contract addresses MUST come from config.php only.
// No hardcoded fallbacks allowed anywhere in the payment flow (per security policy).
if (!defined('NEAR_USDT_CONTRACT') || !defined('NEAR_USDC_CONTRACT')) {
    die('FATAL CONFIG ERROR: NEAR_USDT_CONTRACT and NEAR_USDC_CONTRACT must be defined in private/config.php (see config.example.php for values).');
}

session_start();

// Centralized CSRF token (replaces repeated bin2hex block)
$csrfToken = ensureCsrfToken();

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
?>
<?php
// Include the shared NEAR wallet bridge (it outputs the <link> for styles + <script> for the bundle + the big inline wallet init code).
// We emit it here so the wallet functions (initNearWallet, getErrorMessage, etc.) are available for the on-chain payment buttons.
require_once __DIR__ . '/../private/includes/near-wallet-scripts.php';
?>
<!-- PayPal SDK hidden for now. Users pay via NEAR USDT/USDC on-chain instead.
<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=USD&disable-funding=credit,card"></script>
-->

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
                    <!-- PayPal buttons hidden. Users now pay via NEAR USDT/USDC on-chain (see section below) -->
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
                    <!-- PayPal buttons hidden. Users now pay via NEAR USDT/USDC on-chain (see section below) -->
                <?php endif; ?>
            </div>
        </article>
    </section>

    <!-- On-chain payment section: Pay with USDT / USDC directly on NEAR (using the same wallet you already use for Marketplace) -->
    <section aria-labelledby="near-payments-heading" class="max-w-4xl mx-auto mt-10 mb-14 border border-amber-500/30 bg-amber-950/10 rounded-3xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-3">
            <i class="fas fa-wallet text-amber-400" aria-hidden="true"></i>
            <h3 id="near-payments-heading" class="text-xl font-bold text-white tracking-tight"><?= __('Pay with USDT or USDC on NEAR') ?></h3>
        </div>
        <p class="text-sm text-zinc-400 mb-5 max-w-3xl">
            <?= __('Connect the NEAR wallet you already bound for the Marketplace and pay the exact stablecoin amount on-chain. Lower fees, instant. The contract records a credit that we verify on-chain before applying your tier.') ?>
        </p>

        <div class="mb-4 p-3 rounded-xl bg-amber-900/20 border border-amber-500/30 text-amber-300 text-xs">
            ⚠️ <strong>Important for multiple payments:</strong> The on-chain credit is a single slot per tier. Pay → wait for the success redirect + claim to complete before sending another USDT/USDC payment if you want to stack time. Paying a second time before claiming will overwrite the previous credit proof (you will have paid for both but only the last one will be claimable). Each distinct successful payment can be claimed once.
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- VIP on NEAR -->
            <div class="bg-zinc-900/70 border border-white/10 rounded-2xl p-5 flex flex-col">
                <div class="mb-3">
                    <div class="text-emerald-400 text-xs font-bold tracking-widest">STANDARD</div>
                    <div class="font-bold text-lg">VIP — $<?= NEAR_UPGRADE_VIP_USD_AMOUNT ?> USDT or USDC (30 days)</div>
                </div>
                <div class="flex gap-2 mt-auto">
                    <button onclick="payWithNearFt('vip','usdt')" class="flex-1 py-2.5 bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white text-sm font-bold rounded-xl transition"><?= sprintf(__('Pay with %s USDT'), NEAR_UPGRADE_VIP_USD_AMOUNT) ?></button>
                    <button onclick="payWithNearFt('vip','usdc')" class="flex-1 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white text-sm font-bold rounded-xl transition"><?= sprintf(__('Pay with %s USDC'), NEAR_UPGRADE_VIP_USD_AMOUNT) ?></button>
                </div>
            </div>

            <!-- PRO on NEAR -->
            <div class="bg-zinc-900/70 border border-white/10 rounded-2xl p-5 flex flex-col">
                <div class="mb-3">
                    <div class="text-amber-400 text-xs font-bold tracking-widest">ADVANCED</div>
                    <div class="font-bold text-lg">PRO — $<?= NEAR_UPGRADE_PRO_USD_AMOUNT ?> USDT or USDC (30 days)</div>
                </div>
                <div class="flex gap-2 mt-auto">
                    <button onclick="payWithNearFt('pro','usdt')" class="flex-1 py-2.5 bg-sky-600 hover:bg-sky-500 active:bg-sky-700 text-white text-sm font-bold rounded-xl transition"><?= sprintf(__('Pay with %s USDT'), NEAR_UPGRADE_PRO_USD_AMOUNT) ?></button>
                    <button onclick="payWithNearFt('pro','usdc')" class="flex-1 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white text-sm font-bold rounded-xl transition"><?= sprintf(__('Pay with %s USDC'), NEAR_UPGRADE_PRO_USD_AMOUNT) ?></button>
                </div>
            </div>
        </div>

        <div id="near-payment-status" class="hidden mt-4 p-3 rounded-2xl text-sm font-medium border" aria-live="polite"></div>

        <!-- Manual Claim for users who paid on-chain but frontend claim failed due to wallet errors -->
        <div class="mt-8 p-5 bg-zinc-900/70 border border-white/10 rounded-3xl">
            <div class="flex items-start gap-3">
                <i class="fas fa-wallet text-emerald-400 mt-1" aria-hidden="true"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-lg mb-1">Already sent USDT or USDC on-chain?</h3>
                    <p class="text-sm text-zinc-400 mb-3">
                        If your <code>ft_transfer_call</code> succeeded on the blockchain (you can check on nearblocks.io) but this page showed an error (e.g. wallet "Request validation error"), click below to claim. Manual claim only works for credits granted in the last hour.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="manualClaimNear('vip')" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white text-sm font-bold rounded-2xl transition flex items-center gap-2">
                            <i class="fas fa-check-circle"></i> Claim VIP upgrade
                        </button>
                        <button onclick="manualClaimNear('pro')" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-500 active:bg-amber-700 text-white text-sm font-bold rounded-2xl transition flex items-center gap-2">
                            <i class="fas fa-check-circle"></i> Claim PRO upgrade
                        </button>
                    </div>
                    <p class="mt-2 text-[10px] text-zinc-500">You must be logged in with the NEAR wallet that sent the payment. The system will verify the on-chain credit automatically.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="max-w-3xl mx-auto mt-auto border-t border-white/5 pt-6 sm:pt-8 text-center">
        <p class="text-[10px] sm:text-[11px] text-zinc-500 leading-relaxed px-2">
            <strong class="text-zinc-400"><?= __('Terms of Purchase & No Refund Policy:') ?></strong><br>
            <?= __('Legal Text') ?>
        </p>
    </footer>
</main>

<script>
    // PayPal integration hidden. Users now pay exclusively via NEAR USDT/USDC on-chain (see NEAR section below).
    /*
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
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' },
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

    // renderPayPalButton('paypal-button-container-vip', 'vip', '<?= PRICE_VIP_MONTHLY ?>');
    // renderPayPalButton('paypal-button-container-pro', 'pro', '<?= PRICE_PRO_MONTHLY ?>');
    */

    // ============================================================
    // On-chain NEAR USDT/USDC payment JS (strict version)
    // - ALL token contract addresses come exclusively from private/config.php via NEAR_USDT_CONTRACT / NEAR_USDC_CONTRACT.
    // - No hardcoded addresses allowed in this file (enforced at PHP require time + runtime guard).
    // - After successful ft_transfer_call we call the claim API which does REAL on-chain verification.
    // ============================================================
    const NEAR_USDT_CONTRACT = '<?= NEAR_USDT_CONTRACT ?>';
    const NEAR_USDC_CONTRACT = '<?= NEAR_USDC_CONTRACT ?>';

    async function payWithNearFt(tier, token) {
        const status = document.getElementById('near-payment-status');
        if (!status) return;

        status.style.display = 'block';
        status.className = 'mt-4 p-3 rounded-2xl text-sm font-medium border bg-blue-900/30 text-blue-200 border-blue-500/40';
        status.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Initializing NEAR wallet...';

        try {
            const wrapper = await window.initNearWallet();
            if (!wrapper || !wrapper.getAccountId()) {
                status.innerHTML = '<i class="fas fa-plug mr-2"></i> Please connect your NEAR wallet first...';
                await window.connectOrBindWallet();
                return;
            }

            const tokenContract = (token === 'usdt') ? NEAR_USDT_CONTRACT : NEAR_USDC_CONTRACT;
            const hubContract = '<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>';
            const amount = (tier === 'vip') ? '<?= NEAR_UPGRADE_VIP_USD_AMOUNT * 1000000 ?>' : '<?= NEAR_UPGRADE_PRO_USD_AMOUNT * 1000000 ?>'; // raw on-chain units (6 decimals)
            const displayAmount = (tier === 'vip') ? '<?= NEAR_UPGRADE_VIP_USD_AMOUNT ?>' : '<?= NEAR_UPGRADE_PRO_USD_AMOUNT ?>';
            const msg = `upgrade:${tier}`;

            // Runtime guard (defense in depth): if config was set to the wrong value (e.g. accidentally pointed at the app contract),
            // refuse to construct the transaction. This is the only place in the payment flow that is allowed to decide the target.
            if (!tokenContract || tokenContract === hubContract) {
                throw new Error('Payment misconfiguration: token contract for ' + token.toUpperCase() + ' resolved to "' + (tokenContract || '') + '". Check NEAR_USDT_CONTRACT / NEAR_USDC_CONTRACT in private/config.php.');
            }

            status.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> Requesting signature — sending $${displayAmount} worth of ${token.toUpperCase()} via ${tokenContract} (msg: ${msg})...`;

            // Use requestSignTransactions with explicit per-tx receiverId instead of account().functionCall.
            // This gives us direct control over the transaction receiver (the token contract),
            // and is the same pattern used elsewhere in the project for FT operations (e.g. marketplace swap).
            // It avoids some quirks in the single functionCall path + custom bundle's NAJ handling
            // that were causing either "Unsupported NAJ action" or the wallet UI showing the wrong "To".
            //
            // The actual on-chain transaction will have receiverId = tokenContract (usdt or usdc),
            // with the ft_transfer_call args telling it to forward to soulmd-hub.near with the upgrade msg.
            let payResult = null;
            let shouldProceedToClaim = false;

            try {
                payResult = await wrapper.requestSignTransactions({
                    transactions: [{
                        receiverId: tokenContract,
                        actions: [{
                            methodName: 'ft_transfer_call',
                            args: {
                                receiver_id: hubContract,
                                amount: amount,
                                msg: msg
                            },
                            gas: '300000000000000',
                            deposit: '1'
                        }]
                    }]
                });

                // Some wallet implementations resolve even on on-chain ActionError (e.g. MethodNotFound, insufficient storage, panic in ft_on_transfer).
                // Inspect common shapes so we fail fast with a clear message instead of proceeding to claim.
                const outcomeFailed = payResult && (
                    payResult.status?.Failure ||
                    payResult.error ||
                    (payResult.receipts_outcome && Array.isArray(payResult.receipts_outcome) && payResult.receipts_outcome.some(r => r?.outcome?.status?.Failure)) ||
                    (typeof payResult.status === 'object' && 'Failure' in payResult.status)
                );
                if (outcomeFailed) {
                    const errMsg = (window.getErrorMessage ? window.getErrorMessage(payResult) : 'On-chain transaction failed (see explorer for details)');
                    throw new Error(errMsg);
                }

                shouldProceedToClaim = true;
                status.className = 'mt-4 p-3 rounded-2xl text-sm font-medium border bg-emerald-900/30 text-emerald-200 border-emerald-500/40';
                status.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Transaction signed on-chain. Verifying credit...';

            } catch (signErr) {
                console.error('NEAR FT sign error', signErr);
                const rawMsg = (window.getErrorMessage ? window.getErrorMessage(signErr) : (signErr && signErr.message) || 'Unknown sign error');
                const lower = rawMsg.toLowerCase();

                // Some wallets (e.g. HereWallet) throw "Request validation error", "Transaction not found, but maybe executed",
                // or ProviderError even when the tx actually landed on-chain and executed successfully.
                // In those cases we still want to attempt the claim (which does independent on-chain credit verification).
                if (lower.includes('validation') || lower.includes('not found') || lower.includes('executed') ||
                    lower.includes('providererror') || lower.includes('request validation error')) {
                    shouldProceedToClaim = true;
                    status.className = 'mt-4 p-3 rounded-2xl text-sm font-medium border bg-amber-900/30 text-amber-200 border-amber-500/40';
                    status.innerHTML = `<i class="fas fa-info-circle mr-2"></i> Transaction was submitted (wallet had trouble confirming the result). Checking for on-chain credit now... (or use the buttons below)`;
                } else {
                    status.className = 'mt-4 p-3 rounded-2xl text-sm font-medium border bg-red-900/30 text-red-200 border-red-500/40';
                    status.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i> ${rawMsg}`;
                    throw signErr; // rethrow to outer catch so we don't claim
                }
            }

            if (shouldProceedToClaim) {
                // Strict claim (the API will do the real view_call proof + exact credit ts binding)
                // Pass payResult for potential tx hash verification (if available in outcome)
                await claimNearUpgrade(tier, token, status, payResult);
            }

        } catch (e) {
            console.error('NEAR FT payment error', e);
            status.className = 'mt-4 p-3 rounded-2xl text-sm font-medium border bg-red-900/30 text-red-200 border-red-500/40';
            const msg = (window.getErrorMessage ? window.getErrorMessage(e) : (e && e.message) || 'Unknown error');
            status.innerHTML = `<i class="fas fa-exclamation-triangle mr-2"></i> ${msg}`;
        }
    }

    async function claimNearUpgrade(tier, token, statusEl, payResult = null) {
        try {
            const payload = { tier, token };
            if (payResult) payload.tx = payResult; // pass for future/server-side tx verification
            const res = await fetch('/api/near-upgrade', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                statusEl.className = 'mt-4 p-3 rounded-2xl text-sm font-medium border bg-emerald-900/50 text-emerald-300 border-emerald-400';
                statusEl.innerHTML = `<i class="fas fa-check-double mr-2"></i> On-chain payment verified! ${data.message || 'Tier upgraded.'} Redirecting...`;
                setTimeout(() => { window.location.href = '<?= url('/billing') ?>'; }, 2200);
            } else {
                statusEl.className = 'mt-4 p-3 rounded-2xl text-sm font-medium border bg-amber-900/30 text-amber-200 border-amber-500/40';
                statusEl.innerHTML = `<i class="fas fa-info-circle mr-2"></i> ${data.error || 'On-chain credit not yet visible or already claimed. Please refresh Billing in a moment.'}`;
            }
        } catch (err) {
            statusEl.innerHTML = 'Transaction succeeded on-chain. The credit may take a moment to appear. Use the "Already sent USDT or USDC on-chain?" buttons below.';
        }
    }

    // Manual claim for users who paid successfully on-chain (ft_transfer_call succeeded)
    // but the automatic claim after signing failed due to wallet confirmation issues
    // (e.g. "ProviderError: Request validation error", "Transaction not found but maybe executed").
    // The claim API is safe to call multiple times; it will only succeed once per credit.
    async function manualClaimNear(tier) {
        const status = document.getElementById('near-payment-status');
        if (!status) return;

        status.style.display = 'block';
        status.className = 'mt-4 p-3 rounded-2xl text-sm font-medium border bg-blue-900/30 text-blue-200 border-blue-500/40';
        status.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Checking on-chain credit for your bound NEAR wallet...';

        // token is only for logging/display; the server uses the user's bound near_wallet_address
        await claimNearUpgrade(tier, 'usdt', status, null);
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>