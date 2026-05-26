<?php
/**
 * SoulMD Hub - Chat Core JavaScript Engine
 * Included dynamically in chat.php
 * (100% i18n Internationalized Edition - Syntax Error & Auth Handshake Fixed)
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
        window.location.href = '<?= url("/browse") ?>';
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
            // 💡 使用 json_encode 防止任何引號及語言斷行衝突
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
                    // 💡 用 json_encode 安全載入初始歡迎詞
                    appendMessage('assistant', <?= json_encode(__('Init message'), JSON_UNESCAPED_UNICODE) ?>);
                }
            } else {
                const errMsg = data.error || 'Access Denied';
                // 💡 關鍵修復：同時兼容英文 "Access Denied" 與中文 "拒絕存取" 攔截觸發
                if (errMsg.includes('Access Denied') || errMsg.includes('拒絕存取')) {
                    appendMessage('assistant', <?= json_encode(__('Private Session warning'), JSON_UNESCAPED_UNICODE) ?>);
                    chatInput.disabled = true; sendBtn.disabled = true;
                } else {
                    appendMessage('assistant', `⚠️ Error: ${escapeHTML(errMsg)}`);
                }
            }
        } catch (e) {
            if (loading) {
                loading.innerHTML = '<span class="text-red-400"><i class="fas fa-exclamation-circle"></i> ' + <?= json_encode(__('Failed to load conversation history.'), JSON_UNESCAPED_UNICODE) ?> + '</span>';
            } else {
                appendMessage('assistant', "⚠️ " + <?= json_encode(__('Browser core exception while compiling logs frame.'), JSON_UNESCAPED_UNICODE) ?>);
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
            alert(<?= json_encode(__('Message exceeds chars limit.'), JSON_UNESCAPED_UNICODE) ?>.replace(':chars', MAX_INPUT_CHARS));
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
                    aiBubble.innerHTML = `<span class="text-amber-400"><i class="fas fa-hourglass-end"></i> ` + <?= json_encode(__('Cloudflare Timeout'), JSON_UNESCAPED_UNICODE) ?> + `</span>`;
                } else {
                    aiBubble.innerHTML = `<span class="text-red-400"><i class="fas fa-bug"></i> ` + <?= json_encode(__('Fatal Server Error'), JSON_UNESCAPED_UNICODE) ?> + `</span>`;
                }
                return;
            }

            if (data.success) {
                aiBubble.innerHTML = DOMPurify.sanitize(parseMarkdown(data.reply || ''));
            } else {
                if (data.needs_upgrade) {
                    // 💡 API 後端會經由 i18n 吐出翻譯後的 data.error
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