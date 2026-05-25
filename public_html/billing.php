<?php
/**
 * SoulMD Hub - Billing & Subscription Management
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 強制登入
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// 1. 獲取用戶當前訂閱狀態
$stmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$currentTier = $user['tier'] ?? 'free';
$expiresAt = $user['vip_expires_at'] ? strtotime($user['vip_expires_at']) : 0;
$isActivePremium = ($currentTier !== 'free' && $expiresAt > time());

// 2. 獲取付款紀錄 (Payment History)
$payStmt = $pdo->prepare("SELECT id, paypal_order_id, amount, currency, tier_purchased, status, created_at FROM payments WHERE user_id = ? ORDER BY created_at DESC");
$payStmt->execute([$userId]);
$payments = $payStmt->fetchAll();

$pageTitle = 'Billing & Subscriptions - SoulMD Hub';
$pageDesc = 'Manage your premium AI subscription and view billing history.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-10 flex-grow">
    
    <div class="flex flex-col sm:flex-row justify-between sm:items-end mb-10 border-b border-white/10 pb-6 gap-4">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter">Billing & Subscriptions</h1>
            <p class="text-zinc-400 mt-2">Manage your current plan and download invoices.</p>
        </div>
        <a href="/upgrade" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-2xl transition flex items-center gap-2 shadow-lg">
            <i class="fas fa-arrow-up"></i> Upgrade Plan
        </a>
    </div>

    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 mb-12 shadow-xl backdrop-blur-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
        <h2 class="text-xl font-bold mb-6 flex items-center gap-2"><i class="fas fa-shield-check text-emerald-400"></i> Current Subscription</h2>
        
        <?php if ($isActivePremium): ?>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-zinc-950/50 p-6 rounded-2xl border border-white/5">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl font-bold text-white uppercase tracking-widest"><?= htmlspecialchars($currentTier) ?> Plan</span>
                        <span class="px-2.5 py-1 bg-emerald-500/20 text-emerald-400 text-[10px] font-bold uppercase rounded-md border border-emerald-500/30">Active</span>
                    </div>
                    <p class="text-sm text-zinc-400">
                        Your subscription is active until <b class="text-white"><?= date('F j, Y, g:i a', $expiresAt) ?></b>.
                    </p>
                </div>
                <div class="text-right">
                    <?php 
                        $daysLeft = max(0, floor(($expiresAt - time()) / (60 * 60 * 24)));
                    ?>
                    <div class="text-3xl font-black text-emerald-400"><?= $daysLeft ?></div>
                    <div class="text-xs text-zinc-500 uppercase tracking-widest mt-1">Days Remaining</div>
                </div>
            </div>
            <p class="text-xs text-amber-400/80 mt-4 px-2">
                <i class="fas fa-info-circle"></i> Note: We do not auto-renew your subscription. You will need to purchase a new pass when your current one expires.
            </p>
        <?php else: ?>
            <div class="bg-zinc-950/50 p-6 rounded-2xl border border-white/5 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-xl font-bold text-zinc-300">Free Tier</span>
                    <p class="text-sm text-zinc-500 mt-1">You are currently on the free trial plan.</p>
                </div>
                <a href="/upgrade" class="px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-white text-sm font-medium rounded-xl transition border border-white/10">View Plans</a>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <h2 class="text-xl font-bold mb-6 flex items-center gap-2"><i class="fas fa-history text-zinc-400"></i> Payment History</h2>
        
        <?php if (empty($payments)): ?>
            <div class="bg-zinc-900/40 border border-dashed border-white/10 rounded-3xl p-12 text-center text-zinc-500">
                <i class="fas fa-file-invoice-dollar text-4xl mb-3 opacity-50"></i>
                <p>No billing history found.</p>
            </div>
        <?php else: ?>
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl overflow-hidden shadow-lg">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-950/80 text-zinc-500 text-xs uppercase tracking-widest border-b border-white/10">
                                <th class="p-4 font-medium whitespace-nowrap">Date</th>
                                <th class="p-4 font-medium whitespace-nowrap">Order ID</th>
                                <th class="p-4 font-medium whitespace-nowrap">Plan</th>
                                <th class="p-4 font-medium whitespace-nowrap">Amount</th>
                                <th class="p-4 font-medium whitespace-nowrap text-right">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-white/5">
                            <?php foreach ($payments as $pay): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="p-4 text-zinc-300 whitespace-nowrap"><?= date('M j, Y', strtotime($pay['created_at'])) ?></td>
                                    <td class="p-4 font-mono text-xs text-zinc-400 whitespace-nowrap"><?= htmlspecialchars($pay['paypal_order_id']) ?></td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded text-xs font-bold uppercase <?= $pay['tier_purchased'] === 'pro' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' ?>">
                                            <?= htmlspecialchars($pay['tier_purchased']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-white whitespace-nowrap"><?= htmlspecialchars($pay['currency']) ?> $<?= number_format($pay['amount'], 2) ?></td>
                                    <td class="p-4 text-right whitespace-nowrap">
                                        <a href="/invoice?id=<?= $pay['id'] ?>" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-medium rounded-lg border border-white/10 transition shadow-sm">
                                            <i class="fas fa-download"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="text-xs text-zinc-500 mt-4 text-center">
                All transactions are final. As stated in our terms, no refunds will be provided.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>