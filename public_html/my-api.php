<?php
/**
 * SoulMD Hub - My API Controller
 * (Clean & Modular Edition)
 */

// 如果沒有宣告 $isPublicApiPage，則預設為 false (私人管理模式)
$isPublicApiPage = $isPublicApiPage ?? false;

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

$apiKey = 'YOUR_API_KEY';
$isPremiumActive = false;
$userTier = 'free';
$isExpired = false;

// 如果是私人模式，才進行登入驗證及撈取/生成 API Key 與 訂閱狀態
if (!$isPublicApiPage) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user_id'];

    // 獲取 API Key 與 訂閱狀態
    $stmt = $pdo->prepare("SELECT api_key, tier, vip_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userRow = $stmt->fetch();
    
    if ($userRow) {
        $apiKey = $userRow['api_key'];
        $userTier = $userRow['tier'];
        $expiry = $userRow['vip_expires_at'] ? strtotime($userRow['vip_expires_at']) : 0;
        
        if ($userTier !== 'free') {
            if ($expiry > time()) {
                $isPremiumActive = true;
            } else {
                $isExpired = true; // 曾經付費但已過期
            }
        }
    }

    // 如果沒有 API Key，自動生成一個
    if (!$apiKey) {
        $apiKey = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$apiKey, $userId]);
    }
}

$baseUrl = defined('BASE_URL') ? BASE_URL : ("https://" . $_SERVER['HTTP_HOST']);

