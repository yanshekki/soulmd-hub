<?php
/**
 * SoulMD Hub - Public Creator Profile Portfolio
 * (Dynamic i18n Internationalization & V5 Dual-Track Web2.5 Hybrid Edition)
 * 🚀 Patched: Fixed HTTP 500 DB Column Crash & Restored RPC Market Price Fetching
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

loadTranslations('profile');

$db = Database::getInstance();
$pdo = $db->getConnection();

$usernameParam = $_GET['username'] ?? '';

$userStmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE username = ?");
$userStmt->execute([$usernameParam]);
$profileUser = $userStmt->fetch();

if (!$profileUser) {
    http_response_code(404);
    $pageTitle = __('User Not Found');
    $pageDesc = __('User Not Found Desc');
    require_once __DIR__ . '/../private/includes/header.php';
    ?>
    <div class="max-w-md w-full mx-auto px-4 py-24 text-center animate-fade-in flex-grow flex flex-col justify-center">
        <div class="w-20 h-20 bg-zinc-900 border border-white/10 rounded-2xl flex items-center justify-center mx-auto mb-6 text-zinc-500"><i class="fas fa-user-slash text-3xl"></i></div>
        <h1 class="text-3xl font-bold mb-2 text-white"><?= __('User Not Found') ?></h1>
        <p class="text-sm text-zinc-400 mb-8"><?= __('User Not Found Desc') ?></p>
        <a href="<?= url('/browse') ?>" class="px-6 py-3 bg-emerald-500 text-zinc-950 font-bold rounded-2xl hover:bg-emerald-400 transition shadow-lg w-fit mx-auto"><?= __('Back to Hub') ?></a>
    </div>
    <?php
    require_once __DIR__ . '/../private/includes/footer.php';
    exit;
}

$profileUserId = (int)$profileUser['id'];
$safeUsername = htmlspecialchars($profileUser['username']);

// 🚨 完美修復 HTTP 500：移除不存在的 sale_price 欄位，回歸原本的 Web2+Web3 統計邏輯
$statsStmt = $pdo->prepare("
    SELECT COUNT(*) as total_souls, 
           COALESCE(SUM(like_count), 0) as total_likes, 
           COALESCE(SUM(fork_count), 0) as total_forks 
    FROM souls 
    WHERE user_id = ? AND ((is_public = 1 AND (is_nft = 0 OR is_nft IS NULL)) OR is_nft = 1)
");
$statsStmt->execute([$profileUserId]);
$stats = $statsStmt->fetch();

$totalSouls = (int)($stats['total_souls'] ?? 0);
$totalLikes = (int)($stats['total_likes'] ?? 0);
$totalForks = (int)($stats['total_forks'] ?? 0);

$pageTitle = __('SEO Title', ['username' => $safeUsername]);
$pageDesc = __('SEO Desc', ['username' => $safeUsername]);
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow flex flex-col">
    
    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 mb-10 backdrop-blur-sm shadow-xl flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-cyan-400"></div>
        <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-5 text-center sm:text-left">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-tr from-emerald-400 to-cyan-400 flex items-center justify-center text-zinc-950 font-black text-2xl sm:text-3xl shadow-lg shadow-emerald-500/10 select-none">
                <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">@<?= $safeUsername ?></h1>
                <p class="text-zinc-400 text-xs sm:text-sm mt-1 flex items-center gap-1.5 justify-center sm:justify-start">
                    <i class="far fa-calendar-alt text-zinc-500"></i> <?= date('M Y', strtotime($profileUser['created_at'])) ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 sm:gap-6 text-center border-t md:border-t-0 border-white/5 pt-5 md:pt-0 w-full md:w-auto">
            <div class="px-2 sm:px-4">
                <div class="text-xl sm:text-2xl font-black text-white font-mono"><?= number_format($totalSouls) ?></div>
                <div class="text-[9px] sm:text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1"><?= __('Total Shared') ?></div>
            </div>
            <div class="px-2 sm:px-4 border-x border-white/5">
                <div class="text-xl sm:text-2xl font-black text-emerald-400 font-mono"><?= number_format($totalForks) ?></div>
                <div class="text-[9px] sm:text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1"><?= __('Forks Received') ?></div>
            </div>
            <div class="px-2 sm:px-4">
                <div class="text-xl sm:text-2xl font-black text-red-400 font-mono"><?= number_format($totalLikes) ?></div>
                <div class="text-[9px] sm:text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1"><?= __('Likes Received') ?></div>
            </div>
        </div>
    </div>

    <div class="mb-14">
        <h2 class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-emerald-400 pl-3">
            <i class="fas fa-tools text-emerald-400"></i> <?= __('Web2 Prototype Box') ?>
        </h2>
        
        <div id="web2-container" class="min-h-[200px]"></div>
        <div id="web2-pagination" class="mt-8 flex justify-center items-center w-full select-none"></div>
    </div>

    <div class="mb-8">
        <h2 class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-purple-500 pl-3">
            <i class="fas fa-gem text-purple-400"></i> <?= __('AgentFi NFT Asset Inventory') ?>
        </h2>
        
        <div id="web3-container" class="min-h-[200px]"></div>
        <div id="web3-pagination" class="mt-8 flex justify-center items-center w-full select-none"></div>
    </div>
</div>

<div id="renters-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[500] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeRentersModal()">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-md w-full max-h-[80vh] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
        <div class="p-5 border-b border-white/10 flex justify-between items-center bg-zinc-950/50">
            <h3 class="text-lg font-bold text-white flex items-center gap-2"><i class="fas fa-users text-blue-400"></i> <?= __('Renter List') ?></h3>
            <button type="button" onclick="closeRentersModal()" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-5 overflow-y-auto custom-scrollbar flex-grow bg-zinc-900/50">
            <div id="renters-list-content" class="space-y-3"></div>
        </div>
        <div class="p-4 border-t border-white/10 bg-zinc-950 text-right">
            <button type="button" onclick="closeRentersModal()" class="px-5 py-2 bg-zinc-800 text-white rounded-xl font-bold hover:bg-zinc-700 transition shadow"><?= __('Close') ?></button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<script>
    let web2Page = 1;
    let web3Page = 1;

    const profileUserId = <?= $profileUserId ?>;
    const safeUsername = <?= json_encode($safeUsername, JSON_UNESCAPED_UNICODE) ?>;

    const lang_Modular = <?= json_encode(__('Modular'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_SingleMd = <?= json_encode(__('Single .md'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Unassigned = <?= json_encode(__('Unassigned'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_ViewRepo = <?= json_encode(__('View Repository'), JSON_UNESCAPED_UNICODE) ?>;
    
    const lang_Page = <?= json_encode(__('Page'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Forks = <?= json_encode(__('Forks Received'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Likes = <?= json_encode(__('Likes Received'), JSON_UNESCAPED_UNICODE) ?>;
    const url_hub = <?= json_encode(url('/browse'), JSON_UNESCAPED_UNICODE) ?>;
    const url_prefix = <?= json_encode(url('/soul/'), JSON_UNESCAPED_UNICODE) ?>;

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }
    
    function makeSlug(str) {
        if (!str) return 'unassigned';
        let slug = str.toLowerCase();
        slug = slug.replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|]+/g, '-');
        slug = slug.replace(/^-+|-+$/g, '');
        return encodeURIComponent(slug);
    }

    function getCallbackUrl(actionType) {
        const url = new URL(window.location.origin + window.location.pathname);
        url.searchParams.set('tx_action', actionType);
        return url.toString();
    }

    window.addEventListener('DOMContentLoaded', async () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('transactionHashes')) {
            const txAction = urlParams.get('tx_action');
            if (txAction === 'buy') alert('<?= addslashes(__('Buy success')) ?>');
            else if (txAction === 'rent') alert('<?= addslashes(__('Rent success')) ?>');
            else alert('<?= addslashes(__('Transaction Success')) ?>');
            
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        } else if (urlParams.has('errorMessage')) {
            alert('<?= addslashes(__('Swap fail')) ?>' + decodeURIComponent(urlParams.get('errorMessage')));
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        }

        loadWeb2Souls();
        loadWeb3Souls();
    });

    function changeWeb2Page(page) {
        web2Page = page;
        loadWeb2Souls();
        window.scrollTo({ top: 300, behavior: 'smooth' });
    }

    function changeWeb3Page(page) {
        web3Page = page;
        loadWeb3Souls();
        window.scrollTo({ top: 600, behavior: 'smooth' });
    }

    function renderPagination(containerId, current, totalPages, funcName) {
        const container = document.getElementById(containerId);
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        let html = '';
        html += `<div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">`;
        if (current > 1) {
            html += `<button onclick="${funcName}(${current - 1})" class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-left"></i></button>`;
        } else {
            html += `<button disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"><i class="fas fa-chevron-left"></i></button>`;
        }
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase">${lang_Page} <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        if (current < totalPages) {
            html += `<button onclick="${funcName}(${current + 1})" class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-right"></i></button>`;
        } else {
            html += `<button disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"><i class="fas fa-chevron-right"></i></button>`;
        }
        html += `</div>`;

        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        if (current > 1) {
            html += `<button onclick="${funcName}(${current - 1})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-left text-xs"></i></button>`;
        } else {
            html += `<button disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"><i class="fas fa-chevron-left text-xs"></i></button>`;
        }

        const windowSize = 2; 
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - windowSize && i <= current + windowSize)) {
                if (i === current) {
                    html += `<button class="w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-500 text-zinc-950 font-bold shadow-md transform scale-105 transition">${i}</button>`;
                } else {
                    html += `<button onclick="${funcName}(${i})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition font-medium text-sm shadow">${i}</button>`;
                }
            } else if (i === current - windowSize - 1 || i === current + windowSize + 1) {
                html += `<span class="w-10 h-10 flex items-center justify-center text-zinc-500 tracking-widest text-sm">...</span>`;
            }
        }

        if (current < totalPages) {
            html += `<button onclick="${funcName}(${current + 1})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-right text-xs"></i></button>`;
        } else {
            html += `<button disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"><i class="fas fa-chevron-right text-xs"></i></button>`;
        }
        html += `</div>`;

        container.innerHTML = html;
    }

    async function loadWeb2Souls() {
        const container = document.getElementById('web2-container');
        container.innerHTML = `<div class="flex justify-center py-12 flex-grow items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;
        
        try {
            const res = await fetch(`/api/souls?user_id=${profileUserId}&page=${web2Page}&limit=6&sort=newest&is_nft=0`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
                data.data.forEach(soul => {
                    const tags = soul.domain ? soul.domain.split(',').map(t => t.trim()).filter(Boolean).slice(0, 3) : [];
                    let tagsHtml = '';
                    tags.forEach(t => { tagsHtml += `<span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded shadow-sm">#${escapeHTML(t)}</span>`; });

                    const seoUrl = `${url_prefix}${encodeURIComponent(safeUsername)}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                    const typeLabel = soul.file_type === 'full_soul_folder' ? lang_Modular : lang_SingleMd;
                    const roleLabel = soul.role ? escapeHTML(soul.role) : lang_Unassigned;

                    html += `
                        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-5 sm:p-6 hover:border-emerald-400/40 transition-all shadow-lg flex flex-col justify-between backdrop-blur-sm group h-full">
                            <div>
                                <div class="flex justify-between items-start gap-3 mb-3">
                                    <a href="${seoUrl}" class="font-bold text-lg text-white group-hover:text-emerald-400 transition line-clamp-2 leading-tight">${escapeHTML(soul.title)}</a>
                                    <span class="text-[9px] px-2 py-0.5 rounded font-medium border shrink-0 shadow-sm ${soul.file_type === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20'}">${typeLabel}</span>
                                </div>
                                ${soul.description ? `<p class="text-xs sm:text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                                <div class="flex flex-wrap gap-1.5 mb-5">${tagsHtml}</div>
                            </div>
                            <div class="pt-4 border-t border-white/5 flex flex-col gap-4 mt-auto">
                                <div class="flex items-center justify-between text-xs text-zinc-500">
                                    <span class="truncate pr-2"><i class="fas fa-robot mr-1 text-zinc-600"></i> ${roleLabel}</span>
                                    <div class="flex items-center gap-3 shrink-0 font-mono">
                                        <span title="${lang_Forks}"><i class="fas fa-code-branch text-emerald-500 mr-1"></i><b>${soul.fork_count}</b></span>
                                        <span title="${lang_Likes}"><i class="fas fa-heart text-red-500 mr-1"></i><b>${soul.like_count}</b></span>
                                    </div>
                                </div>
                                <a href="${seoUrl}" class="w-full py-2.5 bg-zinc-800 hover:bg-emerald-500 hover:text-zinc-950 font-bold text-xs text-white rounded-xl text-center border border-white/5 transition shadow-inner">
                                    ${lang_ViewRepo} <i class="fas fa-arrow-right text-[10px] ml-0.5"></i>
                                </a>
                            </div>
                        </div>
                    `;
                });
                html += `</div>`;
                container.innerHTML = html;
                renderPagination('web2-pagination', data.current_page, data.total_pages, 'changeWeb2Page');
            } else {
                container.innerHTML = `
                    <div class="text-center py-12 bg-zinc-900/20 border border-white/5 rounded-3xl flex-grow flex flex-col justify-center items-center">
                        <div class="text-4xl mb-3 opacity-40">📁</div>
                        <p class="text-lg font-bold mb-1 text-zinc-300"><?= addslashes(__('No Web2 souls found')) ?></p>
                        <p class="text-sm text-zinc-500 max-w-xs mx-auto mb-4"><?= addslashes(__('Web2 Empty Desc')) ?></p>
                    </div>
                `;
                document.getElementById('web2-pagination').innerHTML = '';
            }
        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-12 font-medium flex-grow flex items-center justify-center"><i class="fas fa-wifi mr-2"></i> Network Error</div>`;
        }
    }

    async function loadWeb3Souls() {
        const container = document.getElementById('web3-container');
        container.innerHTML = `<div class="flex justify-center py-12 flex-grow items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div></div>`;
        
        try {
            const res = await fetch(`/api/souls?user_id=${profileUserId}&page=${web3Page}&limit=6&sort=newest&is_nft=1`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                
                // 1. 同步拉取 RPC 租客資料與實時價格
                const safeRpcUrl = window.activeNearRpcUrl || "https://free.rpc.fastnear.com";
                const rpcPromises = data.data.map(async (soul) => {
                    soul.market = {};
                    try {
                        const rpcRes = await fetch(safeRpcUrl, {
                            method: 'POST', headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                jsonrpc: "2.0", id: "dontcare", method: "query",
                                params: { request_type: "call_function", finality: "final", account_id: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>", method_name: "get_soul", args_base64: btoa(JSON.stringify({ token_id: "soul_" + soul.id })) }
                            })
                        });
                        const rpcData = await rpcRes.json();
                        if (rpcData.result && rpcData.result.result) {
                            const tokenInfo = JSON.parse(new TextDecoder().decode(new Uint8Array(rpcData.result.result)));
                            if (tokenInfo) soul.market = tokenInfo;
                        }
                    } catch(e) {}
                });
                await Promise.all(rpcPromises);

                // 2. 準備當前登入者之錢包比對
                const wallet = typeof initNearWallet === 'function' ? await initNearWallet() : null;
                const myWallet = wallet && wallet.isSignedIn() ? wallet.getAccountId() : null;

                let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
                data.data.forEach(soul => {
                    const tags = soul.domain ? soul.domain.split(',').map(t => t.trim()).filter(Boolean).slice(0, 3) : [];
                    let tagsHtml = '';
                    tags.forEach(t => { tagsHtml += `<span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded shadow-sm">#${escapeHTML(t)}</span>`; });

                    const seoUrl = `${url_prefix}${encodeURIComponent(safeUsername)}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                    
                    // 🚨 完美修復前端讀價 Bug：改由 RPC 讀取價格，而非從資料庫讀取
                    const isOwner = myWallet && soul.market && soul.market.owner_id === myWallet;
                    const salePrice = soul.market && soul.market.sale_price ? nearApi.utils.format.formatNearAmount(soul.market.sale_price) : null;
                    const rentPrice = soul.market && soul.market.rent_price ? nearApi.utils.format.formatNearAmount(soul.market.rent_price) : null;
                    const marketOwner = soul.market && soul.market.owner_id ? escapeHTML(soul.market.owner_id) : 'Loading...';

                    let activeRenters = [];
                    if (soul.market && soul.market.renters) {
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
                                    <a href="${seoUrl}" class="font-bold text-xl text-white group-hover:text-purple-400 transition line-clamp-2 leading-tight">${escapeHTML(soul.title)}</a>
                                </div>
                                <div class="flex justify-between items-center mb-4 border-b border-white/5 pb-3">
                                    <div class="text-[10px] text-zinc-500 font-mono truncate mr-2">
                                        <?= addslashes(__('Owner:')) ?> <span class="text-purple-300 font-bold">${marketOwner}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button" onclick="showRentersModal('${rentersJson}')" class="text-[10px] bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded font-bold cursor-pointer shadow-sm transition">
                                            <i class="fas fa-users mr-1"></i> ${rentersCount} <?= addslashes(__('Active Renters')) ?>
                                        </button>
                                    </div>
                                </div>
                                ${soul.description ? `<p class="text-xs text-zinc-400 line-clamp-2 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                                <div class="flex flex-wrap gap-1.5 mb-5">${tagsHtml}</div>
                            </div>
                            <div class="mt-auto space-y-3 pt-4 border-t border-white/5">
                                ${salePrice && salePrice !== "0" ? `
                                <div class="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                                    <div class="min-w-0 pr-2">
                                        <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Sale')) ?></div>
                                        <div class="text-lg font-black text-white font-mono truncate">${salePrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                    </div>
                                    <button ${isOwner ? 'disabled' : `onclick="buyMarketSoul(${soul.id}, '${soul.market.sale_price}')"`} class="shrink-0 px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 ${isOwner ? 'opacity-50 cursor-not-allowed' : 'hover:brightness-110'} text-white text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap border-none">
                                        <i class="fas fa-shopping-cart"></i> <?= addslashes(__('Buy Now')) ?>
                                    </button>
                                </div>` : ''}
                                
                                ${rentPrice && rentPrice !== "0" ? `
                                <div class="flex items-center justify-between bg-zinc-950 p-3 rounded-xl border border-white/5">
                                    <div class="min-w-0 pr-2">
                                        <div class="text-[10px] text-zinc-500 font-bold uppercase tracking-wider mb-0.5"><?= addslashes(__('Rent')) ?></div>
                                        <div class="text-lg font-black text-emerald-400 font-mono truncate">${rentPrice} <span class="text-xs text-zinc-500">NEAR</span></div>
                                    </div>
                                    <button ${isOwner ? 'disabled' : `onclick="rentMarketSoul(${soul.id}, '${soul.market.rent_price}')"`} class="shrink-0 px-4 py-2 bg-emerald-500 ${isOwner ? 'opacity-50 cursor-not-allowed text-zinc-950/50' : 'hover:bg-emerald-400 text-zinc-950'} text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap">
                                        <i class="fas fa-handshake"></i> <?= addslashes(__('Rent (30d)')) ?>
                                    </button>
                                </div>` : ''}
                                
                                ${(!salePrice || salePrice === "0") && (!rentPrice || rentPrice === "0") ? `
                                    <a href="${seoUrl}" class="w-full py-2.5 bg-zinc-800 hover:bg-emerald-500 hover:text-zinc-950 font-bold text-xs text-white rounded-xl text-center border border-white/5 transition shadow-inner flex items-center justify-center gap-2">
                                        ${lang_ViewRepo} <i class="fas fa-arrow-right text-[10px]"></i>
                                    </a>
                                ` : ''}
                            </div>
                        </div>`;
                });
                html += `</div>`;
                container.innerHTML = html;
                renderPagination('web3-pagination', data.current_page, data.total_pages, 'changeWeb3Page');
            } else {
                container.innerHTML = `
                    <div class="text-center py-12 bg-zinc-900/20 border border-white/5 rounded-3xl flex-grow flex flex-col justify-center items-center">
                        <div class="text-4xl mb-3 opacity-40">💎</div>
                        <p class="text-lg font-bold mb-1 text-zinc-300"><?= addslashes(__('No NFT assets found')) ?></p>
                        <p class="text-sm text-zinc-500 max-w-xs mx-auto mb-4"><?= addslashes(__('Web3 Empty Desc')) ?></p>
                    </div>
                `;
                document.getElementById('web3-pagination').innerHTML = '';
            }
        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-12 font-medium flex-grow flex items-center justify-center"><i class="fas fa-wifi mr-2"></i> Network Error</div>`;
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
                        <div class="font-mono text-sm text-blue-300 truncate pr-2 font-bold"><i class="fas fa-user-circle text-zinc-600 mr-1.5"></i>${escapeHTML(r.account)}</div>
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

    async function buyMarketSoul(id, rawPrice) {
        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) { await window.connectOrBindWallet(); return; }
        
        await wallet.account().functionCall({ contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>" , methodName: "buy_soul" , args: { token_id: "soul_" + id }, gas: "30000000000000" , attachedDeposit: rawPrice, walletCallbackUrl: getCallbackUrl('buy') });
    }

    async function rentMarketSoul(id, rawPrice) {
        if (!confirm(<?= json_encode(__('Rent Warning Desc'), JSON_UNESCAPED_UNICODE) ?>)) return;

        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) { await window.connectOrBindWallet(); return; }
        
        await wallet.account().functionCall({ contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>" , methodName: "rent_soul" , args: { token_id: "soul_" + id }, gas: "30000000000000" , attachedDeposit: rawPrice, walletCallbackUrl: getCallbackUrl('rent') });
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>