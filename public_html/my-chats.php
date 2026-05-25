<?php
/**
 * SoulMD Hub - My Chats Page
 * (Hybrid Edition: Displays owned DB sessions + visited guest sessions simultaneously)
 * (Safari Date Parsing Bug Fixed)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
$db = Database::getInstance();
$pdo = $db->getConnection();

// ==========================================
// 🛡️ API 模式：處理前端 JS 送來的 LocalStorage Token 查詢
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $tokens = $input['tokens'] ?? [];

    if (empty($tokens) || !is_array($tokens)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $tokens = array_slice($tokens, 0, 50); // 限制最多查詢 50 個
    $inQuery = implode(',', array_fill(0, count($tokens), '?'));

    // 🚨 包含 owner_username，讓前端知道這是誰的 Session
    $stmt = $pdo->prepare("
        SELECT cs.session_token, cs.soul_id, cs.created_at, s.title, s.role, u.username as owner_username
        FROM chat_sessions cs
        JOIN souls s ON cs.soul_id = s.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE cs.session_token IN ($inQuery)
        ORDER BY cs.created_at DESC
    ");
    $stmt->execute($tokens);
    $guestChats = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $guestChats], JSON_UNESCAPED_UNICODE);
    exit;
}

// ==========================================
// 🖥️ UI 渲染模式
// ==========================================
$isLoggedIn = isset($_SESSION['user_id']);
$myChats = [];
$ownedTokens = [];

if ($isLoggedIn) {
    // 獲取自己創建/擁有的對話
    $stmt = $pdo->prepare("
        SELECT cs.session_token, cs.soul_id, cs.is_private, cs.created_at, s.title, s.role
        FROM chat_sessions cs
        JOIN souls s ON cs.soul_id = s.id
        WHERE cs.user_id = ?
        ORDER BY cs.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $myChats = $stmt->fetchAll();
    
    // 抽出 Token 供前端過濾使用，避免與 LocalStorage 重複顯示
    $ownedTokens = array_column($myChats, 'session_token');
}

function getRoleIcon($roleSlug) {
    global $pdo;
    static $icons = [];
    if (empty($icons)) {
        $catStmt = $pdo->query("SELECT slug, icon FROM categories");
        while ($row = $catStmt->fetch()) { $icons[$row['slug']] = $row['icon']; }
    }
    return $icons[$roleSlug] ?? '✨';
}

$pageTitle = 'My Chats - SoulMD Hub';
$pageDesc = 'View your recent AI chat sessions and continue your conversations.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow flex flex-col">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-10 border-b border-white/10 pb-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter">My Chats</h1>
            <p class="text-zinc-400 mt-1">Review and continue your recent AI conversations.</p>
        </div>
        <div>
            <a href="/browse" class="px-6 py-3 bg-white text-zinc-950 rounded-2xl font-bold hover:bg-zinc-200 transition flex items-center gap-2 shadow-lg">
                <i class="fas fa-search"></i> Discover Souls
            </a>
        </div>
    </div>

    <?php if ($isLoggedIn): ?>
        <h2 class="text-xl font-bold mb-5 flex items-center gap-2 text-white">
            <i class="fas fa-user-circle text-emerald-400"></i> My Personal Sessions
        </h2>
        
        <?php if (empty($myChats)): ?>
            <div class="text-center py-16 bg-zinc-900/20 border border-white/5 rounded-3xl mb-12">
                <div class="mx-auto w-16 h-16 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-4 text-zinc-500"><i class="fas fa-comments text-2xl"></i></div>
                <p class="text-zinc-400 text-sm mb-4">You haven't created any chat sessions yet.</p>
                <a href="/browse" class="inline-flex items-center gap-2 px-5 py-2.5 bg-zinc-800 text-white rounded-xl font-bold hover:bg-zinc-700 transition">Start a Chat</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <?php foreach ($myChats as $chat): ?>
                    <div class="bg-zinc-900/60 border border-emerald-500/20 rounded-3xl p-6 hover:border-emerald-400/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-xl hover:-translate-y-1 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500/50 to-transparent"></div>
                        <div>
                            <div class="flex justify-between items-start gap-4 mb-4">
                                <div>
                                    <div class="font-bold text-xl text-white tracking-tight mb-1 line-clamp-1" title="<?= htmlspecialchars($chat['title']) ?>"><?= htmlspecialchars($chat['title']) ?></div>
                                    <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                        <span><?= getRoleIcon($chat['role']) ?> <?= htmlspecialchars($chat['role'] ?: 'Unassigned') ?></span>
                                        <span>•</span>
                                        <span><?= date('M j, H:i', strtotime($chat['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <?php if ($chat['is_private']): ?>
                                        <span class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wider border bg-emerald-500/10 text-emerald-400 border-emerald-500/20" title="Only you can access this"><i class="fas fa-lock mr-1"></i>Private</span>
                                    <?php else: ?>
                                        <span class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wider border bg-zinc-800 text-zinc-400 border-white/5" title="Anyone with the link can view"><i class="fas fa-globe mr-1"></i>Public</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-[10px] font-mono text-emerald-500/70 mb-6 bg-black/20 p-2 rounded-lg border border-emerald-500/10 truncate">
                                <i class="fas fa-link mr-1"></i> <?= htmlspecialchars($chat['session_token']) ?>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-white/5 mt-auto">
                            <a href="/chat/<?= $chat['soul_id'] ?>/<?= $chat['session_token'] ?>" class="w-full py-3 bg-zinc-800 hover:bg-emerald-500 hover:text-zinc-950 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition shadow-lg">
                                Continue Chat <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div id="visited-section" class="hidden flex-grow flex-col">
        <h2 class="text-xl font-bold mb-5 flex items-center gap-2 text-zinc-300">
            <i class="fas fa-history text-zinc-500"></i> Recently Viewed / Guest Sessions
        </h2>
        <div id="guest-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
    </div>

    <?php if (!$isLoggedIn): ?>
        <div id="guest-loading" class="flex-grow flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-emerald-400"></div>
        </div>

        <div id="guest-empty" class="hidden text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl flex-grow">
            <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500"><i class="fas fa-user-secret text-3xl"></i></div>
            <h2 class="text-2xl font-semibold mb-2">Guest History Empty</h2>
            <p class="text-zinc-400 text-sm mb-6 max-w-md mx-auto">We couldn't find any recent chats saved in your browser. Create an account to permanently sync and lock your private sessions!</p>
            <div class="flex items-center justify-center gap-4">
                <a href="/register" class="px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg">Create Account</a>
                <a href="/browse" class="px-6 py-3 bg-zinc-800 text-white rounded-2xl font-bold hover:bg-zinc-700 transition border border-white/5">Explore Souls</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    const ownedTokens = <?= json_encode($ownedTokens) ?>;
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

    // 從 LocalStorage 抽取 Token，並自動過濾掉已登入用戶擁有的對話
    function getGuestTokens() {
        const tokens = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith('soulmd_agreement_')) {
                const parts = key.split('_');
                if (parts.length >= 4) {
                    const token = parts.slice(3).join('_');
                    if (token && !tokens.includes(token) && !ownedTokens.includes(token)) {
                        tokens.push(token);
                    }
                }
            }
        }
        return tokens;
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }

    async function loadGuestChats() {
        const tokens = getGuestTokens();
        const loading = document.getElementById('guest-loading');
        const empty = document.getElementById('guest-empty');
        const grid = document.getElementById('guest-grid');
        const section = document.getElementById('visited-section');

        if (tokens.length === 0) {
            if (loading) loading.classList.add('hidden');
            if (empty && !isLoggedIn) empty.classList.remove('hidden');
            return;
        }

        try {
            const res = await fetch('/my-chats', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tokens: tokens })
            });
            const result = await res.json();

            if (loading) loading.classList.add('hidden');

            if (result.success && result.data.length > 0) {
                section.classList.remove('hidden');
                section.classList.add('flex');
                let html = '';
                
                result.data.forEach(chat => {
                    // 🚨 終極修復：解決 Safari 瀏覽器出現 Invalid Date / NaN 的問題
                    // 將 "2024-05-20 12:00:00" 格式的 "-" 替換為 "/" 以兼容 iOS/macOS Safari 引擎
                    const safeDateString = (chat.created_at || '').replace(/-/g, '/');
                    const dateObj = new Date(safeDateString);
                    const dateStr = isNaN(dateObj) ? 'Recent' : dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    
                    // 明確標示這對話是誰擁有的
                    const ownerText = chat.owner_username ? `@${escapeHTML(chat.owner_username)}` : 'Guest User';
                    
                    html += `
                        <div class="bg-zinc-900/40 border border-dashed border-white/10 rounded-3xl p-6 hover:border-emerald-500/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-xl hover:-translate-y-1">
                            <div>
                                <div class="flex justify-between items-start gap-4 mb-4">
                                    <div>
                                        <div class="font-bold text-lg text-zinc-300 tracking-tight mb-1 line-clamp-1" title="${escapeHTML(chat.title)}">${escapeHTML(chat.title)}</div>
                                        <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                            <span>${escapeHTML(chat.role || 'Unassigned')}</span>
                                            <span>•</span>
                                            <span>${dateStr}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="text-[10px] px-2 py-1 rounded-md font-medium border bg-zinc-800/80 text-zinc-400 border-white/5" title="Owned by"><i class="fas fa-user mr-1"></i>${ownerText}</span>
                                    </div>
                                </div>
                                <div class="text-[10px] font-mono text-zinc-500 mb-6 bg-black/20 p-2 rounded-lg border border-white/5 truncate">
                                    <i class="fas fa-link text-emerald-500/30 mr-1"></i> ${escapeHTML(chat.session_token)}
                                </div>
                            </div>
                            <div class="pt-4 border-t border-white/5 mt-auto">
                                <a href="/chat/${chat.soul_id}/${chat.session_token}" class="w-full py-2.5 bg-zinc-800/50 hover:bg-emerald-500 hover:text-zinc-950 text-zinc-300 font-bold rounded-xl flex items-center justify-center gap-2 transition shadow-sm border border-white/5">
                                    View Session <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    `;
                });
                grid.innerHTML = html;
            } else {
                if (empty && !isLoggedIn) empty.classList.remove('hidden');
            }
        } catch (e) {
            if (loading) loading.classList.add('hidden');
            if (empty && !isLoggedIn) {
                empty.classList.remove('hidden');
                empty.querySelector('h2').innerText = 'Connection Error';
                empty.querySelector('p').innerText = 'Could not load your history at this time. Please try again later.';
            }
        }
    }

    // 無論有冇登入，都會自動執行呢個函數去合併 LocalStorage 嘅對話
    window.onload = loadGuestChats;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>