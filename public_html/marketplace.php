<?php
/**
 * SoulMD Hub - AgentFi Marketplace
 * 🚀 V9 FIXED: Cleaned Wrapper Implementation (Syncs with V16 Dual-Action Format)
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

<main class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow">
    
    <header class="flex flex-col lg:flex-wrap items-center justify-between gap-6 mb-10 border-b border-white/10 pb-8 md:flex-row">
        <div class="flex-1 min-w-[280px]">
            <div class="inline-flex items-center gap-2 bg-purple-950/40 text-purple-400 border border-purple-500/30 px-3 py-1 rounded-full text-[10px] font-bold tracking-widest uppercase mb-3 shadow-sm">
                <i class="fas fa-gem" aria-hidden="true"></i> Web3 Market
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tighter text-white break-words"><?= __('AgentFi Marketplace') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-2 leading-relaxed break-words"><?= __('Market Subtitle') ?></p>
        </div>
        
        <div class="shrink-0" id="wallet-status-container">
            <button onclick="ensureWalletConnection()" id="marketplace-wallet-btn" aria-label="<?= __('Connect Wallet to Trade') ?>" class="px-6 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-black hover:brightness-110 transition flex items-center justify-center gap-2 shadow-[0_0_25px_rgba(147,51,234,0.25)] border-none group transform hover:-translate-y-0.5 duration-200">
                <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" id="marketplace-btn-icon" class="w-5 h-5 opacity-90 group-hover:scale-105 transition shrink-0" alt="NEAR Protocol Logo">
                <span id="wallet-btn-text" class="truncate max-w-[200px]"><?= __('Connect Wallet to Trade') ?></span>
            </button>
        </div>
    </header>

    <section aria-label="Swap Tokens" class="bg-gradient-to-r from-purple-900/40 to-indigo-900/40 border border-purple-500/30 rounded-3xl p-6 sm:p-8 mb-12 shadow-xl backdrop-blur-sm relative overflow-hidden flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="absolute top-0 left-0 w-1 h-full bg-purple-500"></div>
        <div class="flex-1 min-w-[280px]">
            <div class="text-purple-400 text-[10px] font-bold tracking-widest uppercase mb-1.5"><i class="fas fa-bolt" aria-hidden="true"></i> <?= __('Swap Subtitle') ?></div>
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-2"><?= __('Swap Title') ?></h2>
            <p class="text-sm text-zinc-400 leading-relaxed max-w-xl"><?= __('Swap Desc') ?></p>
        </div>
        <div class="shrink-0 flex items-center gap-3 bg-zinc-950/40 p-2.5 rounded-2xl border border-white/5 w-full sm:w-auto">
            <input type="number" id="buy-soul-amount" aria-label="<?= __('Pay Amount') ?>" placeholder="<?= __('Pay Amount') ?>" step="0.1" min="0.1" class="w-full sm:w-48 bg-zinc-900 border border-white/10 rounded-xl px-4 py-3.5 text-sm focus:outline-none focus:border-purple-400 text-white shadow-inner font-mono">
            <button type="button" onclick="executeBuySoul()" id="buy-soul-btn" aria-label="<?= __('Swap Button') ?>" class="shrink-0 px-6 py-3.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition shadow-lg whitespace-nowrap transform hover:-translate-y-0.5 duration-200 flex items-center gap-2">
                <i class="fas fa-exchange-alt" aria-hidden="true"></i> <span id="buy-soul-text"><?= __('Swap Button') ?></span>
            </button>
        </div>
    </section>

    <div id="market-container" class="min-h-[400px]" aria-live="polite">
        <div class="flex flex-col items-center justify-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-500 mb-4" role="status"></div>
            <p class="text-zinc-400 font-medium animate-pulse"><?= __('Scanning...') ?></p>
        </div>
    </div>
    
    <nav id="pagination-container" aria-label="Marketplace Pagination" class="mt-12 flex justify-center items-center w-full"></nav>
</main>

<div id="renters-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[500] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeRentersModal()" aria-modal="true" role="dialog">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-md w-full max-h-[80vh] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
        <div class="p-5 border-b border-white/10 flex justify-between items-center bg-zinc-950/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2"><i class="fas fa-users text-blue-400" aria-hidden="true"></i> <?= __('Renter List') ?></h3>
            <button type="button" onclick="closeRentersModal()" aria-label="<?= __('Close') ?>" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="p-5 overflow-y-auto custom-scrollbar flex-grow bg-zinc-900/50">
            <div id="renters-list-content" class="space-y-3"></div>
        </div>
        <div class="p-4 border-t border-white/10 bg-zinc-950 text-right">
            <button type="button" onclick="closeRentersModal()" class="px-5 py-2 bg-zinc-800 text-white rounded-xl font-bold hover:bg-zinc-700 transition shadow"><?= __('Close') ?></button>
        </div>
    </div>
</div>

<script>
    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;
    const lang_ViewAsset = "<?= addslashes(__('View Asset')) ?>"; 

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }
    
    function makeSlug(str) {
        if (!str) return 'unassigned';
        return encodeURIComponent(str.toLowerCase().replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|]+/g, '-').replace(/^-+|-+$/g, ''));
    }

    function getCallbackUrl(actionType, id) {
        const url = new URL(window.location.origin + window.location.pathname);
        url.searchParams.set('tx_action', actionType);
        if (id) url.searchParams.set('sync_id', id);
        if (currentPage > 1) {
            url.searchParams.set('page', currentPage);
        }
        return url.toString();
    }

    async function updateWalletButton() {
        const btnTextEl = document.getElementById('wallet-btn-text');
        const container = document.getElementById('wallet-status-container');
        if (!btnTextEl) return;
        try {
            const w = await initNearWallet();
            const addr = w ? w.getAccountId() : null;
            if (addr) {
                // Show the current address (prefers DB-bound near_wallet_address if user is web2-logged,
                // else the live wallet selector account). This way, if user has logged-in near address in DB,
                // the button shows it instead of always falling back to "Connect NEAR wallet to trade".
                btnTextEl.innerText = addr;
                if (container) {
                    if (w && w.isSignedIn()) {
                        container.classList.add('opacity-80');
                    } else {
                        container.classList.remove('opacity-80');
                    }
                }
            } else {
                btnTextEl.innerText = '<?= addslashes(__('Connect Wallet to Trade')) ?>';
                if (container) container.classList.remove('opacity-80');
            }
        } catch (e) {
            console.warn('updateWalletButton error:', e);
            btnTextEl.innerText = '<?= addslashes(__('Connect Wallet to Trade')) ?>';
        }
    }

    async function ensureWalletConnection() {
        const wrapper = await initNearWallet();
        if (wrapper && wrapper.isSignedIn()) {
            if(confirm("Wallet Connected: " + wrapper.getAccountId() + "\nDo you want to sign out?")) {
                wrapper.signOut();
                window.location.reload();
            }
        } else {
            await window.connectOrBindWallet();
            // After connect (new account may have been selected), force update the button
            // using the shared update function (handles live getAccountId from current selector state).
            setTimeout(updateWalletButton, 600);
        }
    }

    window.addEventListener('DOMContentLoaded', async () => {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('transactionHashes')) {
            const txAction = urlParams.get('tx_action');
            const syncId = urlParams.get('sync_id');
            
            if (syncId) {
                await fetch(`/api/soul/${syncId}`);
            }

            if (txAction === 'swap') {
                alert('<?= addslashes(__('Swap success')) ?>');
            } else if (txAction === 'buy') {
                alert('<?= addslashes(__('Buy success')) ?>');
            } else if (txAction === 'rent') {
                alert('<?= addslashes(__('Rent success')) ?>');
            } else {
                alert('<?= addslashes(__('Transaction Success')) ?>');
            }
            
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + (urlParams.has('page') ? '?page=' + urlParams.get('page') : '');
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            
        } else if (urlParams.has('errorMessage')) {
            // Real failure (including contract ExecutionError panics like "prefix of undefined" in rent_soul).
            // getErrorMessage now deeply extracts the inner panic message from ActionError JSON.
            const raw = decodeURIComponent(urlParams.get('errorMessage') || '');
            let nice = raw;
            try {
                if (raw.trim().startsWith('{')) {
                    nice = window.getErrorMessage(JSON.parse(raw));
                } else {
                    nice = window.getErrorMessage(raw);
                }
            } catch (_) {
                nice = window.getErrorMessage(raw) || raw;
            }
            alert('<?= addslashes(__('Swap fail')) ?>\n' + nice);
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + (urlParams.has('page') ? '?page=' + urlParams.get('page') : '');
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        }

        await updateWalletButton();

        // Explicit signedIn listener for this page to force button refresh when account changes
        // (previous subscription to observable had no effect for some wallets/switches).
        // Also listen to signedOut to reset the button text immediately.
        if (window.walletSelectorInstance) {
            window.walletSelectorInstance.on("signedIn", async () => {
                await updateWalletButton();
            });
            window.walletSelectorInstance.on("signedOut", () => {
                const btnTextEl = document.getElementById('wallet-btn-text');
                const container = document.getElementById('wallet-status-container');
                if (btnTextEl) btnTextEl.innerText = '<?= addslashes(__('Connect Wallet to Trade')) ?>';
                if (container) container.classList.remove('opacity-80');
            });
        }

        loadMarketplace();
    });

    async function executeBuySoul() {
        const btn = document.getElementById('buy-soul-btn');
        const textSpan = document.getElementById('buy-soul-text');
        const amountInput = document.getElementById('buy-soul-amount').value;

        if (!amountInput || parseFloat(amountInput) <= 0) {
            alert('<?= addslashes(__('Invalid amount')) ?>'); return;
        }

        const wrapper = await initNearWallet();
        if (!wrapper || !wrapper.isSignedIn()) {
            await window.connectOrBindWallet(); return;
        }

        const originalText = textSpan.innerHTML;
        textSpan.innerHTML = '<i class="fas fa-spinner animate-spin"></i> <?= addslashes(__('Processing...')) ?>';
        btn.disabled = true; btn.classList.add('opacity-80', 'cursor-not-allowed');

        try {
            const amountYocto = window.nearApi.utils.format.parseNearAmount(amountInput.toString());
            const tokenContract = '<?= defined('NEAR_TOKEN_CONTRACT_ID') ? NEAR_TOKEN_CONTRACT_ID : 'soul.tkn.near' ?>';

            const transactions = [
                {
                    receiverId: tokenContract,
                    actions: [{ methodName: 'storage_deposit', args: { account_id: wrapper.getAccountId(), registration_only: true }, gas: '30000000000000', deposit: window.nearApi.utils.format.parseNearAmount('0.00125') }]
                },
                {
                    receiverId: 'wrap.near',
                    actions: [
                        { methodName: 'storage_deposit', args: { account_id: wrapper.getAccountId(), registration_only: true }, gas: '30000000000000', deposit: window.nearApi.utils.format.parseNearAmount('0.00125') },
                        { methodName: 'near_deposit', args: {}, gas: '30000000000000', deposit: amountYocto },
                        {
                            methodName: 'ft_transfer_call',
                            args: {
                                receiver_id: '<?= defined('NEAR_REF_FINANCE_ID') ? NEAR_REF_FINANCE_ID : 'v2.ref-finance.near' ?>', 
                                amount: amountYocto,
                                msg: JSON.stringify({ force: 0, actions: [{ pool_id: <?= defined('NEAR_POOL_ID') ? NEAR_POOL_ID : 8546 ?>, token_in: 'wrap.near', token_out: tokenContract, amount_in: amountYocto, min_amount_out: '1' }] })
                            },
                            gas: '100000000000000', 
                            deposit: '1'
                        }
                    ]
                }
            ];
            
            await wrapper.requestSignTransactions({ transactions: transactions, callbackUrl: getCallbackUrl('swap', null) });
        } catch(e) {
            console.error("BuySoul Swap Error:", e); 
            const errMsg = window.getErrorMessage(e) || '';
            // Only suppress the alert for the *benign* redirect artifact of callbackUrl flows.
            // Any real error (contract panic, ExecutionError, user reject, insufficient deposit, etc.)
            // must produce a visible alert so the user knows the tx failed.
            if (errMsg.includes('Transaction not found, but maybe executed') && !errMsg.includes('panick') && !errMsg.includes('ExecutionError') && !errMsg.includes('ActionError')) {
                textSpan.innerHTML = originalText; btn.disabled = false; btn.classList.remove('opacity-80', 'cursor-not-allowed');
                return;
            }
            alert('<?= addslashes(__('Transaction failed')) ?>\n' + errMsg);
            textSpan.innerHTML = originalText; btn.disabled = false; btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

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
        html += `<div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">`;
        html += `<button onclick="changePage(${current - 1})" aria-label="Previous Page" ${current <= 1 ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-purple-400 transition shadow"'}><i class="fas fa-chevron-left" aria-hidden="true"></i></button>`;
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase"><?= addslashes(__('Page')) ?> <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        html += `<button onclick="changePage(${current + 1})" aria-label="Next Page" ${current >= totalPages ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-purple-400 transition shadow"'}><i class="fas fa-chevron-right" aria-hidden="true"></i></button>`;
        html += `</div>`;

        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        html += `<button onclick="changePage(${current - 1})" aria-label="Previous Page" ${current <= 1 ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-purple-400 transition shadow"'}><i class="fas fa-chevron-left text-xs" aria-hidden="true"></i></button>`;

        const windowSize = 2; 
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - windowSize && i <= current + windowSize)) {
                if (i === current) {
                    html += `<button aria-current="page" class="w-10 h-10 flex items-center justify-center rounded-xl bg-purple-500 text-white font-bold shadow-md transform scale-105 transition">${i}</button>`;
                } else {
                    html += `<button onclick="changePage(${i})" aria-label="Page ${i}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-purple-400 transition font-medium text-sm shadow">${i}</button>`;
                }
            } else if (i === current - windowSize - 1 || i === current + windowSize + 1) {
                html += `<span class="w-10 h-10 flex items-center justify-center text-zinc-500 tracking-widest text-sm" aria-hidden="true">...</span>`;
            }
        }

        html += `<button onclick="changePage(${current + 1})" aria-label="Next Page" ${current >= totalPages ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-purple-400 transition shadow"'}><i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></button>`;
        html += `</div>`;

        container.innerHTML = html;
    }

    async function loadMarketplace() {
        const container = document.getElementById('market-container');
        const pagination = document.getElementById('pagination-container');
        
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-500 mb-4" role="status"></div>
                <p class="text-zinc-400 font-medium animate-pulse"><?= addslashes(__('Scanning...')) ?></p>
            </div>`;
        pagination.innerHTML = '';

        try {
            const wrapper = await initNearWallet();
            const myWallet = (wrapper && wrapper.isSignedIn()) ? wrapper.getAccountId() : null;

            const res = await fetch(`/api/souls?limit=12&page=${currentPage}&sort=newest&is_nft=1`);
            const data = await res.json();
            
            if (!data.success || data.data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                        <i class="fas fa-store-slash text-4xl text-zinc-600 mb-4" aria-hidden="true"></i>
                        <p class="text-zinc-400 font-medium"><?= addslashes(__('No listings')) ?></p>
                    </div>`;
                return;
            }

            const rpcPromises = data.data.map(async (soul) => {
                soul.market = {
                    sale_price: soul.sale_price,
                    rent_price: soul.rent_price,
                    owner_id: soul.nft_owner_wallet,
                    renters: {}
                };
                const rpcRes = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soul.id });
                if (rpcRes.success && rpcRes.data) {
                    soul.market = rpcRes.data;
                }
            });
            
            await Promise.all(rpcPromises);

            const activeListings = data.data.filter(s => s.market.sale_price !== null || s.market.rent_price !== null);

            if (activeListings.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                        <i class="fas fa-store-slash text-4xl text-zinc-600 mb-4" aria-hidden="true"></i>
                        <p class="text-zinc-400 font-medium"><?= addslashes(__('No listings')) ?></p>
                    </div>`;
                renderPagination(data.current_page, data.total_pages);
                return;
            }

            let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
            activeListings.forEach(soul => {
                const isOwner = myWallet && soul.market.owner_id === myWallet;
                const seoUrl = `<?= url('/soul/') ?>${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                
                const salePrice = soul.market.sale_price ? window.nearApi.utils.format.formatNearAmount(soul.market.sale_price) : null;
                const rentPrice = soul.market.rent_price ? window.nearApi.utils.format.formatNearAmount(soul.market.rent_price) : null;

                let activeRenters = [];
                if (soul.market.renters) {
                    const nowMs = Date.now();
                    for (const accountId in soul.market.renters) {
                        const expiryMs = Number(BigInt(soul.market.renters[accountId]) / 1000000n);
                        if (expiryMs > nowMs) {
                            activeRenters.push({ account: accountId, expiry: expiryMs });
                        }
                    }
                }
                const rentersJson = encodeURIComponent(JSON.stringify(activeRenters));
                const rentersCount = activeRenters.length;

                html += `
                    <div class="bg-zinc-900/80 border border-purple-500/20 rounded-3xl p-6 hover:border-purple-400/50 transition-all shadow-xl flex flex-col justify-between h-full backdrop-blur-sm relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                        <div>
                            <div class="flex justify-between items-start gap-3 mb-2">
                                <a href="${seoUrl}" title="${escapeHTML(soul.title)} - On-Chain AI Agent" aria-label="View on-chain AI agent ${escapeHTML(soul.title)}" class="font-bold text-xl text-white group-hover:text-purple-400 transition line-clamp-2 leading-tight">${escapeHTML(soul.title)}</a>
                            </div>
                            <div class="flex justify-between items-center mb-4 border-b border-white/5 pb-3">
                                <div class="text-[10px] text-zinc-500 font-mono truncate mr-2">
                                    <?= addslashes(__('Owner:')) ?> <span class="text-purple-300 font-bold">${escapeHTML(soul.market.owner_id)}</span>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" aria-label="View ${rentersCount} Renters" onclick="showRentersModal('${rentersJson}')" class="text-[10px] bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded font-bold cursor-pointer shadow-sm transition">
                                        <i class="fas fa-users mr-1" aria-hidden="true"></i> ${rentersCount} <?= addslashes(__('Active Renters')) ?>
                                    </button>
                                    <div class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded font-bold cursor-help shadow-sm" title="<?= addslashes(__('Floor Desc')) ?>">
                                        <?= addslashes(__('Floor Price')) ?>: <span class="text-white">0.45</span> N
                                    </div>
                                </div>
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
                                <button aria-label="Buy for ${salePrice} NEAR" ${isOwner ? 'disabled' : `onclick="buyMarketSoul(${soul.id}, '${soul.market.sale_price}', this)"`} class="shrink-0 px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 ${isOwner ? 'opacity-50 cursor-not-allowed' : 'hover:brightness-110'} text-white text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap border-none flex items-center justify-center gap-1.5 min-w-[110px]">
                                    <i class="fas fa-shopping-cart" aria-hidden="true"></i> <span><?= addslashes(__('Buy Now')) ?></span>
                                </button>
                            </div>` : ''}
                            
                            ${rentPrice && rentPrice !== "0" ? `
                            <div class="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                                <div class="min-w-0 pr-2">
                                    <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Rent')) ?></div>
                                    <div class="text-lg font-black text-emerald-400 font-mono truncate">${rentPrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                </div>
                                <button aria-label="Rent for ${rentPrice} NEAR" ${isOwner ? 'disabled' : `onclick="rentMarketSoul(${soul.id}, '${soul.market.rent_price}', this)"`} class="shrink-0 px-4 py-2 bg-emerald-500 ${isOwner ? 'opacity-50 cursor-not-allowed text-zinc-950/50' : 'hover:bg-emerald-400 text-zinc-950'} text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap flex items-center justify-center gap-1.5 min-w-[110px]">
                                    <i class="fas fa-handshake" aria-hidden="true"></i> <span><?= addslashes(__('Rent (30d)')) ?></span>
                                </button>
                            </div>` : ''}
                            
                            ${(!salePrice || salePrice === "0") && (!rentPrice || rentPrice === "0") ? `
                                <a href="${seoUrl}" aria-label="View Asset Details" class="w-full py-2.5 bg-zinc-800 hover:bg-emerald-500 hover:text-zinc-950 font-bold text-xs text-white rounded-xl text-center border border-white/5 transition shadow-inner flex items-center justify-center gap-2">
                                    ${lang_ViewAsset} <i class="fas fa-arrow-right text-[10px]" aria-hidden="true"></i>
                                </a>
                            ` : ''}
                        </div>
                    </div>`;
            });
            html += `</div>`;
            container.innerHTML = html;
            
            renderPagination(data.current_page, data.total_pages);

        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-20 font-medium"><i class="fas fa-wifi mr-2" aria-hidden="true"></i><?= addslashes(__('Network Error')) ?></div>`;
        }
    }

    function showRentersModal(encodedJson) {
        const renters = JSON.parse(decodeURIComponent(encodedJson));
        const listContainer = document.getElementById('renters-list-content');
        listContainer.innerHTML = '';

        if (renters.length === 0) {
            listContainer.innerHTML = `<div class="text-center text-zinc-500 py-6"><?= addslashes(__('No active renters')) ?></div>`;
        } else {
            renters.forEach(r => {
                const d = new Date(r.expiry);
                const dateStr = d.toLocaleString();
                listContainer.innerHTML += `
                    <div class="bg-zinc-950 border border-white/5 p-3 rounded-xl flex justify-between items-center hover:border-blue-500/30 transition">
                        <div class="font-mono text-sm text-blue-300 truncate pr-2 font-bold"><i class="fas fa-user-circle text-zinc-600 mr-1.5" aria-hidden="true"></i>${escapeHTML(r.account)}</div>
                        <div class="text-[10px] text-zinc-500 shrink-0 text-right">
                            <div class="uppercase tracking-wider"><?= addslashes(__('Expires At')) ?></div>
                            <div class="text-zinc-300 font-bold">${dateStr}</div>
                        </div>
                    </div>
                `;
            });
        }

        document.body.style.overflow = 'hidden';
        const modal = document.getElementById('renters-modal');
        const content = modal.firstElementChild;
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    function closeRentersModal() {
        document.body.style.overflow = '';
        const modal = document.getElementById('renters-modal');
        const content = modal.firstElementChild;
        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    // 🚀 FIXED: Using wrapper.account().functionCall properly with Dual-Action format
    async function buyMarketSoul(id, rawPrice, btn) {
        const wrapper = await initNearWallet();
        if (!wrapper || !wrapper.isSignedIn()) { await window.connectOrBindWallet(); return; }
        
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> <span><?= addslashes(__('Processing...')) ?></span>';
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        
        try {
            await wrapper.account().functionCall({
                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                methodName: "buy_soul",
                args: { token_id: "soul_" + id },
                gas: "30000000000000",
                attachedDeposit: rawPrice.toString(),
                walletCallbackUrl: getCallbackUrl('buy', id)
            });
        } catch(e) {
            console.error("Buy Transaction Error:", e);
            const errMsg = window.getErrorMessage(e) || '';
            if (errMsg.includes('Transaction not found, but maybe executed') && !errMsg.includes('panick') && !errMsg.includes('ExecutionError') && !errMsg.includes('ActionError')) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
                return;
            }
            // Full recovery: verify buyer now owns or has the token
            try {
                const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + id });
                const currentUser = (await initNearWallet()).getAccountId();
                if (check.success && check.data && (check.data.owner_id === currentUser || (await (async () => {
                    const acc = await window.nearRpcQuery('check_access', { token_id: "soul_" + id, account_id: currentUser });
                    return acc.success && acc.data;
                })()))) {
                    btn.innerHTML = '<?= addslashes(__('Success! Reloading...')) ?>';
                    setTimeout(() => location.reload(), 800);
                    return;
                }
            } catch (_) {}
            alert("<?= addslashes(__('Transaction failed')) ?>: \n" + errMsg);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

    // 🚀 FIXED: Using wrapper.account().functionCall properly with Dual-Action format
    async function rentMarketSoul(id, rawPrice, btn) {
        if (!confirm(<?= json_encode(__('Rent Warning Desc'), JSON_UNESCAPED_UNICODE) ?>)) return;
        
        const wrapper = await initNearWallet();
        if (!wrapper || !wrapper.isSignedIn()) { await window.connectOrBindWallet(); return; }
        
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> <span><?= addslashes(__('Processing...')) ?></span>';
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        
        try {
            await wrapper.account().functionCall({
                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                methodName: "rent_soul",
                args: { token_id: "soul_" + id },
                gas: "30000000000000",
                attachedDeposit: rawPrice.toString(),
                walletCallbackUrl: getCallbackUrl('rent', id)
            });
        } catch(e) {
            console.error("Rent Transaction Error:", e);
            const errMsg = window.getErrorMessage(e) || '';
            if (errMsg.includes('Transaction not found, but maybe executed') && !errMsg.includes('panick') && !errMsg.includes('ExecutionError') && !errMsg.includes('ActionError')) {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
                return;
            }
            // Full recovery: verify renter now has access
            try {
                const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + id });
                const currentUser = (await initNearWallet()).getAccountId();
                if (check.success && check.data) {
                    const acc = await window.nearRpcQuery('check_access', { token_id: "soul_" + id, account_id: currentUser });
                    if (acc.success && acc.data) {
                        btn.innerHTML = '<?= addslashes(__('Rented successfully! Reloading...')) ?>';
                        setTimeout(() => location.reload(), 800);
                        return;
                    }
                }
            } catch (_) {}
            alert("<?= addslashes(__('Transaction failed')) ?>: \n" + errMsg);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>