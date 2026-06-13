<?php
/**
 * SoulMD Hub - Billing & Subscription Management Dashboard
 * (V5 Dual-Track Web2.5 Hybrid Ledger & Asynchronous Blockchain Radar Edition)
 * 🚀 V6 FIXED: Synchronized with V16 Dual-Action Near Scripts & Strict Error Handling
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';
require_once __DIR__ . '/../private/src/ApiSecurity.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

// Use ApiSecurity only for CSRF token setup on this normal HTML page.
// Do NOT call ApiSecurity::initialize() here — it forces Content-Type: application/json
// which would break the HTML output for /billing.
$csrfToken = ApiSecurity::ensureCsrfToken();

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('billing');

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = (int)$_SESSION['user_id'];

// =========================================================
// 1. 撈取訂閱狀態與過期判定 (Expiration Logic)
// =========================================================
$stmt = $pdo->prepare("SELECT tier, vip_expires_at, near_wallet_address FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$currentTier = $user['tier'] ?? 'free';
$expiresAt = $user['vip_expires_at'] ? strtotime($user['vip_expires_at']) : 0;
$nearWallet = $user['near_wallet_address'] ?? null;

$isActivePremium = ($currentTier !== 'free' && $expiresAt > time());
$isExpired = (!$isActivePremium && $expiresAt > 0 && $expiresAt <= time());

// =========================================================
// 2. 分頁計算與查詢帳單紀錄 (Web2 PayPal Invoices)
// =========================================================
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ?");
$countStmt->execute([$userId]);
$totalPayments = (int)$countStmt->fetchColumn();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10; 
$totalPages = max(1, ceil($totalPayments / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

$payStmt = $pdo->prepare("
    SELECT id, paypal_order_id, amount, currency, tier_purchased, status, created_at 
    FROM payments 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$payStmt->bindValue(1, $userId, PDO::PARAM_INT);
$payStmt->bindValue(2, $limit, PDO::PARAM_INT);
$payStmt->bindValue(3, $offset, PDO::PARAM_INT);
$payStmt->execute();
$payments = $payStmt->fetchAll();

function getPageUrl($newPage) {
    $queryParams = $_GET;
    $queryParams['page'] = $newPage;
    return '?' . http_build_query($queryParams);
}

// 🌍 SEO Meta 多語言化
$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
require_once __DIR__ . '/../private/includes/header.php';
?>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-10 flex-grow flex flex-col">
    
    <div class="flex flex-col sm:flex-row justify-between sm:items-end mb-10 border-b border-white/10 pb-6 gap-4 animate-fade-in">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter text-white"><?= __('Billing & Subscriptions') ?></h1>
            <p class="text-zinc-400 mt-2 text-sm"><?= __('Billing Subtitle') ?></p>
        </div>
        <a href="<?= url('/upgrade') ?>" class="px-6 py-3 <?= $isExpired ? 'bg-red-500 hover:bg-red-400 text-zinc-950' : 'bg-emerald-500 hover:bg-emerald-400 text-zinc-950' ?> font-bold rounded-2xl transition flex items-center gap-2 shadow-lg shrink-0 transform hover:scale-[1.02] duration-200">
            <i class="fas <?= $isExpired ? 'fa-sync-alt' : 'fa-arrow-up' ?>" aria-hidden="true"></i> <?= $isExpired ? __('Renew Subscription') : __('Upgrade Plan') ?>
        </a>
    </div>

    <?php if ($nearWallet): ?>
    <!-- Manual on-chain claim for NEAR FT USDT/USDC upgrades -->
    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 mb-8">
        <h2 class="text-xl font-bold text-white mb-2 flex items-center gap-2">
            <i class="fas fa-link text-emerald-400"></i> On-chain Upgrade Claim
        </h2>
        <p class="text-sm text-zinc-400 mb-4">
            If you sent USDT or USDC via <code>ft_transfer_call</code> (and the transaction succeeded on-chain) but the automatic claim after payment failed (wallet errors like "Request validation error"), use the buttons below. Manual claim only works for credits granted in the last hour.
        </p>
        <div class="flex flex-wrap gap-3">
            <button onclick="manualClaimNearBilling('vip')" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-2xl transition">Claim VIP (30 days)</button>
            <button onclick="manualClaimNearBilling('pro')" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-sm font-bold rounded-2xl transition">Claim PRO (30 days)</button>
        </div>
        <p class="mt-2 text-[10px] text-zinc-500">Requires your bound NEAR wallet (<?= htmlspecialchars($nearWallet) ?>) to have a valid unclaimed on-chain credit.</p>
    </div>
    <?php endif; ?>

    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 mb-8 shadow-xl backdrop-blur-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r <?= $isExpired ? 'from-red-500 to-amber-500' : 'from-emerald-400 to-cyan-400' ?>"></div>
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas <?= $isExpired ? 'fa-exclamation-triangle text-red-400' : 'fa-shield-check text-emerald-400' ?>" aria-hidden="true"></i> <?= __('Current Subscription Status') ?>
        </h2>
        
        <?php if ($isActivePremium): ?>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-zinc-950/50 p-6 rounded-2xl border border-white/5">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl font-black text-white uppercase tracking-widest"><?= __('Tier Member', ['tier' => htmlspecialchars($currentTier)]) ?></span>
                        <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 text-[10px] font-bold uppercase rounded-md border border-emerald-500/20 tracking-wider"><?= __('Active Pass') ?></span>
                    </div>
                    <p class="text-sm text-zinc-400 leading-relaxed">
                        <?php 
                        $dateFormatted = date('F j, Y, g:i a', $expiresAt);
                        echo __('Active Pass Desc', ['date' => $dateFormatted]); 
                        ?>
                    </p>
                </div>
                <div class="text-left md:text-right shrink-0">
                    <?php $daysLeft = max(0, floor(($expiresAt - time()) / (60 * 60 * 24))); ?>
                    <div class="text-4xl font-black text-emerald-400 tracking-tight font-mono"><?= $daysLeft ?></div>
                    <div class="text-xs text-zinc-500 font-bold uppercase tracking-widest mt-1"><?= __('Days Remaining') ?></div>
                </div>
            </div>
            <p class="text-xs text-amber-400/80 mt-4 px-2 flex items-start gap-1.5 leading-relaxed">
                <i class="fas fa-info-circle mt-0.5 shrink-0" aria-hidden="true"></i> 
                <span><strong><?= __('Manual Lifecycle Management:') ?></strong> <?= __('Lifecycle Desc') ?></span>
            </p>

        <?php elseif ($isExpired): ?>
            <div class="bg-red-950/20 p-6 rounded-2xl border border-red-500/30 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-6 relative overflow-hidden">
                <div class="absolute left-0 top-0 w-1 h-full bg-red-500"></div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center text-red-400 text-xl shrink-0">
                        <i class="fas fa-history" aria-hidden="true"></i>
                    </div>
                    <div>
                        <span class="text-xl font-bold text-red-400"><?= __('Subscription Expired') ?></span>
                        <p class="text-sm text-zinc-400 mt-1">
                            <?php 
                            $dateFormatted = date('F j, Y', $expiresAt);
                            echo __('Expired Desc', ['date' => $dateFormatted]); 
                            ?>
                        </p>
                    </div>
                </div>
                <a href="<?= url('/upgrade') ?>" class="px-6 py-3 bg-red-500 text-zinc-950 text-sm font-bold rounded-xl transition shadow-lg shadow-red-500/20 whitespace-nowrap flex items-center justify-center gap-2 shrink-0">
                    <i class="fas fa-sync-alt" aria-hidden="true"></i> <?= __('Renew Plan') ?>
                </a>
            </div>

        <?php else: ?>
            <div class="bg-zinc-950/50 p-6 rounded-2xl border border-white/5 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-xl font-bold text-zinc-300"><?= __('Free Tier Account') ?></span>
                    <p class="text-sm text-zinc-500 mt-1"><?= __('Free Tier Desc') ?></p>
                </div>
                <a href="<?= url('/upgrade') ?>" class="px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-white text-sm font-semibold rounded-xl transition border border-white/10 shadow-sm">
                    <?= __('View Premium Plans') ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex border-b border-white/10 mb-6 gap-2">
        <button type="button" onclick="switchLedgerTab('web2')" id="tab-btn-web2" class="px-5 py-3 text-sm font-bold border-b-2 border-emerald-400 text-emerald-400 transition-all flex items-center gap-2">
            <i class="fab fa-paypal" aria-hidden="true"></i> <?= __('Web2 Pass') ?>
        </button>
        <button type="button" onclick="switchLedgerTab('web3')" id="tab-btn-web3" class="px-5 py-3 text-sm font-bold border-b-2 border-transparent text-zinc-400 hover:text-white transition-all flex items-center gap-2">
            <i class="fas fa-gem" aria-hidden="true"></i> <?= __('AgentFi Web3') ?>
        </button>
    </div>

    <div id="ledger-web2-panel" class="block flex-grow flex flex-col">
        <?php if (empty($payments)): ?>
            <div class="bg-zinc-900/20 border border-dashed border-white/10 rounded-3xl p-16 text-center text-zinc-500 flex flex-col items-center justify-center flex-grow min-h-[250px]">
                <div class="w-14 h-14 rounded-2xl bg-zinc-900 border border-white/5 flex items-center justify-center mb-4 shadow-inner">
                    <i class="fas fa-file-invoice-dollar text-2xl opacity-40" aria-hidden="true"></i>
                </div>
                <h3 class="text-lg font-bold text-zinc-400 mb-1"><?= __('No billing rows located') ?></h3>
                <p class="text-xs text-zinc-500 max-w-xs leading-relaxed"><?= __('No billing desc') ?></p>
            </div>
        <?php else: ?>
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl overflow-hidden shadow-2xl backdrop-blur-sm">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-950/80 text-zinc-500 text-xs uppercase tracking-widest border-b border-white/10 select-none">
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('Timestamp') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('Transaction ID') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('Plan') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('Status') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('Gross Amount') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap text-right"><?= __('Action') ?></th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-white/5 font-medium">
                            <?php foreach ($payments as $pay): 
                                $statusClass = 'bg-zinc-800 text-zinc-400 border-white/5';
                                $statusText = strtoupper($pay['status']);
                                $statusTooltip = __('Unknown status.');
                                
                                switch(strtolower($pay['status'])) {
                                    case 'completed':
                                        $statusClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                                        $statusText = 'COMPLETED';
                                        $statusTooltip = __('Payment cleared successfully.');
                                        break;
                                    case 'pending':
                                        $statusClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20 animate-pulse';
                                        $statusText = 'PENDING';
                                        $statusTooltip = __('Awaiting gateway clearance. Assets will provision once settled.');
                                        break;
                                    case 'failed':
                                    case 'denied':
                                    case 'expired':
                                        $statusClass = 'bg-red-500/10 text-red-400 border-red-500/20';
                                        $statusText = 'FAILED';
                                        $statusTooltip = __('Transaction was declined by the issuer or expired.');
                                        break;
                                    case 'refunded':
                                        $statusClass = 'bg-purple-500/10 text-purple-400 border-purple-500/20';
                                        $statusText = 'REFUNDED';
                                        $statusTooltip = __('Manual refund executed. Assets revoked.');
                                        break;
                                    case 'reversed':
                                        $statusClass = 'bg-orange-500/10 text-orange-400 border-orange-500/20';
                                        $statusText = 'REVERSED';
                                        $statusTooltip = __('Dispute / Chargeback filed. Access terminated.');
                                        break;
                                }
                            ?>
                                <tr class="hover:bg-white/5 transition-colors duration-150">
                                    <td class="p-4 text-zinc-300 whitespace-nowrap font-mono text-xs"><?= date('Y-m-d H:i', strtotime($pay['created_at'])) ?></td>
                                    <td class="p-4 font-mono text-xs text-zinc-400 whitespace-nowrap select-all"><?= htmlspecialchars($pay['paypal_order_id']) ?></td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider <?= $pay['tier_purchased'] === 'pro' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20' ?>">
                                            <?= htmlspecialchars($pay['tier_purchased']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase border tracking-wider cursor-help <?= $statusClass ?>" title="<?= htmlspecialchars($statusTooltip) ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-white whitespace-nowrap font-mono"><?= htmlspecialchars($pay['currency']) ?> $<?= number_format($pay['amount'], 2) ?></td>
                                    <td class="p-4 text-right whitespace-nowrap">
                                        <a href="<?= url('/invoice/' . $pay['id']) ?>" target="_blank" onclick="var el=this,orig=el.innerHTML; el.innerHTML='<i class=\'fas fa-spinner fa-spin mr-1\'></i>...'; el.classList.add('pointer-events-none','opacity-50'); setTimeout(function(){if(el){el.innerHTML=orig; el.classList.remove('pointer-events-none','opacity-50');}},2500);" class="inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-bold rounded-lg border border-white/10 transition shadow-sm hover:text-white">
                                            <i class="fas fa-file-invoice text-zinc-500" aria-hidden="true"></i> <?= __('Receipt') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex justify-center select-none">
                    <div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">
                        <a href="<?= $page > 1 ? getPageUrl($page - 1) : '#' ?>" aria-label="Previous Page" class="px-4 py-2.5 bg-zinc-800 rounded-xl text-sm font-bold <?= $page <= 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-zinc-700 hover:text-emerald-400' ?> transition"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
                        <span class="text-xs font-bold text-zinc-400 tracking-widest uppercase"><?= __('Page') ?> <span class="text-white text-sm font-mono"><?= $page ?></span> / <?= $totalPages ?></span>
                        <a href="<?= $page < $totalPages ? getPageUrl($page + 1) : '#' ?>" aria-label="Next Page" class="px-4 py-2.5 bg-zinc-800 rounded-xl text-sm font-bold <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : 'hover:bg-zinc-700 hover:text-emerald-400' ?> transition"><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="hidden sm:flex items-center gap-1.5 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">
                        <a href="<?= $page > 1 ? getPageUrl($page - 1) : '#' ?>" aria-label="Previous Page" class="w-9 h-9 flex items-center justify-center rounded-xl bg-zinc-800 <?= $page <= 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-zinc-700 hover:text-emerald-400' ?> transition"><i class="fas fa-chevron-left text-xs" aria-hidden="true"></i></a>
                        <?php
                        $window = 2; 
                        $start = max(1, $page - $window);
                        $end = min($totalPages, $page + $window);
                        if ($start > 1) {
                            echo '<a href="' . getPageUrl(1) . '" class="w-9 h-9 flex items-center justify-center rounded-xl text-sm text-zinc-400 hover:bg-zinc-800 hover:text-emerald-400 transition font-mono">1</a>';
                            if ($start > 2) echo '<span class="w-9 h-9 flex items-center justify-center text-zinc-600 select-none">...</span>';
                        }
                        for ($i = $start; $i <= $end; $i++) {
                            if ($i === $page) echo '<span class="w-9 h-9 flex items-center justify-center rounded-xl bg-emerald-500 text-zinc-950 font-black font-mono shadow-md">' . $i . '</span>';
                            else echo '<a href="' . getPageUrl($i) . '" class="w-9 h-9 flex items-center justify-center rounded-xl text-sm text-zinc-400 hover:bg-zinc-800 hover:text-emerald-400 transition font-mono">' . $i . '</a>';
                        }
                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) echo '<span class="w-9 h-9 flex items-center justify-center text-zinc-600 select-none">...</span>';
                            echo '<a href="' . getPageUrl($totalPages) . '" class="w-9 h-9 flex items-center justify-center rounded-xl text-sm text-zinc-400 hover:bg-zinc-800 hover:text-emerald-400 transition font-mono">' . $totalPages . '</a>';
                        }
                        ?>
                        <a href="<?= $page < $totalPages ? getPageUrl($page + 1) : '#' ?>" aria-label="Next Page" class="w-9 h-9 flex items-center justify-center rounded-xl bg-zinc-800 <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : 'hover:bg-zinc-700 hover:text-emerald-400' ?> transition"><i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div id="ledger-web3-panel" class="hidden flex-grow flex flex-col">
        <?php if (empty($nearWallet)): ?>
            <div class="text-center py-12 bg-purple-950/10 border border-dashed border-purple-500/30 rounded-3xl p-8">
                <i class="fas fa-wallet text-purple-400 text-4xl mb-4" aria-hidden="true"></i>
                <h3 class="text-lg font-bold text-white mb-2"><?= __('No Web3 Wallet Detected') ?></h3>
                <p class="text-sm text-zinc-400 max-w-md mx-auto mb-6"><?= __('Wallet bind prompt') ?></p>
                <a href="<?= url('/my-setting?tab=web3') ?>" class="inline-block px-6 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-purple-500/20"><i class="fas fa-link" aria-hidden="true"></i> <?= __('Go to Bind Wallet') ?></a>
            </div>
        <?php else: ?>
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl overflow-hidden shadow-2xl backdrop-blur-sm">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-950/80 text-zinc-500 text-xs uppercase tracking-widest border-b border-white/10 select-none">
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('Asset Type') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('Agent Asset') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('On-Chain Role') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap"><?= __('Market Status') ?></th>
                                <th class="p-4 font-semibold whitespace-nowrap text-right"><?= __('Live Action') ?></th>
                            </tr>
                        </thead>
                        <tbody id="web3-ledger-body" class="text-sm divide-y divide-white/5 font-medium">
                            <!-- JS Web3 Render Logic Here -->
                        </tbody>
                    </table>
                </div>
                <div id="web3-scanning-loading" class="flex flex-col items-center justify-center py-20 bg-zinc-950/20">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500 mb-3" aria-hidden="true"></div>
                    <p class="text-zinc-400 text-xs animate-pulse"><?= __('Scanning Blockchain...') ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <p class="text-[10px] text-zinc-600 mt-6 text-center leading-relaxed select-none max-w-2xl mx-auto uppercase tracking-wide">
        <?= __('Legal Footer 1') ?> <strong class="text-zinc-500"><?= __('NON-REFUNDABLE') ?></strong>.
    </p>
</div>

<script>
    const boundWallet = <?= json_encode($nearWallet) ?>;

    async function manualClaimNearBilling(tier) {
        if (!boundWallet) {
            alert('No NEAR wallet bound to your account. Please bind one in My Settings first.');
            return;
        }
        const btns = document.querySelectorAll('button');
        btns.forEach(b => b.disabled = true);

        try {
            const res = await fetch('/api/near-upgrade', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' },
                body: JSON.stringify({ tier, token: 'usdt' })
            });
            const data = await res.json();

            if (data.success) {
                alert('On-chain credit claimed! ' + (data.message || 'Tier upgraded.'));
                window.location.reload();
            } else {
                alert(data.error || 'No valid unclaimed on-chain credit found for your bound NEAR wallet.');
            }
        } catch (e) {
            alert('Claim request failed. Please try again or contact support.');
            console.error(e);
        } finally {
            btns.forEach(b => b.disabled = false);
        }
    }

    function switchLedgerTab(type) {
        document.querySelectorAll('.tab-btn-ledger').forEach(el => {
            el.classList.remove('border-emerald-400', 'text-emerald-400');
            el.classList.add('border-transparent', 'text-zinc-400', 'hover:text-white');
        });
        
        // 切換控制
        if (type === 'web2') {
            document.getElementById('tab-btn-web2').className = "px-5 py-3 text-sm font-bold border-b-2 border-emerald-400 text-emerald-400 transition-all flex items-center gap-2";
            document.getElementById('tab-btn-web3').className = "px-5 py-3 text-sm font-bold border-b-2 border-transparent text-zinc-400 hover:text-white transition-all flex items-center gap-2";
            document.getElementById('ledger-web2-panel').classList.remove('hidden');
            document.getElementById('ledger-web2-panel').classList.add('block');
            document.getElementById('ledger-web3-panel').classList.remove('block','flex');
            document.getElementById('ledger-web3-panel').classList.add('hidden');
        } else {
            document.getElementById('tab-btn-web3').className = "px-5 py-3 text-sm font-bold border-b-2 border-purple-500 text-purple-400 transition-all flex items-center gap-2";
            document.getElementById('tab-btn-web2').className = "px-5 py-3 text-sm font-bold border-b-2 border-transparent text-zinc-400 hover:text-white transition-all flex items-center gap-2";
            document.getElementById('ledger-web3-panel').classList.remove('hidden');
            document.getElementById('ledger-web3-panel').classList.add('block','flex');
            document.getElementById('ledger-web2-panel').classList.remove('block');
            document.getElementById('ledger-web2-panel').classList.add('hidden');
            
            // 每次切換至 Web3 重新觸發鏈上數據雷達
            scanWeb3Positions();
        }
    }

    function makeSlug(str) {
        if (!str) return 'unassigned';
        return encodeURIComponent(str.toLowerCase().replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|]+/g, '-').replace(/^-+|-+$/g, ''));
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }

    // 🚀 核心升級：Web3 去中心化雷達掃描與分流渲染庫 (支援無限翻頁 Chunking Polling)
    async function scanWeb3Positions() {
        if (!boundWallet) return;
        const body = document.getElementById('web3-ledger-body');
        const loader = document.getElementById('web3-scanning-loading');
        
        body.innerHTML = '';
        if(loader) loader.classList.remove('hidden');

        try {
            // 🌟 1. 自動翻頁撈取所有 NFT 候選清單，無視 API 的 100 筆硬限制
            let allFetchedNfts = [];
            let page = 1;
            let totalPages = 1;
            
            while (page <= totalPages) {
                const res = await fetch(`/api/souls?is_nft=1&limit=100&page=${page}&sort=newest`);
                const data = await res.json();
                
                if (data.success && data.data && data.data.length > 0) {
                    allFetchedNfts.push(...data.data);
                    totalPages = data.total_pages || 1;
                    page++;
                } else {
                    break;
                }
            }
            
            if (allFetchedNfts.length === 0) {
                if(loader) loader.classList.add('hidden');
                body.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-zinc-500"><?= addslashes(__('No Web3 positions')) ?></td></tr>`;
                return;
            }

            let matchedCount = 0;

            // 🌟 2. 批次處理 (Chunking) RPC 查詢，使用全域 nearRpcQuery
            const chunkSize = 20; 
            for (let i = 0; i < allFetchedNfts.length; i += chunkSize) {
                const batch = allFetchedNfts.slice(i, i + chunkSize);
                
                const scanPromises = batch.map(async (soul) => {
                    try {
                        const rpcRes = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soul.id });
                        if (rpcRes.success && rpcRes.data) {
                            const tokenInfo = rpcRes.data;

                            let isMyAsset = false;
                            let roleLabel = '';
                            let typeLabel = '';
                            let statusHtml = '';
                            let actionHtml = '';

                            const nowMs = Date.now();
                            const isOwner = tokenInfo.owner_id === boundWallet;
                            const isCreator = tokenInfo.metadata?.creator_id === boundWallet;
                            
                            let isRenter = false;
                            let leaseExpiryStr = '';
                            if (tokenInfo.renters && tokenInfo.renters[boundWallet]) {
                                const expiryMs = Number(BigInt(tokenInfo.renters[boundWallet]) / 1000000n);
                                if (expiryMs > nowMs) {
                                    isRenter = true;
                                    leaseExpiryStr = new Date(expiryMs).toLocaleString();
                                }
                            }

                            // 判斷分流
                            if (isOwner) {
                                isMyAsset = true;
                                typeLabel = `<span class="px-2.5 py-1 bg-purple-500/10 text-purple-400 border border-purple-500/20 rounded-md text-[10px] font-black uppercase tracking-wider"><i class="fas fa-cube mr-1" aria-hidden="true"></i><?= addslashes(__('Ownership')) ?></span>`;
                                roleLabel = `<span class="text-zinc-300 font-mono text-xs"><?= addslashes(__('Legal Owner')) ?></span>`;
                                
                                if (tokenInfo.sale_price && tokenInfo.sale_price !== "0") {
                                    statusHtml = `<span class="text-xs text-blue-400 font-bold"><i class="fas fa-tag mr-1" aria-hidden="true"></i><?= addslashes(__('Listed for Sale')) ?> (${window.nearApi.utils.format.formatNearAmount(tokenInfo.sale_price)} N)</span>`;
                                } else if (tokenInfo.rent_price && tokenInfo.rent_price !== "0") {
                                    statusHtml = `<span class="text-xs text-purple-400 font-bold"><i class="fas fa-handshake mr-1" aria-hidden="true"></i><?= addslashes(__('Listed for Rent')) ?> (${window.nearApi.utils.format.formatNearAmount(tokenInfo.rent_price)} N)</span>`;
                                } else {
                                    statusHtml = `<span class="text-xs text-zinc-500"><i class="fas fa-box mr-1" aria-hidden="true"></i><?= addslashes(__('Idle')) ?></span>`;
                                }
                            } else if (isRenter) {
                                isMyAsset = true;
                                typeLabel = `<span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-md text-[10px] font-black uppercase tracking-wider"><i class="fas fa-key mr-1" aria-hidden="true"></i><?= addslashes(__('Active Lease')) ?></span>`;
                                roleLabel = `<span class="text-zinc-300 font-mono text-xs"><?= addslashes(__('Active Renter')) ?></span>`;
                                statusHtml = `<div class="text-[11px] text-zinc-400"><div class="text-zinc-500 text-[9px] uppercase tracking-wider"><?= addslashes(__('Lease Expires At')) ?></div><div class="font-bold font-mono text-emerald-400">${leaseExpiryStr}</div></div>`;
                            } else if (isCreator) {
                                isMyAsset = true;
                                typeLabel = `<span class="px-2.5 py-1 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-md text-[10px] font-black uppercase tracking-wider"><i class="fas fa-code-branch mr-1" aria-hidden="true"></i><?= addslashes(__('Royalty Node')) ?></span>`;
                                roleLabel = `<span class="text-zinc-300 font-mono text-xs"><?= addslashes(__('Creator')) ?></span>`;
                                statusHtml = `<span class="text-xs text-zinc-500"><?= addslashes(__('Perpetual 5% Royalty')) ?></span>`;
                            }

                            if (isMyAsset) {
                                matchedCount++;
                                const seoUrl = `<?= url('/soul/') ?>${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                                
                                if (isOwner || isRenter) {
                                    actionHtml = `<a href="<?= url('/chat/') ?>${soul.id}" onclick="var el=this,orig=el.innerHTML; el.innerHTML='<i class=\\'fas fa-spinner fa-spin mr-1\\' aria-hidden=\\'true\\'></i>...'; el.classList.add('pointer-events-none','opacity-50'); setTimeout(function(){if(el){el.innerHTML=orig; el.classList.remove('pointer-events-none','opacity-50');}},2500);" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-lg border border-purple-500/30 hover:bg-purple-500 transition shadow-sm"><i class="fas fa-comments" aria-hidden="true"></i> <?= addslashes(__('Enter Chat')) ?></a>`;
                                } else {
                                    actionHtml = `<a href="${seoUrl}" onclick="var el=this,orig=el.innerHTML; el.innerHTML='<i class=\\'fas fa-spinner fa-spin mr-1\\' aria-hidden=\\'true\\'></i>...'; el.classList.add('pointer-events-none','opacity-50'); setTimeout(function(){if(el){el.innerHTML=orig; el.classList.remove('pointer-events-none','opacity-50');}},2500);" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-zinc-800 text-zinc-300 text-xs font-bold rounded-lg border border-white/5 hover:text-white transition shadow-sm"><i class="fas fa-eye" aria-hidden="true"></i> <?= addslashes(__('View Codebase')) ?></a>`;
                                }

                                body.innerHTML += `
                                    <tr class="hover:bg-white/5 transition-colors duration-150 animate-fade-in">
                                        <td class="p-4 whitespace-nowrap">${typeLabel}</td>
                                        <td class="p-4 text-white font-bold max-w-[200px] truncate select-all" title="${escapeHTML(String(soul.title))}">${escapeHTML(String(soul.title))}</td>
                                        <td class="p-4 whitespace-nowrap">${roleLabel}</td>
                                        <td class="p-4">${statusHtml}</td>
                                        <td class="p-4 text-right whitespace-nowrap">${actionHtml}</td>
                                    </tr>
                                `;
                            }
                        }
                    } catch(e) { console.error("RPC scan row failed", e); }
                });

                // 等待這一個 Chunk 處理完，再進行下一個 Chunk
                await Promise.all(scanPromises);
            }
            
            if(loader) loader.classList.add('hidden');
            
            if (matchedCount === 0) {
                body.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-zinc-500"><?= addslashes(__('No Web3 positions')) ?></td></tr>`;
            }
        } catch(e) {
            console.error("Blockchain Scanner Error:", e);
            if(loader) loader.classList.add('hidden');
            body.innerHTML = `<tr><td colspan="5" class="p-8 text-center text-red-400"><i class="fas fa-exclamation-triangle mr-2"></i><?= addslashes(__('Blockchain connection failed')) ?></td></tr>`;
        }
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>