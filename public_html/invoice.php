<?php
/**
 * SoulMD Hub - Printable Invoice Generator (White-label & Dark Theme Print Full-Status Edition)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

// 🛡️ 權限安全檢查層 1：強制驗證登入狀態
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "<div style='color:#ef4444; font-family:sans-serif; text-align:center; margin-top:50px; font-weight:bold;'>Unauthorized access. Please log in first.</div>";
    exit;
}

$paymentId = (int)($_GET['id'] ?? 0);
if (!$paymentId) {
    http_response_code(400);
    die("Invalid Invoice ID.");
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = (int)$_SESSION['user_id'];

try {
    // 🛡️ 權限安全檢查層 2：嚴格多租戶隔離 (Multi-Tenant Isolation)
    // 透過 SQL 直接限制只有當前登入用戶的 user_id 才能成功撈取對應的訂單 ID
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.email 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$paymentId, $userId]);
    $invoice = $stmt->fetch();

    // 🛡️ 權限安全檢查層 3：強類型防禦校驗 (Bulletproof Defense)
    if (!$invoice || (int)$invoice['user_id'] !== $userId) {
        http_response_code(403);
        echo "<div style='color:#ef4444; font-family:sans-serif; text-align:center; margin-top:50px; font-weight:bold;'>🔒 Access Denied: You do not have permission to view this invoice.</div>";
        exit;
    }

    $orderDate = date('F j, Y', strtotime($invoice['created_at']));
    $invoiceNumber = "INV-" . str_pad($invoice['id'], 6, "0", STR_PAD_LEFT);

    // 🚨 智慧型 PayPal 狀態防護分流與法律條文映射
    $currentStatus = strtolower($invoice['status']);
    $stampText = 'PAID';
    $stampColor = 'text-emerald-500/20 border-emerald-500/20';
    $legalStatusNotice = '';

    switch($currentStatus) {
        case 'completed':
            $stampText = 'PAID';
            $stampColor = 'text-emerald-500/20 border-emerald-500/20';
            break;
        case 'pending':
            $stampText = 'PENDING';
            $stampColor = 'text-amber-500/20 border-amber-500/20';
            $legalStatusNotice = '⚠️ TRANSACTION CURRENTLY IN SUSPENSE: Premium cluster asset synchronization will execute immediately upon clearing from PayPal gateway.';
            break;
        case 'failed':
            $stampText = 'FAILED';
            $stampColor = 'text-red-500/20 border-red-500/20';
            $legalStatusNotice = '❌ TRANSACTION DECLINED / VOID: This statement is an authentication of a failed payment attempt. No automated server capacity was provisioned.';
            break;
        case 'refunded':
            $stampText = 'REFUNDED';
            $stampColor = 'text-purple-500/20 border-purple-500/20';
            $legalStatusNotice = '↩️ REVERSED TRANSACTION: A manual merchant refund has been dispatched. Associated server allocation keys and PRO context frames are permanently revoked.';
            break;
        case 'reversed':
            $stampText = 'REVERSED';
            $stampColor = 'text-orange-500/20 border-orange-500/20';
            $legalStatusNotice = '🚫 DISPUTED / CHARGEBACK VOID: Payment forced back via gateway dispute claim. Access terminated. Relational accounting log locked.';
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    die("Internal server error while processing billing statement.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= $invoiceNumber ?> - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 🚨 終極列印優化配置：確保列印或儲存 PDF 時與 HTML 視覺效果完全 100% 一致 */
        @media print {
            @page {
                size: auto;
                margin: 0mm; /* 徹底抹除瀏覽器自帶的頁首網址、頁尾日期等雜音 */
            }
            /* 打破 min-h-screen 死鎖，並優化 padding 防止爆出多餘空白頁 */
            html, body {
                height: auto !important;
                min-height: auto !important;
                background-color: #09090b !important; /* 強制保持螢幕看到的 zinc-950 背景色 */
                color: #ffffff !important;
                margin: 0 !important;
                padding: 20mm 15mm !important; /* 重新定義列印時乾淨的內邊距，防版面過度貼邊 */
                -webkit-print-color-adjust: exact !important; /* 關鍵：強制 Chrome / Safari 渲染深色背景 */
                print-color-adjust: exact !important;         /* 關鍵：強制 Firefox / Edge 渲染深色背景 */
            }
            .no-print { 
                display: none !important; /* 隱藏控制按鈕 */
            }
            .invoice-card {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important; /* 徹底清除外部 Margin 防止頂爆分頁 */
                background-color: #18181b !important; /* 強制保持 zinc-900 區塊色 */
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen p-4 sm:p-8 font-sans transition-colors duration-300">

    <div class="max-w-3xl mx-auto mb-8 flex justify-between items-center no-print">
        <a href="/billing" class="text-zinc-400 hover:text-white flex items-center gap-2 transition text-sm font-medium">
            <i class="fas fa-arrow-left"></i> Back to Billing
        </a>
        <button onclick="window.print()" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 font-bold rounded-xl shadow-lg hover:bg-emerald-400 transition flex items-center gap-2 text-sm transform hover:scale-105 duration-200">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <div class="invoice-card max-w-3xl mx-auto bg-zinc-900 border border-white/10 rounded-2xl p-8 sm:p-12 shadow-2xl transition-all relative overflow-hidden">
        
        <div class="absolute right-6 top-36 border-8 <?= $stampColor ?> font-black text-4xl sm:text-6xl tracking-widest px-6 py-2 rounded-2xl uppercase pointer-events-none select-none transform rotate-12 font-mono mix-blend-screen z-0 select-none">
            <?= $stampText ?>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-white/10 pb-8 mb-8 relative z-10">
            <div>
                <div class="text-3xl font-black tracking-tighter text-white">
                    SoulMD <span class="text-emerald-400">HUB</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1">Powered by YSK Limited</div>
            </div>
            <div class="mt-4 sm:mt-0 text-left sm:text-right">
                <h1 class="text-2xl font-bold text-zinc-300 tracking-widest uppercase">INVOICE</h1>
                <p class="text-sm font-mono text-zinc-400 mt-1"># Rhine-<?= $invoiceNumber ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-12 relative z-10">
            <div>
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2">Billed To</h3>
                <div class="font-bold text-lg text-white">@<?= htmlspecialchars($invoice['username']) ?></div>
                <?php if ($invoice['email']): ?>
                    <div class="text-sm text-zinc-400 mt-1"><?= htmlspecialchars($invoice['email']) ?></div>
                <?php endif; ?>
            </div>
            <div class="text-left sm:text-right">
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2">Payment Details</h3>
                <div class="text-sm text-zinc-300 mb-1"><span class="text-zinc-500">Date:</span> <?= $orderDate ?></div>
                <div class="text-sm text-zinc-300 mb-1"><span class="text-zinc-500">Method:</span> PayPal Gateway</div>
                <div class="text-sm text-zinc-300"><span class="text-zinc-500">Transaction ID:</span> <span class="font-mono text-xs text-zinc-400"><?= htmlspecialchars($invoice['paypal_order_id']) ?></span></div>
            </div>
        </div>

        <?php if ($legalStatusNotice): ?>
            <div class="mb-8 p-4 bg-zinc-950/80 border border-white/5 rounded-2xl text-xs font-medium tracking-wide leading-relaxed text-zinc-300 relative z-10">
                <?= $legalStatusNotice ?>
            </div>
        <?php endif; ?>

        <div class="mb-12 relative z-10">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-white/10 text-zinc-400 text-xs uppercase tracking-widest">
                        <th class="py-3 font-semibold">Description</th>
                        <th class="py-3 font-semibold text-center">Period</th>
                        <th class="py-3 font-semibold text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-white/5">
                    <tr>
                        <td class="py-5">
                            <div class="font-bold text-base text-white mb-1">SoulMD Hub - <?= strtoupper($invoice['tier_purchased']) ?> Pass</div>
                            <div class="text-zinc-400 text-xs leading-relaxed">Immediate unlock of advanced architecture cluster tokens, dedicated multi-modal asset understanding capabilities, and isolated system execution window.</div>
                        </td>
                        <td class="py-5 text-center text-zinc-300 font-medium">30 Days</td>
                        <td class="py-5 text-right font-bold text-white"><?= htmlspecialchars($invoice['currency']) ?> $<?= number_format($invoice['amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-16 relative z-10">
            <div class="w-full sm:w-1/2">
                <div class="flex justify-between items-center py-3 border-b border-white/5 text-sm">
                    <span class="text-zinc-400">Subtotal</span>
                    <span class="text-zinc-300">$<?= number_format($invoice['amount'], 2) ?></span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-white/5 text-sm">
                    <span class="text-zinc-400">Tax / VAT (0%)</span>
                    <span class="text-zinc-300">$0.00</span>
                </div>
                <div class="flex justify-between items-center py-4 text-xl font-bold">
                    <span class="text-white">Total Paid</span>
                    <span class="text-emerald-400"><?= htmlspecialchars($invoice['currency']) ?> $<?= number_format($invoice['amount'], 2) ?></span>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10 pt-6 text-[10px] text-zinc-500 leading-relaxed relative z-10">
            <p class="mb-2"><strong class="text-zinc-300 font-semibold uppercase tracking-wider">Terms & Conditions - Non-Refundable Transaction</strong></p>
            <p class="mb-2">
                This document serves as an official cryptographic and legal certificate of your purchase. By authorizing this financial transaction, you acknowledge that immediate digital token activation and architectural capabilities have been rendered onto your container profile.
            </p>
            <p class="uppercase font-bold text-zinc-400 border border-zinc-800 p-3 rounded-xl bg-zinc-950/40 text-center my-3">
                ⚠️ All transactions executed within this node framework are final. No refunds, partial credits, or automated chargebacks shall be facilitated under any scenario.
            </p>
            <p class="mt-4 text-center pt-4 border-t border-white/5 font-medium">
                Thank you for supporting the infrastructure ecosystem. For corporate inquiries, contact <?= htmlspecialchars(SITE_BILLING_EMAIL) ?>.
            </p>
        </div>

    </div>
</body>
</html>