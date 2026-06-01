<?php
/**
 * SoulMD Hub - Grand Unified Documentation Controller
 * (i18n Fully Bound, Mobile-Responsive Tabs & Strict LFI Whitelist Engine)
 * 🚀 Patched: Changed sidebar menu links to use clean SEO URLs (/docs/xxxx)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 🌍 載入 Step 1 建立好的 docs 語言包
loadTranslations('docs');

// 🛡️ 安全校驗：設定防禦路徑穿越 (Path Traversal) 與區域檔案引入 (LFI) 的硬核白名單
$allowedTabs = ['intro', 'solutions', 'usecases', 'future'];
$currentTab = $_GET['tab'] ?? 'intro';

if (!in_array($currentTab, $allowedTabs)) {
    $currentTab = 'intro'; // 熔斷機制：一旦不匹配直接降級回首頁
}

// 🌍 編譯全域多語言 Meta 標題與敘述
$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');

require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 flex flex-col lg:flex-row gap-8 flex-grow">
    
    <div class="w-full lg:w-64 shrink-0 flex flex-row lg:flex-col gap-2 overflow-x-auto lg:overflow-visible pb-4 lg:pb-0 custom-scrollbar select-none">
        
        <a href="<?= url('/docs/intro') ?>" 
           class="w-fit lg:w-full shrink-0 text-left px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border whitespace-nowrap flex items-center gap-3 <?= $currentTab === 'intro' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 shadow-lg shadow-emerald-500/5' : 'bg-transparent text-zinc-400 border-transparent hover:bg-white/5 hover:text-zinc-200' ?>">
            <?= __('Tab Intro') ?>
        </a>
        
        <a href="<?= url('/docs/solutions') ?>" 
           class="w-fit lg:w-full shrink-0 text-left px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border whitespace-nowrap flex items-center gap-3 <?= $currentTab === 'solutions' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 shadow-lg shadow-emerald-500/5' : 'bg-transparent text-zinc-400 border-transparent hover:bg-white/5 hover:text-zinc-200' ?>">
            <?= __('Tab Solutions') ?>
        </a>
        
        <a href="<?= url('/docs/usecases') ?>" 
           class="w-fit lg:w-full shrink-0 text-left px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border whitespace-nowrap flex items-center gap-3 <?= $currentTab === 'usecases' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30 shadow-lg shadow-emerald-500/5' : 'bg-transparent text-zinc-400 border-transparent hover:bg-white/5 hover:text-zinc-200' ?>">
            <?= __('Tab UseCases') ?>
        </a>
        
        <a href="<?= url('/docs/future') ?>" 
           class="w-fit lg:w-full shrink-0 text-left px-5 py-3.5 rounded-2xl font-bold text-sm transition-all duration-200 border whitespace-nowrap flex items-center gap-3 <?= $currentTab === 'future' ? 'bg-purple-500/10 text-purple-400 border-purple-500/30 shadow-lg shadow-purple-500/5' : 'bg-transparent text-zinc-400 border-transparent hover:bg-white/5 hover:text-zinc-200' ?>">
            <?= __('Tab Future') ?>
        </a>
        
    </div>

    <div class="flex-1 bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 md:p-10 backdrop-blur-sm shadow-2xl relative overflow-hidden min-h-[500px]">
        
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r <?= $currentTab === 'future' ? 'from-purple-500 to-indigo-500' : 'from-emerald-400 to-cyan-400' ?>"></div>
        
        <div class="animate-fade-in">
            <?php
            // 🎯 安全讀取：建立內部受保護的子模組目錄
            $tabFilePath = __DIR__ . "/../private/includes/docs/tab-{$currentTab}.php";
            
            if (file_exists($tabFilePath)) {
                require_once $tabFilePath;
            } else {
                echo '<div class="text-zinc-500 font-mono flex items-center justify-center h-48 gap-2"><i class="fas fa-spinner fa-spin"></i> ' . __('Loading Content...') . '</div>';
            }
            ?>
        </div>
        
    </div>

</div>

<?php 
require_once __DIR__ . '/../private/includes/footer.php'; 
?>