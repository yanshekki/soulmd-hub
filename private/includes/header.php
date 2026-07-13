<?php
/**
 * SoulMD Hub - Core Responsive Header Matrix
 * (Dynamic Enterprise i18n Engine, Automated SEO Hreflang Compiler & Mobile Menu Edition)
 * 🚀 V5 SEO Optimized: Accessible Viewport, Preconnects, Semantic HTML & Safe Analytics
 */

// Safe session via unified bootstrap (no-op if already active / headers sent)
if (!class_exists('AppBootstrap', false)) {
    require_once __DIR__ . '/../src/AppBootstrap.php';
}
AppBootstrap::sessionStart();
if (function_exists('loadTranslations')) {
    loadTranslations('header');
}

// Remember Me
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/../src/Database.php';
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $tokenParts = explode(':', $_COOKIE['remember_token']);
        if (count($tokenParts) === 2) {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? AND remember_token = ?");
            $stmt->execute([$tokenParts[0], $tokenParts[1]]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
            }
        }
    } catch(Exception $e) {}
}
$isLoggedIn = isset($_SESSION['user_id']);

// =========================================================
// =========================================================
$showExpiredBanner = false;
if ($isLoggedIn) {
    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/../src/Database.php';
            $db = Database::getInstance();
            $pdo = $db->getConnection();
        }
        $uStmt = $pdo->prepare("SELECT tier, vip_expires_at FROM users WHERE id = ?");
        $uStmt->execute([$_SESSION['user_id']]);
        $uData = $uStmt->fetch();
        
        if ($uData) {
            $expiry = $uData['vip_expires_at'] ? strtotime($uData['vip_expires_at']) : 0;
            if ($expiry > 0 && $expiry < time()) {
                $showExpiredBanner = true;
                if ($uData['tier'] !== 'free') {
                    $pdo->prepare("UPDATE users SET tier = 'free' WHERE id = ?")->execute([$_SESSION['user_id']]);
                }
            }
        }
    } catch(Exception $e) {}
}

// =========================================================
// URI ( hreflang Compiler )
// =========================================================
global $SUPPORTED_LANGS;
$current_uri = $_SERVER['REQUEST_URI'];
$base_path = $current_uri;
foreach (array_keys($SUPPORTED_LANGS) as $lang_code) {
    if (preg_match('/^\/' . preg_quote($lang_code, '/') . '(\/|$)/', $base_path, $matches)) {
        $base_path = '/' . substr($base_path, strlen($matches[0]));
        break;
    }
}
$base_path = '/' . ltrim($base_path, '/');
$clean_base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://soulmd-hub.ysk.hk';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($SUPPORTED_LANGS[CURRENT_LANG]['hreflang'] ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <!-- 🚀 SEO Update: Removed user-scalable=no to fix Google Lighthouse Accessibility Penalty -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 🚀 Preconnect to external resources for faster loading (LCP Optimization) -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://www.googletagmanager.com">

    <?php 
    if (isset($pageTitle)) {
        setSEO($pageTitle, $pageDesc ?? '');
    } else {
        setSEO('SoulMD Hub', '');
    }
    ?>
    <?php
    foreach ($SUPPORTED_LANGS as $lang_code => $lang_meta) {
        $lang_url = $clean_base_url . ($lang_code === DEFAULT_LANG ? '' : '/' . $lang_code) . ($base_path === '/' ? '' : $base_path);
        if ($base_path === '/' && $lang_code === DEFAULT_LANG) {
            $lang_url = $clean_base_url . '/';
        }
        echo '    <link rel="alternate" hreflang="' . htmlspecialchars($lang_meta['hreflang']) . '" href="' . htmlspecialchars($lang_url) . '" />' . "\n";
        if ($lang_code === DEFAULT_LANG) {
            echo '    <link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($lang_url) . '" />' . "\n";
        }
    }
    ?>
    
    <meta name="author" content="SoulMD Hub">
    <meta name="theme-color" content="#09090b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SoulMD Hub">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    <link rel="icon" href="/images/icon-192x192.png" sizes="192x192" type="image/png">
    <link rel="manifest" href="/manifest.json">
    
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.9/purify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    
    <style>
        .gradient-text { background: linear-gradient(to right, #34d399, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.3); }
        .tag-input-field:focus { outline: none !important; box-shadow: none !important; }
        ::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
        /* Global print: never show marketing / renew banners on receipts or chat print */
        @media print {
            .no-print,
            .expired-sub-banner,
            #expired-sub-banner {
                display: none !important;
            }
        }
    </style>
    
    <?php if (defined('GOOGLE_ANALYTICS_ID') && !empty(GOOGLE_ANALYTICS_ID) && GOOGLE_ANALYTICS_ID !== 'YOUR_GOOGLE_ANALYTICS_ID_HERE'): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= GOOGLE_ANALYTICS_ID; ?>"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= GOOGLE_ANALYTICS_ID; ?>');
    </script>
    <?php endif; ?>
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(reg => {
                    console.log('PWA Service Worker registered successfully.');
                }).catch(err => {
                    console.log('PWA Service Worker registration failed: ', err);
                });
            });
        }
    </script>
