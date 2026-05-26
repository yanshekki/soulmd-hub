<?php
/**
 * SoulMD Hub - Public AI Souls Catalog
 * (Dynamic i18n Internationalization & Fully Responsive Mobile Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

// 🌍 引入此頁面的專屬獨立語言包
loadTranslations('browse');

$db = Database::getInstance();
$pdo = $db->getConnection();

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$trendingTags = $pdo->query("SELECT name FROM tags_domain WHERE usage_count > 0 ORDER BY usage_count DESC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);

// 🌍 支援語意變數替換，動態編譯雙語 SEO 標題與簡介
$pageTitle = __('Browse AI Souls');
$pageDesc = __('Discover and explore AI personalities, prompts, and modular agents shared by the community.');

if (!empty($_GET['q'])) {
    $sanitizeQ = htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8');
    $pageTitle = __('Search: :q', ['q' => $sanitizeQ]);
    $pageDesc = __('Search results for ":q" on SoulMD Hub.', ['q' => $sanitizeQ]);
}

require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-4xl sm:text-5xl font-bold tracking-tighter mb-2"><?= __('Browse AI Souls') ?></h1>
    <p class="text-sm sm:text-base text-zinc-400 mb-8 sm:mb-10"><?= __('Discover and explore AI personalities, prompts, and modular agents shared by the community.') ?></p>

    <div class="bg-zinc-900/40 border border-white/10 p-5 sm:p-6 rounded-3xl mb-8 sm:mb-10 shadow-lg backdrop-blur-sm">
        
        <div class="flex flex-col xl:flex-row gap-4 mb-4">
            
            <div class="w-full xl:flex-1 relative shrink-0">
                <input id="search-input" type="text" placeholder="<?= __('Search titles or tags...') ?>" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                       class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-6 py-3.5 text-sm focus:outline-none focus:border-emerald-400 pl-12 transition shadow-inner">
                <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-zinc-400"></i>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-3 w-full xl:w-auto">
                <select id="sort-filter" class="col-span-2 sm:col-span-1 w-full sm:w-auto bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3.5 text-sm focus:outline-none focus:border-emerald-400 shadow-inner text-zinc-300 appearance-none cursor-pointer truncate">
                    <option value="newest" <?= ($_GET['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>><?= __('✨ Newest First') ?></option>
                    <option value="oldest" <?= ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>><?= __('⏳ Oldest First') ?></option>
                    <option value="popular" <?= ($_GET['sort'] ?? '') === 'popular' ? 'selected' : '' ?>><?= __('❤️ Like Count') ?></option>
                    <option value="forks" <?= ($_GET['sort'] ?? '') === 'forks' ? 'selected' : '' ?>><?= __('🌿 Fork Count') ?></option>
                    <option value="az" <?= ($_GET['sort'] ?? '') === 'az' ? 'selected' : '' ?>><?= __('🔤 Title (A-Z)') ?></option>
                    <option value="za" <?= ($_GET['sort'] ?? '') === 'za' ? 'selected' : '' ?>><?= __('🔡 Title (Z-A)') ?></option>
                </select>

                <select id="role-filter" class="col-span-2 sm:col-span-1 w-full sm:w-auto bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3.5 text-sm focus:outline-none focus:border-emerald-400 shadow-inner text-zinc-300 appearance-none cursor-pointer truncate">
                    <option value=""><?= __('All Roles') ?></option>
                    <?php $activeRole = $_GET['role'] ?? ''; foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $activeRole === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="type-filter" class="col-span-1 w-full sm:w-auto bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3.5 text-sm focus:outline-none focus:border-emerald-400 shadow-inner text-zinc-300 appearance-none cursor-pointer truncate">
                    <option value=""><?= __('All Types') ?></option>
                    <option value="single_md" <?= ($_GET['file_type'] ?? '') === 'single_md' ? 'selected' : '' ?>><?= __('Single .md') ?></option>
                    <option value="full_soul_folder" <?= ($_GET['file_type'] ?? '') === 'full_soul_folder' ? 'selected' : '' ?>><?= __('Modular') ?></option>
                </select>

                <button onclick="clearFilters()" class="col-span-1 w-full sm:w-auto px-4 py-3.5 border border-white/10 bg-zinc-800 rounded-2xl hover:bg-zinc-700 hover:text-white transition text-sm flex items-center justify-center gap-2 text-zinc-400 shadow">
                    <i class="fas fa-times"></i> <span><?= __('Clear') ?></span>
                </button>
            </div>
        </div>

        <?php if (!empty($trendingTags)): ?>
            <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap custom-scrollbar pb-2 pt-1 -mx-2 px-2 sm:mx-0 sm:px-0">
                <span class="text-xs text-zinc-500 font-medium uppercase tracking-wider shrink-0 mr-1"><i class="fas fa-fire text-amber-500 mr-1"></i><?= __('Trending:') ?></span>
                <?php foreach($trendingTags as $tag): ?>
                    <button onclick="applyQuickTag('<?= htmlspecialchars(addslashes($tag)) ?>')" class="shrink-0 px-3 py-1.5 bg-white/5 border border-white/10 rounded-full text-xs text-zinc-300 hover:bg-emerald-500/20 hover:text-emerald-400 hover:border-emerald-500/30 transition shadow-sm">
                        #<?= htmlspecialchars($tag) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="results-container" class="min-h-[400px]"></div>
    
    <div id="pagination-container" class="mt-12 flex justify-center items-center w-full"></div>
</div>

<script>
    let timeout = null;
    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;

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

    function applyQuickTag(tag) {
        document.getElementById('search-input').value = tag;
        currentPage = 1; 
        loadSouls();
    }

    function resetAndLoad() {
        currentPage = 1; 
        loadSouls();
    }

    function changePage(page) {
        currentPage = page;
        loadSouls();
        window.scrollTo({ top: 250, behavior: 'smooth' });
    }

    // 🚨 完美多語言化：JavaScript 異步分頁器注入
    function renderPagination(current, totalPages) {
        const container = document.getElementById('pagination-container');
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        
        // Mobile UI (sm:hidden)
        html += `<div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">`;
        if (current > 1) {
            html += `<button onclick="changePage(${current - 1})" class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-left"></i></button>`;
        } else {
            html += `<button disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"><i class="fas fa-chevron-left"></i></button>`;
        }
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase"><?= __('Page') ?> <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        if (current < totalPages) {
            html += `<button onclick="changePage(${current + 1})" class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-right"></i></button>`;
        } else {
            html += `<button disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"><i class="fas fa-chevron-right"></i></button>`;
        }
        html += `</div>`;

        // Desktop UI (hidden sm:flex)
        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        if (current > 1) {
            html += `<button onclick="changePage(${current - 1})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-left text-xs"></i></button>`;
        } else {
            html += `<button disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"><i class="fas fa-chevron-left text-xs"></i></button>`;
        }

        const windowSize = 2; 
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - windowSize && i <= current + windowSize)) {
                if (i === current) {
                    html += `<button class="w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-500 text-zinc-950 font-bold shadow-md transform scale-105 transition">${i}</button>`;
                } else {
                    html += `<button onclick="changePage(${i})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition font-medium text-sm shadow">${i}</button>`;
                }
            } else if (i === current - windowSize - 1 || i === current + windowSize + 1) {
                html += `<span class="w-10 h-10 flex items-center justify-center text-zinc-500 tracking-widest text-sm">...</span>`;
            }
        }

        if (current < totalPages) {
            html += `<button onclick="changePage(${current + 1})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-right text-xs"></i></button>`;
        } else {
            html += `<button disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"><i class="fas fa-chevron-right text-xs"></i></button>`;
        }
        html += `</div>`;

        container.innerHTML = html;
    }

    // 🚨 完美多語言化：JavaScript 動態卡片渲染整合
    async function loadSouls() {
        const container = document.getElementById('results-container');
        const pagination = document.getElementById('pagination-container');
        
        container.innerHTML = `<div class="flex justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;
        pagination.innerHTML = '';

        const q = document.getElementById('search-input').value.trim();
        const sort = document.getElementById('sort-filter').value;
        const role = document.getElementById('role-filter').value;
        const type = document.getElementById('type-filter').value;

        const params = new URLSearchParams();
        if (q) params.append('q', q);
        if (sort && sort !== 'newest') params.append('sort', sort);
        if (role) params.append('role', role);
        if (type) params.append('file_type', type);
        params.append('page', currentPage);
        params.append('limit', 12); 

        const newUrl = window.location.pathname + '?' + params.toString();
        window.history.replaceState({}, '', newUrl);

        try {
            const res = await fetch(`/api/souls?${params.toString()}`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                let html = `<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">`;
                data.data.forEach(soul => {
                    let tagsHtml = '';
                    if (soul.domain) {
                        const tags = soul.domain.split(',').map(t => t.trim()).filter(Boolean).slice(0, 3);
                        tags.forEach(t => {
                            tagsHtml += `<span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded shadow-sm">#${escapeHTML(t)}</span>`;
                        });
                    }

                    const seoUrl = `/soul/${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                    
                    // 🌍 將 JS 模板內嵌之文字進行後端多語言編譯
                    const typeLabel = soul.file_type === 'full_soul_folder' ? '<?= __('Modular') ?>' : '<?= __('Single .md') ?>';
                    const roleLabel = soul.role ? escapeHTML(soul.role) : '<?= __('Unassigned') ?>';

                    html += `
                        <a href="${seoUrl}" class="group bg-zinc-900/60 border border-white/10 rounded-3xl p-5 sm:p-6 hover:border-emerald-400/50 transition-all shadow-lg flex flex-col justify-between h-full backdrop-blur-sm">
                            <div>
                                <div class="flex justify-between items-start gap-3 mb-4">
                                    <div class="font-bold text-lg sm:text-xl text-white group-hover:text-emerald-400 transition line-clamp-2 leading-tight">${escapeHTML(soul.title)}</div>
                                    <div class="text-[10px] px-2 py-1 rounded font-medium border shrink-0 shadow-sm ${soul.file_type === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20'}">
                                        ${typeLabel}
                                    </div>
                                </div>
                                ${soul.description ? `<p class="text-xs sm:text-sm text-zinc-400 line-clamp-3 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                                
                                <div class="flex flex-wrap gap-1.5 mb-5 sm:mb-6">
                                    ${tagsHtml}
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-zinc-500 pt-4 border-t border-white/5 mt-auto">
                                <div class="flex-1 min-w-0 truncate pr-3">
                                    <span class="text-white font-medium">@${escapeHTML(soul.username || 'anonymous')}</span>
                                    <span class="opacity-50 ml-1">• ${roleLabel}</span>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span title="Forks"><i class="fas fa-code-branch text-emerald-500"></i> <b class="text-zinc-300">${soul.fork_count}</b></span>
                                    <span title="Likes"><i class="fas fa-heart text-red-500"></i> <b class="text-zinc-300">${soul.like_count}</b></span>
                                </div>
                            </div>
                        </a>
                    `;
                });
                html += `</div>`;
                container.innerHTML = html;
                
                renderPagination(data.current_page, data.total_pages);

            } else {
                // 🌍 完美修復：AJAX 空白狀態多語言化
                container.innerHTML = `
                    <div class="text-center py-20 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner mx-4 sm:mx-0">
                        <div class="text-5xl sm:text-6xl mb-6 opacity-50">🔎</div>
                        <p class="text-xl sm:text-2xl font-bold mb-2"><?= __('No souls found') ?></p>
                        <p class="text-sm text-zinc-400 max-w-xs mx-auto"><?= __('Try adjusting your keywords or clearing the active filters.') ?></p>
                        <button onclick="clearFilters()" class="mt-6 px-6 py-2.5 bg-zinc-800 text-white rounded-xl hover:bg-zinc-700 transition text-sm shadow font-medium"><?= __('Clear all filters') ?></button>
                    </div>
                `;
            }
        } catch (e) {
            // 🌍 完美修復：網絡例外錯誤多語言化
            container.innerHTML = `<div class="text-red-400 text-center py-20 font-medium"><i class="fas fa-wifi mr-2"></i><?= __('Network error while loading souls') ?></div>`;
        }
    }

    function clearFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('sort-filter').value = 'newest';
        document.getElementById('role-filter').value = '';
        document.getElementById('type-filter').value = '';
        currentPage = 1; 
        loadSouls();
    }

    document.getElementById('search-input').addEventListener('input', () => { 
        clearTimeout(timeout); 
        timeout = setTimeout(resetAndLoad, 400); 
    });
    
    document.getElementById('sort-filter').addEventListener('change', resetAndLoad);
    document.getElementById('role-filter').addEventListener('change', resetAndLoad);
    document.getElementById('type-filter').addEventListener('change', resetAndLoad);

    window.onload = loadSouls;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>