<?php
/**
 * SoulMD Hub - Printable Invoice Generator (Financial-Grade Full-Status Edition)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

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
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.email 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$paymentId, $userId]);
    $invoice = $stmt->fetch();

    if (!$invoice || (int)$invoice['user_id'] !== $userId) {
        http_response_code(403);
        echo "<div style='color:#ef4444; font-family:sans-serif; text-align:center; margin-top:50px; font-weight:bold;'>🔒 Access Denied: You do not have permission to view this cryptographic record.</div>";
        exit;
    }

    $orderDate = date('F j, Y, H:i', strtotime($invoice['created_at']));
    $invoiceNumber = "INV-" . str_pad($invoice['id'], 6, "0", STR_PAD_LEFT);

    // 🚨 金融級別 PayPal 狀態機與法律條文映射
    $currentStatus = strtolower($invoice['status']);
    $stampText = 'PAID';
    $stampColor = 'text-emerald-500/20 border-emerald-500/20';
    $legalStatusNotice = '';

    switch($currentStatus) {
        case 'completed':
            $stampText = 'COMPLETED';
            $stampColor = 'text-emerald-500/20 border-emerald-500/20';
            break;
        case 'pending':
            $stampText = 'PENDING';
            $stampColor = 'text-amber-500/20 border-amber-500/20';
            $legalStatusNotice = '<strong class="text-amber-400">⏳ STATUS: PENDING CLEARANCE</strong><br>This transaction is currently under review by the payment gateway. Premium cluster allocations will be provisioned automatically upon successful monetary clearance.';
            break;
        case 'failed':
        case 'denied':
        case 'expired':
            $stampText = 'FAILED';
            $stampColor = 'text-red-500/20 border-red-500/20';
            $legalStatusNotice = '<strong class="text-red-400">❌ STATUS: FAILED / DECLINED</strong><br>This statement acts as a record of an unsuccessful transaction attempt. No funds were securely captured, and no licenses were issued.';
            break;
        case 'refunded':
            $stampText = 'REFUNDED';
            $stampColor = 'text-purple-500/20 border-purple-500/20';
            $legalStatusNotice = '<strong class="text-purple-400">↩️ STATUS: REFUNDED</strong><br>A refund has been dispatched for this transaction. All associated premium node access and API context allocations have been successfully revoked and nullified.';
            break;
        case 'reversed':
            $stampText = 'REVERSED';
            $stampColor = 'text-orange-500/20 border-orange-500/20';
            $legalStatusNotice = '<strong class="text-orange-400">🚫 STATUS: DISPUTED / CHARGEBACK</strong><br>This transaction was forcefully reversed via a payment dispute claim. Access has been permanently terminated and the incident securely logged into the audit ledger.';
            break;
        default:
            $stampText = strtoupper($currentStatus);
            $stampColor = 'text-zinc-500/20 border-zinc-500/20';
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
    <link rel="icon" href="/images/icon-192x192.png" sizes="192x192" type="image/png">
    <title>Receipt <?= $invoiceNumber ?> - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @media print {
            @page {
                size: auto;
                margin: 0mm; 
            }
            html, body {
                height: auto !important;
                min-height: auto !important;
                background-color: #09090b !important; 
                color: #ffffff !important;
                margin: 0 !important;
                padding: 20mm 15mm !important; 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important;         
            }
            .no-print { display: none !important; }
            .invoice-card {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important; 
                background-color: #18181b !important; 
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
            <i class="fas fa-print"></i> Print / Save PDF
        </button>
    </div>

    <div class="invoice-card max-w-3xl mx-auto bg-zinc-900 border border-white/10 rounded-2xl p-8 sm:p-12 shadow-2xl transition-all relative overflow-hidden">
        
        <div class="absolute right-6 top-40 border-[6px] <?= $stampColor ?> font-black text-4xl sm:text-6xl tracking-widest px-8 py-2 rounded-2xl uppercase pointer-events-none select-none transform rotate-[15deg] font-mono mix-blend-screen z-0 opacity-80">
            <?= $stampText ?>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-white/10 pb-8 mb-8 relative z-10">
            <div>
                <div class="text-3xl font-black tracking-tighter text-white">
                    SoulMD <span class="text-emerald-400">HUB</span>
                </div>
                <div class="text-xs text-zinc-500 mt-1">Operated by YSK Limited</div>
            </div>
            <div class="mt-4 sm:mt-0 text-left sm:text-right">
                <h1 class="text-2xl font-bold text-zinc-300 tracking-widest uppercase">TRANSACTION RECEIPT</h1>
                <p class="text-sm font-mono text-zinc-400 mt-1">#<?= $invoiceNumber ?></p>
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
                <div class="text-sm text-zinc-300 mb-1"><span class="text-zinc-500">Method:</span> PayPal Checkout Gateway</div>
                <div class="text-sm text-zinc-300"><span class="text-zinc-500">Transaction ID:</span> <span class="font-mono text-xs text-zinc-400"><?= htmlspecialchars($invoice['paypal_order_id']) ?></span></div>
            </div>
        </div>

        <?php if ($legalStatusNotice): ?>
            <div class="mb-10 p-5 bg-zinc-950/90 border border-white/10 rounded-2xl text-xs tracking-wide leading-relaxed text-zinc-300 relative z-10 shadow-inner">
                <?= $legalStatusNotice ?>
            </div>
        <?php endif; ?>

        <div class="mb-12 relative z-10">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-white/10 text-zinc-400 text-xs uppercase tracking-widest">
                        <th class="py-3 font-semibold">Service Description</th>
                        <th class="py-3 font-semibold text-center">Period</th>
                        <th class="py-3 font-semibold text-right">Gross Amount</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-white/5">
                    <tr>
                        <td class="py-5">
                            <div class="font-bold text-base text-white mb-1">SoulMD Hub - <?= strtoupper($invoice['tier_purchased']) ?> License Pass</div>
                            <div class="text-zinc-400 text-xs leading-relaxed max-w-sm">Immediate unmetered unlock of advanced logic architecture cluster tokens, multi-modal asset reasoning capabilities, and isolated system execution window.</div>
                        </td>
                        <td class="py-5 text-center text-zinc-300 font-medium">30 Days</td>
                        <td class="py-5 text-right font-bold text-white font-mono"><?= htmlspecialchars($invoice['currency']) ?> $<?= number_format($invoice['amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mb-16 relative z-10">
            <div class="w-full sm:w-1/2">
                <div class="flex justify-between items-center py-3 border-b border-white/5 text-sm">
                    <span class="text-zinc-400">Subtotal</span>
                    <span class="text-zinc-300 font-mono">$<?= number_format($invoice['amount'], 2) ?></span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-white/5 text-sm">
                    <span class="text-zinc-400">Tax / VAT (0%)</span>
                    <span class="text-zinc-300 font-mono">$0.00</span>
                </div>
                <div class="flex justify-between items-center py-4 text-xl font-bold">
                    <span class="text-white">Total Captured</span>
                    <span class="text-emerald-400 font-mono"><?= htmlspecialchars($invoice['currency']) ?> $<?= number_format($invoice['amount'], 2) ?></span>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10 pt-6 text-[10px] text-zinc-500 leading-relaxed relative z-10">
            <p class="mb-2"><strong class="text-zinc-300 font-bold uppercase tracking-wider">Terms & Conditions - Non-Refundable Digital Goods</strong></p>
            <p class="mb-2">
                This document serves as an official cryptographic and legal certificate of your purchase. By authorizing this financial transaction, you acknowledge that immediate digital token activation and architectural capabilities have been rendered onto your container profile.
            </p>
            <p class="uppercase font-bold text-zinc-400 border border-zinc-800 p-3 rounded-xl bg-zinc-950/60 text-center my-3 shadow-inner">
                ⚠️ All transactions executed within this node framework are final. No refunds, partial credits, or automated chargebacks shall be facilitated under any scenario.
            </p>
            <p class="mt-4 text-center pt-4 border-t border-white/5 font-medium tracking-wide text-zinc-600">
                Thank you for supporting the infrastructure ecosystem. For corporate inquiries, contact <?= htmlspecialchars(defined('SITE_BILLING_EMAIL') ? SITE_BILLING_EMAIL : 'billing@ysk.hk') ?>.
            </p>
        </div>

    </div>
</body>
</html>