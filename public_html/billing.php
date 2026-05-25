<?php
/**
 * SoulMD Hub - Billing & Subscription Management (Enterprise Full-Status Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 🛡️ 權限安全檢查：強制驗證登入狀態，未登入踢回登入頁
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = (int)$_SESSION['user_id'];

// ==========================================
// 1. 撈取用戶當前訂閱狀態與有效期
// ==========================================
$stmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$currentTier = $user['tier'] ?? 'free';
$expiresAt = $user['vip_expires_at'] ? strtotime($user['vip_expires_at']) : 0;
$isActivePremium = ($currentTier !== 'free' && $expiresAt > time());

// ==========================================
// 2. 嚴格多租戶隔離：只撈取屬於該用戶的所有付款紀錄
// ==========================================
$payStmt = $pdo->prepare("
    SELECT id, paypal_order_id, amount, currency, tier_purchased, status, created_at 
    FROM payments 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$payStmt->execute([$userId]);
$payments = $payStmt->fetchAll();

$pageTitle = 'Billing & Subscriptions - SoulMD Hub';
$pageDesc = 'Manage your premium AI subscription and view billing history.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-10 flex-grow flex flex-col">
    
    <div class="flex flex-col sm:flex-row justify-between sm:items-end mb-10 border-b border-white/10 pb-6 gap-4 animate-fade-in">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter text-white">Billing & Subscriptions</h1>
            <p class="text-zinc-400 mt-2 text-sm">Manage your premium tier passes, context token allocations, and transaction receipts.</p>
        </div>
        <a href="/upgrade" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-2xl transition flex items-center gap-2 shadow-lg shrink-0 transform hover:scale-[1.02] duration-200">
            <i class="fas fa-arrow-up"></i> Upgrade Plan
        </a>
    </div>

    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 mb-12 shadow-xl backdrop-blur-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-shield-check text-emerald-400"></i> Current Subscription Status
        </h2>
        
        <?php if ($isActivePremium): ?>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-zinc-950/50 p-6 rounded-2xl border border-white/5">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl font-black text-white uppercase tracking-widest"><?= htmlspecialchars($currentTier) ?> Member</span>
                        <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 text-[10px] font-bold uppercase rounded-md border border-emerald-500/20 tracking-wider">Active Pass</span>
                    </div>
                    <p class="text-sm text-zinc-400 leading-relaxed">
                        Your enterprise-grade AI session cluster layer is unlocked and fully verified. Fully operational until: <b class="text-zinc-100 font-mono"><?= date('F j, Y, g:i a', $expiresAt) ?></b>.
                    </p>
                </div>
                <div class="text-left md:text-right shrink-0">
                    <?php $daysLeft = max(0, floor(($expiresAt - time()) / (60 * 60 * 24))); ?>
                    <div class="text-4xl font-black text-emerald-400 tracking-tight font-mono"><?= $daysLeft ?></div>
                    <div class="text-xs text-zinc-500 font-bold uppercase tracking-widest mt-1">Days Remaining</div>
                </div>
            </div>
            <p class="text-xs text-amber-400/80 mt-4 px-2 flex items-start gap-1.5 leading-relaxed">
                <i class="fas fa-info-circle mt-0.5 shrink-0"></i> 
                <span><strong>Manual Lifecycle Management:</strong> To maintain strict data integrity, we enforce zero automatic recurring billings. Your tokens will automatically expire at the set date. Simply purchase a new license pass to continue.</span>
            </p>
        <?php else: ?>
            <div class="bg-zinc-950/50 p-6 rounded-2xl border border-white/5 text-center sm:text-left flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span class="text-xl font-bold text-zinc-300">Free Tier Account</span>
                    <p class="text-sm text-zinc-500 mt-1">You are currently running on standard sandbox trial limits. Upgrade to expand context size.</p>
                </div>
                <a href="/upgrade" class="px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-white text-sm font-semibold rounded-xl transition border border-white/10 shadow-sm">
                    View Premium Plans
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex-grow flex flex-col">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-history text-zinc-400"></i> Transaction History
        </h2>
        
        <?php if (empty($payments)): ?>
            <div class="bg-zinc-900/20 border border-dashed border-white/10 rounded-3xl p-16 text-center text-zinc-500 flex flex-col items-center justify-center flex-grow min-h-[250px]">
                <div class="w-14 h-14 rounded-2xl bg-zinc-900 border border-white/5 flex items-center justify-center mb-4 shadow-inner">
                    <i class="fas fa-file-invoice-dollar text-2xl opacity-40"></i>
                </div>
                <h3 class="text-lg font-bold text-zinc-400 mb-1">No billing rows located</h3>
                <p class="text-xs text-zinc-500 max-w-xs leading-relaxed">You haven't executed any standard gateway premium orders inside this account cluster yet.</p>
            </div>
        <?php else: ?>
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl overflow-hidden shadow-2xl backdrop-blur-sm">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-950/80 text-zinc-500 text-xs uppercase tracking-widest border-b border-white/10 select-none">
                                <th class="p-4 font-semibold whitespace-nowrap">Timestamp</th>
                                <th class="p-4 font-semibold whitespace-nowrap">PayPal Order Identifier</th>
                                <th class="p-4 font-semibold whitespace-nowrap">Allocated Plan</th>
                                <th class="p-4 font-semibold whitespace-nowrap">Gateway Status</th>
                                <th class="p-4 font-semibold whitespace-nowrap">Gross Amount</th>
                                <th class="p-4 font-semibold whitespace-nowrap text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-white/5 font-medium">
                            <?php foreach ($payments as $pay): 
                                // 🚨 完美對齊 PayPal 官方五大權益狀態機
                                $statusClass = 'bg-zinc-800 text-zinc-400 border-white/5';
                                $statusText = strtoupper($pay['status']);
                                
                                switch(strtolower($pay['status'])) {
                                    case 'completed':
                                        $statusClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                                        $statusText = 'Paid';
                                        break;
                                    case 'pending':
                                        $statusClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20 animate-pulse';
                                        $statusText = 'Pending';
                                        break;
                                    case 'failed':
                                        $statusClass = 'bg-red-500/10 text-red-400 border-red-500/20';
                                        $statusText = 'Failed';
                                        break;
                                    case 'refunded':
                                        $statusClass = 'bg-purple-500/10 text-purple-400 border-purple-500/20';
                                        $statusText = 'Refunded';
                                        break;
                                    case 'reversed':
                                        $statusClass = 'bg-orange-500/10 text-orange-400 border-orange-500/20';
                                        $statusText = 'Reversed';
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
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase border tracking-wider <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-white whitespace-nowrap font-mono"><?= htmlspecialchars($pay['currency']) ?> $<?= number_format($pay['amount'], 2) ?></td>
                                    <td class="p-4 text-right whitespace-nowrap">
                                        <a href="/invoice/<?= $pay['id'] ?>" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-bold rounded-lg border border-white/10 transition shadow-sm hover:text-white">
                                            <i class="fas fa-file-invoice text-zinc-500"></i> Receipt
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <p class="text-[10px] text-zinc-600 mt-4 text-center leading-relaxed select-none max-w-2xl mx-auto">
                All localized frame logs comply with global cryptographic ledger signatures. As locked inside your binding purchase contract, all active or terminated subscription licenses are completely <span class="font-bold text-zinc-500">NON-REFUNDABLE</span>. For clearing anomalies, escalate to billing logs network.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>