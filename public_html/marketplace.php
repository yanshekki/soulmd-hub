<?php
/**
 * SoulMD Hub - AgentFi Marketplace
 * (Dynamic Blockchain Polling, Web2.5 Integration & $SOUL Swap Widget)
 * 🚀 Fixed: Pure MyNearWallet Native Integration & Emerald Contrast UI with RPC Loading (i18n Fixed)
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
    
    <div class="flex flex-wrap items-center justify-between gap-6 mb-10 border-b border-white/10 pb-8">
        <div class="flex-1 min-w-[280px]">
            <div class="inline-flex items-center gap-2 bg-blue-900/30 text-blue-400 border border-blue-500/30 px-3 py-1 rounded-full text-[10px] font-bold tracking-widest uppercase mb-3 shadow-sm">
                <i class="fas fa-gem"></i> Web3 Market
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tighter text-white break-words"><?= __('AgentFi Marketplace') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-2 leading-relaxed break-words"><?= __('Market Subtitle') ?></p>
        </div>
        
        <div class="shrink-0" id="wallet-status-container">
            <button onclick="ensureWalletConnection()" id="marketplace-wallet-btn" class="px-6 py-3.5 bg-gradient-to-r from-emerald-400 to-teal-500 text-zinc-950 rounded-xl font-black hover:brightness-110 transition flex items-center justify-center gap-2 shadow-[0_0_25px_rgba(52,211,153,0.25)] border-none group transform hover:-translate-y-0.5 duration-200">
                <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" id="marketplace-btn-icon" class="w-5 h-5 opacity-90 group-hover:scale-105 transition shrink-0" alt="NEAR">
                <span id="wallet-btn-text" class="truncate max-w-[200px]"><?= __('Connect Wallet to Trade') ?></span>
            </button>
        </div>
    </div>

    <div class="bg-gradient-to-r from-emerald-900/40 to-teal-900/40 border border-emerald-500/30 rounded-3xl p-6 sm:p-8 mb-12 shadow-xl backdrop-blur-sm relative overflow-hidden flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
        <div class="flex-1 min-w-[280px]">
            <div class="text-emerald-400 text-[10px] font-bold tracking-widest uppercase mb-1.5"><i class="fas fa-bolt"></i> <?= __('Swap Subtitle') ?></div>
            <h3 class="text-2xl sm:text-3xl font-bold text-white mb-2"><?= __('Swap Title') ?></h3>
            <p class="text-sm text-zinc-400 leading-relaxed max-w-xl"><?= __('Swap Desc') ?></p>
        </div>
        <div class="shrink-0 flex items-center gap-3 bg-zinc-950/40 p-2.5 rounded-2xl border border-white/5 w-full sm:w-auto">
            <input type="number" id="buy-soul-amount" placeholder="<?= __('Pay Amount') ?>" step="0.1" min="0.1" class="w-full sm:w-48 bg-zinc-900 border border-white/10 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-emerald-400 text-white shadow-inner font-mono">
            <button type="button" onclick="executeBuySoul()" id="buy-soul-btn" class="shrink-0 px-6 py-3.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-xl transition shadow-lg whitespace-nowrap transform hover:-translate-y-0.5 duration-200 flex items-center gap-2">
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
        const btn = document.getElementById('marketplace-wallet-btn');
        const btnText = document.getElementById('wallet-btn-text');
        const btnIcon = document.getElementById('marketplace-btn-icon');
        const originalHTML = btnText.innerHTML;

        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) {
            // 🌟 已修正為 i18n 語言包寫法
            btnText.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> <?= addslashes(__('Connecting to RPC...')) ?>';
            btn.classList.add('opacity-80', 'pointer-events-none');
            if(btnIcon) btnIcon.classList.add('hidden');

            try {
                wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
            } catch(e) {
                btnText.innerHTML = originalHTML;
                btn.classList.remove('opacity-80', 'pointer-events-none');
                if(btnIcon) btnIcon.classList.remove('hidden');
            }
        } else {
            if(confirm("Wallet Connected: " + wallet.getAccountId() + "\nDo you want to sign out?")) {
                wallet.signOut();
                window.location.reload();
            }
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
            const tokenContract = '<?= defined('NEAR_TOKEN_CONTRACT_ID') ? NEAR_TOKEN_CONTRACT_ID : 'soul.tkn.near' ?>';

            const transactions = [
                {
                    receiverId: tokenContract,
                    actions: [
                        {
                            methodName: 'storage_deposit',
                            args: { account_id: wallet.getAccountId(), registration_only: true },
                            gas: '30000000000000',
                            deposit: nearApi.utils.format.parseNearAmount('0.00125')
                        }
                    ]
                },
                {
                    receiverId: 'wrap.near',
                    actions: [
                        {
                            methodName: 'near_deposit',
                            args: {},
                            gas: '30000000000000',
                            deposit: amountYocto
                        },
                        {
                            methodName: 'ft_transfer_call',
                            args: {
                                receiver_id: '<?= defined('NEAR_REF_FINANCE_ID') ? NEAR_REF_FINANCE_ID : 'v2.ref-finance.near' ?>',
                                amount: amountYocto,
                                msg: JSON.stringify({
                                    force: 0,
                                    actions: [{
                                        pool_id: <?= defined('NEAR_POOL_ID') ? NEAR_POOL_ID : 8546 ?>,
                                        token_in: 'wrap.near',
                                        token_out: tokenContract,
                                        amount_in: amountYocto,
                                        min_amount_out: '1'
                                    }]
                                })
                            },
                            gas: '100000000000000',
                            deposit: '1'
                        }
                    ]
                }
            ];

            await wallet.requestSignTransactions({
                transactions: transactions,
                callbackUrl: window.location.href
            });

        } catch(e) {
            alert('<?= addslashes(__('Transaction failed')) ?>');
            textSpan.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

    async function loadMarketplace() {
        const container = document.getElementById('market-container');
        try {
            const res = await fetch('/api/souls?limit=100&sort=newest');
            const data = await res.json();
            if (!data.success || data.data.length === 0) throw new Error("No data");

            const activeListings = [];
            const safeRpcUrl = window.activeNearRpcUrl || "https://free.rpc.fastnear.com";
            
            const rpcPromises = data.data.map(async (soul) => {
                try {
                    const rpcRes = await fetch(safeRpcUrl, {
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
                } catch(e) {
                    console.warn("Listing fetch skipped due to RPC limit", e);
                }
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
                                <div class="min-w-0 pr-2">
                                    <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Sale')) ?></div>
                                    <div class="text-lg font-black text-white font-mono truncate">${salePrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                </div>
                                <button onclick="buyMarketSoul(${soul.id}, '${soul.market.sale_price}')" class="shrink-0 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap">
                                    <i class="fas fa-shopping-cart"></i> <?= addslashes(__('Buy Now')) ?>
                                </button>
                            </div>` : ''}
                            ${rentPrice ? `
                            <div class="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                                <div class="min-w-0 pr-2">
                                    <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Rent')) ?></div>
                                    <div class="text-lg font-black text-emerald-400 font-mono truncate">${rentPrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                </div>
                                <button onclick="rentMarketSoul(${soul.id}, '${soul.market.rent_price}')" class="shrink-0 px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap">
                                    <i class="fas fa-handshake"></i> <?= addslashes(__('Rent Now')) ?>
                                </button>
                            </div>` : ''}
                        </div>
                    </div>`;
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