<?php
/**
 * SoulMD Hub - Creator Workspace & Model Management Dashboard
 * (V5: 100% SPA Async Fetch API, Dual-Track Pagination & Proactive Radar)
 * 🚀 Patched: Added Floor Price indicator
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

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

$pageTitle = __('My Souls');
$pageDesc = __('Manage and edit your uploaded AI personalities');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8">
    
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10 border-b border-white/10 pb-6">
        <div>
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tighter"><?= __('My Souls') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-2"><?= __('Manage and edit your uploaded AI personalities') ?></p>
        </div>
        
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-3 w-full lg:w-auto">
            <select id="sort-filter" onchange="changeSort(this.value)" class="col-span-2 sm:col-span-1 w-full sm:w-auto px-4 py-3 sm:py-2.5 text-sm bg-zinc-900 border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition focus:outline-none focus:border-emerald-400 shadow-inner cursor-pointer appearance-none">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= __('✨ Newest') ?></option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>><?= __('❤️ Like Count') ?></option>
                <option value="forks" <?= $sort === 'forks' ? 'selected' : '' ?>><?= __('🌿 Fork Count') ?></option>
            </select>
            
            <a href="<?= url('/profile/' . rawurlencode($username)) ?>" target="_blank" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition flex items-center justify-center gap-2 whitespace-nowrap">
                <i class="fas fa-external-link-alt text-[10px] text-zinc-500"></i> <?= __('Profile') ?>
            </a>
            <a href="<?= url('/my-api') ?>" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-emerald-500/30 text-emerald-400 rounded-2xl hover:bg-emerald-900/10 transition text-center whitespace-nowrap">
                <?= __('My API Key') ?>
            </a>
            <a href="<?= url('/upload') ?>" class="col-span-2 sm:col-span-1 px-6 py-3 sm:py-2.5 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center justify-center gap-2 shadow-lg w-full sm:w-auto">
                <i class="fas fa-plus"></i> <?= __('New Soul') ?>
            </a>
        </div>
    </div>

    <div class="mb-14">
        <h2 class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-emerald-400 pl-3">
            <i class="fas fa-tools text-emerald-400"></i> <?= __('Web2 Prototype Box') ?>
        </h2>
        
        <div id="web2-container" class="min-h-[250px]"></div>
        <div id="web2-pagination" class="mt-8 flex justify-center items-center w-full"></div>
    </div>

    <div>
        <h2 class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-purple-500 pl-3">
            <i class="fas fa-gem text-purple-400"></i> <?= __('AgentFi NFT Asset Inventory') ?>
        </h2>
        
        <?php if (empty($nearWallet)): ?>
            <div class="text-center py-12 bg-purple-950/10 border border-dashed border-purple-500/30 rounded-3xl p-8">
                <i class="fas fa-wallet text-purple-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-bold text-white mb-2"><?= __('No Web3 Wallet Detected') ?></h3>
                <p class="text-sm text-zinc-400 max-w-md mx-auto mb-6"><?= __('Wallet bind prompt') ?></p>
                <a href="<?= url('/my-setting') ?>" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-purple-500/20"><i class="fas fa-cog"></i> <?= __('Go to Bind Wallet') ?></a>
            </div>
        <?php else: ?>
            <div id="web3-container" class="min-h-[250px]"></div>
            <div id="web3-pagination" class="mt-8 flex justify-center items-center w-full"></div>
        <?php endif; ?>
    </div>
</div>

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
    const lang_PermDelete = <?= json_encode(__('Are you sure you want to permanently delete this AI soul?'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_FloorPrice = <?= json_encode(__('Floor Price'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_FloorDesc = <?= json_encode(__('Floor Desc'), JSON_UNESCAPED_UNICODE) ?>;
    
    const url_soul_prefix = <?= json_encode(url('/soul/'), JSON_UNESCAPED_UNICODE) ?>;
    const url_edit_prefix = <?= json_encode(url('/edit/'), JSON_UNESCAPED_UNICODE) ?>;
    const url_versions = <?= json_encode(url('/soul-versions/'), JSON_UNESCAPED_UNICODE) ?>;
    const currentUsername = <?= json_encode($username, JSON_UNESCAPED_UNICODE) ?>;
    const hasWallet = <?= !empty($nearWallet) ? 'true' : 'false' ?>;

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
        if(hasWallet) loadWeb3Souls();
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
        html += `<button onclick="${funcName}(${current - 1})" ${current <= 1 ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-left"></i></button>`;
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase">${lang_Page} <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        html += `<button onclick="${funcName}(${current + 1})" ${current >= totalPages ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-right"></i></button>`;
        html += `</div>`;

        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        html += `<button onclick="${funcName}(${current - 1})" ${current <= 1 ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-left text-xs"></i></button>`;

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

        html += `<button onclick="${funcName}(${current + 1})" ${current >= totalPages ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-right text-xs"></i></button>`;
        html += `</div>`;

        container.innerHTML = html;
    }

    async function loadWeb2Souls() {
        const container = document.getElementById('web2-container');
        container.innerHTML = `<div class="flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;
        
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
                                        <span>${escapeHTML(soul.role_icon || '✨')} ${escapeHTML(soul.role_name || lang_Unassigned)}</span><span>•</span><span>${dateStr}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2 shrink-0 flex-col items-end">
                                    ${soul.is_public == 0 
                                        ? `<span class="text-[10px] px-2.5 py-1 rounded-full font-bold border bg-red-500/10 text-red-400 border-red-500/20 shadow-sm"><i class="fas fa-lock mr-1"></i>${lang_PrivateOnlyMe}</span>`
                                        : `<span class="text-[10px] px-2.5 py-1 rounded-full font-medium border bg-emerald-500/10 text-emerald-400 border-emerald-500/20 shadow-sm"><i class="fas fa-globe mr-1"></i>${lang_Public}</span>`
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
                                <span title="${lang_Forks}"><i class="fas fa-code-branch mr-1 text-emerald-500"></i><b class="text-zinc-300">${soul.fork_count}</b></span>
                                <span title="${lang_Likes}"><i class="fas fa-heart mr-1 text-red-500"></i><b class="text-zinc-300">${soul.like_count}</b></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="${url_edit_prefix}${soul.id}" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-xl border border-white/5 transition flex-1 sm:flex-auto text-center"><i class="fas fa-edit"></i> ${lang_Edit}</a>
                                <a href="${url_versions}${soul.id}" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-zinc-200 rounded-xl border border-white/5 transition flex items-center justify-center" title="${lang_VersionHistory}"><i class="fas fa-history"></i></a>
                                <button onclick="deleteSoul(${soul.id})" class="px-4 py-2.5 sm:p-2 text-xs text-zinc-500 hover:text-red-400 transition bg-zinc-800 sm:bg-transparent rounded-xl sm:rounded-none border border-white/5 sm:border-none flex items-center justify-center"><i class="far fa-trash-alt sm:text-base"></i></button>
                                <a href="${seoUrl}" class="px-5 py-2.5 sm:py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow flex-1 sm:flex-auto">${lang_View}</a>
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
                    <div class="mx-auto w-12 h-12 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-xl mb-4 text-zinc-500"><i class="fas fa-code text-xl"></i></div>
                    <p class="text-zinc-400 text-sm">${lang_NoSouls}</p>
                </div>`;
                document.getElementById('web2-pagination').innerHTML = '';
            }
        } catch(e) {
            container.innerHTML = `<div class="text-red-400 text-center py-12">Network Error</div>`;
        }
    }

    async function loadWeb3Souls() {
        if (!hasWallet) return;
        const container = document.getElementById('web3-container');
        container.innerHTML = `<div class="flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div></div>`;
        
        try {
            const res = await fetch(`/api/souls?scope=me&is_nft=1&page=${web3Page}&sort=${currentSort}`);
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
                    const seoUrl = `${url_soul_prefix}${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;

                    // 🚨 注入 Floor Price 保底價顯示
                    html += `
                    <div class="soul-card bg-zinc-900/60 border border-purple-500/20 rounded-3xl p-5 sm:p-6 hover:border-purple-400/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-lg relative overflow-hidden" data-id="${soul.id}">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-transparent"></div>
                        <div>
                            <div class="flex justify-between items-start gap-3 mb-3">
                                <div>
                                    <div class="font-bold text-lg sm:text-xl text-white tracking-tight mb-1 line-clamp-2 leading-tight">${escapeHTML(soul.title)}</div>
                                    <div class="text-[10px] sm:text-xs text-zinc-500 flex items-center gap-1.5 flex-wrap">
                                        <span>${escapeHTML(soul.role_icon || '✨')} ${escapeHTML(soul.role_name || lang_Unassigned)}</span><span>•</span><span>${dateStr}</span>
                                    </div>
                                </div>
                                <div class="flex gap-2 shrink-0 flex-col items-end">
                                    <span class="text-[10px] px-2.5 py-1 rounded-full font-bold border bg-purple-500/10 text-purple-400 border-purple-500/20 shadow-sm">
                                        <i class="fas fa-cube mr-1"></i>${lang_AgentNFTAsset}
                                    </span>
                                    <span class="text-[9px] px-2 py-0.5 rounded font-bold border bg-emerald-500/10 text-emerald-400 border-emerald-500/20 shadow-sm cursor-help" title="${lang_FloorDesc}">
                                        ${lang_FloorPrice}: 0.45 N
                                    </span>
                                </div>
                            </div>
                            ${soul.description ? `<p class="text-xs sm:text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                            <div class="flex flex-wrap gap-1.5 mb-6">${tagsHtml}</div>
                        </div>
                        <div class="pt-4 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-auto">
                            <div class="flex items-center gap-4 text-xs text-zinc-500 font-mono">
                                <span><i class="fas fa-code-branch mr-1 text-emerald-500"></i><b>${soul.fork_count}</b></span>
                                <span><i class="fas fa-heart mr-1 text-red-500"></i><b>${soul.like_count}</b></span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="${url_edit_prefix}${soul.id}" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-purple-300 border border-purple-500/20 rounded-xl transition flex-1 sm:flex-auto text-center"><i class="fas fa-edit"></i> ${lang_Edit}</a>
                                <button onclick="deleteSoul(${soul.id})" class="px-4 py-2.5 sm:p-2 text-xs text-zinc-500 hover:text-red-400 transition bg-zinc-800 sm:bg-transparent rounded-xl sm:rounded-none border border-white/5 sm:border-none flex items-center justify-center" title="${lang_BurnAndRefund}"><i class="fas fa-fire-alt sm:text-base"></i></button>
                                <a href="${seoUrl}" class="px-5 py-2.5 sm:py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow flex-1 sm:flex-auto">${lang_View}</a>
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
                    <div class="mx-auto w-12 h-12 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-xl mb-4 text-zinc-500"><i class="fas fa-box-open text-xl"></i></div>
                    <p class="text-zinc-400 text-sm">${lang_NoNFTs}</p>
                </div>`;
                document.getElementById('web3-pagination').innerHTML = '';
            }
        } catch(e) {
            container.innerHTML = `<div class="text-red-400 text-center py-12">Network Error</div>`;
        }
    }

    async function deleteSoul(id) {
        if (!confirm(lang_BurnConfirm)) {
            if (!confirm(lang_PermDelete)) return;
            executeDatabaseDelete(id);
            return;
        }

        try {
            if (typeof initNearWallet !== 'function') { executeDatabaseDelete(id); return; }
            const wallet = await initNearWallet();
            if (!wallet.isSignedIn()) {
                executeDatabaseDelete(id);
                return;
            }

            await wallet.account().functionCall({
                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                methodName: "burn_soul",
                args: { token_id: "soul_" + id },
                gas: "30000000000000", 
                attachedDeposit: "0", 
                walletCallbackUrl: window.location.href 
            });
        } catch (e) {
            executeDatabaseDelete(id);
        }
    }

    async function executeDatabaseDelete(id) {
        try {
            const res = await fetch(`/api/soul/${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (data.success) { 
                location.reload(); 
            } else { 
                alert(data.error || "Delete failed"); 
            }
        } catch(e) { 
            alert("Network Error"); 
        }
    }

    async function proactiveAssetSync() {
        if (typeof initNearWallet !== 'function') return;
        const wallet = await initNearWallet();
        if (!wallet || !wallet.isSignedIn()) return;
        const myWallet = wallet.getAccountId();

        try {
            const res = await fetch('/api/souls?limit=1000&is_nft=1');
            const data = await res.json();
            if (!data.success || !data.data) return;

            let needsReload = false;
            const safeRpcUrl = window.activeNearRpcUrl || "https://free.rpc.fastnear.com";
            
            const syncPromises = data.data.map(async (soul) => {
                if (soul.nft_owner_wallet === myWallet) return; 

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
                        
                        if (tokenInfo && tokenInfo.owner_id === myWallet) {
                            await fetch(`/api/soul/${soul.id}`);
                            needsReload = true;
                        }
                    }
                } catch(e) {}
            });

            await Promise.all(syncPromises);
            if (needsReload) { loadWeb2Souls(); loadWeb3Souls(); }
        } catch(e) {}
    }

    window.addEventListener('DOMContentLoaded', () => {
        loadWeb2Souls();
        if(hasWallet) loadWeb3Souls();
        proactiveAssetSync();
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>