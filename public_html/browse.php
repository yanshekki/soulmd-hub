<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$trendingTags = $pdo->query("SELECT name FROM tags_domain WHERE usage_count > 0 ORDER BY usage_count DESC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Browse AI Souls';
$pageDesc = 'Discover and explore thousands of AI agent souls shared by the community.';
if (!empty($_GET['q'])) {
    $pageTitle = 'Search: ' . htmlspecialchars($_GET['q']);
    $pageDesc = 'Search results for "' . htmlspecialchars($_GET['q']) . '" on SoulMD Hub.';
}
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-5xl font-bold tracking-tighter mb-2">Browse Souls</h1>
    <p class="text-zinc-400 mb-10">Discover and explore AI personalities, prompts, and modular agents shared by the community.</p>

    <div class="bg-zinc-900/40 border border-white/10 p-6 rounded-3xl mb-10 shadow-lg backdrop-blur-sm">
        <div class="flex flex-col lg:flex-row gap-4 mb-4">
            
            <div class="flex-1 relative">
                <input id="search-input" type="text" placeholder="Search titles or tags..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                       class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-6 py-3.5 text-sm focus:outline-none focus:border-emerald-400 pl-12 transition shadow-inner">
                <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-zinc-400"></i>
            </div>

            <div class="flex flex-wrap gap-3">
                <select id="sort-filter" class="bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3.5 text-sm focus:outline-none focus:border-emerald-400 shadow-inner text-zinc-300">
                    <option value="newest" <?= ($_GET['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>✨ Newest First</option>
                    <option value="oldest" <?= ($_GET['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>⏳ Oldest First</option>
                    <option value="popular" <?= ($_GET['sort'] ?? '') === 'popular' ? 'selected' : '' ?>>❤️ Like Count</option>
                    <option value="forks" <?= ($_GET['sort'] ?? '') === 'forks' ? 'selected' : '' ?>>🌿 Fork Count</option>
                    <option value="az" <?= ($_GET['sort'] ?? '') === 'az' ? 'selected' : '' ?>>🔤 Title (A-Z)</option>
                    <option value="za" <?= ($_GET['sort'] ?? '') === 'za' ? 'selected' : '' ?>>🔡 Title (Z-A)</option>
                </select>

                <select id="role-filter" class="bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3.5 text-sm focus:outline-none focus:border-emerald-400 shadow-inner text-zinc-300">
                    <option value="">All Roles</option>
                    <?php $activeRole = $_GET['role'] ?? ''; foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $activeRole === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="type-filter" class="bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3.5 text-sm focus:outline-none focus:border-emerald-400 shadow-inner text-zinc-300">
                    <option value="">All Types</option>
                    <option value="single_md" <?= ($_GET['file_type'] ?? '') === 'single_md' ? 'selected' : '' ?>>Single .md</option>
                    <option value="full_soul_folder" <?= ($_GET['file_type'] ?? '') === 'full_soul_folder' ? 'selected' : '' ?>>Modular Folder</option>
                </select>

                <button onclick="clearFilters()" class="px-5 py-3.5 border border-white/10 bg-zinc-800 rounded-2xl hover:bg-zinc-700 hover:text-white transition text-sm flex items-center gap-2 text-zinc-400 shadow">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>

        <?php if (!empty($trendingTags)): ?>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-zinc-500 font-medium uppercase tracking-wider mr-1"><i class="fas fa-fire text-amber-500 mr-1"></i>Trending:</span>
                <?php foreach($trendingTags as $tag): ?>
                    <button onclick="applyQuickTag('<?= htmlspecialchars(addslashes($tag)) ?>')" class="px-3 py-1 bg-white/5 border border-white/10 rounded-full text-xs text-zinc-300 hover:bg-emerald-500/20 hover:text-emerald-400 hover:border-emerald-500/30 transition">
                        #<?= htmlspecialchars($tag) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="results-container" class="min-h-[400px]"></div>
    
    <div id="pagination-container" class="mt-12 flex justify-center items-center gap-2"></div>
</div>

<script>
    let timeout = null;
    
    // 讀取網址列的 page 參數，預設為 1
    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;

    // 完美防禦 DOM XSS
    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }
    
    // SEO URL 轉換
    function makeSlug(str) {
        if (!str) return 'unassigned';
        let slug = str.toLowerCase();
        slug = slug.replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|]+/g, '-');
        slug = slug.replace(/^-+|-+$/g, '');
        return encodeURIComponent(slug);
    }

    function applyQuickTag(tag) {
        document.getElementById('search-input').value = tag;
        currentPage = 1; // 點擊 Tag 時重置為第一頁
        loadSouls();
    }

    function resetAndLoad() {
        currentPage = 1; // 改變過濾條件時，必須回到第一頁
        loadSouls();
    }

    function changePage(page) {
        currentPage = page;
        loadSouls();
        // 換頁後自動平滑捲動到結果區頂部
        window.scrollTo({ top: 350, behavior: 'smooth' });
    }

    // 動態渲染分頁器 UI
    function renderPagination(current, totalPages) {
        const container = document.getElementById('pagination-container');
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        
        // 上一頁按鈕
        if (current > 1) {
            html += `<button onclick="changePage(${current - 1})" class="px-4 py-2 bg-zinc-900 border border-white/10 rounded-xl hover:bg-white/5 transition text-sm text-zinc-300 shadow"><i class="fas fa-chevron-left"></i></button>`;
        }

        // 頁碼邏輯：只顯示首尾，以及當前頁前後 2 頁，其他用省略號代替
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - 2 && i <= current + 2)) {
                if (i === current) {
                    html += `<button class="px-4 py-2 bg-emerald-500 text-zinc-950 font-bold rounded-xl text-sm shadow-lg transform scale-105 transition">${i}</button>`;
                } else {
                    html += `<button onclick="changePage(${i})" class="px-4 py-2 bg-zinc-900 border border-white/10 rounded-xl hover:bg-white/5 transition text-sm text-zinc-300 shadow">${i}</button>`;
                }
            } else if (i === current - 3 || i === current + 3) {
                html += `<span class="px-2 text-zinc-500 tracking-widest text-sm">...</span>`;
            }
        }

        // 下一頁按鈕
        if (current < totalPages) {
            html += `<button onclick="changePage(${current + 1})" class="px-4 py-2 bg-zinc-900 border border-white/10 rounded-xl hover:bg-white/5 transition text-sm text-zinc-300 shadow"><i class="fas fa-chevron-right"></i></button>`;
        }

        container.innerHTML = html;
    }

    async function loadSouls() {
        const container = document.getElementById('results-container');
        const pagination = document.getElementById('pagination-container');
        
        // 顯示 Loading
        container.innerHTML = `<div class="flex justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;
        pagination.innerHTML = '';

        const q = document.getElementById('search-input').value.trim();
        const sort = document.getElementById('sort-filter').value;
        const role = document.getElementById('role-filter').value;
        const type = document.getElementById('type-filter').value;

        // 組合 API 查詢參數
        const params = new URLSearchParams();
        if (q) params.append('q', q);
        if (sort && sort !== 'newest') params.append('sort', sort);
        if (role) params.append('role', role);
        if (type) params.append('file_type', type);
        params.append('page', currentPage);
        params.append('limit', 12); // 每頁顯示 12 筆 (剛好排滿 3 欄 x 4 行)

        // 靜默更新網址列，方便用戶分享當前條件及頁數的連結
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
                            tagsHtml += `<span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded">#${escapeHTML(t)}</span>`;
                        });
                    }

                    // 🚨 完美構建 4 層 SEO Link
                    const seoUrl = `/soul/${encodeURIComponent(soul.username || 'anonymous')}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;

                    html += `
                        <a href="${seoUrl}" class="group bg-zinc-900/60 border border-white/10 rounded-3xl p-6 hover:border-emerald-400/50 transition-all shadow-lg flex flex-col justify-between h-full backdrop-blur-sm">
                            <div>
                                <div class="flex justify-between items-start gap-3 mb-4">
                                    <div class="font-bold text-xl text-white group-hover:text-emerald-400 transition line-clamp-2 leading-tight">${escapeHTML(soul.title)}</div>
                                    <div class="text-[10px] px-2 py-1 rounded font-medium border shrink-0 ${soul.file_type === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20'}">
                                        ${soul.file_type === 'full_soul_folder' ? 'Modular' : '.md'}
                                    </div>
                                </div>
                                ${soul.description ? `<p class="text-sm text-zinc-400 line-clamp-3 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                                
                                <div class="flex flex-wrap gap-1.5 mb-6">
                                    ${tagsHtml}
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-zinc-500 pt-4 border-t border-white/5 mt-auto">
                                <div class="truncate max-w-[120px]">
                                    <span class="text-white font-medium">@${escapeHTML(soul.username || 'anonymous')}</span>
                                    <span class="opacity-50 ml-1">• ${escapeHTML(soul.role || 'Unassigned')}</span>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span><i class="fas fa-code-branch text-emerald-500"></i> <b class="text-zinc-300">${soul.fork_count}</b></span>
                                    <span><i class="fas fa-heart text-red-500"></i> <b class="text-zinc-300">${soul.like_count}</b></span>
                                </div>
                            </div>
                        </a>
                    `;
                });
                html += `</div>`;
                container.innerHTML = html;
                
                // 渲染分頁器 UI
                renderPagination(data.current_page, data.total_pages);

            } else {
                container.innerHTML = `
                    <div class="text-center py-20 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                        <div class="text-6xl mb-6 opacity-50">🔎</div>
                        <p class="text-2xl font-bold mb-2">No souls found</p>
                        <p class="text-zinc-400">Try adjusting your keywords or filters.</p>
                        <button onclick="clearFilters()" class="mt-6 px-6 py-2 bg-zinc-800 text-white rounded-full hover:bg-zinc-700 transition text-sm">Clear all filters</button>
                    </div>
                `;
            }
        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-20">Network error while loading souls</div>`;
        }
    }

    function clearFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('sort-filter').value = 'newest';
        document.getElementById('role-filter').value = '';
        document.getElementById('type-filter').value = '';
        currentPage = 1; // 清除條件必定回到第一頁
        loadSouls();
    }

    // 綁定 Event Listeners
    document.getElementById('search-input').addEventListener('input', () => { 
        clearTimeout(timeout); 
        timeout = setTimeout(resetAndLoad, 400); 
    });
    
    document.getElementById('sort-filter').addEventListener('change', resetAndLoad);
    document.getElementById('role-filter').addEventListener('change', resetAndLoad);
    document.getElementById('type-filter').addEventListener('change', resetAndLoad);

    // 初始載入
    window.onload = loadSouls;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>