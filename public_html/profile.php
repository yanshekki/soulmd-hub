<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_GET['username'] ?? '';

// 如果網址沒有 username 參數，直接導向瀏覽主頁
if (empty($username)) {
    header('Location: /browse');
    exit;
}

$pageTitle = htmlspecialchars($username) . "'s Profile";
$pageDesc = "Check out AI agent souls and prompts created by @" . htmlspecialchars($username) . " on SoulMD Hub.";
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow flex flex-col">
    
    <div id="loading-view" class="flex-grow flex items-center justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-emerald-400"></div>
    </div>

    <div id="error-view" class="hidden flex-grow flex items-center justify-center py-20">
        <div class="text-center max-w-sm">
            <div class="text-6xl mb-4">🔎</div>
            <h2 class="text-2xl font-bold mb-2">User Not Found</h2>
            <p class="text-zinc-400 text-sm mb-6">The developer profile you are trying to view does not exist or has been deactivated.</p>
            <a href="/browse" class="px-6 py-3 bg-zinc-900 border border-white/10 rounded-2xl text-sm font-medium hover:bg-white/5 transition inline-block">Explore Other Souls</a>
        </div>
    </div>

    <div id="profile-content" class="hidden space-y-10">
        
        <div class="bg-zinc-900/40 border border-white/10 rounded-3xl p-6 md:p-8 backdrop-blur-sm relative overflow-hidden shadow-xl">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div id="avatar-box" class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-400 to-cyan-400 flex items-center justify-center text-zinc-950 font-extrabold text-3xl shadow-lg shadow-emerald-500/10">
                        <span id="avatar-char">U</span>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-white flex items-center gap-2">
                            @<span id="profile-username">username</span>
                        </h1>
                        <p class="text-xs text-zinc-500 mt-1">
                            <i class="far fa-calendar-alt mr-1"></i> Joined <span id="profile-joined">Mmm DD, YYYY</span>
                        </p>
                    </div>
                </div>
                
                <div id="owner-badge" class="hidden">
                    <span class="text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-4 py-2 rounded-2xl font-semibold shadow-sm">
                        <i class="fas fa-user-check mr-1"></i> This is you
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 max-w-md mt-8 pt-6 border-t border-white/5 text-center sm:text-left">
                <div>
                    <div id="stat-souls" class="text-2xl md:text-3xl font-bold text-white font-mono">0</div>
                    <div class="text-xs text-zinc-500 mt-0.5">Shared Souls</div>
                </div>
                <div>
                    <div id="stat-likes" class="text-2xl md:text-3xl font-bold text-red-400 font-mono">0</div>
                    <div class="text-xs text-zinc-500 mt-0.5">Total Likes</div>
                </div>
                <div>
                    <div id="stat-forks" class="text-2xl md:text-3xl font-bold text-emerald-400 font-mono">0</div>
                    <div class="text-xs text-zinc-500 mt-0.5">Total Forks</div>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                Published Souls <span id="souls-count-badge" class="text-xs bg-white/10 px-2.5 py-0.5 rounded-full text-zinc-400">0</span>
            </h2>
            
            <div id="souls-grid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                </div>
            
            <div id="empty-souls" class="hidden text-center py-20 bg-zinc-900/20 border border-dashed border-white/10 rounded-3xl">
                <div class="text-4xl mb-3">📁</div>
                <p class="text-zinc-400 text-sm">This user hasn't published any public AI souls yet.</p>
            </div>
        </div>

    </div>
</div>

<script>
    // 🚨 完美安全修復：防禦 DOM-based XSS 攻擊
    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }

    async function fetchProfile() {
        const username = "<?= addslashes($username) ?>";
        const loadingView = document.getElementById('loading-view');
        const errorView = document.getElementById('error-view');
        const profileContent = document.getElementById('profile-content');

        try {
            // 呼叫我們剛剛起好的純 JSON Profile API
            const res = await fetch(`/api/profile?username=${encodeURIComponent(username)}`);
            const data = await res.json();

            if (!data.success) {
                loadingView.classList.add('hidden');
                errorView.classList.remove('hidden');
                return;
            }

            // 1. 綁定基礎資料 (innerText 內建安全過濾)
            document.getElementById('profile-username').innerText = data.user.username;
            document.getElementById('avatar-char').innerText = data.user.username.substr(0, 1).toUpperCase();
            
            const joinedDate = new Date(data.user.joined_at);
            document.getElementById('profile-joined').innerText = joinedDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            // 檢查 Session 是否為本人，顯示專屬辨識標誌
            const currentSessionUser = "<?= $_SESSION['username'] ?? '' ?>";
            if (currentSessionUser && currentSessionUser.toLowerCase() === data.user.username.toLowerCase()) {
                document.getElementById('owner-badge').classList.remove('hidden');
            }

            // 2. 刷新大數據看板
            document.getElementById('stat-souls').innerText = data.stats.total_souls;
            document.getElementById('stat-likes').innerText = data.stats.total_likes;
            document.getElementById('stat-forks').innerText = data.stats.total_forks;
            document.getElementById('souls-count-badge').innerText = data.stats.total_souls;

            // 3. 渲染公開大腦卡片列表
            const soulsGrid = document.getElementById('souls-grid');
            const emptySouls = document.getElementById('empty-souls');
            
            if (data.souls.length === 0) {
                emptySouls.classList.remove('hidden');
            } else {
                let html = '';
                data.souls.forEach(soul => {
                    let tagsHtml = '';
                    if (soul.domain) {
                        const tags = soul.domain.split(',').map(t => t.trim()).filter(Boolean).slice(0, 3);
                        tags.forEach(t => {
                            tagsHtml += `<span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded">#${escapeHTML(t)}</span>`;
                        });
                    }

                    // 🚨 套用 escapeHTML() 安全過濾字串
                    html += `
                        <a href="/soul/${soul.id}" class="group bg-zinc-900/60 border border-white/10 rounded-3xl p-6 hover:border-emerald-400/50 transition-all shadow-lg flex flex-col justify-between h-full backdrop-blur-sm">
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
                                <div class="truncate max-w-[120px]">${escapeHTML(soul.role || 'Unassigned')}</div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span><i class="fas fa-code-branch text-emerald-500"></i> <b class="text-zinc-300">${soul.fork_count}</b></span>
                                    <span><i class="fas fa-heart text-red-500"></i> <b class="text-zinc-300">${soul.like_count}</b></span>
                                </div>
                            </div>
                        </a>
                    `;
                });
                soulsGrid.innerHTML = html;
            }

            // 關閉 Loading 並淡入主體內容
            loadingView.classList.add('hidden');
            profileContent.classList.remove('hidden');

        } catch (e) {
            loadingView.classList.add('hidden');
            errorView.classList.remove('hidden');
        }
    }

    window.onload = fetchProfile;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>