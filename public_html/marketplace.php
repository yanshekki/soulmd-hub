<?php
/**
 * SoulMD Hub - AgentFi Marketplace
 * (Dynamic Blockchain Polling, Web2.5 Integration & $SOUL Swap Widget)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

loadTranslations('marketplace');

$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
require_once __DIR__ . '/../private/includes/header.php';
?>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 border-b border-white/10 pb-8">
        <div>
            <div class="inline-flex items-center gap-2 bg-blue-900/30 text-blue-400 border border-blue-500/30 px-3 py-1 rounded-full text-[10px] font-bold tracking-widest uppercase mb-3 shadow-sm">
                <i class="fas fa-gem"></i> Web3 Market
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tighter text-white"><?= __('AgentFi Marketplace') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-2 max-w-2xl leading-relaxed"><?= __('Market Subtitle') ?></p>
        </div>
        
        <div class="shrink-0" id="wallet-status-container">
            <button onclick="ensureWalletConnection()" class="px-6 py-3 bg-zinc-950 border border-emerald-500/30 text-emerald-400 rounded-xl font-bold hover:bg-emerald-900/30 transition flex items-center gap-2 shadow-lg group">
                <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" class="w-5 h-5 opacity-80 group-hover:opacity-100 transition" alt="NEAR">
                <span id="wallet-btn-text"><?= __('Connect Wallet to Trade') ?></span>
            </button>
        </div>
    </div>

    <div class="bg-gradient-to-r from-emerald-900/40 to-teal-900/40 border border-emerald-500/30 rounded-3xl p-6 sm:p-8 mb-12 shadow-xl backdrop-blur-sm relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
        <div class="flex-1">
            <div class="text-emerald-400 text-[10px] font-bold tracking-widest uppercase mb-1.5"><i class="fas fa-bolt"></i> <?= __('Swap Subtitle') ?></div>
            <h3 class="text-2xl sm:text-3xl font-bold text-white mb-2"><?= __('Swap Title') ?></h3>
            <p class="text-sm text-zinc-400 leading-relaxed max-w-xl"><?= __('Swap Desc') ?></p>
        </div>
        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3 shrink-0">
            <input type="number" id="buy-soul-amount" placeholder="<?= __('Pay Amount') ?>" step="0.1" min="0.1" class="w-full sm:w-48 bg-zinc-950 border border-white/10 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-emerald-400 text-white shadow-inner font-mono">
            <button type="button" onclick="executeBuySoul()" id="buy-soul-btn" class="w-full sm:w-auto px-6 py-3.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-xl transition shadow-lg whitespace-nowrap transform hover:-translate-y-0.5 duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-exchange-alt"></i> <span id="buy-soul-text"><?= __('Swap Button') ?></span>
            </button>
        </div>
    </div>

    <div id="market-container" class="min-h-[400px]">
        <div class="flex flex-col items-center justify-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-500 mb-4"></div>
            <p class="text-zinc-400 font-medium animate-pulse"><?= __('Scanning...') ?></p>
        </div>
    </div>
</div>

<script>
    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }
    
    function makeSlug(str) {
        if (!str) return 'unassigned';
        return encodeURIComponent(str.toLowerCase().replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|]+/g, '-').replace(/^-+|-+$/g, ''));
    }

    async function ensureWalletConnection() {
        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) {
            wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
        } else {
            alert(`Wallet Connected: ${wallet.getAccountId()}`);
        }
    }

    window.addEventListener('DOMContentLoaded', async () => {
        const wallet = await initNearWallet();
        if (wallet.isSignedIn()) {
            document.getElementById('wallet-btn-text').innerText = wallet.getAccountId();
            document.getElementById('wallet-status-container').classList.add('opacity-80');
        }
        loadMarketplace();
    });

    // 🚀 $SOUL 閃兌邏輯 (完美相容 Wallet Selector 批量交易)
    async function executeBuySoul() {
        const btn = document.getElementById('buy-soul-btn');
        const textSpan = document.getElementById('buy-soul-text');
        const amountInput = document.getElementById('buy-soul-amount').value;

        if (!amountInput || parseFloat(amountInput) <= 0) {
            alert('<?= addslashes(__('Invalid amount')) ?>');
            return;
        }

        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) {
            wallet.requestSignIn({ contractId: "<?= defined('NEAR_TOKEN_CONTRACT_ID') ? NEAR_TOKEN_CONTRACT_ID : 'soul.tkn.near' ?>" });
            return;
        }

        const originalText = textSpan.innerHTML;
        textSpan.innerHTML = '<?= addslashes(__('Processing')) ?>';
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');

        try {
            const amountYocto = nearApi.utils.format.parseNearAmount(amountInput.toString());
            const encoder = new TextEncoder(); // 將 JSON 轉為 Uint8Array (API 規定)

            // 構建批量交易 (Batch Transaction)
            const transactions = [
                // 交易 1：(安全機制) 確保買家在 $SOUL 合約註冊了儲存空間
                {
                    receiverId: '<?= defined('NEAR_TOKEN_CONTRACT_ID') ? NEAR_TOKEN_CONTRACT_ID : 'soul.tkn.near' ?>',
                    actions: [
                        nearApi.transactions.functionCall(
                            'storage_deposit',
                            encoder.encode(JSON.stringify({
                                account_id: wallet.getAccountId(),
                                registration_only: true
                            })),
                            '30000000000000', // 30 TGas
                            nearApi.utils.format.parseNearAmount('0.00125') // 0.00125 NEAR 標準押金
                        )
                    ]
                },
                // 交易 2：將 Native NEAR 包裝並射入 Ref Finance 進行 Swap
                {
                    receiverId: 'wrap.near',
                    actions: [
                        // A. 存入 Native NEAR 變成 wNEAR
                        nearApi.transactions.functionCall(
                            'near_deposit',
                            encoder.encode(JSON.stringify({})),
                            '30000000000000', // 30 TGas
                            amountYocto
                        ),
                        // B. 將 wNEAR 射入 AMM 觸發 Swap
                        nearApi.transactions.functionCall(
                            'ft_transfer_call',
                            encoder.encode(JSON.stringify({
                                receiver_id: '<?= defined('NEAR_REF_FINANCE_ID') ? NEAR_REF_FINANCE_ID : 'v2.ref-finance.near' ?>',
                                amount: amountYocto,
                                msg: JSON.stringify({
                                    force: 0,
                                    actions: [{
                                        pool_id: <?= defined('NEAR_POOL_ID') ? NEAR_POOL_ID : 8546 ?>,
                                        token_in: 'wrap.near',
                                        token_out: '<?= defined('NEAR_TOKEN_CONTRACT_ID') ? NEAR_TOKEN_CONTRACT_ID : 'soul.tkn.near' ?>',
                                        amount_in: amountYocto,
                                        min_amount_out: '1' // 接受最低 1 粒，防止滑點失敗
                                    }]
                                })
                            })),
                            '100000000000000', // 100 TGas 確保路由夠氣
                            '1' // 安全要求：夾帶 1 yoctoNEAR
                        )
                    ]
                }
            ];

            // 喚起錢包，一次過簽署並執行這兩筆交易！
            await wallet.requestSignTransactions({
                transactions: transactions,
                callbackUrl: window.location.href // 完成後跳回目前頁面
            });

        } catch(e) {
            alert('<?= addslashes(__('Transaction failed')) ?>');
            textSpan.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

    // 🚀 市集加載與區塊鏈狀態抓取
    async function loadMarketplace() {
        const container = document.getElementById('market-container');
        try {
            const res = await fetch('/api/souls?limit=100&sort=newest');
            const data = await res.json();
            
            if (!data.success || data.data.length === 0) throw new Error("No data");

            const activeListings = [];
            
            const rpcPromises = data.data.map(async (soul) => {
                try {
                    const rpcRes = await fetch('https://rpc.mainnet.near.org', {
                        method: 'POST', headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            jsonrpc: "2.0", id: "dontcare", method: "query",
                            params: { request_type: "call_function", finality: "final", account_id: "<?= NEAR_CONTRACT_ID ?>", method_name: "get_soul", args_base64: btoa(JSON.stringify({ token_id: "soul_" + soul.id })) }
                        })
                    });
                    const rpcData = await rpcRes.json();
                    if (rpcData.result && rpcData.result.result) {
                        const tokenInfo = JSON.parse(new TextDecoder().decode(new Uint8Array(rpcData.result.result)));
                        if (tokenInfo && (tokenInfo.sale_price || tokenInfo.rent_price)) {
                            soul.market = tokenInfo;
                            activeListings.push(soul);
                        }
                    }
                } catch(e) {}
            });
            
            await Promise.all(rpcPromises);

            if (activeListings.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                        <i class="fas fa-store-slash text-4xl text-zinc-600 mb-4"></i>
                        <p class="text-zinc-400 font-medium"><?= addslashes(__('No listings')) ?></p>
                    </div>`;
                return;
            }

            let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
            activeListings.forEach(soul => {
                const seoUrl = `<?= url('/soul/') ?>${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                const salePrice = soul.market.sale_price ? nearApi.utils.format.formatNearAmount(soul.market.sale_price) : null;
                const rentPrice = soul.market.rent_price ? nearApi.utils.format.formatNearAmount(soul.market.rent_price) : null;

                html += `
                    <div class="bg-zinc-900/80 border border-blue-500/20 rounded-3xl p-6 hover:border-blue-400/50 transition-all shadow-xl flex flex-col justify-between h-full backdrop-blur-sm relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-purple-500"></div>
                        
                        <div>
                            <div class="flex justify-between items-start gap-3 mb-2">
                                <a href="${seoUrl}" class="font-bold text-xl text-white hover:text-blue-400 transition line-clamp-2 leading-tight">${escapeHTML(soul.title)}</a>
                            </div>
                            <div class="text-[10px] text-zinc-500 mb-4 font-mono truncate">
                                <?= addslashes(__('Owner:')) ?> <span class="text-blue-300">${escapeHTML(soul.market.owner_id)}</span>
                            </div>
                        </div>

                        <div class="mt-auto space-y-3 pt-4 border-t border-white/10">
                            ${salePrice ? `
                            <div class="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                                <div>
                                    <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Sale')) ?></div>
                                    <div class="text-lg font-black text-white font-mono">${salePrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                </div>
                                <button onclick="buyMarketSoul(${soul.id}, '${soul.market.sale_price}')" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap">
                                    <i class="fas fa-shopping-cart"></i> <?= addslashes(__('Buy Now')) ?>
                                </button>
                            </div>` : ''}

                            ${rentPrice ? `
                            <div class="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                                <div>
                                    <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Rent')) ?></div>
                                    <div class="text-lg font-black text-emerald-400 font-mono">${rentPrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                </div>
                                <button onclick="rentMarketSoul(${soul.id}, '${soul.market.rent_price}')" class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap">
                                    <i class="fas fa-handshake"></i> <?= addslashes(__('Rent Now')) ?>
                                </button>
                            </div>` : ''}
                        </div>
                    </div>
                `;
            });
            html += `</div>`;
            container.innerHTML = html;

        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-20 font-medium"><i class="fas fa-wifi mr-2"></i>Network Error</div>`;
        }
    }

    async function buyMarketSoul(id, rawPrice) {
        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) return wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
        await wallet.account().functionCall({
            contractId: "<?= NEAR_CONTRACT_ID; ?>", methodName: "buy_soul", args: { token_id: "soul_" + id },
            gas: "30000000000000", attachedDeposit: rawPrice, walletCallbackUrl: window.location.href
        });
    }

    async function rentMarketSoul(id, rawPrice) {
        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) return wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
        await wallet.account().functionCall({
            contractId: "<?= NEAR_CONTRACT_ID; ?>", methodName: "rent_soul", args: { token_id: "soul_" + id },
            gas: "30000000000000", attachedDeposit: rawPrice, walletCallbackUrl: window.location.href
        });
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>