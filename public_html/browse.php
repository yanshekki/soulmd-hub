<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

$pageTitle = 'Browse AI Souls';
$pageDesc = 'Discover and explore thousands of AI agent souls shared by the community.';
if (!empty($_GET['q'])) {
    $pageTitle = 'Search: ' . htmlspecialchars($_GET['q']);
    $pageDesc = 'Search results for "' . htmlspecialchars($_GET['q']) . '" on SoulMD Hub.';
}
require_once __DIR__ . '/../private/includes/header.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <h1 class="text-5xl font-bold tracking-tighter mb-2">Browse Souls</h1>
    <p class="text-zinc-400 mb-10">Discover and explore AI personalities shared by the community.</p>

    <div class="flex flex-col lg:flex-row gap-4 mb-10">
        <div class="flex-1 relative">
            <input id="search-input" type="text" placeholder="Search souls..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                   class="w-full bg-zinc-900 border border-white/10 rounded-3xl px-6 py-4 text-lg focus:outline-none focus:border-emerald-400 pl-12">
            <i class="fas fa-search absolute left-6 top-1/2 -translate-y-1/2 text-zinc-400"></i>
        </div>

        <div class="flex flex-wrap gap-3">
            <select id="role-filter" class="bg-zinc-900 border border-white/10 rounded-3xl px-6 py-4 text-sm focus:outline-none focus:border-emerald-400">
                <option value="">All Roles</option>
                <?php 
                $activeRole = $_GET['role'] ?? '';
                foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $activeRole === $cat['slug'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="type-filter" class="bg-zinc-900 border border-white/10 rounded-3xl px-6 py-4 text-sm focus:outline-none focus:border-emerald-400">
                <option value="">All Types</option>
                <option value="single_md">Single .md</option>
                <option value="full_soul_folder">Full Soul Folder</option>
            </select>

            <button onclick="clearFilters()" class="px-6 py-4 border border-white/20 rounded-3xl hover:bg-white/5 transition text-sm flex items-center gap-2">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
    </div>

    <div id="results-container" class="min-h-[400px]"></div>
</div>

<script>
    let timeout = null;

    async function loadSouls() {
        const container = document.getElementById('results-container');
        container.innerHTML = `<div class="flex justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;

        const q = document.getElementById('search-input').value.trim();
        const role = document.getElementById('role-filter').value;
        const type = document.getElementById('type-filter').value;

        const params = new URLSearchParams();
        if (q) params.append('q', q);
        if (role) params.append('role', role);
        if (type) params.append('file_type', type);

        // 更新網址列狀態
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, '', newUrl);

        try {
            const res = await fetch(`/api/souls?${params.toString()}`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
                data.data.forEach(soul => {
                    html += `
                        <a href="/soul/${soul.id}" class="group bg-zinc-900 border border-white/10 rounded-3xl p-6 hover:border-emerald-400/50 transition-all shadow-lg flex flex-col justify-between h-full">
                            <div>
                                <div class="flex justify-between items-start gap-3 mb-4">
                                    <div class="font-bold text-xl group-hover:text-emerald-400 transition line-clamp-2">${soul.title}</div>
                                    <div class="text-[10px] px-2 py-1 rounded font-medium border shrink-0 ${soul.file_type === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20'}">
                                        ${soul.file_type === 'full_soul_folder' ? 'Folder' : '.md'}
                                    </div>
                                </div>
                                ${soul.description ? `<p class="text-sm text-zinc-400 line-clamp-3 mb-6">${soul.description}</p>` : ''}
                            </div>
                            <div class="flex items-center justify-between text-xs text-zinc-500 pt-4 border-t border-white/5 mt-auto">
                                <div>${soul.role || '—'}</div>
                                <div class="flex items-center gap-3">
                                    <span><i class="fas fa-code-branch text-emerald-500"></i> ${soul.fork_count}</span>
                                    <span><i class="fas fa-heart text-red-500"></i> ${soul.like_count}</span>
                                </div>
                            </div>
                        </a>
                    `;
                });
                html += `</div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center py-20 bg-zinc-900/20 border border-white/5 rounded-3xl">
                        <div class="text-6xl mb-6 opacity-50">🔎</div>
                        <p class="text-2xl font-bold mb-2">No souls found</p>
                        <p class="text-zinc-400">Try adjusting your keywords or filters.</p>
                    </div>
                `;
            }
        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-20">Network error while loading souls</div>`;
        }
    }

    function clearFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('role-filter').value = '';
        document.getElementById('type-filter').value = '';
        loadSouls();
    }

    document.getElementById('search-input').addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(loadSouls, 300); });
    document.getElementById('role-filter').addEventListener('change', loadSouls);
    document.getElementById('type-filter').addEventListener('change', loadSouls);

    window.onload = loadSouls;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>