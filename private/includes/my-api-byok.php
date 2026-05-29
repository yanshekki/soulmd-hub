<?php
/**
 * SoulMD Hub - BYOK (Bring Your Own Key) LocalStorage Component
 * Included dynamically in my-api.php
 */
?>
<div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 backdrop-blur-sm shadow-xl relative overflow-hidden">
    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
    <h3 class="text-lg font-bold mb-1 text-white"><?= __('Bring Your Own Key (BYOK)') ?></h3>
    <p class="text-xs text-zinc-400 mb-6 leading-relaxed"><?= __('BYOK Subtitle') ?></p>
    
    <div class="space-y-4">
        <div>
            <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5"><?= __('DeepSeek API Key') ?></label>
            <input type="password" id="byok-deepseek" placeholder="sk-..." class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-xs font-mono focus:outline-none focus:border-purple-400 text-purple-300 placeholder-zinc-700 shadow-inner">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-zinc-400 uppercase tracking-wider mb-1.5"><?= __('Together AI (Vision) Key') ?></label>
            <input type="password" id="byok-vision" placeholder="sk-..." class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 text-xs font-mono focus:outline-none focus:border-purple-400 text-purple-300 placeholder-zinc-700 shadow-inner">
        </div>
        
        <div class="flex gap-2 pt-2">
            <button type="button" onclick="saveLocalKeys()" class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded-xl transition shadow-lg shadow-purple-500/10 flex items-center justify-center gap-1.5">
                <i class="fas fa-save"></i> <?= __('Save Keys Locally') ?>
            </button>
            <button type="button" onclick="clearLocalKeys()" class="py-3 px-4 bg-zinc-800 hover:bg-red-500/20 text-zinc-400 hover:text-red-400 border border-white/5 hover:border-red-500/20 text-xs font-bold rounded-xl transition">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div>
</div>

<script>
    // 🚀 BYOK 本地存儲引擎
    window.addEventListener('DOMContentLoaded', () => {
        const dsInput = document.getElementById('byok-deepseek');
        const vsInput = document.getElementById('byok-vision');
        if (dsInput && vsInput) {
            dsInput.value = localStorage.getItem('soulmd_byok_deepseek') || '';
            vsInput.value = localStorage.getItem('soulmd_byok_vision') || '';
        }
    });

    function saveLocalKeys() {
        const dsValue = document.getElementById('byok-deepseek').value.trim();
        const vsValue = document.getElementById('byok-vision').value.trim();
        
        localStorage.setItem('soulmd_byok_deepseek', dsValue);
        localStorage.setItem('soulmd_byok_vision', vsValue);

        showFeedbackNotification(true, "<?= addslashes(__('Keys saved successfully!')) ?>");
    }

    function clearLocalKeys() {
        localStorage.removeItem('soulmd_byok_deepseek');
        localStorage.removeItem('soulmd_byok_vision');
        
        document.getElementById('byok-deepseek').value = '';
        document.getElementById('byok-vision').value = '';

        showFeedbackNotification(true, "<?= addslashes(__('Keys cleared successfully!')) ?>");
    }

    function showFeedbackNotification(isSuccess, message) {
        const successBox = document.getElementById('success-box');
        const successMsg = document.getElementById('success-msg');
        if (!successBox) return;

        successMsg.innerText = message;
        successBox.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => successBox.classList.add('hidden'), 4000);
    }
</script>