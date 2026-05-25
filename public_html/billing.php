<?php
/**
 * SoulMD Hub - Billing & Subscription Management (Enterprise Full-Status & Pagination Edition)
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
// 2. 🚨 分頁計算與嚴格多租戶隔離查詢
// ==========================================
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE user_id = ?");
$countStmt->execute([$userId]);
$totalPayments = (int)$countStmt->fetchColumn();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10; // 🚨 每頁嚴格限制顯示 10 筆紀錄，防止表格過長
$totalPages = max(1, ceil($totalPayments / $limit));
$page = min($page, $totalPages);
$offset = ($page - 1) * $limit;

// 使用強類型安全的 PDO 參數繫結，杜絕任何分頁邊界 SQL 注入隱患
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

// 輔助函數：保留網址現有的其他 GET 參數並安全更新頁碼
function getPageUrl($newPage) {
    $queryParams = $_GET;
    $queryParams['page'] = $newPage;
    return '?' . http_build_query($queryParams);
}

$pageTitle = 'Billing & Subscriptions - SoulMD Hub';
$pageDesc = 'Manage your premium AI subscription and view billing history.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-10 flex-grow flex flex-col">
    
    <div class="flex flex-col sm:flex-row justify-between sm:items-end mb-10 border-b border-white/10 pb-6 gap-4 animate-fade-in">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter text-white">Billing & Subscriptions</h1>
            <p class="text-zinc-400 mt-2 text-sm">Manage your current plan and download invoices.</p>
        </div>
        <a href="/upgrade" class="px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-2xl transition flex items-center gap-2 shadow-lg shrink-0 transform hover:scale-[1.02] duration-200">
            <i class="fas fa-arrow-up"></i> Upgrade Plan
        </a>
    </div>

    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 mb-12 shadow-xl backdrop-blur-sm relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2"><i class="fas fa-shield-check text-emerald-400"></i> Current Subscription</h2>
        
        <?php if ($isActivePremium): ?>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-zinc-950/50 p-6 rounded-2xl border border-white/5">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl font-black text-white uppercase tracking-widest"><?= htmlspecialchars($currentTier) ?> Plan</span>
                        <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 text-[10px] font-bold uppercase rounded-md border border-emerald-500/20 tracking-wider">Active</span>
                    </div>
                    <p class="text-sm text-zinc-400 leading-relaxed">
                        Your subscription is active until <b class="text-white font-mono"><?= date('F j, Y, g:i a', $expiresAt) ?></b>.
                    </p>
                </div>
                <div class="text-left md:text-right shrink-0">
                    <?php $daysLeft = max(0, floor(($expiresAt - time()) / (60 * 60 * 24))); ?>
                    <div class="text-3xl font-black text-emerald-400 font-mono"><?= $daysLeft ?></div>
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
                <a href="/upgrade" class="px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-white text-sm font-semibold rounded-xl transition border border-white/10 shadow-sm">View Plans</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex-grow flex flex-col">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2"><i class="fas fa-history text-zinc-400"></i> Payment History</h2>
        
        <?php if (empty($payments)): ?>
            <div class="bg-zinc-900/40 border border-dashed border-white/10 rounded-3xl p-12 text-center text-zinc-500 flex flex-col items-center justify-center flex-grow min-h-[250px]">
                <i class="fas fa-file-invoice-dollar text-4xl mb-3 opacity-50"></i>
                <p>No billing history found.</p>
            </div>
        <?php else: ?>
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl overflow-hidden shadow-2xl backdrop-blur-sm">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-zinc-950/80 text-zinc-500 text-xs uppercase tracking-widest border-b border-white/10 select-none">
                                <th class="p-4 font-semibold whitespace-nowrap">Date</th>
                                <th class="p-4 font-semibold whitespace-nowrap">Order ID</th>
                                <th class="p-4 font-semibold whitespace-nowrap">Plan</th>
                                <th class="p-4 font-semibold whitespace-nowrap">Status</th>
                                <th class="p-4 font-semibold whitespace-nowrap">Amount</th>
                                <th class="p-4 font-semibold whitespace-nowrap text-right">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-white/5 font-medium">
                            <?php foreach ($payments as $pay): 
                                // 🚨 智慧型狀態 Badge 分流引擎
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
                                    <td class="p-4 text-zinc-300 whitespace-nowrap font-mono text-xs"><?= date('M j, Y', strtotime($pay['created_at'])) ?></td>
                                    <td class="p-4 font-mono text-xs text-zinc-400 whitespace-nowrap select-all"><?= htmlspecialchars($pay['paypal_order_id']) ?></td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase <?= $pay['tier_purchased'] === 'pro' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20' ?>">
                                            <?= htmlspecialchars($pay['tier_purchased']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase border tracking-wider <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-white whitespace-nowrap font-mono"><?= htmlspecialchars($pay['currency']) ?> $<?= number_format($pay['amount'], 2) ?></td>
                                    <td class="p-4 text-right whitespace-nowrap font-sans">
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

            <?php if ($totalPages > 1): ?>
                <div class="mt-8 flex justify-center select-none">
                    
                    <div class="flex sm:hidden w-full max-w-sm items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">
                        <a href="<?= $page > 1 ? getPageUrl($page - 1) : '#' ?>" class="px-4 py-2.5 bg-zinc-800 rounded-xl text-sm font-bold <?= $page <= 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-zinc-700 hover:text-emerald-400' ?> transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span class="text-xs font-bold text-zinc-400 tracking-widest uppercase">Page <span class="text-white text-sm font-mono"><?= $page ?></span> / <?= $totalPages ?></span>
                        <a href="<?= $page < $totalPages ? getPageUrl($page + 1) : '#' ?>" class="px-4 py-2.5 bg-zinc-800 rounded-xl text-sm font-bold <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : 'hover:bg-zinc-700 hover:text-emerald-400' ?> transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>

                    <div class="hidden sm:flex items-center gap-1.5 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">
                        <a href="<?= $page > 1 ? getPageUrl($page - 1) : '#' ?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-zinc-800 <?= $page <= 1 ? 'opacity-50 pointer-events-none' : 'hover:bg-zinc-700 hover:text-emerald-400' ?> transition">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>

                        <?php
                        $window = 2; // 當前頁碼左右各動態擴展顯示 2 頁
                        $start = max(1, $page - $window);
                        $end = min($totalPages, $page + $window);

                        if ($start > 1) {
                            echo '<a href="' . getPageUrl(1) . '" class="w-9 h-9 flex items-center justify-center rounded-xl text-sm text-zinc-400 hover:bg-zinc-800 hover:text-emerald-400 transition font-mono">1</a>';
                            if ($start > 2) {
                                echo '<span class="w-9 h-9 flex items-center justify-center text-zinc-600 select-none">...</span>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            if ($i === $page) {
                                echo '<span class="w-9 h-9 flex items-center justify-center rounded-xl bg-emerald-500 text-zinc-950 font-black font-mono shadow-md">' . $i . '</span>';
                            } else {
                                echo '<a href="' . getPageUrl($i) . '" class="w-9 h-9 flex items-center justify-center rounded-xl text-sm text-zinc-400 hover:bg-zinc-800 hover:text-emerald-400 transition font-mono">' . $i . '</a>';
                            }
                        }

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) {
                                echo '<span class="w-9 h-9 flex items-center justify-center text-zinc-600 select-none">...</span>';
                            }
                            echo '<a href="' . getPageUrl($totalPages) . '" class="w-9 h-9 flex items-center justify-center rounded-xl text-sm text-zinc-400 hover:bg-zinc-800 hover:text-emerald-400 transition font-mono">' . $totalPages . '</a>';
                        }
                        ?>

                        <a href="<?= $page < $totalPages ? getPageUrl($page + 1) : '#' ?>" class="w-9 h-9 flex items-center justify-center rounded-xl bg-zinc-800 <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : 'hover:bg-zinc-700 hover:text-emerald-400' ?> transition">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    </div>

                </div>
            <?php endif; ?>

            <p class="text-xs text-zinc-500 mt-6 text-center">
                All transactions are final. As stated in our terms, no refunds will be provided.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>