$pageTitle = $isPublicApiPage ? 'Public API Reference' : 'Developer API';
$pageDesc = 'Manage your API key and read integration docs for SoulMD Hub.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-8">
        <div>
            <?php if ($isPublicApiPage): ?>
                <a href="/browse" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                    <i class="fas fa-arrow-left"></i> Back to Hub
                </a>
            <?php else: ?>
                <a href="/my-souls" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                    <i class="fas fa-arrow-left"></i> Back to My Souls
                </a>
            <?php endif; ?>
            
            <h1 class="text-4xl font-bold tracking-tighter"><?= $isPublicApiPage ? 'Public API Reference' : 'Developer API' ?></h1>
            <p class="text-zinc-400 mt-2">Integrate SoulMD Hub programmatically. 100% API-Driven Architecture.</p>
        </div>
    </div>

    <?php if (!$isPublicApiPage): ?>
        <?php if (!$isPremiumActive): ?>
            <div class="bg-gradient-to-r from-red-900/40 to-amber-900/40 border border-red-500/50 p-6 rounded-3xl mb-8 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
                <div class="flex items-start gap-4 z-10">
                    <div class="text-red-400 text-3xl mt-1"><i class="fas fa-lock"></i></div>
                    <div>
                        <h3 class="text-xl font-bold text-white mb-1">
                            <?= $isExpired ? 'Your Premium Subscription has Expired!' : 'Headless Chat API is Locked (Free Tier)' ?>
                        </h3>
                        <p class="text-sm text-zinc-300 leading-relaxed">
                            <?= $isExpired 
                                ? "Your VIP/PRO access has lapsed. Direct headless access to the <code>/api/chat</code> endpoint has been restricted. Please renew your pass to restore full API integration capabilities." 
                                : "Direct headless access to the core Chat Engine (<code>/api/chat</code>) is exclusively reserved for VIP and PRO members. Upgrade now to build automated agents." ?>
                        </p>
                    </div>
                </div>
                <div class="shrink-0 z-10 w-full md:w-auto">
                    <a href="/upgrade" class="w-full md:w-auto px-6 py-3 bg-red-500 hover:bg-red-400 text-zinc-950 font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-red-500/20">
                        <?= $isExpired ? '<i class="fas fa-sync-alt"></i> Renew Subscription' : '<i class="fas fa-crown"></i> Upgrade to Unlock' ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div id="success-box" class="hidden bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-8 text-sm text-emerald-100 shadow-lg flex items-center gap-2 transition-all">
            <i class="fas fa-check-circle"></i> <span id="success-msg"></span>
        </div>
        <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-red-200 shadow-lg flex items-center gap-2 transition-all">
            <i class="fas fa-exclamation-circle"></i> <span id="error-msg"></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <?php if (!$isPublicApiPage): ?>
        <div class="xl:col-span-4 space-y-6">
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 backdrop-blur-sm shadow-xl relative overflow-hidden sticky top-6">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                <h3 class="text-lg font-bold mb-1">Your Secret Key</h3>
                <p class="text-xs text-zinc-400 mb-6 leading-relaxed">This key grants full access to create, edit, and interact with souls on your behalf. Keep it secure.</p>
                
                <div class="bg-zinc-950 border border-white/10 p-4 rounded-2xl flex items-center justify-between gap-3 mb-6">
                    <code id="key-display" class="text-sm text-emerald-400 font-mono truncate select-all"><?= htmlspecialchars($apiKey) ?></code>
                    <button onclick="copyKey(this)" class="text-zinc-400 hover:text-white transition shrink-0 bg-white/5 hover:bg-white/10 w-8 h-8 rounded-lg flex items-center justify-center">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>

                <button type="button" id="roll-btn" onclick="rollApiKey()" class="mb-3 w-full py-3 bg-zinc-800 hover:bg-red-500/20 text-zinc-300 hover:text-red-400 border border-white/5 hover:border-red-500/30 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                    <span id="roll-text"><i class="fas fa-redo text-xs"></i> Roll API Key</span>
                    <span id="roll-loading" class="hidden animate-spin h-4 w-4 border-2 border-current border-t-transparent rounded-full"></span>
                </button>

                <button onclick="downloadPostmanCollection()" class="w-full py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/10">
                    <i class="fas fa-file-download"></i> Download Postman Collection
                </button>

                <div class="mt-8 pt-6 border-t border-white/10">
                    <h3 class="text-emerald-400 font-bold mb-2 flex items-center gap-2"><i class="fas fa-shield-alt"></i> Authentication</h3>
                    <p class="text-xs text-zinc-300 leading-relaxed mb-4">Pass your API key via the HTTP <code>Authorization</code> header for endpoints that require it.</p>
                    <div class="bg-zinc-950 border border-white/10 p-3 rounded-xl text-xs font-mono text-zinc-400 overflow-x-auto whitespace-nowrap">
                        Authorization: Bearer <span class="text-emerald-300">YOUR_API_KEY</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php require_once __DIR__ . '/../private/includes/api-docs.php'; ?>

    </div>
</div>

<script>
    <?php if (!$isPublicApiPage): ?>
    // 複製 API Key
    function copyKey(btn) {
        const key = document.getElementById('key-display').innerText;
        navigator.clipboard.writeText(key).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i>';
            setTimeout(() => btn.innerHTML = original, 2000);
        });
    }

    // 重置 API Key
    async function rollApiKey() {
        if (!confirm('Are you sure you want to roll your API Key? All applications using the old key will lose access immediately.')) return;
        
        const btn = document.getElementById('roll-btn');
        const text = document.getElementById('roll-text');
        const loading = document.getElementById('roll-loading');
        const successBox = document.getElementById('success-box');
        const errorBox = document.getElementById('error-box');

        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        successBox.classList.add('hidden');
        errorBox.classList.add('hidden');

        try {
            const res = await fetch('/api/regenerate-key', { method: 'POST' });
            const data = await res.json();

            if (data.success) {
                document.getElementById('key-display').innerText = data.new_api_key;
                document.getElementById('success-msg').innerText = data.message;
                successBox.classList.remove('hidden');
            } else {
                document.getElementById('error-msg').innerText = data.error || 'Operation failed';
                errorBox.classList.remove('hidden');
            }
        } catch(e) {
            document.getElementById('error-msg').innerText = 'Network error. Please try again.';
            errorBox.classList.remove('hidden');
        } finally {
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../private/includes/api-postman.php'; ?>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>