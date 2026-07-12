<?php
/**
 * SoulMD Hub - Core Intelligent Chat Interface
 * (Slim & Modular Master Controller Edition with Dynamic i18n Support)
 * 🚀 V5 SEO Optimized: a11y ARIA Live Regions, Semantic Main Tag, and Form Labels
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';
require_once __DIR__ . '/../private/src/ApiSecurity.php';

session_start();

loadTranslations('chat');

// Centralized CSRF token (replaces repeated bin2hex block)
$csrfToken = ensureCsrfToken();

$db = Database::getInstance();
$pdo = $db->getConnection();

$soulId = (int)($_GET['soul_id'] ?? 0);
$sessionToken = $_GET['session_token'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

if (!$soulId) {
    header('Location: ' . url('/browse'));
    exit;
}

$stmt = $pdo->prepare("SELECT title, role, content, file_type, description, is_nft, user_id FROM souls WHERE id = ? AND (is_public = 1 OR is_nft = 1 OR user_id = ?)");
$stmt->execute([$soulId, $userId]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

if (empty($sessionToken)) {
    $newToken = bin2hex(random_bytes(16));
    header("Location: " . url("/chat/{$soulId}/{$newToken}"), true, 302);
    exit;
}

$isOwner = ($userId > 0 && $userId === $soul['user_id']);
$maskContent = ($soul['is_nft'] == 1 && !$isOwner); 

$userTier = 'free';
$isSessionOwner = false;
$isPrivate = false;
$isExpired = false; 

if ($userId > 0) {
    $userStmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $uData = $userStmt->fetch();
    
    if ($uData) {
        $userTier = $uData['tier'];
        $expiryTime = $uData['vip_expires_at'] ? strtotime($uData['vip_expires_at']) : 0;
        
        if ($expiryTime > 0 && $expiryTime < time()) {
            $isExpired = true;
            $userTier = 'free'; 
        }
    }
}

$sessStmt = $pdo->prepare("SELECT user_id, is_private FROM chat_sessions WHERE session_token = ?");
$sessStmt->execute([$sessionToken]);
if ($chatSession = $sessStmt->fetch()) {
    $isPrivate = (bool)$chatSession['is_private'];
    if ($userId > 0 && $userId === $chatSession['user_id']) {
        $isSessionOwner = true;
    }
} else {
    $isSessionOwner = ($userId > 0);
}

$tierPrefix = strtoupper($userTier);
$maxTurns = constant("{$tierPrefix}_MAX_TURNS");
$maxInputChars = constant("{$tierPrefix}_MAX_INPUT_CHARS");
$allowImage = constant("{$tierPrefix}_ALLOW_IMAGE") ? 'true' : 'false';

// 🚀 SEO Enhancement: Dynamic i18n Titles for Sharable Links
$pageTitle = __('Chat Session', ['title' => htmlspecialchars($soul['title'])]);
$pageDesc = __('Live interaction with this specialized AI persona architecture.');
// Show global header menu (Browse / Apps / Premium / etc.) like other pages
$hideNavLinks = false;

require_once __DIR__ . '/../private/includes/header.php';
require_once __DIR__ . '/../private/includes/disclaimer-modal.php';
require_once __DIR__ . '/../private/includes/chat-modals.php';

if ($maskContent) {
    $rawContentForModal = "🔒 **" . __('Protected') . "**\n\n" . __('Protected NFT Msg');
} else {
    $rawContentForModal = '';
    if ($soul['file_type'] === 'full_soul_folder') {
        $cleaned = str_replace("\\'", "'", $soul['content']);
        $parsed = json_decode($cleaned, true);
        if (is_array($parsed)) {
            foreach ($parsed as $fname => $fcontent) {
                if (strpos($fname, 'ERROR.md') !== false) continue;
                $fcontentStr = is_string($fcontent) ? $fcontent : json_encode($fcontent, JSON_UNESCAPED_UNICODE);
                $rawContentForModal .= "### 📄 {$fname}\n\n{$fcontentStr}\n\n---\n\n";
            }
        } else {
            $rawContentForModal = $soul['content'];
        }
    } else {
        $rawContentForModal = $soul['content'];
    }
}
?>

<textarea id="raw-soul-content" class="hidden" aria-hidden="true"><?= htmlspecialchars($rawContentForModal) ?></textarea>

<!-- 🚀 SEO Enhancement: Semantic <main> tag -->
<main class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-4 flex flex-col flex-1 min-h-0 h-[calc(100dvh-7.5rem)] sm:h-[calc(100dvh-8.5rem)]">
    
    <header class="bg-zinc-900/80 border border-white/10 rounded-t-3xl p-4 flex justify-between items-center backdrop-blur-md shrink-0">
        <div class="flex items-center gap-3 w-full">
            <a href="<?= url('/soul/' . $soulId) ?>" aria-label="<?= __('Back to Soul') ?>" class="w-10 h-10 rounded-full bg-zinc-800 flex items-center justify-center text-zinc-400 hover:text-emerald-400 hover:bg-zinc-700 transition shrink-0">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
            </a>
            <div class="flex-grow">
                <h1 class="text-base sm:text-lg font-bold text-white leading-tight line-clamp-1"><?= htmlspecialchars($soul['title']) ?></h1>
                
                <div class="text-[10px] sm:text-xs text-zinc-500 flex items-center gap-2 mt-0.5">
                    <span class="flex items-center gap-1"><i class="fas fa-circle text-emerald-500 text-[8px] animate-pulse" aria-hidden="true"></i> <?= __('Active Persona Session') ?></span>
                    
                    <span class="opacity-50 hidden sm:inline">|</span>
                    <span id="online-badge" class="hidden items-center gap-1 font-bold transition-colors duration-300" aria-live="polite">
                        <i class="fas fa-users" aria-hidden="true"></i> <span id="online-count">1</span>
                    </span>
                    <span class="opacity-50">|</span>
                    <button type="button" aria-haspopup="dialog" onclick="openSoulModal()" class="text-emerald-400 hover:text-emerald-300 font-medium transition flex items-center gap-1 focus:outline-none">
                        <i class="fas fa-info-circle" aria-hidden="true"></i> <?= __('More Info') ?>
                    </button>
                </div>
                
                <?php if (!empty($soul['description'])): ?>
                    <p class="text-[10px] sm:text-xs text-zinc-400 mt-1 line-clamp-1 max-w-md hidden sm:block"><?= htmlspecialchars($soul['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex items-center shrink-0 ml-2">
            <?php if ($userTier !== 'free' && $isSessionOwner): ?>
                <label class="flex items-center cursor-pointer gap-2 mr-4 pr-4 border-r border-white/10 hidden sm:flex" title="Toggle Private Session">
                    <div class="relative">
                        <input type="checkbox" id="privacy-toggle" class="sr-only" aria-label="<?= __('Toggle Private Session') ?>" <?= $isPrivate ? 'checked' : '' ?> onchange="updatePrivacyUI()">
                        <div class="block w-10 h-6 rounded-full border transition-colors duration-300 <?= $isPrivate ? 'bg-emerald-500 border-emerald-500' : 'bg-zinc-800 border-white/10' ?>" id="privacy-bg"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 <?= $isPrivate ? 'translate-x-4' : '' ?>" id="privacy-dot"></div>
                    </div>
                    <span class="text-xs font-medium <?= $isPrivate ? 'text-emerald-400' : 'text-zinc-500' ?>" id="privacy-label">
                        <i class="fas <?= $isPrivate ? 'fa-lock' : 'fa-globe' ?>" aria-hidden="true"></i> <span class="hidden sm:inline"><?= $isPrivate ? __('Private') : __('Public') ?></span>
                    </span>
                </label>
            <?php endif; ?>

            <button id="share-btn" onclick="shareChat(this)" aria-label="<?= __('Share URL') ?>" class="<?= $isPrivate ? 'hidden ' : '' ?>px-3 sm:px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-sm font-medium transition flex items-center gap-2">
                <i class="fas fa-share-alt" aria-hidden="true"></i> <span class="hidden sm:inline"><?= __('Share URL') ?></span>
            </button>
        </div>
    </header>

    <aside class="bg-amber-500/10 border-x border-b border-amber-500/20 p-3 text-xs text-amber-200/80 flex items-start gap-2 shrink-0">
        <i class="fas fa-shield-alt mt-0.5 text-amber-500" aria-hidden="true"></i>
        <p><strong><?= __('Privacy Warning:') ?></strong> <?= __('Privacy warning text', ['chars' => number_format($maxInputChars)]) ?></p>
    </aside>

    <!-- 🚀 SEO Enhancement: role="log" & aria-live="polite" for Screen Readers -->
    <div id="chat-box" role="log" aria-live="polite" aria-atomic="false" class="flex-grow bg-zinc-950 border-x border-white/10 overflow-y-auto p-4 sm:p-6 space-y-6 custom-scrollbar scroll-smooth">
        <div class="flex justify-center items-center h-full" id="loading-history">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400" aria-label="Loading history"></div>
        </div>
    </div>

    <footer class="bg-zinc-900 border border-white/10 rounded-b-3xl p-4 shrink-0 flex flex-col gap-3">
        <form id="chat-form" aria-label="Chat input form" class="relative flex items-end gap-3">
            <div class="flex-grow relative bg-zinc-950 border border-white/10 rounded-2xl flex flex-col transition focus-within:border-emerald-400 shadow-inner">
                
                <!-- Image Preview -->
                <div id="image-preview-container" class="hidden p-3 border-b border-white/5 relative bg-zinc-900/30 rounded-t-2xl">
                    <img id="image-preview" class="h-20 w-auto rounded-lg object-cover border border-white/10 shadow-md cursor-pointer hover:opacity-80 transition" onclick="openImageModal(this.src)" alt="Uploaded Image Preview">
                    <button type="button" onclick="removeImage()" aria-label="Remove Image" class="absolute top-2 right-2 bg-zinc-800 hover:bg-red-500 text-zinc-400 hover:text-white rounded-full w-6 h-6 flex items-center justify-center transition shadow-lg"><i class="fas fa-times text-xs" aria-hidden="true"></i></button>
                </div>
                
                <div class="flex items-end">
                    <button type="button" onclick="triggerImageUpload()" aria-label="Upload Image (VIP/PRO)" class="p-3.5 text-zinc-400 hover:text-emerald-400 transition" title="Upload Image (VIP/PRO)">
                        <i class="fas fa-paperclip text-lg" aria-hidden="true"></i>
                    </button>
                    <input type="file" id="image-upload-input" accept="image/jpeg, image/png, image/webp" class="hidden" onchange="handleImageSelection(event)">
                    
                    <textarea id="chat-input" rows="1" maxlength="<?= $maxInputChars ?>" aria-label="<?= __('Type your message...') ?>" placeholder="<?= __('Type your message...') ?>" class="w-full bg-transparent px-2 py-3.5 pr-16 text-sm focus:outline-none resize-none custom-scrollbar text-white placeholder-zinc-500" style="min-height: 48px; max-height: 120px;" oninput="updateCharCount(this)"></textarea>
                    
                    <div id="char-count" class="absolute bottom-3 right-4 text-[10px] text-zinc-500 font-mono select-none" aria-live="polite">0/<?= $maxInputChars ?></div>
                </div>
            </div>
            
            <button type="submit" id="send-btn" aria-label="<?= __('Send Message') ?>" class="h-12 w-16 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center justify-center shrink-0 shadow-lg">
                <span id="send-icon"><i class="fas fa-paper-plane" aria-hidden="true"></i></span>
                <span id="send-spinner" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full" aria-hidden="true"></span>
            </button>
        </form>
    </footer>

</main>

<?php require_once __DIR__ . '/../private/includes/chat-scripts.php'; ?>
<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>