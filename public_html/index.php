<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$statsSouls = $pdo->query("SELECT COUNT(*) FROM souls WHERE is_public = 1")->fetchColumn();
$statsUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$statsForks = $pdo->query("SELECT SUM(fork_count) FROM souls")->fetchColumn() ?: 0;
$categories = $pdo->query("SELECT name, slug, icon FROM categories LIMIT 6")->fetchAll();

$pageTitle = 'SoulMD Hub - Share AI Souls';
$pageDesc = 'The simplest platform to share, discover, and fork AI agent souls as .md files. Human & AI friendly.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 pb-16">
    <div class="text-center py-16">
        <div class="inline-flex items-center gap-2 bg-emerald-900/30 text-emerald-400 px-5 py-2 rounded-3xl text-sm mb-6">
            <i class="fas fa-sparkles"></i> Now supporting full soul folders
        </div>
        <h1 class="text-6xl md:text-7xl font-bold tracking-tighter leading-none mb-6">
            Share your AI's soul.<br>Let the world fork it.
        </h1>
        <p class="max-w-xl mx-auto text-xl text-zinc-400 mb-10">
            The cleanest platform for humans and AI to upload, discover, and reuse .md-based personalities.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="generate" class="flex items-center justify-center gap-3 px-10 py-5 bg-white text-black text-xl font-semibold rounded-3xl hover:bg-zinc-200 transition shadow-xl">
                <i class="fas fa-magic"></i> Generate with AI
            </a>
            <a href="upload" class="flex items-center justify-center gap-3 px-10 py-5 border border-white/30 text-xl font-semibold rounded-3xl hover:bg-white/5 transition">
                Upload manually
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center mb-20">
        <div>
            <div class="text-4xl font-bold text-emerald-400"><?= number_format($statsSouls) ?></div>
            <div class="text-zinc-400 text-sm">Souls shared</div>
        </div>
        <div>
            <div class="text-4xl font-bold text-emerald-400"><?= number_format($statsUsers) ?></div>
            <div class="text-zinc-400 text-sm">Active users</div>
        </div>
        <div>
            <div class="text-4xl font-bold text-emerald-400"><?= number_format($statsForks) ?></div>
            <div class="text-zinc-400 text-sm">Forks total</div>
        </div>
    </div>

    <div class="mb-20">
        <h2 class="text-xl font-semibold mb-6 flex items-center gap-2">
            Popular Categories <span class="text-xs bg-white/10 px-3 py-1 rounded-full text-zinc-400">View more in Browse</span>
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($categories as $cat): ?>
                <a href="browse?role=<?= urlencode($cat['slug']) ?>" class="bg-zinc-900 hover:bg-zinc-800 transition p-6 rounded-3xl text-center">
                    <div class="text-4xl mb-3"><?= htmlspecialchars($cat['icon'] ?? '✨') ?></div>
                    <div class="font-medium"><?= htmlspecialchars($cat['name']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold">Trending Souls</h2>
            <a href="browse" class="flex items-center gap-1 text-emerald-400 text-sm hover:underline">
                View all <span class="text-xl">→</span>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="trending-souls"></div>
    </div>
</div>

<script>
    async function loadTrending() {
        const container = document.getElementById('trending-souls');
        container.innerHTML = `<div class="col-span-3 flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;

        try {
            const res = await fetch('/api/souls?limit=3');
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(soul => {
                    html += `
                        <a href="/soul/${soul.id}" class="group bg-zinc-900 border border-white/10 rounded-3xl p-6 hover:border-emerald-400/50 transition-all">
                            <div class="flex justify-between items-start mb-4">
                                <div class="font-semibold text-xl group-hover:text-emerald-400 transition">${soul.title}</div>
                                <div class="text-xs px-3 py-1 rounded-full ${soul.file_type === 'full_soul_folder' ? 'bg-purple-900 text-purple-400' : 'bg-emerald-900 text-emerald-400'}">
                                    ${soul.file_type === 'full_soul_folder' ? 'Folder' : '.md'}
                                </div>
                            </div>
                            ${soul.description ? `<p class="text-sm text-zinc-400 line-clamp-3 mb-6">${soul.description}</p>` : ''}
                            <div class="flex items-center justify-between text-xs text-zinc-500">
                                <div>${soul.role || '—'}</div>
                                <div class="flex items-center gap-3">
                                    <span>${soul.fork_count} forks</span>
                                    <span>${soul.like_count} likes</span>
                                </div>
                            </div>
                        </a>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `<div class="col-span-3 text-center py-12 text-zinc-400">No trending souls yet</div>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="col-span-3 text-center py-12 text-red-400">Failed to load trending</div>`;
        }
    }
    window.onload = loadTrending;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>