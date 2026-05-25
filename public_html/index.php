<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// 獲取平台統計數據
$statsSouls = $pdo->query("SELECT COUNT(*) FROM souls WHERE is_public = 1")->fetchColumn();
$statsUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$statsTags = $pdo->query("SELECT COUNT(*) FROM tags_domain")->fetchColumn() ?: 0;
$categories = $pdo->query("SELECT name, slug, icon FROM categories LIMIT 6")->fetchAll();

$pageTitle = 'SoulMD Hub - The Ultimate Multi-Modal AI Agent Platform';
// 🚨 隱藏了底層模型名稱，改為 Elite Reasoning Engine
$pageDesc = 'Discover, interact, and build powerful AI personas. Featuring Elite Reasoning Engine, Vision AI, and smart sliding memory.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 pb-20 pt-8">
    
    <div class="text-center py-12 md:py-20 relative">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-3/4 h-3/4 bg-emerald-500/10 blur-[100px] rounded-full pointer-events-none -z-10"></div>
        
        <a href="/upgrade" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-900/40 to-teal-900/40 text-emerald-300 border border-emerald-500/30 px-5 py-2 rounded-full text-sm font-medium mb-8 hover:scale-105 transition transform shadow-lg shadow-emerald-500/10">
            <i class="fas fa-rocket text-amber-400"></i> Now supporting Vision AI & Elite Reasoning Engine!
        </a>
        
        <h1 class="text-5xl md:text-7xl font-extrabold tracking-tighter leading-tight mb-6 text-white">
            Give your AI a <span class="gradient-text">Soul</span>.<br>Let the world talk to it.
        </h1>
        
        <p class="max-w-2xl mx-auto text-lg md:text-xl text-zinc-400 mb-12 leading-relaxed">
            The most advanced SaaS ecosystem to discover, interact, and monetize `.md` based AI personas. Powered by extreme logic reasoning and real-time image analysis.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <a href="/browse" class="w-full sm:w-auto flex items-center justify-center gap-3 px-10 py-4 bg-emerald-500 text-zinc-950 text-lg font-bold rounded-2xl hover:bg-emerald-400 transition shadow-xl shadow-emerald-500/20 hover:-translate-y-1">
                <i class="fas fa-compass"></i> Discover Souls
            </a>
            <a href="/generate" class="w-full sm:w-auto flex items-center justify-center gap-3 px-10 py-4 border border-white/20 bg-zinc-900/50 text-white text-lg font-bold rounded-2xl hover:bg-white/10 hover:border-white/40 transition backdrop-blur-sm">
                <i class="fas fa-magic"></i> AI Generator
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center mb-24 border-y border-white/5 py-10 bg-zinc-900/20">
        <div>
            <div class="text-4xl font-black text-white tracking-tight"><?= number_format($statsSouls) ?>+</div>
            <div class="text-emerald-400 text-sm font-bold uppercase tracking-widest mt-1">Souls Shared</div>
        </div>
        <div>
            <div class="text-4xl font-black text-white tracking-tight"><?= number_format($statsUsers) ?>+</div>
            <div class="text-emerald-400 text-sm font-bold uppercase tracking-widest mt-1">Active Creators</div>
        </div>
        <div>
            <div class="text-4xl font-black text-white tracking-tight"><?= number_format($statsTags) ?></div>
            <div class="text-emerald-400 text-sm font-bold uppercase tracking-widest mt-1">Knowledge Domains</div>
        </div>
    </div>

    <div class="mb-24">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-4">Enterprise-Grade AI Features</h2>
            <p class="text-zinc-400">Upgrade to Premium and unlock the ultimate agent ecosystem.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-zinc-900/50 border border-white/10 p-6 rounded-3xl hover:border-emerald-500/30 transition group">
                <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fas fa-eye text-2xl text-emerald-400"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Vision AI Analysis</h3>
                <p class="text-sm text-zinc-400 leading-relaxed">Upload images directly in chat. Let the AI analyze charts, code snippets, and designs in real-time.</p>
            </div>
            
            <div class="bg-zinc-900/50 border border-white/10 p-6 rounded-3xl hover:border-amber-500/30 transition group">
                <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fas fa-brain text-2xl text-amber-400"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Elite Reasoning Engine</h3>
                <p class="text-sm text-zinc-400 leading-relaxed">Unlock the PRO engine. Experience unmatched logical reasoning, complex math solving, and deep thinking.</p>
            </div>

            <div class="bg-zinc-900/50 border border-white/10 p-6 rounded-3xl hover:border-purple-500/30 transition group">
                <div class="w-12 h-12 bg-purple-500/10 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fas fa-layer-group text-2xl text-purple-400"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Smart Memory</h3>
                <p class="text-sm text-zinc-400 leading-relaxed">Dynamic sliding-window compression ensures your agent never forgets important context over long conversations.</p>
            </div>

            <div class="bg-zinc-900/50 border border-white/10 p-6 rounded-3xl hover:border-blue-500/30 transition group">
                <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <i class="fas fa-lock text-2xl text-blue-400"></i>
                </div>
                <h3 class="text-lg font-bold text-white mb-2">Private Sessions</h3>
                <p class="text-sm text-zinc-400 leading-relaxed">Keep your corporate secrets safe. Lock your chat sessions so only you can access the URLs and history.</p>
            </div>
        </div>
    </div>

    <div class="mb-24">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold">Popular Domains</h2>
                <p class="text-zinc-400 text-sm mt-1">Find the perfect AI persona for your workflow.</p>
            </div>
            <a href="/browse" class="text-emerald-400 text-sm font-medium hover:underline hidden sm:block">Explore All &rarr;</a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach ($categories as $cat): ?>
                <a href="/browse?role=<?= urlencode($cat['slug']) ?>" class="bg-zinc-900/60 hover:bg-zinc-800 transition p-6 rounded-3xl text-center shadow-lg border border-white/5 hover:border-emerald-500/20 group">
                    <div class="text-4xl mb-3 transform group-hover:scale-110 transition duration-300"><?= htmlspecialchars($cat['icon'] ?? '✨') ?></div>
                    <div class="font-bold text-sm text-zinc-300 group-hover:text-white"><?= htmlspecialchars($cat['name']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold flex items-center gap-2"><i class="fas fa-fire text-amber-500"></i> Trending Souls</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="trending-souls">
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
        let slug = str.toLowerCase();
        slug = slug.replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|]+/g, '-');
        slug = slug.replace(/^-+|-+$/g, '');
        return encodeURIComponent(slug);
    }

    async function loadTrending() {
        const container = document.getElementById('trending-souls');
        container.innerHTML = `<div class="col-span-3 flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;

        try {
            const res = await fetch('/api/souls?limit=6&sort=popular'); 
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(soul => {
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
                                ${soul.description ? `<p class="text-sm text-zinc-400 line-clamp-3 mb-6 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                            </div>
                            <div class="flex items-center justify-between text-xs text-zinc-500 pt-4 border-t border-white/5 mt-auto">
                                <div class="truncate max-w-[120px]"><i class="fas fa-user-circle text-zinc-600 mr-1"></i> ${escapeHTML(soul.username || 'Anonymous')}</div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span><i class="fas fa-code-branch text-emerald-500"></i> <b class="text-zinc-300">${soul.fork_count}</b></span>
                                    <span><i class="fas fa-heart text-red-500"></i> <b class="text-zinc-300">${soul.like_count}</b></span>
                                </div>
                            </div>
                        </a>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `<div class="col-span-3 text-center py-12 text-zinc-400 bg-zinc-900/20 rounded-3xl border border-white/5">No trending souls yet. Be the first to upload!</div>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="col-span-3 text-center py-12 text-red-400">Failed to load trending souls.</div>`;
        }
    }
    window.onload = loadTrending;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>