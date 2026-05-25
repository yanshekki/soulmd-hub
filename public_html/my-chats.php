<?php
/**
 * SoulMD Hub - My Chats Page
 * Displays chat history for logged-in users (from DB) and guests (from LocalStorage)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
$db = Database::getInstance();
$pdo = $db->getConnection();

// ==========================================
// 🛡️ API 模式：處理未登入訪客從 LocalStorage 傳來的 Token 查詢
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $tokens = $input['tokens'] ?? [];

    if (empty($tokens) || !is_array($tokens)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    // 限制最多查詢 50 個 Token 防止惡意濫用
    $tokens = array_slice($tokens, 0, 50);
    $inQuery = implode(',', array_fill(0, count($tokens), '?'));

    $stmt = $pdo->prepare("
        SELECT cs.session_token, cs.soul_id, cs.created_at, s.title, s.role, u.username
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

if ($isLoggedIn) {
    // 登入用戶：直接從資料庫獲取其擁有的對話 Session
    $stmt = $pdo->prepare("
        SELECT cs.session_token, cs.soul_id, cs.is_private, cs.created_at, s.title, s.role, u.username
        FROM chat_sessions cs
        JOIN souls s ON cs.soul_id = s.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE cs.user_id = ?
        ORDER BY cs.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $myChats = $stmt->fetchAll();
}

// 輔助函數：角色圖示
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
        <?php if (empty($myChats)): ?>
            <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl flex-grow">
                <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500"><i class="fas fa-comments text-3xl"></i></div>
                <h2 class="text-2xl font-semibold mb-2">No conversations yet</h2>
                <p class="text-zinc-400 text-sm mb-6">Start chatting with an AI persona to see your history here.</p>
                <a href="/browse" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg">Start a Chat</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($myChats as $chat): ?>
                    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 hover:border-emerald-500/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-xl hover:-translate-y-1">
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
                                        <span class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wider border bg-emerald-500/10 text-emerald-400 border-emerald-500/20"><i class="fas fa-lock mr-1"></i>Private</span>
                                    <?php else: ?>
                                        <span class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wider border bg-zinc-800 text-zinc-400 border-white/5"><i class="fas fa-globe mr-1"></i>Public</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-xs text-zinc-400 mb-6 bg-zinc-950/50 p-3 rounded-xl border border-white/5 flex items-center gap-2">
                                <i class="fas fa-fingerprint text-emerald-500/50"></i> <code class="truncate"><?= htmlspecialchars($chat['session_token']) ?></code>
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

    <?php else: ?>
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

        <div id="guest-grid" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>

        <script>
            // 從 LocalStorage 抽取曾同意免責聲明嘅 Token
            function getGuestTokens() {
                const tokens = [];
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    // 格式：soulmd_agreement_{soulId}_{sessionToken}
                    if (key.startsWith('soulmd_agreement_')) {
                        const parts = key.split('_');
                        if (parts.length >= 4) {
                            const token = parts.slice(3).join('_');
                            if (token && !tokens.includes(token)) {
                                tokens.push(token);
                            }
                        }
                    }
                }
                return tokens;
            }

            // 安全渲染 HTML
            function escapeHTML(str) {
                if (!str) return '';
                return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
            }

            async function loadGuestChats() {
                const tokens = getGuestTokens();
                const loading = document.getElementById('guest-loading');
                const empty = document.getElementById('guest-empty');
                const grid = document.getElementById('guest-grid');

                if (tokens.length === 0) {
                    loading.classList.add('hidden');
                    empty.classList.remove('hidden');
                    return;
                }

                try {
                    const res = await fetch('/my-chats', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ tokens: tokens })
                    });
                    const result = await res.json();

                    loading.classList.add('hidden');

                    if (result.success && result.data.length > 0) {
                        grid.classList.remove('hidden');
                        let html = '';
                        
                        result.data.forEach(chat => {
                            const dateObj = new Date(chat.created_at);
                            const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                            
                            html += `
                                <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 hover:border-emerald-500/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-xl hover:-translate-y-1">
                                    <div>
                                        <div class="flex justify-between items-start gap-4 mb-4">
                                            <div>
                                                <div class="font-bold text-xl text-white tracking-tight mb-1 line-clamp-1" title="${escapeHTML(chat.title)}">${escapeHTML(chat.title)}</div>
                                                <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                                    <span>${escapeHTML(chat.role || 'Unassigned')}</span>
                                                    <span>•</span>
                                                    <span>${dateStr}</span>
                                                </div>
                                            </div>
                                            <div class="shrink-0">
                                                <span class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wider border bg-zinc-800 text-zinc-400 border-white/5"><i class="fas fa-globe mr-1"></i>Public</span>
                                            </div>
                                        </div>
                                        <div class="text-xs text-zinc-400 mb-6 bg-zinc-950/50 p-3 rounded-xl border border-white/5 flex items-center gap-2">
                                            <i class="fas fa-fingerprint text-emerald-500/50"></i> <code class="truncate">${escapeHTML(chat.session_token)}</code>
                                        </div>
                                    </div>
                                    <div class="pt-4 border-t border-white/5 mt-auto">
                                        <a href="/chat/${chat.soul_id}/${chat.session_token}" class="w-full py-3 bg-zinc-800 hover:bg-emerald-500 hover:text-zinc-950 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition shadow-lg">
                                            Resume Chat <i class="fas fa-arrow-right text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                            `;
                        });
                        grid.innerHTML = html;
                    } else {
                        empty.classList.remove('hidden');
                    }
                } catch (e) {
                    loading.classList.add('hidden');
                    empty.classList.remove('hidden');
                    empty.querySelector('h2').innerText = 'Connection Error';
                    empty.querySelector('p').innerText = 'Could not load your history at this time. Please try again later.';
                }
            }

            window.onload = loadGuestChats;
        </script>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>