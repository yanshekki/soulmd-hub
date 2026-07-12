<?php
/**
 * SoulMD Hub - Shared Global Footer Matrix
 * (Dynamic i18n Internationalization & Language-Safe Routing Edition)
 * 🚀 V5 SEO Optimized: Semantic Navigation, Link Titles, and Keyword Injection
 */
loadTranslations('footer');
?>
</main>

<footer class="w-full border-t border-white/10 bg-zinc-950 py-10 mt-auto relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-8">
            
            <a href="<?= url('/') ?>" title="SoulMD Hub - Multi-Modal AI Agent Platform" class="flex items-center gap-2 text-2xl font-bold tracking-tighter text-white select-none">
                SoulMD <span class="text-emerald-400 text-[10px] px-2 py-1 bg-emerald-900/30 rounded-full font-mono">HUB</span>
            </a>
            
            <nav aria-label="Footer Navigation" class="flex flex-wrap justify-center md:justify-end gap-6 text-sm font-medium text-zinc-400">
                <a href="<?= url('/browse') ?>" title="<?= __('Browse Souls') ?>" class="hover:text-emerald-400 transition"><?= __('Browse Souls') ?></a>
                <a href="<?= url('/marketplace') ?>" title="<?= __('Marketplace') ?>" class="hover:text-blue-400 transition flex items-center gap-1"><i class="fas fa-gem" aria-hidden="true"></i> <?= __('Marketplace') ?></a>
                <a href="<?= url('/apps') ?>" title="<?= __('Apps') ?>" class="hover:text-emerald-400 transition flex items-center gap-1"><i class="fas fa-puzzle-piece" aria-hidden="true"></i> <?= __('Apps') ?></a>
                <a href="<?= url('/my-chats') ?>" title="<?= __('My Chats') ?>" class="hover:text-emerald-400 transition"><?= __('My Chats') ?></a>
                <a href="<?= url('/generate') ?>" title="<?= __('AI Generator') ?>" class="hover:text-emerald-400 transition"><?= __('AI Generator') ?></a>
                <a href="<?= url('/billing') ?>" title="<?= __('Billing') ?>" class="hover:text-emerald-400 transition"><?= __('Billing') ?></a>
                <a href="<?= url('/upgrade') ?>" title="<?= __('Premium') ?>" class="text-amber-400 hover:text-amber-300 transition flex items-center gap-1"><i class="fas fa-crown" aria-hidden="true"></i> <?= __('Premium') ?></a>
                <a href="<?= url('/api-docs') ?>" title="<?= __('API Docs') ?>" class="text-emerald-500 hover:text-emerald-400 transition flex items-center gap-1"><i class="fas fa-code" aria-hidden="true"></i> <?= __('API Docs') ?></a>
                <a href="<?= url('/docs/intro') ?>" title="<?= __('Documentation') ?>" class="text-purple-400 hover:text-purple-300 transition flex items-center gap-1"><i class="fas fa-book" aria-hidden="true"></i> <?= __('Documentation') ?></a>
                <a href="https://github.com/yanshekki/soulmd-hub" target="_blank" rel="noopener noreferrer" title="SoulMD Hub GitHub Repository" class="hover:text-white transition flex items-center gap-1"><i class="fab fa-github" aria-hidden="true"></i> GitHub</a>
            </nav>
        </div>
        
        <div class="border-t border-white/5 pt-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-zinc-500">
            <p>&copy; <?= date('Y') ?> SoulMD Hub. <?= __('Open-source platform for AI personas.') ?></p>
            <p>
                <?= __('Powered by') ?> <a href="https://ysk.hk/" target="_blank" rel="noopener noreferrer" title="YSK Limited" class="text-emerald-500 hover:text-emerald-400 hover:underline transition font-semibold">YSK Limited</a>
            </p>
        </div>
    </div>
</footer>
</body>
</html>