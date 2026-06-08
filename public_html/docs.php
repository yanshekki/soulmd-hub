<?php
/**
 * SoulMD Hub - Grand Unified Documentation Controller
 * (i18n Fully Bound, Mobile-Responsive Tabs & Strict LFI Whitelist Engine)
 * 🚀 V5 SEO Optimized: Semantic Article Tag, a11y Navigation, Dynamic Title Routing
 */
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

$allowedTabs = ['intro', 'solutions', 'usecases', 'future'];
$currentTab = $_GET['tab'] ?? 'intro';

if (!in_array($currentTab, $allowedTabs)) {
    $currentTab = 'intro';
}

// 載入全域與當前 Tab 的語言檔
loadTranslations('docs');

$tabLangPath = __DIR__ . '/../private/includes/languages/docs/' . $currentTab . '.php';
if (file_exists($tabLangPath)) {
    loadTranslations('docs/' . $currentTab);
}

// 🚀 SEO Enhancement: 動態根據當前 Tab 變更 Title 與 Description
$pageTitle = __('SEO Title ' . ucfirst($currentTab));
$pageDesc = __('SEO Desc ' . ucfirst($currentTab));

require_once __DIR__ . '/../private/includes/header.php';
?>

<main class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 flex flex-col lg:flex-row gap-8 flex-grow">
    
    <!-- 🚀 SEO: 語意化 Navigation 與 aria-current 標示 -->
    <nav aria-label="Documentation Menu" class="w-full lg:w-64 shrink-0 flex flex-row lg:flex-col gap-2 overflow-x-auto lg:overflow-visible pb-4 lg:pb-0 custom-scrollbar select-none">
        
        <a href="<?= url('/docs/intro') ?>" <?= $currentTab === 'intro' ? 'aria-current="page"' : '' ?>
            class="w-fit lg:w-full shrink-0 text-left px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border whitespace-nowrap flex items-center gap-3 <?= $currentTab === 'intro' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 shadow-lg shadow-emerald-500/5' : 'bg-transparent text-zinc-400 border-transparent hover:bg-white/5 hover:text-zinc-200' ?>">
            <i class="fas fa-book-open" aria-hidden="true"></i> <?= __('Tab Intro') ?>
        </a>
        
        <a href="<?= url('/docs/solutions') ?>" <?= $currentTab === 'solutions' ? 'aria-current="page"' : '' ?>
            class="w-fit lg:w-full shrink-0 text-left px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border whitespace-nowrap flex items-center gap-3 <?= $currentTab === 'solutions' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 shadow-lg shadow-emerald-500/5' : 'bg-transparent text-zinc-400 border-transparent hover:bg-white/5 hover:text-zinc-200' ?>">
            <i class="fas fa-lightbulb" aria-hidden="true"></i> <?= __('Tab Solutions') ?>
        </a>
        
        <a href="<?= url('/docs/usecases') ?>" <?= $currentTab === 'usecases' ? 'aria-current="page"' : '' ?>
            class="w-fit lg:w-full shrink-0 text-left px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border whitespace-nowrap flex items-center gap-3 <?= $currentTab === 'usecases' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 shadow-lg shadow-emerald-500/5' : 'bg-transparent text-zinc-400 border-transparent hover:bg-white/5 hover:text-zinc-200' ?>">
            <i class="fas fa-briefcase" aria-hidden="true"></i> <?= __('Tab UseCases') ?>
        </a>
        
        <a href="<?= url('/docs/future') ?>" <?= $currentTab === 'future' ? 'aria-current="page"' : '' ?>
            class="w-fit lg:w-full shrink-0 text-left px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border whitespace-nowrap flex items-center gap-3 <?= $currentTab === 'future' ? 'bg-purple-500/10 text-purple-400 border-purple-500/30 shadow-lg shadow-purple-500/5' : 'bg-transparent text-zinc-400 border-transparent hover:bg-white/5 hover:text-zinc-200' ?>">
            <i class="fas fa-rocket" aria-hidden="true"></i> <?= __('Tab Future') ?>
        </a>
        
    </nav>

    <!-- 🚀 SEO: 語意化 Article 標籤 -->
    <article class="flex-1 bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 md:p-10 backdrop-blur-sm shadow-2xl relative overflow-hidden min-h-[500px]">
        
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r <?= $currentTab === 'future' ? 'from-purple-500 to-indigo-500' : 'from-emerald-400 to-cyan-400' ?>"></div>
        
        <!-- Content Injection -->
        <div class="animate-fade-in prose prose-invert prose-emerald max-w-none">
            <?php
            $tabFilePath = __DIR__ . "/../private/includes/docs/tab-{$currentTab}.php";
            if (file_exists($tabFilePath)) {
                require_once $tabFilePath;
            } else {
                echo '<div class="text-zinc-500 font-mono flex items-center justify-center h-48 gap-2"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ' . __('Loading Content...') . '</div>';
            }
            ?>
        </div>
    </article>
</main>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>