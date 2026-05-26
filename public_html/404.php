<?php
/**
 * SoulMD Hub - 404 Error Page
 * (Dynamic i18n Internationalization Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🌍 載入專屬語言包
loadTranslations('404');

http_response_code(404);

// 🌍 SEO Meta 多語言化
$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="flex-grow flex items-center justify-center p-6 min-h-[70vh]">
    <div class="w-full max-w-xl text-center">
        <div class="relative inline-block mb-8">
            <div class="text-9xl font-extrabold tracking-tighter opacity-10 font-mono select-none">404</div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="w-20 h-20 bg-emerald-500/10 border border-emerald-500/30 rounded-3xl flex items-center justify-center text-emerald-400 text-3xl animate-pulse shadow-lg shadow-emerald-500/10">
                    <i class="fas fa-ghost"></i>
                </div>
            </div>
        </div>

        <h1 class="text-4xl font-bold tracking-tight mb-3"><?= __('Soul Lost in Space') ?></h1>
        <p class="text-zinc-400 max-w-md mx-auto mb-10 leading-relaxed">
            <?= __('404 Desc') ?>
        </p>

        <div class="bg-zinc-900/50 border border-white/10 p-4 rounded-3xl mb-8 backdrop-blur-sm shadow-inner max-w-md mx-auto">
            <form onsubmit="window.location.href='<?= url('/browse') ?>?q='+encodeURIComponent(this.q.value); return false;" class="relative">
                <input type="text" name="q" placeholder="<?= __('Search Placeholder') ?>" 
                       class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 text-sm focus:outline-none focus:border-emerald-400 pl-12 transition text-white shadow-inner">
                <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-zinc-500"></i>
            </form>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="<?= url('/browse') ?>" class="w-full sm:w-auto px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center justify-center gap-2 shadow-lg">
                <i class="fas fa-compass"></i> <?= __('Explore Hub') ?>
            </a>
            <a href="<?= url('/') ?>" class="w-full sm:w-auto px-6 py-3 border border-white/20 rounded-2xl font-medium hover:bg-white/5 transition flex items-center justify-center gap-2">
                <i class="fas fa-home"></i> <?= __('Back Home') ?>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>