</head>
<body class="bg-zinc-950 text-white min-h-screen flex flex-col relative">
    <?php
    // Stay above chat/app modals (z-[200]–z-[500]) so nav remains usable when popups open
    $headerZ = 'z-[1000]';
    $headerPos = (isset($navAbsolute) && $navAbsolute)
        ? "absolute top-0 left-0 right-0 {$headerZ}"
        : "sticky top-0 {$headerZ} mb-2 bg-zinc-950/95 backdrop-blur-md border-b border-white/5";
    ?>
    <header class="w-full <?= $headerPos ?>">
        <nav class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-5 sm:py-6 flex justify-between items-center">
            <a href="<?= url('/') ?>" class="flex items-center gap-2 text-xl sm:text-2xl font-bold tracking-tighter hover:text-emerald-400 transition shrink-0 select-none">
                SoulMD <span class="text-emerald-400 text-[9px] px-2 py-0.5 bg-emerald-900/30 rounded-full font-mono">HUB</span>
            </a>
            
            <?php if (!isset($hideNavLinks) || !$hideNavLinks): ?>
            <div class="hidden lg:flex items-center gap-6 xl:gap-8 text-sm font-medium">
                <a href="<?= url('/browse') ?>" class="text-zinc-400 hover:text-white transition"><?= __('Browse') ?></a>
                <a href="<?= url('/marketplace') ?>" class="text-zinc-400 hover:text-white transition flex items-center gap-1.5"><i class="fas fa-gem text-blue-400"></i> <?= __('Marketplace') ?></a>
                <a href="<?= url('/apps') ?>" class="text-zinc-400 hover:text-white transition flex items-center gap-1.5"><i class="fas fa-puzzle-piece text-emerald-400"></i> <?= __('Apps') ?></a>
                <a href="<?= url('/my-chats') ?>" class="text-zinc-400 hover:text-white transition"><?= __('My Chats') ?></a>
                <a href="<?= url('/upload') ?>" class="text-zinc-400 hover:text-white transition"><?= __('Upload') ?></a>
                <a href="<?= url('/upgrade') ?>" class="text-amber-400 hover:text-amber-300 transition flex items-center gap-1.5 px-3 py-1 bg-amber-400/10 rounded-full border border-amber-400/20"><i class="fas fa-crown text-xs"></i> <?= __('Premium') ?></a>
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <div class="flex items-center bg-zinc-900/60 border border-white/10 rounded-xl p-0.5 text-[10px] font-black tracking-wider select-none shadow-inner">
                    <?php foreach ($SUPPORTED_LANGS as $lang_code => $lang_meta): 
                        $lang_target_url = ($lang_code === DEFAULT_LANG ? '' : '/' . $lang_code) . ($base_path === '/' ? '/' : $base_path);
                        $isActive = (CURRENT_LANG === $lang_code);
                    ?>
                        <a href="<?= htmlspecialchars($lang_target_url) ?>" class="px-2 py-1 rounded-lg transition-all duration-200 <?= $isActive ? 'bg-emerald-500 text-zinc-950 font-black shadow' : 'text-zinc-500 hover:text-zinc-300' ?>">
                            <?= htmlspecialchars($lang_meta['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($isLoggedIn): ?>
                    <a href="<?= url('/my-souls') ?>" class="hidden sm:flex text-sm px-3 py-2 border border-white/10 rounded-xl hover:bg-white/5 transition items-center gap-1.5" title="<?= __('My Souls') ?>">
                        <i class="fas fa-layer-group text-emerald-400"></i> <span class="hidden xl:inline"><?= __('My Souls') ?></span>
                    </a>
                    
                    <a href="<?= url('/billing') ?>" class="hidden sm:flex text-sm px-3 py-2 border border-white/10 rounded-xl hover:bg-white/5 transition items-center gap-1.5" title="<?= __('Billing') ?>">
                        <i class="fas fa-file-invoice-dollar text-emerald-400"></i> <span class="hidden xl:inline"><?= __('Billing') ?></span>
                    </a>
                    <a href="<?= url('/my-setting') ?>" class="text-xs sm:text-sm px-3 py-2 border border-white/10 rounded-xl hover:bg-white/5 transition flex items-center justify-center" title="<?= __('Settings') ?>">
                        <i class="fas fa-cog text-emerald-400"></i>
                    </a>
                    
                    <button onclick="handleLogout()" class="hidden sm:flex text-sm px-3 py-2 bg-red-500/10 text-red-400 border border-red-500/20 rounded-xl hover:bg-red-500 hover:text-white transition items-center gap-1.5" title="<?= __('Log out') ?>">
                        <i class="fas fa-sign-out-alt"></i> <span class="hidden xl:inline"><?= __('Log out') ?></span>
                    </button>
                <?php else: ?>
                    <a href="<?= url('/login') ?>" class="hidden sm:inline-block text-xs sm:text-sm px-3.5 py-2 border border-white/20 rounded-xl hover:bg-white/5 transition font-medium"><?= __('Log in') ?></a>
                    <a href="<?= url('/register') ?>" class="text-xs sm:text-sm px-3.5 py-2 bg-emerald-500 text-zinc-950 rounded-xl font-black hover:bg-emerald-400 transition shadow-md"><?= __('Sign up') ?></a>
                <?php endif; ?>

                <?php if (!isset($hideNavLinks) || !$hideNavLinks): ?>
                    <button id="mobile-menu-toggle" class="lg:hidden w-10 h-10 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-xl text-zinc-400 hover:text-white transition shadow-sm ml-1">
                        <i class="fas fa-bars"></i>
                    </button>
                <?php endif; ?>
            </div>
        </nav>

        <?php if (!isset($hideNavLinks) || !$hideNavLinks): ?>
        <div id="mobile-menu" class="hidden lg:hidden flex-col absolute top-full left-0 w-full bg-zinc-950/95 backdrop-blur-2xl border-b border-white/10 shadow-2xl p-5 gap-1.5 z-[1001]">
            <a href="<?= url('/browse') ?>" class="p-3.5 text-base font-bold text-zinc-300 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-compass w-6 text-center text-emerald-400"></i> <?= __('Browse') ?></a>
            <a href="<?= url('/marketplace') ?>" class="p-3.5 text-base font-bold text-zinc-300 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-gem w-6 text-center text-blue-400"></i> <?= __('Marketplace') ?></a>
            <a href="<?= url('/apps') ?>" class="p-3.5 text-base font-bold text-zinc-300 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-puzzle-piece w-6 text-center text-emerald-400"></i> <?= __('Apps') ?></a>
            <a href="<?= url('/my-chats') ?>" class="p-3.5 text-base font-bold text-zinc-300 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-comments w-6 text-center text-emerald-400"></i> <?= __('My Chats') ?></a>
            <a href="<?= url('/upload') ?>" class="p-3.5 text-base font-bold text-zinc-300 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-cloud-upload-alt w-6 text-center text-emerald-400"></i> <?= __('Upload') ?></a>
            <a href="<?= url('/upgrade') ?>" class="p-3.5 text-base font-bold text-amber-400 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-crown w-6 text-center"></i> <?= __('Premium') ?></a>
            
            <?php if ($isLoggedIn): ?>
                <div class="border-t border-white/10 mt-3 pt-3 flex flex-col gap-1.5">
                    <a href="<?= url('/my-souls') ?>" class="p-3.5 text-base font-bold text-zinc-300 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-layer-group w-6 text-center text-emerald-400"></i> <?= __('My Souls') ?></a>
                    <a href="<?= url('/billing') ?>" class="p-3.5 text-base font-bold text-zinc-300 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-file-invoice-dollar w-6 text-center text-emerald-400"></i> <?= __('Billing') ?></a>
                    <button onclick="handleLogout()" class="p-3.5 text-base font-bold text-red-400 hover:bg-red-500/10 rounded-2xl transition flex items-center gap-4 text-left w-full"><i class="fas fa-sign-out-alt w-6 text-center text-red-400"></i> <?= __('Log out') ?></button>
                </div>
            <?php else: ?>
                <div class="border-t border-white/10 mt-3 pt-3">
                    <a href="<?= url('/login') ?>" class="p-3.5 text-base font-bold text-zinc-300 hover:bg-white/5 rounded-2xl transition flex items-center gap-4"><i class="fas fa-sign-in-alt w-6 text-center text-zinc-400"></i> <?= __('Log in') ?></a>
                </div>
            <?php endif; ?>
        </div>
        <script>
            document.getElementById('mobile-menu-toggle').addEventListener('click', function() {
                const menu = document.getElementById('mobile-menu');
                menu.classList.toggle('hidden');
                menu.classList.toggle('flex');
            });
        </script>
        <?php endif; ?>
    </header>

    <script>
    // 透過 Web3 處理登出並清空 Session
    async function handleLogout() {
        try {
            if (typeof initNearWallet === 'function') {
                const wallet = await initNearWallet();
                if (wallet && wallet.getAccountId()) {
                    wallet.signOut();
                }
            }
        } catch (e) {}
        
        window.location.href = '<?= url("/logout") ?>';
    }
    </script>

    <main class="flex-grow flex flex-col relative z-10">
        <?php if ($showExpiredBanner && !isset($hideGlobalBanner)): ?>
            <!-- no-print: never appear on invoice / chat / any print layout -->
            <div id="expired-sub-banner" class="no-print expired-sub-banner w-full bg-red-900/50 border-b border-red-500/30 px-4 py-2.5 flex flex-wrap items-center justify-center gap-3 sm:gap-6 text-xs sm:text-sm text-red-200 z-40 backdrop-blur-md shadow-lg text-center">
                <div class="flex items-center justify-center gap-2">
                    <i class="fas fa-exclamation-triangle text-red-400 animate-pulse text-base" aria-hidden="true"></i>
                    <span class="font-medium"><?= __('Your premium subscription has expired. API access and advanced features are currently locked.') ?></span>
                </div>
                <a href="<?= url('/upgrade') ?>" class="px-4 py-1 bg-red-500 hover:bg-red-400 text-zinc-950 font-black rounded-lg transition shadow-md text-xs whitespace-nowrap flex items-center gap-1">
                    <i class="fas fa-sync-alt text-[10px]" aria-hidden="true"></i> <?= __('Renew Now') ?>
                </a>
            </div>
        <?php endif; ?>