<?php
/**
 * SoulMD Hub - Printable Invoice Generator
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

session_start();

// 強制登入
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die("Unauthorized access.");
}

$paymentId = (int)($_GET['id'] ?? 0);
if (!$paymentId) {
    http_response_code(400);
    die("Invalid Invoice ID.");
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

// 嚴格權限檢查：JOIN users 表，確保只撈取屬於當前用戶的單據
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.email 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ? AND p.user_id = ?
");
$stmt->execute([$paymentId, $userId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    http_response_code(404);
    die("Invoice not found or access denied.");
}

$orderDate = date('F j, Y', strtotime($invoice['created_at']));
$invoiceNumber = "INV-" . str_pad($invoice['id'], 6, "0", STR_PAD_LEFT);
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
        @media print {
            body { background-color: white !important; color: black !important; }
            .no-print { display: none !important; }
            .print-border { border-color: #e5e7eb !important; }
            .print-bg { background-color: #f9fafb !important; }
            .print-text { color: #111827 !important; }
            .print-text-muted { color: #6b7280 !important; }
        }
    </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen p-4 sm:p-8 font-sans">

    <div class="max-w-3xl mx-auto mb-8 flex justify-between items-center no-print">
        <button onclick="window.close()" class="text-zinc-400 hover:text-white flex items-center gap-2 transition">
            <i class="fas fa-arrow-left"></i> Close
        </button>
        <button onclick="window.print()" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 font-bold rounded-xl shadow-lg hover:bg-emerald-400 transition flex items-center gap-2">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <div class="max-w-3xl mx-auto bg-zinc-900 border border-white/10 rounded-2xl p-8 sm:p-12 shadow-2xl print-border print-bg">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-white/10 pb-8 mb-8 print-border">
            <div>
                <div class="text-3xl font-black tracking-tighter print-text">
                    SoulMD <span class="text-emerald-400">HUB</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1 print-text-muted">Powered by YSK Limited</div>
            </div>
            <div class="mt-4 sm:mt-0 text-left sm:text-right">
                <h1 class="text-2xl font-bold text-zinc-300 print-text tracking-widest uppercase">INVOICE</h1>
                <p class="text-sm font-mono text-zinc-400 print-text-muted mt-1">#<?= $invoiceNumber ?></p>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between gap-8 mb-12">
            <div>
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2 print-text-muted">Billed To</h3>
                <div class="font-bold text-lg print-text">@<?= htmlspecialchars($invoice['username']) ?></div>
                <?php if ($invoice['email']): ?>
                    <div class="text-sm text-zinc-400 print-text-muted mt-1"><?= htmlspecialchars($invoice['email']) ?></div>
                <?php endif; ?>
            </div>
            <div class="text-left sm:text-right">
                <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-widest mb-2 print-text-muted">Payment Details</h3>
                <div class="text-sm text-zinc-300 print-text mb-1"><span class="text-zinc-500 print-text-muted">Date:</span> <?= $orderDate ?></div>
                <div class="text-sm text-zinc-300 print-text mb-1"><span class="text-zinc-500 print-text-muted">Method:</span> PayPal</div>
                <div class="text-sm text-zinc-300 print-text"><span class="text-zinc-500 print-text-muted">Transaction ID:</span> <span class="font-mono"><?= htmlspecialchars($invoice['paypal_order_id']) ?></span></div>
            </div>
        </div>

        <div class="mb-12">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-white/10 print-border text-zinc-400 print-text-muted text-xs uppercase tracking-widest">
                        <th class="py-3 font-semibold">Description</th>
                        <th class="py-3 font-semibold text-center">Period</th>
                        <th class="py-3 font-semibold text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <tr class="border-b border-white/5 print-border">
                        <td class="py-4 print-text">
                            <div class="font-bold text-base mb-1">SoulMD Hub - <?= strtoupper($invoice['tier_purchased']) ?> Pass</div>
                            <div class="text-zinc-400 print-text-muted text-xs">Unlock premium AI limits, vision capabilities and reasoning engine.</div>
                        </td>
                        <td class="py-4 text-center text-zinc-300 print-text">30 Days</td>
                        <td class="py-4 text-right font-bold text-white print-text"><?= htmlspecialchars($invoice['currency']) ?> $<?= number_format($invoice['amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-16">
            <div class="w-full sm:w-1/2">
                <div class="flex justify-between items-center py-3 border-b border-white/5 print-border text-sm">
                    <span class="text-zinc-400 print-text-muted">Subtotal</span>
                    <span class="text-zinc-300 print-text">$<?= number_format($invoice['amount'], 2) ?></span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-white/5 print-border text-sm">
                    <span class="text-zinc-400 print-text-muted">Tax (0%)</span>
                    <span class="text-zinc-300 print-text">$0.00</span>
                </div>
                <div class="flex justify-between items-center py-4 text-xl font-bold">
                    <span class="text-white print-text">Total Paid</span>
                    <span class="text-emerald-400 print-text"><?= htmlspecialchars($invoice['currency']) ?> $<?= number_format($invoice['amount'], 2) ?></span>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10 print-border pt-6 text-[10px] text-zinc-500 print-text-muted leading-relaxed">
            <p class="mb-2"><strong class="text-zinc-300 print-text">TERMS & CONDITIONS - NON-REFUNDABLE TRANSACTION</strong></p>
            <p class="mb-2">
                This invoice serves as a permanent record of your transaction. By completing this purchase, you acquired immediate digital access to SoulMD Hub's premium server resources, APIs, and proprietary AI processing architectures. 
            </p>
            <p class="uppercase font-semibold text-zinc-400 print-text border border-zinc-700 print-border p-2 inline-block rounded">
                All transactions are final. No refunds, partial credits, or chargebacks will be provided under any circumstances, including lack of usage or account termination.
            </p>
            <p class="mt-4 text-center pt-4 border-t border-white/5 print-border">
                Thank you for your business. For support, please contact <?= htmlspecialchars(SITE_BILLING_EMAIL) ?>.
            </p>
        </div>

    </div>
</body>
</html>