<?php
/**
 * SoulMD Hub - Creator Workspace & Model Management Dashboard
 * 🚀 V5.2 FIXED: Bulletproof API Error Handling & Strict JSON Parsing
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';
require_once __DIR__ . '/../private/src/ApiSecurity.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

// Centralized CSRF token (replaces repeated bin2hex block)
$csrfToken = ensureCsrfToken();

loadTranslations('my-souls');

$db = Database::getInstance();
$pdo = $db->getConnection();

$user_id = $_SESSION['user_id'];
$uStmt = $pdo->prepare("SELECT username, near_wallet_address FROM users WHERE id = ?");
$uStmt->execute([$user_id]);
$currentUserRow = $uStmt->fetch();

$nearWallet = $currentUserRow['near_wallet_address'] ?? null;
$username = $currentUserRow['username'] ?? 'anonymous';

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$topDomains = $pdo->query("SELECT name FROM tags_domain ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);
$topCompatibilities = $pdo->query("SELECT name FROM tags_compatibility ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

$sort = $_GET['sort'] ?? 'newest';

$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');

require_once __DIR__ . '/../private/includes/header.php';
?>

<main class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow">
    
    <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10 border-b border-white/10 pb-6">
        <div>
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tighter"><?= __('My Souls') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-2"><?= __('Manage and edit your uploaded AI personalities') ?></p>
        </div>
        
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-3 w-full lg:w-auto">
            <select id="sort-filter" aria-label="Sort AI Models" onchange="changeSort(this.value)" class="col-span-2 sm:col-span-1 w-full sm:w-auto px-4 py-3 sm:py-2.5 text-sm bg-zinc-900 border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition focus:outline-none focus:border-emerald-400 shadow-inner cursor-pointer appearance-none">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= __('  Newest') ?></option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>><?= __('  Like Count') ?></option>
                <option value="forks" <?= $sort === 'forks' ? 'selected' : '' ?>><?= __('  Fork Count') ?></option>
            </select>
            
            <a href="<?= url('/profile/' . rawurlencode($username)) ?>" target="_blank" title="<?= __('Profile') ?>" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition flex items-center justify-center gap-2 whitespace-nowrap">
                <i class="fas fa-external-link-alt text-[10px] text-zinc-500" aria-hidden="true"></i> <?= __('Profile') ?>
            </a>
            <a href="<?= url('/my-api') ?>" title="<?= __('My API Key') ?>" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-emerald-500/30 text-emerald-400 rounded-2xl hover:bg-emerald-900/10 transition text-center whitespace-nowrap">
                <?= __('My API Key') ?>
            </a>
            <a href="<?= url('/upload') ?>" aria-label="<?= __('New Soul') ?>" class="col-span-2 sm:col-span-1 px-6 py-3 sm:py-2.5 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center justify-center gap-2 shadow-lg w-full sm:w-auto">
                <i class="fas fa-plus" aria-hidden="true"></i> <?= __('New Soul') ?>
            </a>
        </div>
    </header>

    <section aria-labelledby="web2-heading" class="mb-14">
        <h2 id="web2-heading" class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-emerald-400 pl-3">
            <i class="fas fa-tools text-emerald-400" aria-hidden="true"></i> <?= __('Web2 Prototype Box') ?>
        </h2>
        
        <div id="web2-container" class="min-h-[250px]" aria-live="polite"></div>
        <nav id="web2-pagination" aria-label="Web2 Models Pagination" class="mt-8 flex justify-center items-center w-full"></nav>
    </section>

    <section aria-labelledby="web3-heading" class="mb-14">
        <h2 id="web3-heading" class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-purple-500 pl-3">
            <i class="fas fa-gem text-purple-400" aria-hidden="true"></i> <?= __('AgentFi NFT Asset Inventory') ?>
        </h2>
        
        <?php if (empty($nearWallet)): ?>
            <div class="text-center py-12 bg-purple-950/10 border border-dashed border-purple-500/30 rounded-3xl p-8">
                <i class="fas fa-wallet text-purple-400 text-4xl mb-4" aria-hidden="true"></i>
                <h3 class="text-lg font-bold text-white mb-2"><?= __('No Web3 Wallet Detected') ?></h3>
                <p class="text-sm text-zinc-400 max-w-md mx-auto mb-6"><?= __('Wallet bind prompt') ?></p>
                <button type="button" onclick="window.connectOrBindWallet()" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-purple-500/20"><i class="fas fa-link" aria-hidden="true"></i> <?= __('Go to Bind Wallet') ?></button>
            </div>
        <?php else: ?>
            <div id="web3-container" class="min-h-[250px]" aria-live="polite"></div>
            <nav id="web3-pagination" aria-label="Web3 Assets Pagination" class="mt-8 flex justify-center items-center w-full"></nav>
        <?php endif; ?>
    </section>

    <section aria-labelledby="rented-heading">
        <h2 id="rented-heading" class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-blue-500 pl-3">
            <i class="fas fa-handshake text-blue-400" aria-hidden="true"></i> <?= __('My Rented Agents') ?>
        </h2>
        
        <?php if (empty($nearWallet)): ?>
            <div class="text-center py-12 bg-blue-950/10 border border-dashed border-blue-500/30 rounded-3xl p-8">
                <i class="fas fa-wallet text-blue-400 text-4xl mb-4" aria-hidden="true"></i>
                <p class="text-sm text-zinc-400 max-w-md mx-auto mb-6"><?= __('Wallet bind prompt') ?></p>
            </div>
        <?php else: ?>
            <div id="rented-container" class="min-h-[250px]" aria-live="polite"></div>
        <?php endif; ?>
    </section>

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

<?php require_once __DIR__ . '/../private/includes/my-souls-modals.php'; ?>
<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<script>
    const lang_PrivateOnlyMe = <?= json_encode(__('Private Only Me'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Public = <?= json_encode(__('Public'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Modular = <?= json_encode(__('Modular'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_SingleMd = <?= json_encode(__('Single .md'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Unassigned = <?= json_encode(__('Unassigned'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Forks = <?= json_encode(__('Forks'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Likes = <?= json_encode(__('Likes'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Edit = <?= json_encode(__('Edit'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_VersionHistory = <?= json_encode(__('Version History'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_View = <?= json_encode(__('View'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_AgentNFTAsset = <?= json_encode(__('Agent NFT Asset'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_BurnAndRefund = <?= json_encode(__('Burn and Refund'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Page = <?= json_encode(__('Page'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_NoSouls = <?= json_encode(__('No souls shared yet'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_NoNFTs = <?= json_encode(__('No NFT assets'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_BurnConfirm = <?= json_encode(__('Burn Confirm'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_PermDelete = <?= json_encode(__('Perm Delete Confirm'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_DeleteSoul = <?= json_encode(__('Delete Soul'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_FloorPrice = <?= json_encode(__('Floor Price'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_FloorDesc = <?= json_encode(__('Floor Desc'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_ActiveRentersError = <?= json_encode(__('Active renters error'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_ActiveRenters = <?= json_encode(__('Active Renters'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_ExpiresAt = <?= json_encode(__('Expires At'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_NoActiveRenters = <?= json_encode(__('No active renters'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_NoRentedAssets = <?= json_encode(__('No rented assets'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_RentExpiresAt = <?= json_encode(__('Rent Expires At'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_EnterChat = <?= json_encode(__('Enter Chat'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_GoToMarketplace = <?= json_encode(__('Go to Marketplace'), JSON_UNESCAPED_UNICODE) ?>;
    
    const url_soul_prefix = <?= json_encode(url('/soul/'), JSON_UNESCAPED_UNICODE) ?>;
    const url_edit_prefix = <?= json_encode(url('/edit/'), JSON_UNESCAPED_UNICODE) ?>;
    const url_versions = <?= json_encode(url('/soul-versions/'), JSON_UNESCAPED_UNICODE) ?>;
    const currentUsername = <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>;
    
    const hasWallet = <?= !empty($nearWallet) ? 'true' : 'false' ?>;
    const boundWallet = <?= json_encode($nearWallet, JSON_UNESCAPED_UNICODE) ?>;
    
    let web2Page = 1;
    let web3Page = 1;
    let currentSort = '<?= htmlspecialchars($sort) ?>';

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }
    
    function makeSlug(str) {
        if (!str) return 'unassigned';
        return encodeURIComponent(str.toLowerCase().replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|]+/g, '-').replace(/^-+|-+$/g, ''));
    }

    function changeSort(val) {
        currentSort = val;
        web2Page = 1;
        web3Page = 1;
        loadWeb2Souls();
        if(hasWallet) {
            loadWeb3Souls();
            loadRentedSouls();
        }
        const newUrl = window.location.pathname + '?sort=' + val;
        window.history.replaceState({}, '', newUrl);
    }

    function changeWeb2Page(p) { web2Page = p; loadWeb2Souls(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    function changeWeb3Page(p) { web3Page = p; loadWeb3Souls(); window.scrollTo({ top: 500, behavior: 'smooth' }); }

    function renderPagination(containerId, current, totalPages, funcName) {
        const container = document.getElementById(containerId);
        if (totalPages <= 1) { container.innerHTML = ''; return; }
        
        let html = '';
        html += `<div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">`;
        html += `<button onclick="${funcName}(${current - 1})" aria-label="Previous Page" ${current <= 1 ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-left" aria-hidden="true"></i></button>`;
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase">${lang_Page} <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        html += `<button onclick="${funcName}(${current + 1})" aria-label="Next Page" ${current >= totalPages ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-right" aria-hidden="true"></i></button>`;
        html += `</div>`;

        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        html += `<button onclick="${funcName}(${current - 1})" aria-label="Previous Page" ${current <= 1 ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-left text-xs" aria-hidden="true"></i></button>`;

        const windowSize = 2; 
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - windowSize && i <= current + windowSize)) {
                if (i === current) {
                    html += `<button aria-current="page" class="w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-500 text-zinc-950 font-bold shadow-md transform scale-105 transition">${i}</button>`;
                } else {
                    html += `<button aria-label="Page ${i}" onclick="${funcName}(${i})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition font-medium text-sm shadow">${i}</button>`;
                }
            } else if (i === current - windowSize - 1 || i === current + windowSize + 1) {
                html += `<span class="w-10 h-10 flex items-center justify-center text-zinc-500 tracking-widest text-sm" aria-hidden="true">...</span>`;
            }
        }

        html += `<button onclick="${funcName}(${current + 1})" aria-label="Next Page" ${current >= totalPages ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></button>`;
        html += `</div>`;

        container.innerHTML = html;
    }

    async function loadWeb2Souls() {
        const container = document.getElementById('web2-container');
        container.innerHTML = `<div class="flex justify-center py-12 flex-grow items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400" aria-label="Loading"></div></div>`;
        
        try {
            const res = await fetch(`/api/souls?scope=me&is_nft=0&page=${web2Page}&sort=${currentSort}`);
            const data = await res.json();
            
            if (data.success && data.data.length > 0) {
                let html = `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">`;
                data.data.forEach(soul => {
                    const dateObj = new Date(soul.created_at.replace(/-/g, '/'));
                    const dateStr = isNaN(dateObj) ? '' : dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    let tagsHtml = '';
                    if (soul.domain) {
                        soul.domain.split(',').map(t => t.trim()).filter(Boolean).slice(0,3).forEach(t => {
                            tagsHtml += `<span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded shadow-sm">#${escapeHTML(t)}</span>`;
                        });
                    }

                    const seoUrl = `${url_soul_prefix}${encodeURIComponent(currentUsername)}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;

                    html += `
                    <div class="soul-card bg-zinc-900/60 border border-white/10 rounded-3xl p-5 sm:p-6 hover:border-emerald-400/40 transition-all flex flex-col justify-between backdrop-blur-sm shadow-lg" data-id="${soul.id}">
                        <div>
                            <div class="flex justify-between items-start gap-3 mb-3">
                                <div>
                                    <div class="font-bold text-lg sm:text-xl text-white tracking-tight mb-1 line-clamp-2 leading-tight">${escapeHTML(soul.title)}</div>
                                    <div class="text-[10px] sm:text-xs text-zinc-500 flex items-center gap-1.5 flex-wrap">
                                        <span>${escapeHTML(soul.role_icon || ' ')} ${escapeHTML(soul.role_name || lang_Unassigned)}</span><span> </span><span>${dateStr}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2 shrink-0 flex-col items-end">
                                    ${soul.is_public == 0 
                                        ? `<span class="text-[10px] px-2.5 py-1 rounded-full font-bold border bg-red-500/10 text-red-400 border-red-500/20 shadow-sm"><i class="fas fa-lock mr-1" aria-hidden="true"></i>${lang_PrivateOnlyMe}</span>`
                                        : `<span class="text-[10px] px-2.5 py-1 rounded-full font-medium border bg-emerald-500/10 text-emerald-400 border-emerald-500/20 shadow-sm"><i class="fas fa-globe mr-1" aria-hidden="true"></i>${lang_Public}</span>`
                                    }
                                    <span class="text-[9px] px-2 py-0.5 rounded font-medium border ${soul.file_type === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20'} shadow-sm">
                                        ${soul.file_type === 'full_soul_folder' ? lang_Modular : lang_SingleMd}
                                    </span>
                                </div>
                            </div>
                            ${soul.description ? `<p class="text-xs sm:text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                            <div class="flex flex-wrap gap-1.5 mb-6">${tagsHtml}</div>
                        </div>
                        <div class="pt-4 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-auto">
                            <div class="flex items-center gap-4 text-xs text-zinc-500">
                                <span title="${lang_Forks}" aria-label="${soul.fork_count} Forks"><i class="fas fa-code-branch mr-1 text-emerald-500" aria-hidden="true"></i><b class="text-zinc-300">${soul.fork_count}</b></span>
                                <span title="${lang_Likes}" aria-label="${soul.like_count} Likes"><i class="fas fa-heart mr-1 text-red-500" aria-hidden="true"></i><b class="text-zinc-300">${soul.like_count}</b></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="${url_edit_prefix}${soul.id}" title="${lang_Edit} - ${escapeHTML(soul.title)}" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-xl border border-white/5 transition flex-1 sm:flex-auto text-center"><i class="fas fa-edit" aria-hidden="true"></i> ${lang_Edit}</a>
                                <a href="${url_versions}${soul.id}" title="${lang_VersionHistory} - ${escapeHTML(soul.title)}" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-zinc-200 rounded-xl border border-white/5 transition flex items-center justify-center"><i class="fas fa-history" aria-hidden="true"></i></a>
                                
                                <button onclick="deleteWeb2Soul(${soul.id}, this)" aria-label="${lang_DeleteSoul}" class="px-4 py-2.5 sm:p-2 text-xs text-zinc-500 hover:text-red-400 transition bg-zinc-800 sm:bg-transparent rounded-xl sm:rounded-none border border-white/5 sm:border-none flex items-center justify-center min-w-[44px]"><i class="far fa-trash-alt sm:text-base" aria-hidden="true"></i></button>
                                
                                <a href="${seoUrl}" aria-label="${lang_View} - ${escapeHTML(soul.title)}" class="px-5 py-2.5 sm:py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow flex-1 sm:flex-auto">${lang_View}</a>
                            </div>
                        </div>
                    </div>
                    `;
                });
                html += `</div>`;
                container.innerHTML = html;
                renderPagination('web2-pagination', data.current_page, data.total_pages, 'changeWeb2Page');
            } else {
                container.innerHTML = `
                <div class="text-center py-12 bg-zinc-900/20 border border-dashed border-white/5 rounded-3xl">
                    <div class="mx-auto w-12 h-12 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-xl mb-4 text-zinc-500"><i class="fas fa-code text-xl" aria-hidden="true"></i></div>
                    <p class="text-zinc-400 text-sm">${lang_NoSouls}</p>
                </div>`;
                document.getElementById('web2-pagination').innerHTML = '';
            }
        } catch(e) {
            container.innerHTML = `<div class="text-red-400 text-center py-12 border border-red-500/20 bg-red-900/10 rounded-2xl"><i class="fas fa-exclamation-triangle mr-2" aria-hidden="true"></i> <span class="font-mono text-sm">${escapeHTML(window.getErrorMessage(e))}</span></div>`;
        }
    }

    async function loadWeb3Souls() {
        if (!hasWallet) return;
        const container = document.getElementById('web3-container');
        container.innerHTML = `<div class="flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500" aria-label="Loading"></div></div>`;
        
        try {
            const res = await fetch(`/api/souls?scope=me&is_nft=1&page=${web3Page}&sort=${currentSort}`);
            const data = await res.json();
            
            if (data.success && data.data.length > 0) {
                const rpcPromises = data.data.map(async (soul) => {
                    soul.market = {};
                    const rpcRes = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soul.id });
                    if (rpcRes.success && rpcRes.data) {
                        soul.market = rpcRes.data;
                    }
                });
                await Promise.all(rpcPromises);

                let html = `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">`;
                data.data.forEach(soul => {
                    const dateObj = new Date(soul.created_at.replace(/-/g, '/'));
                    const dateStr = isNaN(dateObj) ? '' : dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    
                    let tagsHtml = '';
                    if (soul.domain) {
                        soul.domain.split(',').map(t => t.trim()).filter(Boolean).slice(0,3).forEach(t => {
                            tagsHtml += `<span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded shadow-sm">#${escapeHTML(t)}</span>`;
                        });
                    }

                    const seoUrl = `${url_soul_prefix}${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                    
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

                    let burnBtnHtml = '';
                    if (rentersCount > 0) {
                        burnBtnHtml = `<button disabled aria-label="${lang_ActiveRentersError}" class="px-4 py-2.5 sm:p-2 text-xs text-zinc-500 bg-zinc-800 sm:bg-transparent rounded-xl sm:rounded-none border border-white/5 sm:border-none flex items-center justify-center opacity-30 cursor-not-allowed min-w-[44px]"><i class="fas fa-fire-alt sm:text-base" aria-hidden="true"></i></button>`;
                    } else {
                        burnBtnHtml = `<button onclick="burnWeb3Soul(${soul.id}, this)" aria-label="${lang_BurnAndRefund}" class="px-4 py-2.5 sm:p-2 text-xs text-zinc-500 hover:text-red-400 transition bg-zinc-800 sm:bg-transparent rounded-xl sm:rounded-none border border-white/5 sm:border-none flex items-center justify-center min-w-[44px]" title="${lang_BurnAndRefund}"><i class="fas fa-fire-alt sm:text-base" aria-hidden="true"></i></button>`;
                    }

                    html += `
                    <div class="soul-card bg-zinc-900/60 border border-purple-500/20 rounded-3xl p-5 sm:p-6 hover:border-purple-400/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-lg relative overflow-hidden" data-id="${soul.id}">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-transparent"></div>
                        <div>
                            <div class="flex justify-between items-start gap-3 mb-3">
                                <div>
                                    <div class="font-bold text-lg sm:text-xl text-white tracking-tight mb-1 line-clamp-2 leading-tight">${escapeHTML(soul.title)}</div>
                                    <div class="text-[10px] sm:text-xs text-zinc-500 flex items-center gap-1.5 flex-wrap">
                                        <span>${escapeHTML(soul.role_icon || ' ')} ${escapeHTML(soul.role_name || lang_Unassigned)}</span><span> </span><span>${dateStr}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2 shrink-0 flex-col items-end">
                                    <span class="text-[10px] px-2.5 py-1 rounded-full font-bold border bg-purple-500/10 text-purple-400 border-purple-500/20 shadow-sm">
                                        <i class="fas fa-cube mr-1" aria-hidden="true"></i>${lang_AgentNFTAsset}
                                    </span>
                                    <span class="text-[9px] px-2 py-0.5 rounded font-bold border bg-emerald-500/10 text-emerald-400 border-emerald-500/20 shadow-sm cursor-help" title="${lang_FloorDesc}">
                                        ${lang_FloorPrice}: 0.45 N
                                    </span>
                                </div>
                            </div>
                            <div class="mb-3 flex items-center gap-2">
                                <button type="button" aria-label="View ${rentersCount} Renters" onclick="showRentersModal('${rentersJson}')" class="text-[10px] bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20 px-2.5 py-1 rounded-md font-bold cursor-pointer shadow-sm transition inline-flex items-center gap-1.5">
                                    <i class="fas fa-users" aria-hidden="true"></i> ${rentersCount} ${lang_ActiveRenters}
                                </button>
                            </div>
                            ${soul.description ? `<p class="text-xs sm:text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                            <div class="flex flex-wrap gap-1.5 mb-6">${tagsHtml}</div>
                        </div>
                        <div class="pt-4 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-auto">
                            <div class="flex items-center gap-4 text-xs text-zinc-500 font-mono">
                                <span title="${lang_Forks}" aria-label="${soul.fork_count} Forks"><i class="fas fa-code-branch mr-1 text-emerald-500" aria-hidden="true"></i><b>${soul.fork_count}</b></span>
                                <span title="${lang_Likes}" aria-label="${soul.like_count} Likes"><i class="fas fa-heart mr-1 text-red-500" aria-hidden="true"></i><b>${soul.like_count}</b></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="${url_edit_prefix}${soul.id}" aria-label="Edit Web3 Agent ${escapeHTML(soul.title)}" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-purple-300 border border-purple-500/20 rounded-xl transition flex-1 sm:flex-auto text-center"><i class="fas fa-edit" aria-hidden="true"></i> ${lang_Edit}</a>
                                
                                ${burnBtnHtml}
                                
                                <a href="${seoUrl}" aria-label="View Asset Details" class="px-5 py-2.5 sm:py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow flex-1 sm:flex-auto">${lang_View}</a>
                            </div>
                        </div>
                    </div>
                    `;
                });
                html += `</div>`;
                container.innerHTML = html;
                renderPagination('web3-pagination', data.current_page, data.total_pages, 'changeWeb3Page');
            } else {
                container.innerHTML = `
                <div class="text-center py-12 bg-zinc-900/20 border border-dashed border-white/5 rounded-3xl">
                    <div class="mx-auto w-12 h-12 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-xl mb-4 text-zinc-500"><i class="fas fa-box-open text-xl" aria-hidden="true"></i></div>
                    <p class="text-zinc-400 text-sm">${lang_NoNFTs}</p>
                </div>`;
                document.getElementById('web3-pagination').innerHTML = '';
            }
        } catch(e) {
            container.innerHTML = `<div class="text-red-400 text-center py-12 border border-red-500/20 bg-red-900/10 rounded-2xl"><i class="fas fa-exclamation-triangle mr-2" aria-hidden="true"></i> <span class="font-mono text-sm">${escapeHTML(window.getErrorMessage(e))}</span></div>`;
        }
    }

    async function loadRentedSouls() {
        if (!boundWallet) return;
        const container = document.getElementById('rented-container');
        container.innerHTML = `<div class="flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" aria-label="Loading"></div></div>`;
        
        try {
            let allFetchedNfts = [];
            let page = 1;
            let totalPages = 1;
            
            while (page <= totalPages) {
                // 🚀 防禦機制：移除 limit=100 以防後端處理過載而報錯 500
                const res = await fetch(`/api/souls?is_nft=1&page=${page}&sort=newest`);
                
                // 🚀 透視雷達：如果 API 不是 200，直接抓出後端的真實錯誤字串
                if (!res.ok) {
                    const errText = await res.text();
                    throw new Error(`[API Error ${res.status}] ${errText.substring(0, 150)}...`);
                }
                
                const textData = await res.text();
                let data;
                try {
                    data = JSON.parse(textData);
                } catch(err) {
                    throw new Error(`[JSON Parse Error] Server returned: ${textData.substring(0, 100)}...`);
                }
                
                if (data.success && data.data && data.data.length > 0) {
                    allFetchedNfts.push(...data.data);
                    totalPages = data.total_pages || 1;
                    page++;
                } else {
                    break;
                }
            }

            if (allFetchedNfts.length === 0) {
                container.innerHTML = `
                <div class="text-center py-12 bg-blue-950/10 border border-dashed border-blue-500/30 rounded-3xl p-8">
                    <i class="fas fa-handshake text-blue-400 text-4xl mb-4" aria-hidden="true"></i>
                    <p class="text-sm text-zinc-400 max-w-md mx-auto mb-6">${lang_NoRentedAssets}</p>
                    <a href="<?= url('/marketplace') ?>" class="inline-block px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-xs shadow-md"><i class="fas fa-shopping-cart mr-1" aria-hidden="true"></i> ${lang_GoToMarketplace}</a>
                </div>`;
                return;
            }

            const rentedSouls = [];
            const chunkSize = 20;
            for (let i = 0; i < allFetchedNfts.length; i += chunkSize) {
                const batch = allFetchedNfts.slice(i, i + chunkSize);
                
                const rpcPromises = batch.map(async (soul) => {
                    const rpcRes = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soul.id });
                    if (rpcRes.success && rpcRes.data) {
                        const tokenInfo = rpcRes.data;
                        if (tokenInfo && tokenInfo.renters && tokenInfo.renters[boundWallet]) {
                            const expiryMs = Number(BigInt(tokenInfo.renters[boundWallet]) / 1000000n);
                            if (expiryMs > Date.now()) {
                                soul.rent_expiry = expiryMs;
                                rentedSouls.push(soul);
                            }
                        }
                    }
                });
                
                await Promise.all(rpcPromises);
            }

            if (rentedSouls.length > 0) {
                let html = `<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">`;
                rentedSouls.sort((a,b) => a.rent_expiry - b.rent_expiry).forEach(soul => {
                    const expiryDate = new Date(soul.rent_expiry).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    const seoUrl = `${url_soul_prefix}${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                    const chatUrl = `<?= url('/chat/') ?>${soul.id}`;
                    
                    html += `
                    <div class="soul-card bg-zinc-900/60 border border-blue-500/20 rounded-3xl p-5 sm:p-6 hover:border-blue-400/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-transparent"></div>
                        <div>
                            <div class="flex justify-between items-start gap-3 mb-3">
                                <div>
                                    <div class="font-bold text-lg sm:text-xl text-white tracking-tight mb-1 line-clamp-2 leading-tight">${escapeHTML(soul.title)}</div>
                                    <div class="text-[10px] sm:text-xs text-zinc-500 flex items-center gap-1.5 flex-wrap">
                                        <span>${escapeHTML(soul.role_icon || ' ')} ${escapeHTML(soul.role_name || lang_Unassigned)}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2 shrink-0 flex-col items-end">
                                    <span class="text-[10px] px-2.5 py-1 rounded-full font-bold border bg-blue-500/10 text-blue-400 border-blue-500/20 shadow-sm">
                                        <i class="fas fa-handshake mr-1" aria-hidden="true"></i>Rented
                                    </span>
                                </div>
                            </div>
                            
                            <div class="bg-zinc-950 p-3 rounded-xl border border-white/5 mb-4 shadow-inner">
                                <div class="text-[10px] text-zinc-500 uppercase tracking-wider mb-1">${lang_RentExpiresAt}</div>
                                <div class="text-sm font-bold font-mono text-emerald-400"><i class="far fa-clock mr-1" aria-hidden="true"></i> ${expiryDate}</div>
                            </div>
                            
                            ${soul.description ? `<p class="text-xs sm:text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                        </div>
                        <div class="pt-4 border-t border-white/5 flex gap-2 mt-auto">
                            <a href="${chatUrl}" aria-label="Enter Chat" onclick="this.innerHTML='<i class=&quot;fas fa-spinner fa-spin mr-1&quot;></i> Loading...'; this.classList.add('pointer-events-none','opacity-80');" class="px-5 py-2.5 text-xs bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition flex-1 text-center shadow-lg flex items-center justify-center gap-2 transform hover:-translate-y-0.5 duration-200">
                                <i class="fas fa-comments" aria-hidden="true"></i> ${lang_EnterChat}
                            </a>
                            <a href="${seoUrl}" aria-label="View Asset Details" class="px-5 py-2.5 text-xs bg-zinc-800 hover:bg-zinc-700 text-white font-bold rounded-xl transition text-center shadow-sm shrink-0 border border-white/5 flex items-center justify-center">
                                ${lang_View}
                            </a>
                        </div>
                    </div>
                    `;
                });
                html += `</div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                <div class="text-center py-12 bg-blue-950/10 border border-dashed border-blue-500/30 rounded-3xl p-8">
                    <i class="fas fa-handshake text-blue-400 text-4xl mb-4" aria-hidden="true"></i>
                    <p class="text-sm text-zinc-400 max-w-md mx-auto mb-6">${lang_NoRentedAssets}</p>
                    <a href="<?= url('/marketplace') ?>" class="inline-block px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition text-xs shadow-md"><i class="fas fa-shopping-cart mr-1" aria-hidden="true"></i> ${lang_GoToMarketplace}</a>
                </div>`;
            }
        } catch(e) {
            console.error("loadRentedSouls Caught Error:", e);
            container.innerHTML = `<div class="text-red-400 text-center py-12 border border-red-500/20 bg-red-900/10 rounded-2xl break-all px-4"><i class="fas fa-exclamation-triangle mr-2 text-xl mb-2" aria-hidden="true"></i><br><span class="font-mono text-sm">${escapeHTML(window.getErrorMessage(e))}</span></div>`;
        }
    }

    function showRentersModal(encodedJson) {
        const renters = JSON.parse(decodeURIComponent(encodedJson));
        const listContainer = document.getElementById('renters-list-content');
        listContainer.innerHTML = '';
        if (renters.length === 0) {
            listContainer.innerHTML = `<div class="text-center text-zinc-500 py-6">${lang_NoActiveRenters}</div>`;
        } else {
            renters.forEach(r => {
                const d = new Date(r.expiry);
                const dateStr = d.toLocaleString();
                listContainer.innerHTML += `
                    <div class="bg-zinc-950 border border-white/5 p-3 rounded-xl flex justify-between items-center hover:border-blue-500/30 transition">
                        <div class="font-mono text-sm text-blue-300 truncate pr-2 font-bold"><i class="fas fa-user-circle text-zinc-600 mr-1.5" aria-hidden="true"></i>${escapeHTML(r.account)}</div>
                        <div class="text-[10px] text-zinc-500 shrink-0 text-right">
                            <div class="uppercase tracking-wider">${lang_ExpiresAt}</div>
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

    async function deleteWeb2Soul(id, btn) {
        if (!confirm(lang_PermDelete)) return;
        
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin sm:text-base" aria-hidden="true"></i>';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        try {
            const res = await fetch(`/api/soul/${id}`, { 
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' }
            });
            const data = await res.json();
            if (data.success) { 
                location.reload(); 
            } else { 
                alert(data.error || <?= json_encode(__('Failed to delete'), JSON_UNESCAPED_UNICODE) ?>); 
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } catch(e) { 
            alert(<?= json_encode(__('Network error.'), JSON_UNESCAPED_UNICODE) ?>); 
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    async function burnWeb3Soul(id, btn) {
        if (!confirm(lang_BurnConfirm)) return;
        let originalHtml = btn ? btn.innerHTML : '';
        try {
            if (typeof initNearWallet !== 'function') return;
            const wallet = await initNearWallet();
            
            if (!wallet.isSignedIn()) {
                await window.connectOrBindWallet();
                return;
            }
            originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin sm:text-base" aria-hidden="true"></i>';
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            await wallet.account().functionCall({
                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                methodName: "burn_soul",
                args: { token_id: "soul_" + id },
                gas: "30000000000000", 
                attachedDeposit: "0", 
                walletCallbackUrl: window.location.href 
            });
            
            btn.innerHTML = '<i class="fas fa-sync fa-spin sm:text-base" aria-hidden="true"></i>';
            window.location.reload();
        } catch (e) {
            console.error("Burn execution error:", e);
            // Recovery: verify on-chain if token is burned (get_soul returns null/not found)
            // even if wallet-selector throws "Request validation error" or similar (common with callbackUrl flows)
            try {
                const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + id });
                if (check && (!check.success || !check.data || check.data === null || check.data === undefined)) {
                    // Burn succeeded on-chain
                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-check sm:text-base" aria-hidden="true"></i>';
                    }
                    setTimeout(() => location.reload(), 800);
                    return;
                }
            } catch (_) {}
            if (btn && typeof originalHtml !== 'undefined') {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    async function proactiveAssetSync() {
        if (!boundWallet) return;
        try {
            let allFetchedNfts = [];
            let page = 1;
            let totalPages = 1;
            
            while (page <= totalPages) {
                const res = await fetch(`/api/souls?is_nft=1&page=${page}`);
                if(!res.ok) break;
                
                const textData = await res.text();
                let data;
                try {
                    data = JSON.parse(textData);
                } catch(err) { break; }
                
                if (data.success && data.data && data.data.length > 0) {
                    allFetchedNfts.push(...data.data);
                    totalPages = data.total_pages || 1;
                    page++;
                } else { break; }
            }
            if (allFetchedNfts.length === 0) return;
            let needsReload = false;
            
            const chunkSize = 20;
            for (let i = 0; i < allFetchedNfts.length; i += chunkSize) {
                const batch = allFetchedNfts.slice(i, i + chunkSize);
                
                const syncPromises = batch.map(async (soul) => {
                    if (soul.nft_owner_wallet === boundWallet) return; 
                    const rpcRes = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soul.id });
                    if (rpcRes.success && rpcRes.data) {
                        const tokenInfo = rpcRes.data;
                        if (tokenInfo && tokenInfo.owner_id === boundWallet) {
                            await fetch(`/api/soul/${soul.id}`);
                            needsReload = true;
                        }
                    }
                });
                await Promise.all(syncPromises);
            }
            if (needsReload) { loadWeb2Souls(); loadWeb3Souls(); loadRentedSouls(); }
        } catch(e) {}
    }

    window.addEventListener('DOMContentLoaded', () => {
        loadWeb2Souls();
        if(hasWallet) {
            loadWeb3Souls();
            loadRentedSouls();
        }
        proactiveAssetSync();
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>