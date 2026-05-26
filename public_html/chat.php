<?php
/**
 * SoulMD Hub - Core Intelligent Chat Interface
 * (Slim & Modular Master Controller Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 雙重防護機制：生成專屬 CSRF Token 防止 API 被盜用
if (empty($_SESSION['chat_csrf_token'])) {
    $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['chat_csrf_token'];

$db = Database::getInstance();
$pdo = $db->getConnection();

$soulId = (int)($_GET['soul_id'] ?? 0);
$sessionToken = $_GET['session_token'] ?? '';

// 如果沒有指定 Soul，跳回首頁
if (!$soulId) {
    header('Location: /browse');
    exit;
}

// 獲取 Soul 資訊 (只限公開的 Soul)
$stmt = $pdo->prepare("SELECT title, role, content, file_type FROM souls WHERE id = ? AND is_public = 1");
$stmt->execute([$soulId]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// 核心邏輯：如果是第一次進入 (沒有 Session Token)，自動生成並跳轉到專屬 URL
if (empty($sessionToken)) {
    $newToken = bin2hex(random_bytes(16)); 
    header("Location: /chat/{$soulId}/{$newToken}", true, 302);
    exit;
}

// ==========================================
// 🛡️ 獲取當前用戶階級與動態過期掃描 (Expiration Scan)
// ==========================================
$userTier = 'free';
$isSessionOwner = false;
$isPrivate = false;
$isExpired = false; 

if (isset($_SESSION['user_id'])) {
    $userStmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $uData = $userStmt->fetch();
    if ($uData) {
        $userTier = $uData['tier'];
        $expiryTime = $uData['vip_expires_at'] ? strtotime($uData['vip_expires_at']) : 0;
        
        // 自動過期降級與標記保護
        if ($expiryTime > 0 && $expiryTime < time()) {
            $isExpired = true;
            $userTier = 'free'; 
        }
    }
}

// 判斷私隱狀態與擁有權
$sessStmt = $pdo->prepare("SELECT user_id, is_private FROM chat_sessions WHERE session_token = ?");
$sessStmt->execute([$sessionToken]);
if ($chatSession = $sessStmt->fetch()) {
    $isPrivate = (bool)$chatSession['is_private'];
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $chatSession['user_id']) {
        $isSessionOwner = true;
    }
} else {
    $isSessionOwner = isset($_SESSION['user_id']);
}

// 動態載入 Config 限制 (嚴格控制前端狀態)
$tierPrefix = strtoupper($userTier);
$maxTurns = constant("{$tierPrefix}_MAX_TURNS");
$maxInputChars = constant("{$tierPrefix}_MAX_INPUT_CHARS");
$allowImage = constant("{$tierPrefix}_ALLOW_IMAGE") ? 'true' : 'false';

$pageTitle = 'Chat Session - ' . htmlspecialchars($soul['title']);
$pageDesc = 'Live interaction with this specialized AI persona architecture.';
$hideNavLinks = true; 
require_once __DIR__ . '/../private/includes/header.php';

// 引入獨立的法律免責聲明彈窗
require_once __DIR__ . '/../private/includes/disclaimer-modal.php';

// 🌟 引入分拆出來的圖片放大與智慧手機版 Paywall 彈窗組件
require_once __DIR__ . '/../private/includes/chat-modals.php';
?>

<div class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-4 flex flex-col h-[calc(100vh-80px)]">
    
    <div class="bg-zinc-900/80 border border-white/10 rounded-t-3xl p-4 flex justify-between items-center backdrop-blur-md shrink-0">
        <div class="flex items-center gap-3">
            <a href="/soul/<?= $soulId ?>" class="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center text-zinc-400 hover:text-emerald-400 hover:bg-zinc-700 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-white leading-tight"><?= htmlspecialchars($soul['title']) ?></h1>
                <p class="text-xs text-zinc-500 flex items-center gap-1">
                    <i class="fas fa-circle text-emerald-500 text-[8px] animate-pulse"></i> Active Persona Session
                </p>
            </div>
        </div>
        <div class="flex items-center">
            <?php if ($userTier !== 'free' && $isSessionOwner): ?>
                <label class="flex items-center cursor-pointer gap-2 mr-4 pr-4 border-r border-white/10" title="Toggle Private Session">
                    <div class="relative">
                        <input type="checkbox" id="privacy-toggle" class="sr-only" <?= $isPrivate ? 'checked' : '' ?> onchange="updatePrivacyUI()">
                        <div class="block w-10 h-6 rounded-full border transition-colors duration-300 <?= $isPrivate ? 'bg-emerald-500 border-emerald-500' : 'bg-zinc-800 border-white/10' ?>" id="privacy-bg"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 <?= $isPrivate ? 'translate-x-4' : '' ?>" id="privacy-dot"></div>
                    </div>
                    <span class="text-xs font-medium <?= $isPrivate ? 'text-emerald-400' : 'text-zinc-500' ?>" id="privacy-label">
                        <i class="fas <?= $isPrivate ? 'fa-lock' : 'fa-globe' ?>"></i> <span class="hidden sm:inline"><?= $isPrivate ? 'Private' : 'Public' ?></span>
                    </span>
                </label>
            <?php endif; ?>

            <button id="share-btn" onclick="shareChat(this)" class="<?= $isPrivate ? 'hidden ' : '' ?>px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-medium transition flex items-center gap-2">
                <i class="fas fa-share-alt"></i> <span class="hidden sm:inline">Share URL</span>
            </button>
        </div>
    </div>

    <div class="bg-amber-500/10 border-x border-b border-amber-500/20 p-3 text-xs text-amber-200/80 flex items-start gap-2 shrink-0">
        <i class="fas fa-shield-alt mt-0.5 text-amber-500"></i>
        <p><strong>Privacy Warning:</strong> Public session URLs can be viewed by anyone. Do not share sensitive information. Text input is limited to <?= number_format($maxInputChars) ?> characters.</p>
    </div>

    <div id="chat-box" class="flex-grow bg-zinc-950 border-x border-white/10 overflow-y-auto p-4 sm:p-6 space-y-6 custom-scrollbar scroll-smooth">
        <div class="flex justify-center items-center h-full" id="loading-history">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div>
        </div>
    </div>

    <div class="bg-zinc-900 border border-white/10 rounded-b-3xl p-4 shrink-0 flex flex-col gap-3">
        <form id="chat-form" class="relative flex items-end gap-3">
            <div class="flex-grow relative bg-zinc-950 border border-white/10 rounded-2xl flex flex-col transition focus-within:border-emerald-400 shadow-inner">
                
                <div id="image-preview-container" class="hidden p-3 border-b border-white/5 relative bg-zinc-900/30 rounded-t-2xl">
                    <img id="image-preview" class="h-20 w-auto rounded-lg object-cover border border-white/10 shadow-md cursor-pointer hover:opacity-80 transition" onclick="openImageModal(this.src)">
                    <button type="button" onclick="removeImage()" class="absolute top-2 right-2 bg-zinc-800 hover:bg-red-500 text-zinc-400 hover:text-white rounded-full w-6 h-6 flex items-center justify-center transition shadow-lg"><i class="fas fa-times text-xs"></i></button>
                </div>
                
                <div class="flex items-end">
                    <button type="button" onclick="triggerImageUpload()" class="p-3.5 text-zinc-400 hover:text-emerald-400 transition" title="Upload Image (VIP/PRO)">
                        <i class="fas fa-paperclip text-lg"></i>
                    </button>
                    <input type="file" id="image-upload-input" accept="image/jpeg, image/png, image/webp" class="hidden" onchange="handleImageSelection(event)">
                    
                    <textarea id="chat-input" rows="1" maxlength="<?= $maxInputChars ?>" placeholder="Type your message, Ctrl+V to paste image, Ctrl+Enter to send..." class="w-full bg-transparent px-2 py-3.5 pr-16 text-sm focus:outline-none resize-none custom-scrollbar text-white placeholder-zinc-500" style="min-height: 48px; max-height: 120px;" oninput="updateCharCount(this)"></textarea>
                    
                    <div id="char-count" class="absolute bottom-3 right-4 text-[10px] text-zinc-500 font-mono select-none">0/<?= $maxInputChars ?></div>
                </div>
            </div>

            <button type="submit" id="send-btn" class="h-12 px-6 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center justify-center shrink-0 shadow-lg">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/chat-scripts.php'; ?>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>