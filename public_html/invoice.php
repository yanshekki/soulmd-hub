<?php
/**
 * SoulMD Hub - Invoice & Receipt View
 * (Dynamic i18n Internationalization & Pixel-Perfect Dark Mode Print Edition)
 */

require_once __DIR__ . '/../private/src/AppBootstrap.php';

$app = AppBootstrap::forPage([
    'translations' => 'invoice',
    'csrf' => false,
    'db' => true,
    'require_login' => true,
    'seo' => true,
]);

$pdo = $app['pdo'];
$userId = (int)$app['user_id'];
$invoiceId = (int)($_GET['id'] ?? 0);

// 🛡️ 安全機制：只允許用戶查看屬於自己的訂單
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.email 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->execute([$invoiceId, $userId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    http_response_code(404);
    $pageTitle = __('Invoice Not Found');
    require_once __DIR__ . '/../private/includes/header.php';
    ?>
    <div class="max-w-md w-full mx-auto px-4 py-24 text-center flex-grow flex flex-col justify-center">
        <div class="w-20 h-20 bg-zinc-900 border border-white/10 rounded-2xl flex items-center justify-center mx-auto mb-6 text-zinc-500"><i class="fas fa-file-invoice-dollar text-3xl"></i></div>
        <h1 class="text-3xl font-bold mb-2 text-white"><?= __('Invoice Not Found') ?></h1>
        <p class="text-sm text-zinc-400 mb-8"><?= __('Invoice Not Found Desc') ?></p>
        <a href="<?= url('/billing') ?>" class="px-6 py-3 bg-emerald-500 text-zinc-950 font-bold rounded-2xl hover:bg-emerald-400 transition shadow-lg w-fit mx-auto"><?= __('Back to Billing') ?></a>
    </div>
    <?php
    require_once __DIR__ . '/../private/includes/footer.php';
    exit;
}

$statusClass = 'bg-zinc-800 text-zinc-400 border-white/5';
$statusText = __('PENDING');
switch(strtolower($invoice['status'])) {
    case 'completed':
        $statusClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        $statusText = __('COMPLETED');
        break;
    case 'failed':
    case 'denied':
    case 'expired':
        $statusClass = 'bg-red-500/10 text-red-400 border-red-500/20';
        $statusText = __('FAILED');
        break;
    case 'refunded':
    case 'reversed':
        $statusClass = 'bg-purple-500/10 text-purple-400 border-purple-500/20';
        $statusText = __('REFUNDED');
        break;
}

// 🌍 SEO Meta
$pageTitle = __('SEO Title', ['id' => $invoice['id']]);
$pageDesc = __('SEO Desc');
$hideNavLinks = true; // 隱藏頂部選單以保持收據乾淨
require_once __DIR__ . '/../private/includes/header.php';
?>

<style>
    @media print {
        @page {
            margin: 0; /* 消除所有瀏覽器預設白邊 */
            size: auto;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: #09090b !important; /* 強制保持深色背景 */
            -webkit-print-color-adjust: exact !important; /* 強制列印保留所有顏色與背景 */
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        nav, footer, .no-print, .expired-sub-banner, #expired-sub-banner {
            display: none !important;
        }
        /* 將外層容器歸零，保持卡片與畫面 1:1 比例 */
        .print-container {
            margin: 0 auto !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        /* 取消陰影避免列印模糊，其餘 Tailwind Padding 保持不變 */
        .print-area { 
            margin: 0 !important;
            box-shadow: none !important; 
        }
    }
</style>

<div class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-10 flex-grow print-container">
    
    <div class="flex justify-between items-center mb-6 no-print">
        <a href="<?= url('/billing') ?>" class="text-sm text-zinc-400 hover:text-white flex items-center gap-2 border border-white/10 bg-zinc-900/50 px-4 py-2 rounded-full transition">
            <i class="fas fa-arrow-left"></i> <?= __('Back to Billing') ?>
        </a>
        <button onclick="window.print()" class="px-5 py-2 bg-emerald-500 text-zinc-950 font-bold rounded-xl hover:bg-emerald-400 transition flex items-center gap-2 shadow-lg">
            <i class="fas fa-print"></i> <?= __('Print Invoice') ?>
        </button>
    </div>

    <div class="print-area bg-zinc-900 border border-white/10 rounded-3xl p-8 sm:p-12 shadow-2xl">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-white/10 pb-8 mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tighter text-white flex items-center gap-2">
                    SoulMD <span class="text-emerald-400 text-xs px-2 py-0.5 bg-emerald-900/30 rounded-full font-mono">HUB</span>
                </h1>
                <p class="text-zinc-500 text-sm mt-2 font-mono"><?= defined('SITE_BILLING_EMAIL') ? SITE_BILLING_EMAIL : 'billing@soulmd-hub.com' ?></p>
            </div>
            <div class="text-left md:text-right mt-6 md:mt-0">
                <h2 class="text-2xl font-bold text-zinc-300 tracking-widest uppercase mb-1"><?= __('RECEIPT') ?></h2>
                <p class="text-zinc-500 text-sm font-mono"><?= __('Invoice Number') ?>: #<?= str_pad($invoice['id'], 6, '0', STR_PAD_LEFT) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
            <div>
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2"><?= __('Billed To') ?></h3>
                <div class="text-white font-medium">@<?= htmlspecialchars($invoice['username']) ?></div>
                <?php if ($invoice['email']): ?>
                    <div class="text-zinc-400 text-sm"><?= htmlspecialchars($invoice['email']) ?></div>
                <?php endif; ?>
                <div class="text-zinc-500 text-xs mt-1 font-mono"><?= __('User ID') ?>: <?= $invoice['user_id'] ?></div>
            </div>
            <div class="md:text-right">
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2"><?= __('Payment Method') ?></h3>
                <?php
                $isNearFt = str_starts_with($invoice['paypal_order_id'] ?? '', 'near-ft:') || in_array(strtoupper($invoice['currency'] ?? ''), ['USDT', 'USDC']);
                if ($isNearFt) {
                    $pmIcon = 'fas fa-link text-emerald-400';
                    $pmText = strtoupper(htmlspecialchars($invoice['currency'])) . ' (NEAR On-chain)';
                    $txnLabel = 'NEAR Ref';
                    $txnTitle = 'NEAR FT Payment Reference';
                } else {
                    $pmIcon = 'fab fa-paypal text-blue-400';
                    $pmText = 'PayPal (Gateway)';
                    $txnLabel = 'PayPal Order ID';
                    $txnTitle = 'PayPal Order ID';
                }
                ?>
                <div class="text-white font-medium flex items-center md:justify-end gap-2">
                    <i class="<?= $pmIcon ?>"></i> <?= $pmText ?>
                </div>
                <div class="text-zinc-400 text-sm font-mono mt-1" title="<?= $txnTitle ?>"><?= $txnLabel ?>: <?= htmlspecialchars($invoice['paypal_order_id']) ?></div>
                <div class="text-zinc-500 text-xs mt-1"><?= __('Date Paid') ?>: <?= date('F j, Y', strtotime($invoice['created_at'])) ?></div>
            </div>
        </div>

        <div class="overflow-x-auto mb-8 border border-white/10 rounded-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-zinc-950/50 border-b border-white/10 text-zinc-400 text-xs uppercase tracking-widest">
                        <th class="p-4 font-semibold"><?= __('Description') ?></th>
                        <th class="p-4 font-semibold"><?= __('Period') ?></th>
                        <th class="p-4 font-semibold text-right"><?= __('Amount') ?></th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-white/5">
                    <tr>
                        <td class="p-4 text-white font-medium">
                            <?= __('Tier Subscription', ['tier' => strtoupper(htmlspecialchars($invoice['tier_purchased']))]) ?>
                        </td>
                        <td class="p-4 text-zinc-400">30 Days</td>
                        <td class="p-4 text-white font-bold font-mono text-right">
                            <?= htmlspecialchars($invoice['currency']) ?> $<?= number_format($invoice['amount'], 2) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-end gap-6 pt-4 border-t border-white/10">
            <div>
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2"><?= __('Status') ?></h3>
                <span class="px-3 py-1 rounded-md text-xs font-black uppercase tracking-wider border <?= $statusClass ?>">
                    <?= $statusText ?>
                </span>
            </div>
            <div class="text-right">
                <div class="text-sm text-zinc-400 font-bold uppercase tracking-widest mb-1"><?= __('Total') ?></div>
                <div class="text-3xl font-black text-emerald-400 font-mono">
                    <?= htmlspecialchars($invoice['currency']) ?> $<?= number_format($invoice['amount'], 2) ?>
                </div>
            </div>
        </div>

        <div class="mt-16 text-center border-t border-white/10 pt-8">
            <p class="text-zinc-300 font-medium mb-1"><?= __('Thank you') ?></p>
            <p class="text-[10px] text-zinc-500 max-w-md mx-auto leading-relaxed uppercase tracking-wider">
                <?= __('Invoice Note') ?>
            </p>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>