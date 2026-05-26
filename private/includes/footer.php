<?php
/**
 * SoulMD Hub - Shared Global Footer Matrix
 * (Dynamic i18n Internationalization & Language-Safe Routing Edition)
 */

// 🌍 增量載入頁腳公共組件的專屬獨立語言包
loadTranslations('footer');
?>
</main> 

<footer class="w-full border-t border-white/10 bg-zinc-950 py-10 mt-auto relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-8">
            <a href="<?= url('/') ?>" class="flex items-center gap-2 text-2xl font-bold tracking-tighter text-white select-none">
                SoulMD <span class="text-emerald-400 text-[10px] px-2 py-1 bg-emerald-900/30 rounded-full font-mono">HUB</span>
            </a>
            
            <div class="flex flex-wrap justify-center md:justify-end gap-6 text-sm font-medium text-zinc-400">
                <a href="<?= url('/browse') ?>" class="hover:text-emerald-400 transition"><?= __('Browse Souls') ?></a>
                <a href="<?= url('/my-chats') ?>" class="hover:text-emerald-400 transition"><?= __('My Chats') ?></a>
                <a href="<?= url('/generate') ?>" class="hover:text-emerald-400 transition"><?= __('AI Generator') ?></a>
                <a href="<?= url('/billing') ?>" class="hover:text-emerald-400 transition"><?= __('Billing') ?></a>
                <a href="<?= url('/upgrade') ?>" class="text-amber-400 hover:text-amber-300 transition flex items-center gap-1"><i class="fas fa-crown"></i> <?= __('Premium') ?></a>
                <a href="<?= url('/api-docs') ?>" class="text-emerald-500 hover:text-emerald-400 transition flex items-center gap-1"><i class="fas fa-code"></i> <?= __('API Docs') ?></a>
                <a href="https://github.com/yanshekki/soulmd-hub" target="_blank" rel="noopener noreferrer" class="hover:text-white transition flex items-center gap-1"><i class="fab fa-github"></i> GitHub</a>
            </div>
        </div>
        
        <div class="border-t border-white/5 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-zinc-500">
            <p>&copy; <?= date('Y') ?> SoulMD Hub. <?= __('Open-source platform for AI personas.') ?></p>
            <p>
                <?= __('Powered by') ?> <a href="https://ysk.hk/" target="_blank" rel="noopener noreferrer" class="text-emerald-500 hover:text-emerald-400 hover:underline transition font-semibold">YSK Limited</a>
            </p>
        </div>
    </div>
</footer>

</body>
</html>