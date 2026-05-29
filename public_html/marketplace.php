<?php
/**
 * SoulMD Hub - AgentFi Marketplace
 * (Dynamic Blockchain Polling & Web2.5 Integration)
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
            wallet.requestSignIn({ contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>" });
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

    async function loadMarketplace() {
        const container = document.getElementById('market-container');
        try {
            // 1. 撈取大部份公開的 Souls
            const res = await fetch('/api/souls?limit=100&sort=newest');
            const data = await res.json();
            
            if (!data.success || data.data.length === 0) throw new Error("No data");

            const activeListings = [];
            
            // 2. 併發查詢區塊鏈，過濾出有掛牌價格的資產
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
        if (!wallet.isSignedIn()) return wallet.requestSignIn({ contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>" });
        await wallet.account().functionCall({
            contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>", methodName: "buy_soul", args: { token_id: "soul_" + id },
            gas: "30000000000000", attachedDeposit: rawPrice, walletCallbackUrl: window.location.href
        });
    }

    async function rentMarketSoul(id, rawPrice) {
        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) return wallet.requestSignIn({ contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>" });
        await wallet.account().functionCall({
            contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>", methodName: "rent_soul", args: { token_id: "soul_" + id },
            gas: "30000000000000", attachedDeposit: rawPrice, walletCallbackUrl: window.location.href
        });
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>