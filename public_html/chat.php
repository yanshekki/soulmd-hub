<?php
/**
 * SoulMD Hub - Core Intelligent Chat Interface
 * (Includes Client-side Pre-compression, Ctrl+V Paste, Ctrl+Enter Send, Silent Privacy Sync, Auto-grow Input & Smart Expired Paywall)
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
$isExpired = false; // 🚨 新增：判定該用戶是否已過期

if (isset($_SESSION['user_id'])) {
    $userStmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $uData = $userStmt->fetch();
    if ($uData) {
        $userTier = $uData['tier'];
        $expiryTime = $uData['vip_expires_at'] ? strtotime($uData['vip_expires_at']) : 0;
        
        // 🚨 自動過期降級與標記保護
        if ($expiryTime > 0 && $expiryTime < time()) {
            $isExpired = true;
            $userTier = 'free'; // 強制鎖回免費權限
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
?>

<div id="image-viewer-modal" class="hidden fixed inset-0 z-[300] bg-black/95 flex items-center justify-center p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="closeImageModal()">
    <button type="button" class="absolute top-6 right-6 text-white hover:text-emerald-400 text-3xl transition focus:outline-none"><i class="fas fa-times"></i></button>
    <img id="image-viewer-img" src="" class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl transform scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
</div>

<div id="paywall-modal" class="hidden fixed inset-0 bg-black/90 flex items-center justify-center z-[200] p-4 backdrop-blur-md opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border <?= $isExpired ? 'border-red-500/40 shadow-red-500/5' : 'border-emerald-500/30 shadow-emerald-500/5' ?> rounded-3xl max-w-4xl w-full flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/50">
            <div>
                <h3 class="text-2xl font-bold tracking-tight text-white">
                    <?= $isExpired ? 'Your Premium Subscription has Expired! ⚠️' : 'Unlock Full AI Power 🚀' ?>
                </h3>
                <p class="text-sm text-zinc-400 mt-1">
                    <?= $isExpired ? 'Your access window has closed. Please renew your plan to restore active token clusters.' : 'You\'ve reached the free trial limit or tried to access a premium feature.' ?>
                </p>
            </div>
            <button type="button" onclick="closePaywall()" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
        </div>

        <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-6 bg-zinc-950/30">
            <div class="bg-zinc-900 border border-white/10 rounded-3xl p-6 flex flex-col hover:border-emerald-400/50 transition">
                <div class="text-emerald-400 text-sm font-bold tracking-widest uppercase mb-2">VIP Member</div>
                <div class="text-4xl font-extrabold text-white mb-2">$<?= PRICE_VIP_MONTHLY ?> <span class="text-lg text-zinc-500 font-normal">/mo</span></div>
                <p class="text-sm text-zinc-400 mb-6 pb-6 border-b border-white/10">Perfect for daily tasks and unrestricted standard AI conversations.</p>
                <ul class="space-y-3 mb-8 flex-grow text-sm text-zinc-300">
                    <li><i class="fas fa-check text-emerald-500 mr-2"></i> <b>Unlimited</b> standard messages</li>
                    <li><i class="fas fa-check text-emerald-500 mr-2"></i> Up to <b><?= number_format(VIP_MAX_INPUT_CHARS) ?></b> characters per input</li>
                    <li><i class="fas fa-check text-emerald-500 mr-2"></i> <b>Vision AI</b> (Upload JPG/PNG)</li>
                    <li><i class="fas fa-check text-emerald-500 mr-2"></i> Extended chat memory retention</li>
                    <li><i class="fas fa-check text-emerald-500 mr-2"></i> Private session toggle lock</li>
                </ul>
                <a href="/upgrade" class="w-full夹 py-3 <?= $isExpired ? 'bg-zinc-800 hover:bg-red-500 hover:text-zinc-950' : 'bg-zinc-800 hover:bg-zinc-700' ?> text-white font-bold rounded-xl text-center transition">
                    <?= $isExpired ? '<i class="fas fa-sync-alt mr-1"></i> Renew VIP Pass' : 'Upgrade to VIP' ?>
                </a>
            </div>

            <div class="bg-gradient-to-b from-emerald-900/40 to-zinc-900 border border-emerald-500/50 rounded-3xl p-6 flex flex-col relative transform md:-translate-y-2 shadow-2xl">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-emerald-500 text-zinc-950 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest shadow-md">Most Powerful</div>
                <div class="text-white text-sm font-bold tracking-widest uppercase mb-2 flex items-center gap-2"><i class="fas fa-fire text-amber-500"></i> PRO Member</div>
                <div class="text-4xl font-extrabold text-white mb-2">$<?= PRICE_PRO_MONTHLY ?> <span class="text-lg text-emerald-500/50 font-normal">/mo</span></div>
                <p class="text-sm text-emerald-100/70 mb-6 pb-6 border-b border-emerald-500/20">Unlock our ultimate Elite Reasoning Engine for complex logic and coding tasks.</p>
                <ul class="space-y-3 mb-8 flex-grow text-sm text-zinc-200">
                    <li><i class="fas fa-check text-emerald-400 mr-2"></i> <b>Elite Reasoning Engine</b> Access</li>
                    <li><i class="fas fa-check text-emerald-400 mr-2"></i> <b>Unlimited</b> advanced messages</li>
                    <li><i class="fas fa-check text-emerald-400 mr-2"></i> Massive <b><?= number_format(PRO_MAX_INPUT_CHARS) ?></b> characters per input</li>
                    <li><i class="fas fa-check text-emerald-400 mr-2"></i> Deep thinking & long AI outputs</li>
                    <li><i class="fas fa-check text-emerald-400 mr-2"></i> Advanced Vision AI analysis</li>
                </ul>
                <a href="/upgrade" class="w-full py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-xl text-center transition shadow-lg">
                    <?= $isExpired ? '<i class="fas fa-sync-alt mr-1"></i> Renew PRO Pass' : 'Get PRO Access' ?>
                </a>
            </div>
        </div>
    </div>
</div>

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

<script>
    const soulId = <?= $soulId ?>;
    const sessionToken = "<?= htmlspecialchars($sessionToken) ?>";
    const serverCsrfToken = "<?= $csrfToken ?>"; 
    const chatBox = document.getElementById('chat-box');
    const chatInput = document.getElementById('chat-input');
    const charCount = document.getElementById('char-count');
    const sendBtn = document.getElementById('send-btn');
    const chatForm = document.getElementById('chat-form');
    
    let userMessageCount = 0;
    const MAX_TURNS = <?= $maxTurns ?>;
    const MAX_INPUT_CHARS = <?= $maxInputChars ?>;
    const ALLOW_IMAGE = <?= $allowImage ?>;
    const IMG_MAX_DIM = <?= defined('IMAGE_MAX_DIMENSION') ? IMAGE_MAX_DIMENSION : 800 ?>; 
    const IMG_QUALITY = <?= defined('IMAGE_QUALITY') ? IMAGE_QUALITY : 0.6 ?>;

    let currentImageBase64 = null;

    const agreementKey = `soulmd_agreement_${soulId}_${sessionToken}`;
    if (!localStorage.getItem(agreementKey)) {
        document.getElementById('disclaimer-modal').classList.remove('hidden');
    }
    function acceptDisclaimer() {
        localStorage.setItem(agreementKey, 'true');
        document.getElementById('disclaimer-modal').classList.add('hidden');
    }
    function declineDisclaimer() {
        window.location.href = '/browse';
    }

    function scrollToBottom() {
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    if (typeof marked.use === 'function') {
        marked.use({ breaks: true, gfm: true });
    } else if (typeof marked.setOptions === 'function') {
        try { marked.setOptions({ breaks: true, gfm: true }); } catch(e) {}
    }

    function parseMarkdown(text) {
        try {
            return marked.parse(text);
        } catch (e) {
            return escapeHTML(text).replace(/\n/g, '<br>');
        }
    }

    function openImageModal(src) {
        const modal = document.getElementById('image-viewer-modal');
        const img = document.getElementById('image-viewer-img');
        img.src = src;
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            img.classList.remove('scale-95');
            img.classList.add('scale-100');
        }, 10);
    }

    function closeImageModal() {
        const modal = document.getElementById('image-viewer-modal');
        const img = document.getElementById('image-viewer-img');
        modal.classList.add('opacity-0');
        img.classList.remove('scale-100');
        img.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); img.src = ''; }, 300);
    }

    async function updatePrivacyUI() {
        const toggle = document.getElementById('privacy-toggle');
        if(!toggle) return;
        const bg = document.getElementById('privacy-bg');
        const dot = document.getElementById('privacy-dot');
        const label = document.getElementById('privacy-label');
        const shareBtn = document.getElementById('share-btn');

        if (toggle.checked) {
            bg.classList.replace('bg-zinc-800', 'bg-emerald-500');
            bg.classList.replace('border-white/10', 'border-emerald-500');
            dot.classList.add('translate-x-4');
            label.innerHTML = '<i class="fas fa-lock"></i> <span class="hidden sm:inline">Private</span>';
            label.classList.replace('text-zinc-500', 'text-emerald-400');
            if(shareBtn) shareBtn.classList.add('hidden'); 
        } else {
            bg.classList.replace('bg-emerald-500', 'bg-zinc-800');
            bg.classList.replace('border-emerald-500', 'border-white/10');
            dot.classList.remove('translate-x-4');
            label.innerHTML = '<i class="fas fa-globe"></i> <span class="hidden sm:inline">Public</span>';
            label.classList.replace('text-emerald-400', 'text-zinc-500');
            if(shareBtn) shareBtn.classList.remove('hidden'); 
        }

        try {
            await fetch('/api/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': serverCsrfToken },
                body: JSON.stringify({ 
                    action: 'update_privacy',
                    soul_id: soulId, 
                    session_token: sessionToken, 
                    is_private: toggle.checked 
                })
            });
        } catch(e) { console.error('Privacy sync failed'); }
    }

    function triggerImageUpload() {
        if (!ALLOW_IMAGE) {
            showPaywall();
            return;
        }
        document.getElementById('image-upload-input').click();
    }

    function removeImage() {
        currentImageBase64 = null;
        document.getElementById('image-upload-input').value = '';
        document.getElementById('image-preview-container').classList.add('hidden');
        document.getElementById('image-preview').src = '';
    }

    function processImageFile(file) {
        if (!file.type.match('image.*')) {
            alert("Only JPG, PNG and WEBP images are supported.");
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                let width = img.width;
                let height = img.height;

                if (width > height) {
                    if (width > IMG_MAX_DIM) { height = Math.round((height *= IMG_MAX_DIM / width)); width = IMG_MAX_DIM; }
                } else {
                    if (height > IMG_MAX_DIM) { width = Math.round((width *= IMG_MAX_DIM / height)); height = IMG_MAX_DIM; }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                currentImageBase64 = canvas.toDataURL('image/jpeg', IMG_QUALITY);
                
                document.getElementById('image-preview').src = currentImageBase64;
                document.getElementById('image-preview-container').classList.remove('hidden');
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    function handleImageSelection(event) {
        const file = event.target.files[0];
        if (file) processImageFile(file);
    }

    chatInput.addEventListener('paste', (e) => {
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (let item of items) {
            if (item.type.indexOf('image') === 0) {
                if (!ALLOW_IMAGE) {
                    e.preventDefault();
                    showPaywall();
                    return;
                }
                e.preventDefault(); 
                const file = item.getAsFile();
                processImageFile(file); 
                break; 
            }
        }
    });

    chatInput.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            sendBtn.click();
        }
    });

    function showPaywall() {
        const modal = document.getElementById('paywall-modal');
        modal.classList.remove('hidden');
        setTimeout(() => { modal.classList.remove('opacity-0'); modal.firstElementChild.classList.remove('scale-95'); modal.firstElementChild.classList.add('scale-100'); }, 10);
    }

    function closePaywall() {
        const modal = document.getElementById('paywall-modal');
        modal.classList.add('opacity-0'); modal.firstElementChild.classList.remove('scale-100'); modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    // 🚨 核心 UX 擴展：輸入框自動變高
    function updateCharCount(el) {
        const len = el.value.length;
        charCount.innerText = `${len}/${MAX_INPUT_CHARS}`;
        if (len >= MAX_INPUT_CHARS) charCount.classList.add('text-red-400');
        else charCount.classList.remove('text-red-400');

        el.style.height = '48px'; 
        const newHeight = Math.min(el.scrollHeight, 120); 
        el.style.height = newHeight + 'px';
    }

    function shareChat(btn) {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i> <span class="hidden sm:inline">Copied!</span>';
            btn.classList.add('border-emerald-400/50', 'text-white');
            setTimeout(() => { btn.innerHTML = originalHtml; btn.classList.remove('border-emerald-400/50', 'text-white'); }, 2000);
        });
    }

    function appendMessage(role, content) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `flex w-full ${role === 'user' ? 'justify-end' : 'justify-start'}`;
        
        const bubble = document.createElement('div');
        bubble.className = `max-w-[85%] rounded-2xl p-4 text-sm leading-relaxed shadow-sm ${
            role === 'user' 
            ? 'bg-emerald-500 text-zinc-950 rounded-tr-sm' 
            : 'bg-zinc-800 border border-white/5 text-zinc-200 rounded-tl-sm prose prose-invert prose-sm prose-emerald'
        }`;

        let parsedContent = content;
        if (typeof content === 'string') {
            try {
                const tmp = JSON.parse(content);
                if (Array.isArray(tmp)) parsedContent = tmp;
            } catch (e) {}
        }

        let innerHTML = '';
        if (Array.isArray(parsedContent)) {
            parsedContent.forEach(part => {
                if (part.type === 'text') {
                    innerHTML += DOMPurify.sanitize(parseMarkdown(part.text || ''));
                } else if (part.type === 'image_url' && part.image_url && part.image_url.url) {
                    innerHTML += `<div class="mt-3 mb-1"><img src="${part.image_url.url}" class="max-w-full max-h-60 rounded-lg cursor-pointer hover:opacity-80 transition shadow-md border border-white/10" onclick="openImageModal(this.src)" onload="scrollToBottom()" alt="Uploaded Image"></div>`;
                }
            });
        } else {
            if (content === '...') {
                innerHTML = '<div class="flex gap-1 items-center h-4"><span class="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce"></span><span class="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></span><span class="w-1.5 h-1.5 bg-zinc-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></span></div>';
            } else {
                innerHTML = DOMPurify.sanitize(parseMarkdown(content || ''));
            }
        }

        bubble.innerHTML = innerHTML;
        msgDiv.appendChild(bubble);
        chatBox.appendChild(msgDiv);
        
        scrollToBottom();
        return bubble;
    }

    async function loadChatHistory() {
        const loading = document.getElementById('loading-history');
        try {
            const res = await fetch(`/api/chat?soul_id=${soulId}&session_token=${sessionToken}`);
            const data = await res.json();
            
            if (loading) loading.remove();

            if (data.success) {
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        appendMessage(msg.role, msg.content);
                        if (msg.role === 'user') userMessageCount++;
                    });
                    
                    scrollToBottom();
                    setTimeout(scrollToBottom, 50);
                    setTimeout(scrollToBottom, 250);

                    if (userMessageCount >= MAX_TURNS) {
                        showPaywall();
                    }
                } else {
                    appendMessage('assistant', "Hello! I am initialized and ready. What would you like to discuss?");
                }
            } else {
                const errMsg = data.error || 'Access Denied';
                if (errMsg.includes('Access Denied')) {
                    appendMessage('assistant', "⚠️ **Private Session**\nYou do not have permission to view this chat history.");
                    chatInput.disabled = true; sendBtn.disabled = true;
                } else {
                    appendMessage('assistant', `⚠️ Error: ${escapeHTML(errMsg)}`);
                }
            }
        } catch (e) {
            if (loading) {
                loading.innerHTML = '<span class="text-red-400"><i class="fas fa-exclamation-circle"></i> Failed to load conversation history.</span>';
            } else {
                appendMessage('assistant', "⚠️ Browser core exception while compiling logs frame.");
            }
        }
    }

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (userMessageCount >= MAX_TURNS) {
            showPaywall();
            return;
        }

        const messageText = chatInput.value.trim();
        if (!messageText && !currentImageBase64) return;
        
        if (messageText.length > MAX_INPUT_CHARS) {
            alert(`Message exceeds ${MAX_INPUT_CHARS} characters limit.`);
            return;
        }

        chatInput.value = '';
        chatInput.style.height = '48px';
        updateCharCount(chatInput);
        chatInput.disabled = true;
        sendBtn.disabled = true;

        let displayPayload = [];
        if (messageText) displayPayload.push({ type: 'text', text: messageText });
        if (currentImageBase64) displayPayload.push({ type: 'image_url', image_url: { url: currentImageBase64 } });
        
        let contentToAppend = currentImageBase64 ? displayPayload : messageText;
        appendMessage('user', contentToAppend);
        
        userMessageCount++;
        const aiBubble = appendMessage('assistant', '...');
        
        const privacyToggle = document.getElementById('privacy-toggle');
        const payload = {
            soul_id: soulId,
            session_token: sessionToken,
            content: messageText,
            image: currentImageBase64,
            is_private: privacyToggle ? privacyToggle.checked : false
        };

        removeImage();

        try {
            const res = await fetch('/api/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': serverCsrfToken },
                body: JSON.stringify(payload)
            });

            const rawText = await res.text();
            let data;
            
            try {
                data = JSON.parse(rawText);
            } catch (parseErr) {
                console.error("Raw Server Response:", rawText);
                if (rawText.includes('524') || rawText.includes('timeout') || rawText.includes('Cloudflare')) {
                    aiBubble.innerHTML = `<span class="text-amber-400"><i class="fas fa-hourglass-end"></i> Cloudflare Timeout (100s). The AI model took too long to analyze the image. Please try again.</span>`;
                } else {
                    aiBubble.innerHTML = `<span class="text-red-400"><i class="fas fa-bug"></i> Fatal Server Error. Please check browser console (F12) for details.</span>`;
                }
                return;
            }

            if (data.success) {
                aiBubble.innerHTML = DOMPurify.sanitize(parseMarkdown(data.reply || ''));
            } else {
                if (data.needs_upgrade) {
                    aiBubble.innerHTML = `<span class="text-amber-400"><i class="fas fa-lock"></i> ${data.error}</span>`;
                    showPaywall();
                } else {
                    aiBubble.innerHTML = `<span class="text-red-400"><i class="fas fa-exclamation-circle"></i> ${data.error || 'Failed to get response.'}</span>`;
                }
            }
        } catch (err) {
            aiBubble.innerHTML = `<span class="text-red-400"><i class="fas fa-wifi"></i> Network error. Connection failed.</span>`;
        } finally {
            chatInput.disabled = false;
            sendBtn.disabled = false;
            
            chatInput.style.height = '48px'; 
            
            if (userMessageCount >= MAX_TURNS) {
                showPaywall();
            } else {
                chatInput.focus();
            }
            
            setTimeout(scrollToBottom, 50);
        }
    });

    window.onload = loadChatHistory;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>