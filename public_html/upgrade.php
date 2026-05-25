<?php
/**
 * SoulMD Hub Premium Upgrade Page
 * Fully parameterized with PayPal SDK and Prorated Tier detection (White-label Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$currentTier = $user['tier'] ?? 'free';
$expiresAt = $user['vip_expires_at'] ? strtotime($user['vip_expires_at']) : 0;
$isActivePremium = ($currentTier !== 'free' && $expiresAt > time());

$isVip = ($currentTier === 'vip' && $isActivePremium);
$isPro = ($currentTier === 'pro' && $isActivePremium);

$pageTitle = 'Upgrade Your AI Architecture - SoulMD Hub';
$pageDesc = 'Unlock unlimited turns, advanced deep reasoning, and multi-modal vision AI capabilities.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=USD&disable-funding=credit,card"></script>

<div class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-12 flex-grow flex flex-col">
    <div class="text-center mb-16">
        <div class="inline-flex items-center gap-2 bg-emerald-950/40 text-emerald-400 border border-emerald-500/20 px-4 py-1.5 rounded-full text-xs font-semibold mb-4 shadow-sm">
            <i class="fas fa-crown text-amber-400"></i> SoulMD Premium SaaS Ecosystem
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tighter mb-4">Upgrade Your <span class="gradient-text">AI Brain</span></h1>
        <p class="text-base text-zinc-400 max-w-2xl mx-auto leading-relaxed">
            Get unlimited messaging turns, longer context windows, real-time image analysis, and exclusive access to our brand-new <b>Advanced Smart Model</b> architecture.
        </p>
        
        <?php if ($isActivePremium): ?>
            <div class="mt-8 inline-flex flex-col items-center gap-1.5 bg-emerald-500/10 border border-emerald-500/20 px-6 py-3 rounded-2xl shadow-xl shadow-emerald-500/5 animate-fade-in">
                <span class="text-sm font-bold text-emerald-400 flex items-center gap-2">
                    <i class="fas fa-shield-check"></i> Active Tier: <?='@' . htmlspecialchars($_SESSION['username']) ?> is currently [<?= strtoupper($currentTier) ?>]
                </span>
                <span class="text-xs text-zinc-400 font-medium">
                    Subscription active until: <b class="text-zinc-200"><?= date('Y-m-d H:i', $expiresAt) ?></b>
                </span>
            </div>
            <?php if ($isVip): ?>
                <p class="text-xs text-amber-400/80 mt-3 max-w-md mx-auto leading-relaxed">
                    <i class="fas fa-info-circle"></i> <b>Prorated Upgrade Active:</b> Upgrading to PRO now will automatically convert your remaining VIP days into extra PRO balance!
                </p>
            <?php endif; ?>
            <div class="mt-4">
                <a href="/billing" class="text-xs text-emerald-400 hover:text-emerald-300 underline transition flex items-center justify-center gap-1">
                    <i class="fas fa-file-invoice-dollar"></i> View Billing History & Invoices
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div id="payment-status" class="hidden max-w-2xl mx-auto mb-10 p-5 rounded-2xl text-center border font-bold text-sm shadow-xl transition-all duration-300"></div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto items-stretch mb-12">
        
        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 flex flex-col hover:border-emerald-500/30 transition-all duration-300 shadow-xl backdrop-blur-sm justify-between">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="text-emerald-400 text-xs font-bold tracking-widest uppercase mb-1">Standard Plan</div>
                        <h3 class="text-2xl font-bold text-white tracking-tight">VIP Member</h3>
                    </div>
                    <?php if ($isVip): ?>
                        <span class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-lg text-[10px] font-bold uppercase tracking-wider">Current</span>
                    <?php endif; ?>
                </div>
                
                <div class="text-5xl font-black text-white mb-3 tracking-tight">$<?= PRICE_VIP_MONTHLY ?> <span class="text-sm text-zinc-500 font-normal tracking-normal">/ 30 Days</span></div>
                <p class="text-sm text-zinc-400 mb-6 pb-6 border-b border-white/5 leading-relaxed">Perfect for daily general interactions, prompt engineering testing, and standard multi-file bot testing.</p>
                
                <ul class="space-y-4 mb-8 text-sm text-zinc-300">
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center"></i> <span><b>Unlimited</b> turns per chat session</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center"></i> <span>Up to <b><?= number_format(VIP_MAX_INPUT_CHARS) ?></b> chars input length</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center"></i> <span><b>Vision AI</b>: JPG / PNG image understanding</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center"></i> <span>Smart sliding session memory retention</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-500 mt-0.5 shrink-0 w-4 text-center"></i> <span>Enable Private Mode switch lock</span></li>
                    <li class="flex items-start gap-3 opacity-40"><i class="fas fa-times text-zinc-600 mt-0.5 shrink-0 w-4 text-center"></i> <span class="line-through">Elite Reasoning Engine logic</span></li>
                </ul>
            </div>
            
            <div class="pt-4 border-t border-white/5 mt-auto">
                <?php if ($isVip || $isPro): ?>
                    <button disabled class="w-full py-3 bg-zinc-800/50 text-zinc-500 font-bold rounded-xl cursor-not-allowed border border-white/5 transition flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i> Included in your plan
                    </button>
                <?php else: ?>
                    <div id="paypal-button-container-vip" class="relative z-10 w-full"></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-gradient-to-b from-emerald-950/20 to-zinc-900/90 border border-emerald-500/40 rounded-3xl p-8 flex flex-col hover:border-emerald-400/70 transition-all duration-300 shadow-2xl shadow-emerald-500/5 relative justify-between transform md:-translate-y-4">
            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-emerald-500 to-teal-500 text-zinc-950 text-[10px] font-black px-4 py-1 rounded-full uppercase tracking-widest shadow-md shadow-emerald-500/10 border border-emerald-400">Ultimate Brain</div>
            
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="text-amber-400 text-xs font-bold tracking-widest uppercase mb-1">Advanced Plan</div>
                        <h3 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2"><i class="fas fa-fire text-amber-500 text-sm animate-pulse"></i> PRO Member</h3>
                    </div>
                    <?php if ($isPro): ?>
                        <span class="px-2.5 py-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 rounded-lg text-[10px] font-bold uppercase tracking-wider">Current</span>
                    <?php endif; ?>
                </div>
                
                <div class="text-5xl font-black text-white mb-3 tracking-tight">$<?= PRICE_PRO_MONTHLY ?> <span class="text-sm text-emerald-500/40 font-normal tracking-normal">/ 30 Days</span></div>
                <p class="text-sm text-emerald-100/70 mb-6 pb-6 border-b border-emerald-500/10 leading-relaxed">Designed for developers, quantitative quants, and power users requiring absolute complex logic and mathematical reasoning.</p>
                
                <ul class="space-y-4 mb-8 text-sm text-zinc-200">
                    <li class="flex items-start gap-3"><i class="fas fa-star text-amber-400 mt-0.5 shrink-0 w-4 text-center"></i> <span><b>Elite Reasoning Engine</b> Access</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center"></i> <span><b>Unlimited</b> advanced messages</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center"></i> <span>Massive <b><?= number_format(PRO_MAX_INPUT_CHARS) ?></b> chars per input</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center"></i> <span>Extended <?= number_format(PRO_MAX_AI_TOKENS) ?> tokens deep thinking output</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center"></i> <span>Multi-Modal Vision analysis at highest quality</span></li>
                    <li class="flex items-start gap-3"><i class="fas fa-check text-emerald-400 mt-0.5 shrink-0 w-4 text-center"></i> <span>Max memory snapshot retention (30 layers)</span></li>
                </ul>
            </div>
            
            <div class="pt-4 border-t border-emerald-500/10 mt-auto">
                <?php if ($isPro): ?>
                    <button disabled class="w-full py-3 bg-zinc-800/50 text-zinc-500 font-bold rounded-xl cursor-not-allowed border border-white/5 transition flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i> Highest Tier Reached
                    </button>
                <?php else: ?>
                    <div id="paypal-button-container-pro" class="relative z-10 w-full"></div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="max-w-3xl mx-auto mt-auto border-t border-white/5 pt-8 text-center">
        <p class="text-[11px] text-zinc-500 leading-relaxed">
            <strong class="text-zinc-400">Terms of Purchase & No Refund Policy:</strong><br>
            By proceeding with the payment, you agree to our Terms of Service. All transactions are final and processed securely via PayPal. 
            Because this service provides immediate access to digital digital API resources and premium server capacities, <span class="text-red-400/80 font-semibold">all payments are strictly non-refundable</span> under any circumstances, including partial usage or account termination.
        </p>
    </div>
</div>

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
                statusBox.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Verifying cryptographic transaction signature. Do not close this browser...';

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
                        statusBox.innerHTML = '<i class="fas fa-check-circle mr-2"></i> ' + orderData.message + '<br><span class="text-xs mt-1 block text-emerald-200/70">Syncing subscription tokens... Redirecting to dashboard now.</span>';
                        
                        setTimeout(() => { window.location.href = '/billing'; }, 2500); // 🚨 改為跳轉去帳單紀錄頁
                    } else {
                        statusBox.classList.replace('bg-blue-900/50', 'bg-red-900/50');
                        statusBox.classList.replace('text-blue-200', 'text-red-300');
                        statusBox.classList.replace('border-blue-500', 'border-red-500');
                        statusBox.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Integrity Verification Failed: ' + (orderData.error || 'Unknown error.');
                    }
                }).catch(function(err) {
                    statusBox.classList.replace('bg-blue-900/50', 'bg-red-900/50');
                    statusBox.classList.replace('text-blue-200', 'text-red-300');
                    statusBox.classList.replace('border-blue-500', 'border-red-500');
                    statusBox.innerHTML = '<i class="fas fa-wifi mr-2"></i> Connection handshake timeout. Please secure your connection or provide order token.';
                });
            }
        }).render('#' + containerId);
    }

    renderPayPalButton('paypal-button-container-vip', 'vip', '<?= PRICE_VIP_MONTHLY ?>');
    renderPayPalButton('paypal-button-container-pro', 'pro', '<?= PRICE_PRO_MONTHLY ?>');
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>