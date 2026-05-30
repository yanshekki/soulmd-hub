<?php
/**
 * SoulMD Hub - AgentFi Marketplace
 * (Dynamic Blockchain Polling, Web2.5 Integration & Dynamic Pagination Edition)
 * 🚀 V5 規格完美版：強制定向 API 拉取 is_nft=1 資產，結合多節點 RPC 權威校驗與分頁
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 🌍 載入市集專屬語言包
loadTranslations('marketplace');

$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
require_once __DIR__ . '/../private/includes/header.php';
?>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow">
    
    <div class="flex flex-col lg:flex-wrap items-center justify-between gap-6 mb-10 border-b border-white/10 pb-8 md:flex-row">
        <div class="flex-1 min-w-[280px]">
            <div class="inline-flex items-center gap-2 bg-purple-950/40 text-purple-400 border border-purple-500/30 px-3 py-1 rounded-full text-[10px] font-bold tracking-widest uppercase mb-3 shadow-sm">
                <i class="fas fa-gem"></i> Web3 Market
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tighter text-white break-words"><?= __('AgentFi Marketplace') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-2 leading-relaxed break-words"><?= __('Market Subtitle') ?></p>
        </div>
        
        <div class="shrink-0" id="wallet-status-container">
            <button onclick="ensureWalletConnection()" id="marketplace-wallet-btn" class="px-6 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-black hover:brightness-110 transition flex items-center justify-center gap-2 shadow-[0_0_25px_rgba(147,51,234,0.25)] border-none group transform hover:-translate-y-0.5 duration-200">
                <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" id="marketplace-btn-icon" class="w-5 h-5 opacity-90 group-hover:scale-105 transition shrink-0" alt="NEAR">
                <span id="wallet-btn-text" class="truncate max-w-[200px]"><?= __('Connect Wallet to Trade') ?></span>
            </button>
        </div>
    </div>

    <div class="bg-gradient-to-r from-purple-900/40 to-indigo-900/40 border border-purple-500/30 rounded-3xl p-6 sm:p-8 mb-12 shadow-xl backdrop-blur-sm relative overflow-hidden flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
        <div class="flex-1 min-w-[280px]">
            <div class="text-purple-400 text-[10px] font-bold tracking-widest uppercase mb-1.5"><i class="fas fa-bolt"></i> <?= __('Swap Subtitle') ?></div>
            <h3 class="text-2xl sm:text-3xl font-bold text-white mb-2"><?= __('Swap Title') ?></h3>
            <p class="text-sm text-zinc-400 leading-relaxed max-w-xl"><?= __('Swap Desc') ?></p>
        </div>
        <div class="shrink-0 flex items-center gap-3 bg-zinc-950/40 p-2.5 rounded-2xl border border-white/5 w-full sm:w-auto">
            <input type="number" id="buy-soul-amount" placeholder="<?= __('Pay Amount') ?>" step="0.1" min="0.1" class="w-full sm:w-48 bg-zinc-900 border border-white/10 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-purple-400 text-white shadow-inner font-mono">
            <button type="button" onclick="executeBuySoul()" id="buy-soul-btn" class="shrink-0 px-6 py-3.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition shadow-lg whitespace-nowrap transform hover:-translate-y-0.5 duration-200 flex items-center gap-2">
                <i class="fas fa-exchange-alt"></i> <span id="buy-soul-text"><?= __('Swap Button') ?></span>
            </button>
        </div>
    </div>

    <div id="market-container" class="min-h-[400px]">
        <div class="flex flex-col items-center justify-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-500 mb-4"></div>
            <p class="text-zinc-400 font-medium animate-pulse"><?= __('Scanning...') ?></p>
        </div>
    </div>
    <div id="pagination-container" class="mt-12 flex justify-center items-center w-full"></div>
</div>

<script>
    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;

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
        // 🚨 完美修復：攔截錢包回傳結果，彈出成功或失敗提示！
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('transactionHashes')) {
            alert('<?= addslashes(__('Swap success')) ?>');
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        } else if (urlParams.has('errorMessage')) {
            alert('<?= addslashes(__('Swap fail')) ?>' + decodeURIComponent(urlParams.get('errorMessage')));
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        }

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
        textSpan.innerHTML = '<i class="fas fa-spinner animate-spin"></i> <?= addslashes(__('Processing')) ?>';
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
                        // 🚨 核心防崩潰：為新用戶支付 wrap.near 註冊費，否則 near_deposit 會直接 Panic！
                        {
                            methodName: 'storage_deposit',
                            args: { account_id: wallet.getAccountId(), registration_only: true },
                            gas: '30000000000000',
                            deposit: nearApi.utils.format.parseNearAmount('0.00125')
                        },
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
            console.error("BuySoul Error:", e);
            alert('<?= addslashes(__('Transaction failed')) ?>\n' + e.message);
            textSpan.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

    // 🚀 V5 核心：分頁器與換頁邏輯 (Marketplace 紫色風格)
    function changePage(p) {
        currentPage = p;
        const newUrl = window.location.pathname + '?page=' + currentPage;
        window.history.replaceState({}, '', newUrl);
        loadMarketplace();
        window.scrollTo({ top: 300, behavior: 'smooth' });
    }

    function renderPagination(current, totalPages) {
        const container = document.getElementById('pagination-container');
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        let html = '';
        
        // Mobile UI
        html += `<div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">`;
        html += `<button onclick="changePage(${current - 1})" ${current <= 1 ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-purple-400 transition shadow"'}><i class="fas fa-chevron-left"></i></button>`;
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase"><?= addslashes(__('Page')) ?> <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        html += `<button onclick="changePage(${current + 1})" ${current >= totalPages ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-purple-400 transition shadow"'}><i class="fas fa-chevron-right"></i></button>`;
        html += `</div>`;

        // Desktop UI
        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        html += `<button onclick="changePage(${current - 1})" ${current <= 1 ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-purple-400 transition shadow"'}><i class="fas fa-chevron-left text-xs"></i></button>`;

        const windowSize = 2; 
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - windowSize && i <= current + windowSize)) {
                if (i === current) {
                    html += `<button class="w-10 h-10 flex items-center justify-center rounded-xl bg-purple-500 text-white font-bold shadow-md transform scale-105 transition">${i}</button>`;
                } else {
                    html += `<button onclick="changePage(${i})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-purple-400 transition font-medium text-sm shadow">${i}</button>`;
                }
            } else if (i === current - windowSize - 1 || i === current + windowSize + 1) {
                html += `<span class="w-10 h-10 flex items-center justify-center text-zinc-500 tracking-widest text-sm">...</span>`;
            }
        }

        html += `<button onclick="changePage(${current + 1})" ${current >= totalPages ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-purple-400 transition shadow"'}><i class="fas fa-chevron-right text-xs"></i></button>`;
        html += `</div>`;

        container.innerHTML = html;
    }

    // 🚀 V5 核心：結合分頁並透過 RPC 拉取掛售狀態
    async function loadMarketplace() {
        const container = document.getElementById('market-container');
        const pagination = document.getElementById('pagination-container');
        
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-500 mb-4"></div>
                <p class="text-zinc-400 font-medium animate-pulse"><?= addslashes(__('Scanning...')) ?></p>
            </div>`;
        pagination.innerHTML = '';

        try {
            // 🌟 加入分頁參數 limit=12 & page=X
            const res = await fetch(`/api/souls?limit=12&page=${currentPage}&sort=newest&is_nft=1`);
            const data = await res.json();
            
            if (!data.success || data.data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                        <i class="fas fa-store-slash text-4xl text-zinc-600 mb-4"></i>
                        <p class="text-zinc-400 font-medium"><?= addslashes(__('No listings')) ?></p>
                    </div>`;
                return;
            }

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
                        
                        if (tokenInfo && (tokenInfo.sale_price !== null || tokenInfo.rent_price !== null)) {
                            soul.market = tokenInfo;
                            activeListings.push(soul);
                        }
                    }
                } catch(e) {
                    console.warn("Listing fetch skipped", e);
                }
            });
            await Promise.all(rpcPromises);

            if (activeListings.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                        <i class="fas fa-store-slash text-4xl text-zinc-600 mb-4"></i>
                        <p class="text-zinc-400 font-medium"><?= addslashes(__('No listings')) ?></p>
                    </div>`;
                
                // 若這頁全都是未上架的，仍然需要渲染分頁器供用戶切換
                renderPagination(data.current_page, data.total_pages);
                return;
            }

            let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
            activeListings.forEach(soul => {
                const seoUrl = `<?= url('/soul/') ?>${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                
                const salePrice = soul.market.sale_price ? nearApi.utils.format.formatNearAmount(soul.market.sale_price) : null;
                const rentPrice = soul.market.rent_price ? nearApi.utils.format.formatNearAmount(soul.market.rent_price) : null;

                html += `
                    <div class="bg-zinc-900/80 border border-purple-500/20 rounded-3xl p-6 hover:border-purple-400/50 transition-all shadow-xl flex flex-col justify-between h-full backdrop-blur-sm relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                        <div>
                            <div class="flex justify-between items-start gap-3 mb-2">
                                <a href="${seoUrl}" class="font-bold text-xl text-white group-hover:text-purple-400 transition line-clamp-2 leading-tight">${escapeHTML(soul.title)}</a>
                            </div>
                            <div class="text-[10px] text-zinc-500 mb-4 font-mono truncate">
                                <?= addslashes(__('Owner:')) ?> <span class="text-purple-300 font-bold">${escapeHTML(soul.market.owner_id)}</span>
                            </div>
                            ${soul.description ? `<p class="text-xs text-zinc-400 line-clamp-2 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                        </div>
                        <div class="mt-auto space-y-3 pt-4 border-t border-white/5">
                            ${salePrice && salePrice !== "0" ? `
                            <div class="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                                <div class="min-w-0 pr-2">
                                    <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Sale')) ?></div>
                                    <div class="text-lg font-black text-white font-mono truncate">${salePrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                </div>
                                <button onclick="buyMarketSoul(${soul.id}, '${soul.market.sale_price}')" class="shrink-0 px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:brightness-110 text-white text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap border-none">
                                    <i class="fas fa-shopping-cart"></i> <?= addslashes(__('Buy Now')) ?>
                                </button>
                            </div>` : ''}
                            
                            ${rentPrice && rentPrice !== "0" ? `
                            <div class="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                                <div class="min-w-0 pr-2">
                                    <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Rent')) ?></div>
                                    <div class="text-lg font-black text-emerald-400 font-mono truncate">${rentPrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                </div>
                                <button onclick="rentMarketSoul(${soul.id}, '${soul.market.rent_price}')" class="shrink-0 px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap">
                                    <i class="fas fa-handshake"></i> <?= addslashes(__('Rent (30d)')) ?>
                                </button>
                            </div>` : ''}
                        </div>
                    </div>`;
            });
            html += `</div>`;
            container.innerHTML = html;
            
            // 渲染分頁
            renderPagination(data.current_page, data.total_pages);

        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-20 font-medium"><i class="fas fa-wifi mr-2"></i><?= addslashes(__('Network Error')) ?></div>`;
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