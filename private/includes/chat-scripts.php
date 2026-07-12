<?php
/**
 * SoulMD Hub - Chat Core JavaScript Engine
 * Included dynamically in chat.php
 * (Web2.5 BYOK Dual-Track Router Edition)
 * 🚀 Patched: Full Multiplayer Delta Sync, Sender Identity UI & Message Deduplication.
 */
?>
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
    let lastMessageId = 0; // 🚀 追蹤目前畫面上最新嘅訊息 ID
    window.renderedContents = new Set(); // 🚀 用於防止本地訊息與 API 同步訊息重複顯示

    const MAX_TURNS = <?= $maxTurns ?>;
    const MAX_INPUT_CHARS = <?= $maxInputChars ?>;
    const ALLOW_IMAGE = <?= $allowImage ?>;
    const IMG_MAX_DIM = <?= defined('IMAGE_MAX_DIMENSION') ? IMAGE_MAX_DIMENSION : 800 ?>; 
    const IMG_QUALITY = <?= defined('IMAGE_QUALITY') ? IMAGE_QUALITY : 0.6 ?>;

    let currentImageBase64 = null;
    let isByokMode = false;

    // --- 免責聲明 Modal ---
    const agreementKey = `soulmd_agreement_${soulId}_${sessionToken}`;
    if (!localStorage.getItem(agreementKey)) {
        document.getElementById('disclaimer-modal').classList.remove('hidden');
    }
    function acceptDisclaimer() {
        localStorage.setItem(agreementKey, 'true');
        document.getElementById('disclaimer-modal').classList.add('hidden');
    }
    function declineDisclaimer() {
        window.location.href = '<?= url("/browse") ?>';
    }

    // --- 捲動到底部 ---
    function scrollToBottom() {
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    }

    // --- Markdown 解析 ---
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

    // --- UI Modals ---
    function openSoulModal() {
        document.body.style.overflow = 'hidden';
        const modal = document.getElementById('soul-info-modal');
        const contentDiv = modal.querySelector('div');
        
        const rawContent = document.getElementById('raw-soul-content').value;
        const parsedHTML = marked.parse(rawContent);
        document.getElementById('soul-info-content').innerHTML = DOMPurify.sanitize(parsedHTML);
        
        modal.classList.remove('hidden');
        setTimeout(() => { 
            modal.classList.remove('opacity-0'); 
            contentDiv.classList.remove('scale-95'); 
            contentDiv.classList.add('scale-100'); 
        }, 10);
    }

    function closeSoulModal() {
        document.body.style.overflow = '';
        const modal = document.getElementById('soul-info-modal');
        const contentDiv = modal.querySelector('div');
        
        modal.classList.add('opacity-0'); 
        contentDiv.classList.remove('scale-100'); 
        contentDiv.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
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

    function showPaywall() {
        const modal = document.getElementById('paywall-modal');
        modal.classList.remove('hidden');
        setTimeout(() => { 
            modal.classList.remove('opacity-0'); 
            modal.firstElementChild.classList.remove('scale-95'); 
            modal.firstElementChild.classList.add('scale-100'); 
        }, 10);
    }

    function closePaywall() {
        const modal = document.getElementById('paywall-modal');
        modal.classList.add('opacity-0'); 
        modal.firstElementChild.classList.remove('scale-100'); 
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    // --- Privacy Toggle ---
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
            label.innerHTML = '<i class="fas fa-lock"></i> <span class="hidden sm:inline"><?= addslashes(__('Private')) ?></span>';
            label.classList.replace('text-zinc-500', 'text-emerald-400');
            if(shareBtn) shareBtn.classList.add('hidden'); 
        } else {
            bg.classList.replace('bg-emerald-500', 'bg-zinc-800');
            bg.classList.replace('border-emerald-500', 'border-white/10');
            dot.classList.remove('translate-x-4');
            label.innerHTML = '<i class="fas fa-globe"></i> <span class="hidden sm:inline"><?= addslashes(__('Public')) ?></span>';
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

    // --- Image Processing ---
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
            alert(<?= json_encode(__('Only JPG, PNG and WEBP images are supported.'), JSON_UNESCAPED_UNICODE) ?>);
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

    // --- Event Listeners ---
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
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i> <span class="hidden sm:inline">' + <?= json_encode(__('Copied!'), JSON_UNESCAPED_UNICODE) ?> + '</span>';
            btn.classList.add('border-emerald-400/50', 'text-white');
            setTimeout(() => { btn.innerHTML = originalHtml; btn.classList.remove('border-emerald-400/50', 'text-white'); }, 2000);
        });
    }

    // 🚀 訊息渲染 (支援 Sender Identity UI 及 去重緩存)
    function appendMessage(role, content, senderName = null, msgId = null) {
        if (msgId && msgId > lastMessageId) {
            lastMessageId = msgId;
        }

        // 建立特徵指紋，防重複渲染
        let strContent = typeof content === 'string' ? content : JSON.stringify(content);
        let dedupKey = role + '_' + strContent;
        if (window.renderedContents.has(dedupKey) && content !== '...') {
            return null; // 如果已經顯示過（例如本地剛發送過），忽略！
        }
        if (content !== '...') window.renderedContents.add(dedupKey);

        const msgDiv = document.createElement('div');
        msgDiv.className = `flex flex-col w-full mb-4 ${role === 'user' ? 'items-end' : 'items-start'}`;

        // 🚀 加入發送者名字
        if (senderName) {
            const nameDiv = document.createElement('div');
            nameDiv.className = `text-[10px] text-zinc-500 mb-1 px-2 ${role === 'user' ? 'text-right' : 'text-left'}`;
            nameDiv.innerText = senderName;
            msgDiv.appendChild(nameDiv);
        }
        
        const bubbleWrapper = document.createElement('div');
        bubbleWrapper.className = `flex w-full ${role === 'user' ? 'justify-end' : 'justify-start'}`;

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
        bubbleWrapper.appendChild(bubble);
        msgDiv.appendChild(bubbleWrapper);
        chatBox.appendChild(msgDiv);
        
        scrollToBottom();
        return bubble;
    }

    // 🌟 初始化
    async function initChatEnvironment() {
        try {
            const res = await fetch('/api/settings');
            const data = await res.json();
            if (data.success && data.data.use_byok == 1) {
                isByokMode = true;
                const header = document.querySelector('header');
                const badge = document.createElement('div');
                badge.className = 'w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-xs font-bold text-center py-1.5 tracking-widest shadow-md flex items-center justify-center gap-2';
                badge.innerHTML = '<i class="fas fa-bolt text-yellow-300"></i> <?= addslashes(__('BYOK Active Notice')) ?>';
                header.parentNode.insertBefore(badge, header.nextSibling);
            }
        } catch(e) {}
        
        loadChatHistory();
    }

    // 🌟 載入歷史紀錄
    async function loadChatHistory() {
        const loading = document.getElementById('loading-history');
        try {
            const res = await fetch(`/api/chat?soul_id=${soulId}&session_token=${sessionToken}`);
            const data = await res.json();
            
            if (loading) loading.remove();

            if (data.success) {
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        appendMessage(msg.role, msg.content, msg.sender_name, msg.id);
                        if (msg.role === 'user') userMessageCount++;
                    });
                    
                    scrollToBottom();
                    setTimeout(scrollToBottom, 50);
                    setTimeout(scrollToBottom, 250);

                    if (!isByokMode && userMessageCount >= MAX_TURNS) {
                        showPaywall();
                    }
                    // Drop stale mini-app prefill if this session already has history
                    try { sessionStorage.removeItem('soulmd_app_prefill'); } catch (e) {}
                } else {
                    appendMessage('assistant', <?= json_encode(__('Init message'), JSON_UNESCAPED_UNICODE) ?>, '<?= addslashes(__('AI Assistant')) ?>');
                    // Mini Apps: auto-send form payload into this new chat session once
                    setTimeout(tryMiniAppPrefillSend, 80);
                }
            } else {
                const errMsg = data.error || 'Access Denied';
                if (errMsg.includes('Access Denied') || errMsg.includes('拒絕存取')) {
                    appendMessage('assistant', <?= json_encode(__('Private Session warning'), JSON_UNESCAPED_UNICODE) ?>, '<?= addslashes(__('AI Assistant')) ?>');
                    chatInput.disabled = true; sendBtn.disabled = true;
                } else {
                    appendMessage('assistant', `⚠️ Error: ${escapeHTML(errMsg)}`, '<?= addslashes(__('AI Assistant')) ?>');
                }
            }
        } catch (e) {
            if (loading) {
                loading.innerHTML = '<span class="text-red-400"><i class="fas fa-exclamation-circle"></i> ' + <?= json_encode(__('Failed to load conversation history.'), JSON_UNESCAPED_UNICODE) ?> + '</span>';
            } else {
                appendMessage('assistant', "⚠️ " + <?= json_encode(__('Browser core exception while compiling logs frame.'), JSON_UNESCAPED_UNICODE) ?>, '<?= addslashes(__('AI Assistant')) ?>');
            }
        }
    }

    /**
     * Mini Apps hub: after redirect to /chat/{soul}/{session}, send the form text once.
     * Prefill stored in sessionStorage by /apps (key soulmd_app_prefill).
     */
    function tryMiniAppPrefillSend() {
        let raw = null;
        try { raw = sessionStorage.getItem('soulmd_app_prefill'); } catch (e) { return; }
        if (!raw) return;

        let payload = null;
        try { payload = JSON.parse(raw); } catch (e) {
            try { sessionStorage.removeItem('soulmd_app_prefill'); } catch (err) {}
            return;
        }

        const prefillSoul = parseInt(payload.soulId, 10) || 0;
        const content = (payload.content && String(payload.content).trim()) || '';
        const age = Date.now() - (parseInt(payload.ts, 10) || 0);
        // Must match current soul; expire after 5 minutes
        if (!content || prefillSoul !== soulId || age > 5 * 60 * 1000) {
            try { sessionStorage.removeItem('soulmd_app_prefill'); } catch (e) {}
            return;
        }

        try { sessionStorage.removeItem('soulmd_app_prefill'); } catch (e) {}

        if (!isByokMode && userMessageCount >= MAX_TURNS) {
            showPaywall();
            return;
        }
        if (content.length > MAX_INPUT_CHARS) {
            alert(<?= json_encode(__('Message exceeds chars limit.'), JSON_UNESCAPED_UNICODE) ?>.replace(':chars', MAX_INPUT_CHARS));
            chatInput.value = content.slice(0, MAX_INPUT_CHARS);
            updateCharCount(chatInput);
            return;
        }

        chatInput.value = content;
        updateCharCount(chatInput);
        // Trigger the normal submit pipeline (tier limits, truncation upgrade, etc.)
        if (typeof chatForm.requestSubmit === 'function') {
            chatForm.requestSubmit();
        } else {
            sendBtn.click();
        }
    }

    // 🌟 發送訊息
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!isByokMode && userMessageCount >= MAX_TURNS) {
            showPaywall();
            return;
        }

        const messageText = chatInput.value.trim();
        if (!messageText && !currentImageBase64) return;
        
        if (messageText.length > MAX_INPUT_CHARS) {
            alert(<?= json_encode(__('Message exceeds chars limit.'), JSON_UNESCAPED_UNICODE) ?>.replace(':chars', MAX_INPUT_CHARS));
            return;
        }

        const sendIcon = document.getElementById('send-icon');
        const sendSpinner = document.getElementById('send-spinner');
        if(sendIcon) sendIcon.classList.add('hidden');
        if(sendSpinner) sendSpinner.classList.remove('hidden');

        chatInput.value = '';
        chatInput.style.height = '48px';
        updateCharCount(chatInput);
        chatInput.disabled = true;
        sendBtn.disabled = true;
        sendBtn.classList.add('opacity-80', 'cursor-not-allowed');

        let displayPayload = [];
        if (messageText) displayPayload.push({ type: 'text', text: messageText });
        if (currentImageBase64) displayPayload.push({ type: 'image_url', image_url: { url: currentImageBase64 } });
        
        let contentToAppend = currentImageBase64 ? displayPayload : messageText;
        
        // 🚀 本地立即顯示，並帶上 "You" 身份
        appendMessage('user', contentToAppend, '<?= addslashes(__('You')) ?>');
        userMessageCount++;
        const aiBubble = appendMessage('assistant', '...', '<?= addslashes(__('AI Assistant')) ?>');
        
        const privacyToggle = document.getElementById('privacy-toggle');
        const payload = {
            soul_id: soulId,
            session_token: sessionToken,
            content: messageText,
            image: currentImageBase64,
            is_private: privacyToggle ? privacyToggle.checked : false
        };

        removeImage();

        const targetApiEndpoint = isByokMode ? '/api/self-chat' : '/api/chat';

        try {
            const res = await fetch(targetApiEndpoint, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-Token': serverCsrfToken 
                },
                body: JSON.stringify(payload)
            });

            const rawText = await res.text();
            let data;
            
            try {
                data = JSON.parse(rawText);
            } catch (parseErr) {
                if (rawText.includes('524') || rawText.includes('timeout') || rawText.includes('Cloudflare')) {
                    aiBubble.innerHTML = `<span class="text-amber-400"><i class="fas fa-hourglass-end"></i> ` + <?= json_encode(__('Cloudflare Timeout'), JSON_UNESCAPED_UNICODE) ?> + `</span>`;
                } else {
                    aiBubble.innerHTML = `<span class="text-red-400"><i class="fas fa-bug"></i> ` + <?= json_encode(__('Fatal Server Error'), JSON_UNESCAPED_UNICODE) ?> + `</span>`;
                }
                return;
            }

            if (data.success) {
                const replyText = (data.reply && String(data.reply).trim()) ? String(data.reply) : '';
                // 🚀 將 API 回傳的最終對話內容加入特徵去重 Set，防止稍後同步時被二次渲染
                window.renderedContents.add('assistant_' + replyText);
                aiBubble.innerHTML = replyText
                    ? DOMPurify.sanitize(parseMarkdown(replyText))
                    : `<span class="text-zinc-400 text-sm">${<?= json_encode(__('Failed to get response.'), JSON_UNESCAPED_UNICODE) ?>}</span>`;

                // 只有「有正文 + 真係被 max_tokens cut」先顯示截斷／升級提示（空 reply 唔當截斷）
                if (replyText && data.truncated) {
                    const notice = document.createElement('div');
                    notice.className = 'mt-3 pt-3 border-t border-amber-500/25 text-amber-300/95 text-xs leading-relaxed not-prose';
                    const noticeText = document.createElement('div');
                    noticeText.className = 'flex items-start gap-2';
                    if (data.needs_upgrade) {
                        noticeText.innerHTML = '<i class="fas fa-cut mt-0.5 shrink-0 opacity-90" aria-hidden="true"></i><span>' +
                            <?= json_encode(__('Reply truncated notice'), JSON_UNESCAPED_UNICODE) ?> + '</span>';
                        const cta = document.createElement('button');
                        cta.type = 'button';
                        cta.className = 'mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 border border-amber-400/40 text-amber-200 font-semibold transition';
                        cta.innerHTML = '<i class="fas fa-crown" aria-hidden="true"></i> ' + <?= json_encode(__('Reply truncated upgrade CTA'), JSON_UNESCAPED_UNICODE) ?>;
                        cta.addEventListener('click', () => showPaywall());
                        notice.appendChild(noticeText);
                        notice.appendChild(cta);
                    } else {
                        noticeText.innerHTML = '<i class="fas fa-cut mt-0.5 shrink-0 opacity-90" aria-hidden="true"></i><span>' +
                            <?= json_encode(__('Reply truncated byok notice'), JSON_UNESCAPED_UNICODE) ?> + '</span>';
                        notice.appendChild(noticeText);
                    }
                    aiBubble.appendChild(notice);
                }
            } else {
                if (data.needs_upgrade) {
                    aiBubble.innerHTML = `<span class="text-amber-400"><i class="fas fa-lock"></i> ${data.error}</span>`;
                    showPaywall();
                } else {
                    aiBubble.innerHTML = `<span class="text-red-400"><i class="fas fa-exclamation-circle"></i> ${data.error || <?= json_encode(__('Failed to get response.'), JSON_UNESCAPED_UNICODE) ?>}`;
                }
            }
        } catch (err) {
            aiBubble.innerHTML = `<span class="text-red-400"><i class="fas fa-wifi"></i> ` + <?= json_encode(__('Network error. Connection failed.'), JSON_UNESCAPED_UNICODE) ?> + `</span>`;
        } finally {
            chatInput.disabled = false;
            sendBtn.disabled = false;
            sendBtn.classList.remove('opacity-80', 'cursor-not-allowed');
            if(sendIcon) sendIcon.classList.remove('hidden');
            if(sendSpinner) sendSpinner.classList.add('hidden');
            
            chatInput.style.height = '48px'; 
            
            if (!isByokMode && userMessageCount >= MAX_TURNS) {
                showPaywall();
            } else {
                chatInput.focus();
            }
            
            setTimeout(scrollToBottom, 50);
        }
    });

    // 🌟 4. 多人在線心跳與增量同步 (Delta Sync)
    async function syncHeartbeat() {
        try {
            const res = await fetch(`/api/chat-sync?soul_id=${soulId}&session_token=${sessionToken}&last_id=${lastMessageId}`);
            const data = await res.json();
            
            if (data.success) {
                const badge = document.getElementById('online-badge');
                const countSpan = document.getElementById('online-count');
                
                if (badge && countSpan) {
                    badge.classList.remove('hidden');
                    countSpan.innerText = data.online_count;
                    
                    if (data.online_count > 1) {
                        badge.classList.add('text-purple-400', 'flex');
                        badge.classList.remove('text-zinc-500');
                    } else {
                        badge.classList.add('text-zinc-500', 'flex');
                        badge.classList.remove('text-purple-400');
                    }
                }

                // 🚀 將新拉取到的訊息增量附加到畫面上
                if (data.new_messages && data.new_messages.length > 0) {
                    data.new_messages.forEach(msg => {
                        appendMessage(msg.role, msg.content, msg.sender_name, msg.id);
                    });
                }
            }
        } catch (e) {
            console.warn('Heartbeat sync skipped.');
        }
    }

    // 每 3.5 秒發送一次心跳及拉取最新對話
    setInterval(syncHeartbeat, 3500);

    window.onload = initChatEnvironment;
</script>