<?php
/**
 * SoulMD Hub - Mini Apps Hub
 * Pick a tool → fill form → choose a mapped soul → open chat with prefilled message.
 * SEO: /apps/{slug} and /apps/{slug}/{soul-title-slug}, SSR meta, JSON-LD, sitemap.
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/src/ApiSecurity.php';
require_once __DIR__ . '/../private/src/MiniAppsCatalog.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
loadTranslations('apps');
$csrfToken = ensureCsrfToken();

// SEO path: /apps/{slug} or /apps/{slug}/{soul-title-slug}
$initialAppSlug = trim((string)($_GET['slug'] ?? ''));
if ($initialAppSlug !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/', $initialAppSlug)) {
    $initialAppSlug = '';
}
$initialSoulTitleRaw = trim((string)($_GET['soul_title'] ?? ''));
// Strip accidental query noise / path junk; allow unicode titles
if ($initialSoulTitleRaw !== '' && strlen($initialSoulTitleRaw) > 200) {
    $initialSoulTitleRaw = function_exists('mb_substr')
        ? mb_substr($initialSoulTitleRaw, 0, 200, 'UTF-8')
        : substr($initialSoulTitleRaw, 0, 200);
}

$chatBaseUrl = url('/chat'); // /chat or /zh/chat
$appsBaseUrl = rtrim(url('/apps'), '/'); // /apps or /zh/apps
$baseUrlAbs = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://soulmd-hub.ysk.hk';

// Resolve absolute apps URL for canonical / schema (lang-aware path from url())
$appsPathRel = parse_url($appsBaseUrl, PHP_URL_PATH);
if (!is_string($appsPathRel) || $appsPathRel === '') {
    $appsPathRel = '/apps';
}
$appsPathRel = rtrim($appsPathRel, '/');

$ssrApp = null;
$ssrAppTitle = '';
$ssrAppDesc = '';
$ssrAppIcon = 'fa-puzzle-piece';
$ssrAppCategory = '';
$ssrAppDisclaimer = '';
$ssrAppKeywords = '';
$ssrSoul = null;
$ssrSoulTitle = '';
$ssrSoulTitleSlug = '';
$initialSoulId = 0;

if ($initialAppSlug !== '') {
    $ssrApp = MiniAppsCatalog::getBySlug($initialAppSlug);
    if (!$ssrApp) {
        http_response_code(404);
        loadTranslations('404');
        $pageTitle = __('SEO Title');
        $pageDesc = __('SEO Desc');
        $seoNoIndex = true;
        $seoCanonical = $baseUrlAbs . $appsPathRel;
        require_once __DIR__ . '/../private/includes/header.php';
        echo '<main class="max-w-xl mx-auto px-4 py-24 text-center flex-grow">';
        echo '<h1 class="text-3xl font-bold mb-3">' . htmlspecialchars(__('Soul Lost in Space')) . '</h1>';
        echo '<p class="text-zinc-400 mb-8">' . htmlspecialchars(__('404 Desc')) . '</p>';
        echo '<a href="' . htmlspecialchars($appsBaseUrl) . '" class="text-emerald-400 font-semibold hover:underline">' . htmlspecialchars(__('Back to apps')) . '</a>';
        echo '</main>';
        require_once __DIR__ . '/../private/includes/footer.php';
        exit;
    }

    $ssrAppTitle = __($ssrApp['title_key']);
    $ssrAppDesc = __($ssrApp['desc_key']);
    $ssrAppIcon = preg_replace('/[^a-z0-9-]/i', '', (string)($ssrApp['icon'] ?? 'fa-puzzle-piece')) ?: 'fa-puzzle-piece';
    $ssrAppCategory = (string)($ssrApp['category'] ?? '');
    if (!empty($ssrApp['disclaimer_key'])) {
        $ssrAppDisclaimer = __($ssrApp['disclaimer_key']);
    }
    $ssrAppKeywords = MiniAppsCatalog::searchKeywordsForApp($ssrApp);

    // Resolve AI persona from /apps/{slug}/{title} for SSR SEO + auto-select
    if ($initialSoulTitleRaw !== '') {
        try {
            $pdoApps = Database::getInstance()->getConnection();
            $ssrSouls = MiniAppsCatalog::searchPublicSouls(
                $pdoApps,
                MiniAppsCatalog::searchKeywordsForApp($ssrApp),
                50
            );
            $ssrSoul = MiniAppsCatalog::findSoulByTitleSlug($ssrSouls, $initialSoulTitleRaw);
            if ($ssrSoul) {
                $ssrSoulTitle = (string)($ssrSoul['title'] ?? '');
                $ssrSoulTitleSlug = MiniAppsCatalog::titleToSlug($ssrSoulTitle);
                $initialSoulId = (int)($ssrSoul['id'] ?? 0);
            }
        } catch (Throwable $e) {
            $ssrSoul = null;
        }
    }

    if ($ssrSoul && $ssrSoulTitle !== '') {
        $pageTitle = __('SEO App Soul Title', [
            'app' => $ssrAppTitle,
            'soul' => $ssrSoulTitle,
        ]);
        $pageDesc = __('SEO App Soul Desc', [
            'app' => $ssrAppTitle,
            'soul' => $ssrSoulTitle,
            'desc' => $ssrAppDesc,
        ]);
    } else {
        $pageTitle = __('SEO App Title', ['title' => $ssrAppTitle]);
        $pageDesc = __('SEO App Desc', ['title' => $ssrAppTitle, 'desc' => $ssrAppDesc]);
    }
    if (function_exists('mb_strlen') && mb_strlen($pageDesc, 'UTF-8') > 165) {
        $pageDesc = mb_substr($pageDesc, 0, 162, 'UTF-8') . '…';
    }

    $seoKeywords = trim(str_replace(',', ', ', $ssrAppKeywords . ', AI mini app, SoulMD Hub, ' . $ssrAppTitle
        . ($ssrSoulTitle !== '' ? ', ' . $ssrSoulTitle : '')));
    $appCanonicalPath = $appsPathRel . '/' . rawurlencode($initialAppSlug);
    if ($ssrSoulTitleSlug !== '') {
        $appCanonicalPath .= '/' . rawurlencode($ssrSoulTitleSlug);
    }
    $seoCanonical = $baseUrlAbs . $appCanonicalPath;

    $appOnlyCanonical = $baseUrlAbs . $appsPathRel . '/' . rawurlencode($initialAppSlug);
    $breadcrumbItems = [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'SoulMD Hub',
            'item' => $baseUrlAbs . '/',
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => __('Apps Title'),
            'item' => $baseUrlAbs . $appsPathRel,
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $ssrAppTitle,
            'item' => $appOnlyCanonical,
        ],
    ];
    if ($ssrSoulTitle !== '') {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => 4,
            'name' => $ssrSoulTitle,
            'item' => $seoCanonical,
        ];
    }

    $webAppNode = [
        '@type' => 'WebApplication',
        '@id' => $appOnlyCanonical . '#app',
        'name' => $ssrAppTitle,
        'description' => $ssrAppDesc,
        'url' => $appOnlyCanonical,
        'applicationCategory' => 'UtilitiesApplication',
        'operatingSystem' => 'Web Browser',
        'browserRequirements' => 'Requires JavaScript',
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'USD',
        ],
        'provider' => ['@id' => $baseUrlAbs . '/#organization'],
        'isPartOf' => ['@id' => $baseUrlAbs . $appsPathRel . '#collection'],
    ];
    if ($ssrSoulTitle !== '') {
        $webAppNode['about'] = [
            '@type' => 'Thing',
            'name' => $ssrSoulTitle,
            'description' => (string)($ssrSoul['description'] ?? ''),
            'url' => $seoCanonical,
        ];
    }

    $seoExtraGraph = [
        [
            '@type' => 'WebPage',
            '@id' => $seoCanonical . '#webpage',
            'url' => $seoCanonical,
            'name' => $pageTitle,
            'description' => $pageDesc,
            'isPartOf' => ['@id' => $baseUrlAbs . '/#website'],
            'about' => ['@id' => $appOnlyCanonical . '#app'],
            'inLanguage' => (defined('CURRENT_LANG') && CURRENT_LANG === 'zh') ? 'zh-Hant' : 'en',
        ],
        $webAppNode,
        [
            '@type' => 'BreadcrumbList',
            '@id' => $seoCanonical . '#breadcrumb',
            'itemListElement' => $breadcrumbItems,
        ],
    ];
} else {
    $pageTitle = __('SEO Title');
    $pageDesc = __('SEO Desc');
    $seoCanonical = $baseUrlAbs . $appsPathRel;
    $seoKeywords = __('SEO Keywords');

    $listItems = [];
    $pos = 1;
    foreach (MiniAppsCatalog::listPublic() as $card) {
        $listItems[] = [
            '@type' => 'ListItem',
            'position' => $pos++,
            'name' => $card['title'],
            'url' => $baseUrlAbs . $appsPathRel . '/' . rawurlencode($card['slug']),
            'description' => $card['description'],
        ];
    }
    $seoExtraGraph = [
        [
            '@type' => 'CollectionPage',
            '@id' => $seoCanonical . '#collection',
            'url' => $seoCanonical,
            'name' => $pageTitle,
            'description' => $pageDesc,
            'isPartOf' => ['@id' => $baseUrlAbs . '/#website'],
            'inLanguage' => (defined('CURRENT_LANG') && CURRENT_LANG === 'zh') ? 'zh-Hant' : 'en',
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $listItems,
                'numberOfItems' => count($listItems),
            ],
        ],
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'SoulMD Hub',
                    'item' => $baseUrlAbs . '/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => __('Apps Title'),
                    'item' => $seoCanonical,
                ],
            ],
        ],
    ];
}

require_once __DIR__ . '/../private/includes/header.php';

$showDetailSsr = $ssrApp !== null;
?>

<main class="max-w-7xl w-full mx-auto px-4 sm:px-6 pb-20 pt-8 flex-grow">
<style>
    .apps-hero-mesh {
        background:
            radial-gradient(ellipse 80% 60% at 20% -10%, rgba(16, 185, 129, 0.22), transparent 55%),
            radial-gradient(ellipse 60% 50% at 90% 10%, rgba(20, 184, 166, 0.14), transparent 50%),
            radial-gradient(ellipse 50% 40% at 50% 100%, rgba(52, 211, 153, 0.08), transparent 60%);
    }
    .app-card-pro {
        position: relative;
        overflow: hidden;
    }
    .app-card-pro::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(16,185,129,0.08) 0%, transparent 45%);
        opacity: 0;
        transition: opacity .25s ease;
        pointer-events: none;
    }
    .app-card-pro:hover::before { opacity: 1; }
    .app-card-pro:hover {
        border-color: rgba(52, 211, 153, 0.45);
        box-shadow: 0 20px 40px -20px rgba(16, 185, 129, 0.35), 0 0 0 1px rgba(52, 211, 153, 0.12);
        transform: translateY(-3px);
    }
    .cat-btn.active {
        background: linear-gradient(135deg, rgba(16,185,129,0.25), rgba(20,184,166,0.12));
        border-color: rgba(52, 211, 153, 0.5);
        color: #6ee7b7;
        font-weight: 600;
        box-shadow: 0 0 20px -8px rgba(16, 185, 129, 0.6);
    }
    .detail-hero-glow {
        background:
            linear-gradient(135deg, rgba(24,24,27,0.95), rgba(9,9,11,0.98)),
            radial-gradient(ellipse at top left, rgba(16,185,129,0.2), transparent 55%);
    }
</style>

<nav class="mb-6 text-xs sm:text-sm text-zinc-500" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1.5">
        <li><a href="<?= htmlspecialchars(url('/')) ?>" class="hover:text-emerald-400 transition">SoulMD Hub</a></li>
        <li aria-hidden="true" class="text-zinc-600">/</li>
        <?php if ($showDetailSsr): ?>
            <li><a href="<?= htmlspecialchars($appsBaseUrl) ?>" class="hover:text-emerald-400 transition"><?= htmlspecialchars(__('Apps Title')) ?></a></li>
            <li aria-hidden="true" class="text-zinc-600">/</li>
            <?php if ($ssrSoulTitle !== ''): ?>
                <li><a href="<?= htmlspecialchars($appsBaseUrl . '/' . rawurlencode($initialAppSlug)) ?>" class="hover:text-emerald-400 transition truncate max-w-[30vw]"><?= htmlspecialchars($ssrAppTitle) ?></a></li>
                <li aria-hidden="true" class="text-zinc-600">/</li>
                <li class="text-zinc-300 font-medium truncate max-w-[40vw]" aria-current="page"><?= htmlspecialchars($ssrSoulTitle) ?></li>
            <?php else: ?>
                <li class="text-zinc-300 font-medium truncate max-w-[60vw]" aria-current="page"><?= htmlspecialchars($ssrAppTitle) ?></li>
            <?php endif; ?>
        <?php else: ?>
            <li class="text-zinc-300 font-medium" aria-current="page"><?= htmlspecialchars(__('Apps Title')) ?></li>
        <?php endif; ?>
    </ol>
</nav>

<?php
$catalogApps = !$showDetailSsr ? MiniAppsCatalog::listPublic() : [];
$catalogCount = count($catalogApps);
$catLabel = static function (string $c): string {
    $map = [
        'destiny' => __('cat_destiny'),
        'career' => __('cat_career'),
        'legal' => __('cat_legal'),
        'health' => __('cat_health'),
        'life' => __('cat_life'),
        'emotion' => __('cat_emotion'),
    ];
    return $map[$c] ?? $c;
};
?>

<header class="apps-hero-mesh relative rounded-[2rem] border border-white/10 overflow-hidden mb-10 sm:mb-12 <?= $showDetailSsr ? 'hidden' : '' ?>" id="catalog-header">
    <div class="absolute inset-0 pointer-events-none opacity-30 bg-[radial-gradient(circle_at_1px_1px,rgba(16,185,129,0.15)_1px,transparent_0)] [background-size:28px_28px]"></div>
    <div class="relative px-6 sm:px-10 py-10 sm:py-14 text-center">
        <div class="inline-flex items-center gap-2 bg-emerald-500/10 text-emerald-300 px-4 py-1.5 rounded-full text-[11px] sm:text-xs font-semibold mb-5 border border-emerald-400/25 tracking-wide shadow-[0_0_24px_-6px_rgba(16,185,129,0.5)]">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
            <?= htmlspecialchars(__('Apps Badge')) ?>
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tighter mb-4 leading-[1.05]">
            <span class="gradient-text"><?= htmlspecialchars(__('Apps Title')) ?></span>
        </h1>
        <p class="text-sm sm:text-lg text-zinc-300/90 max-w-3xl mx-auto leading-relaxed mb-8">
            <?= htmlspecialchars(__('Apps Subtitle')) ?>
        </p>
        <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4 mb-8">
            <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-zinc-950/50 border border-white/10 text-xs sm:text-sm text-zinc-300">
                <i class="fas fa-layer-group text-emerald-400" aria-hidden="true"></i>
                <span class="font-semibold text-white"><?= (int)$catalogCount ?></span>
                <span class="text-zinc-500"><?= htmlspecialchars(__('Apps Hero Stat 1')) ?></span>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-zinc-950/50 border border-white/10 text-xs sm:text-sm text-zinc-300">
                <i class="fas fa-user-astronaut text-teal-400" aria-hidden="true"></i>
                <?= htmlspecialchars(__('Apps Hero Stat 2')) ?>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-zinc-950/50 border border-white/10 text-xs sm:text-sm text-zinc-300">
                <i class="fas fa-comments text-amber-400" aria-hidden="true"></i>
                <?= htmlspecialchars(__('Apps Hero Stat 3')) ?>
            </div>
        </div>
        <p class="text-[11px] sm:text-xs text-zinc-500 max-w-xl mx-auto leading-relaxed">
            <i class="fas fa-shield-halved text-emerald-500/80 mr-1" aria-hidden="true"></i>
            <?= htmlspecialchars(__('Apps Trust Line')) ?>
        </p>
    </div>
</header>

<section id="catalog-view" class="<?= $showDetailSsr ? 'hidden' : '' ?>" aria-label="<?= htmlspecialchars(__('Apps Title')) ?>">
    <!-- How it works -->
    <div class="mb-10 sm:mb-12">
        <h2 class="text-center text-xs font-bold uppercase tracking-[0.2em] text-emerald-400/90 mb-5"><?= htmlspecialchars(__('Apps How Title')) ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php
            $howSteps = [
                ['n' => '01', 'icon' => 'fa-box-open', 't' => __('Apps How 1 Title'), 'd' => __('Apps How 1 Desc')],
                ['n' => '02', 'icon' => 'fa-user-check', 't' => __('Apps How 2 Title'), 'd' => __('Apps How 2 Desc')],
                ['n' => '03', 'icon' => 'fa-paper-plane', 't' => __('Apps How 3 Title'), 'd' => __('Apps How 3 Desc')],
            ];
            foreach ($howSteps as $step):
            ?>
            <div class="relative rounded-3xl border border-white/10 bg-zinc-900/50 p-5 sm:p-6 backdrop-blur-sm hover:border-emerald-500/25 transition group">
                <div class="flex items-center gap-3 mb-3">
                    <span class="text-[10px] font-mono font-bold text-emerald-500/60 tracking-widest"><?= htmlspecialchars($step['n']) ?></span>
                    <span class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-105 transition">
                        <i class="fas <?= htmlspecialchars($step['icon']) ?> text-sm" aria-hidden="true"></i>
                    </span>
                </div>
                <h3 class="text-base font-bold text-white mb-1.5 tracking-tight"><?= htmlspecialchars($step['t']) ?></h3>
                <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed"><?= htmlspecialchars($step['d']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search + filters -->
    <div class="rounded-3xl border border-white/10 bg-zinc-900/40 backdrop-blur-sm p-4 sm:p-5 mb-6 shadow-xl shadow-black/20">
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500 text-sm" aria-hidden="true"></i>
                <input type="search" id="apps-search" autocomplete="off"
                    placeholder="<?= htmlspecialchars(__('Search apps')) ?>"
                    class="w-full bg-zinc-950 border border-white/10 rounded-2xl pl-11 pr-4 py-3.5 text-sm focus:outline-none focus:border-emerald-400/70 focus:ring-2 focus:ring-emerald-500/15 transition shadow-inner placeholder-zinc-600">
            </div>
            <div class="flex flex-wrap gap-2 content-center" id="category-filters" role="tablist">
                <button type="button" data-cat="" class="cat-btn active px-3.5 py-2 rounded-full text-xs sm:text-sm border border-emerald-400/40 transition"><?= htmlspecialchars(__('All categories')) ?></button>
                <button type="button" data-cat="destiny" class="cat-btn px-3.5 py-2 rounded-full text-xs sm:text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_destiny')) ?></button>
                <button type="button" data-cat="career" class="cat-btn px-3.5 py-2 rounded-full text-xs sm:text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_career')) ?></button>
                <button type="button" data-cat="legal" class="cat-btn px-3.5 py-2 rounded-full text-xs sm:text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_legal')) ?></button>
                <button type="button" data-cat="health" class="cat-btn px-3.5 py-2 rounded-full text-xs sm:text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_health')) ?></button>
                <button type="button" data-cat="life" class="cat-btn px-3.5 py-2 rounded-full text-xs sm:text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_life')) ?></button>
                <button type="button" data-cat="emotion" class="cat-btn px-3.5 py-2 rounded-full text-xs sm:text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_emotion')) ?></button>
            </div>
        </div>
    </div>

    <div id="apps-grid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 min-h-[120px]">
        <?php if (!$showDetailSsr): ?>
            <?php foreach ($catalogApps as $card):
                $cardHref = $appsBaseUrl . '/' . rawurlencode($card['slug']);
                $cardIcon = preg_replace('/[^a-z0-9-]/i', '', (string)($card['icon'] ?? 'fa-puzzle-piece')) ?: 'fa-puzzle-piece';
                $cardCat = (string)($card['category'] ?? '');
                $fc = (int)($card['field_count'] ?? 0);
                $badge = $card['badge'] ?? null;
            ?>
            <a href="<?= htmlspecialchars($cardHref) ?>" data-slug="<?= htmlspecialchars($card['slug']) ?>"
                class="app-card app-card-pro block text-left group bg-zinc-900/70 border border-white/10 rounded-[1.35rem] p-6 transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50">
                <div class="relative flex items-start justify-between gap-3 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-teal-500/5 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg group-hover:scale-105 transition shadow-[0_0_24px_-8px_rgba(16,185,129,0.5)]">
                        <i class="fas <?= htmlspecialchars($cardIcon) ?>" aria-hidden="true"></i>
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        <?php if ($badge === 'hot'): ?>
                            <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-300 border border-rose-400/30 font-bold"><?= htmlspecialchars(__('Hot')) ?></span>
                        <?php elseif ($badge === 'popular'): ?>
                            <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-300 border border-amber-400/30 font-bold"><?= htmlspecialchars(__('Popular')) ?></span>
                        <?php endif; ?>
                        <?php if ($cardCat !== ''): ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-zinc-400 font-medium"><?= htmlspecialchars($catLabel($cardCat)) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <h2 class="relative text-lg font-bold text-white mb-2 tracking-tight group-hover:text-emerald-300 transition"><?= htmlspecialchars($card['title']) ?></h2>
                <p class="relative text-sm text-zinc-400 leading-relaxed line-clamp-4 mb-5 min-h-[4.5rem]"><?= htmlspecialchars($card['description']) ?></p>
                <div class="relative flex items-center justify-between gap-2 pt-4 border-t border-white/5">
                    <span class="text-[11px] text-zinc-500 font-mono"><?= htmlspecialchars(__('Fields count', ['n' => $fc])) ?></span>
                    <span class="text-xs font-semibold text-emerald-400 inline-flex items-center gap-1.5 group-hover:gap-2.5 transition-all">
                        <?= htmlspecialchars(__('Open app')) ?> <i class="fas fa-arrow-right text-[10px]" aria-hidden="true"></i>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        <?php else: ?>
        <div class="col-span-full flex items-center justify-center py-16 text-zinc-500 text-sm gap-2">
            <span class="animate-spin h-4 w-4 border-2 border-zinc-500 border-t-transparent rounded-full"></span>
            <?= htmlspecialchars(__('Loading apps')) ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<section id="detail-view" class="<?= $showDetailSsr ? '' : 'hidden' ?> max-w-6xl mx-auto w-full" aria-live="polite">
    <button type="button" id="btn-back" class="mb-5 text-sm text-zinc-400 hover:text-emerald-400 transition inline-flex items-center gap-2 group">
        <i class="fas fa-arrow-left group-hover:-translate-x-0.5 transition" aria-hidden="true"></i> <?= htmlspecialchars(__('Back to apps')) ?>
    </button>

    <!-- Detail hero -->
    <div class="detail-hero-glow rounded-[1.75rem] border border-emerald-500/20 p-5 sm:p-8 mb-6 shadow-2xl shadow-emerald-950/20">
        <div class="flex flex-col sm:flex-row items-start gap-4 sm:gap-5">
            <div id="detail-icon" class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-emerald-500/25 to-teal-600/10 border border-emerald-400/35 flex items-center justify-center text-emerald-300 text-xl sm:text-2xl shrink-0 shadow-[0_0_32px_-8px_rgba(16,185,129,0.55)]">
                <i class="fas <?= htmlspecialchars($showDetailSsr ? $ssrAppIcon : 'fa-puzzle-piece') ?>" aria-hidden="true"></i>
            </div>
            <div class="min-w-0 flex-1">
                <?php if ($showDetailSsr && $ssrAppCategory !== ''): ?>
                    <span class="inline-block text-[10px] uppercase tracking-widest font-bold text-emerald-400/90 mb-2 px-2 py-0.5 rounded-md bg-emerald-500/10 border border-emerald-500/20"><?= htmlspecialchars($catLabel($ssrAppCategory)) ?></span>
                <?php endif; ?>
                <<?= $showDetailSsr ? 'h1' : 'h2' ?> id="detail-title" class="text-2xl sm:text-3xl font-black tracking-tight text-white mb-2"><?= $showDetailSsr ? htmlspecialchars($ssrAppTitle) : '' ?></<?= $showDetailSsr ? 'h1' : 'h2' ?>>
                <p id="detail-desc" class="text-sm sm:text-base text-zinc-300/90 leading-relaxed max-w-3xl"><?= $showDetailSsr ? htmlspecialchars($ssrAppDesc) : '' ?></p>
                <?php if ($showDetailSsr && $ssrAppKeywords !== ''): ?>
                    <p class="sr-only"><?= htmlspecialchars($ssrAppKeywords) ?></p>
                <?php endif; ?>
                <div class="mt-4 flex flex-wrap gap-2 text-[11px] text-zinc-500">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/5 border border-white/10"><i class="fas fa-user-check text-emerald-500/80"></i> <?= htmlspecialchars(__('Apps How 2 Title')) ?></span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/5 border border-white/10"><i class="fas fa-file-lines text-teal-500/80"></i> <?= htmlspecialchars(__('Apps How 3 Title')) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6 items-start">
        <!-- Step 1: soul picker -->
        <aside class="lg:col-span-5 bg-zinc-900/70 border border-white/10 rounded-3xl overflow-hidden shadow-xl flex flex-col max-h-[min(75vh,720px)] lg:max-h-[calc(100dvh-12rem)] lg:sticky lg:top-24">
            <div class="shrink-0 p-4 sm:p-5 border-b border-white/5 bg-gradient-to-r from-emerald-950/40 to-zinc-950/60">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h3 class="text-sm font-bold text-zinc-100 flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-emerald-500 text-zinc-950 text-[11px] font-black flex items-center justify-center shadow-lg shadow-emerald-500/20">1</span>
                        <?= htmlspecialchars(__('Choose AI soul')) ?>
                    </h3>
                    <span id="soul-count-badge" class="text-[11px] font-mono text-zinc-500 tabular-nums"></span>
                </div>
                <div class="relative">
                    <i class="fas fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-xs" aria-hidden="true"></i>
                    <input type="search" id="soul-filter" autocomplete="off"
                        placeholder="<?= htmlspecialchars(__('Filter personas…')) ?>"
                        class="w-full bg-zinc-950 border border-white/10 rounded-xl pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-emerald-400/60 focus:ring-2 focus:ring-emerald-500/10 transition placeholder-zinc-600">
                </div>
            </div>

            <div id="soul-picker" role="listbox" aria-label="<?= htmlspecialchars(__('Choose AI soul')) ?>"
                class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5 min-h-[180px]"></div>
            <p id="soul-picker-error" class="hidden shrink-0 px-4 py-2 text-xs text-red-400 border-t border-red-500/20"></p>
        </aside>

        <!-- Step 2 -->
        <div class="lg:col-span-7 flex flex-col gap-4 min-w-0">
            <div id="soul-selected-bar" class="hidden rounded-3xl border border-emerald-400/35 bg-gradient-to-br from-emerald-500/15 via-zinc-900/90 to-zinc-950 p-4 sm:p-5 shadow-xl shadow-emerald-950/30">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="text-[10px] uppercase tracking-wider text-emerald-300 font-bold flex items-center gap-1.5">
                        <i class="fas fa-check-circle" aria-hidden="true"></i> <?= htmlspecialchars(__('Selected')) ?>
                    </div>
                    <div id="soul-selected-stats" class="flex items-center gap-2 text-[11px] text-zinc-400"></div>
                </div>
                <div id="soul-selected-title" class="text-base sm:text-lg font-bold text-white leading-snug"></div>
                <div id="soul-selected-meta" class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] text-zinc-400"></div>
                <div id="soul-selected-tags" class="mt-2 flex flex-wrap gap-1.5"></div>
                <p id="soul-selected-desc" class="text-xs sm:text-sm text-zinc-300 mt-2.5 leading-relaxed whitespace-pre-wrap"></p>
                <a id="soul-selected-link" href="#" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 mt-3 text-[11px] font-semibold text-emerald-400 hover:text-emerald-300 transition">
                    <i class="fas fa-external-link-alt text-[10px]" aria-hidden="true"></i>
                    <?= htmlspecialchars(__('View soul page')) ?>
                </a>
            </div>

            <div class="bg-zinc-900/70 border border-white/10 rounded-3xl p-5 sm:p-7 shadow-xl">
                <h3 class="text-sm font-bold text-zinc-100 flex items-center gap-2 mb-5">
                    <span class="w-7 h-7 rounded-lg bg-emerald-500 text-zinc-950 text-[11px] font-black flex items-center justify-center shadow-lg shadow-emerald-500/20">2</span>
                    <?= htmlspecialchars(__('Fill in details')) ?>
                </h3>

                <div id="app-disclaimer" class="<?= ($showDetailSsr && $ssrAppDisclaimer !== '') ? '' : 'hidden' ?> mb-4 text-xs text-amber-200/90 bg-amber-500/10 border border-amber-500/25 rounded-xl px-3 py-2.5 leading-relaxed"><?php
                    if ($showDetailSsr && $ssrAppDisclaimer !== '') {
                        echo '<span class="font-semibold text-amber-200">' . htmlspecialchars(__('Disclaimer label')) . '：</span> ';
                        echo htmlspecialchars($ssrAppDisclaimer);
                    }
                ?></div>

                <form id="app-form" class="space-y-4"></form>

                <p id="form-error" class="hidden mt-4 text-sm text-red-400"></p>

                <button type="submit" form="app-form" id="run-btn"
                    class="mt-6 w-full py-3.5 sm:py-4 bg-gradient-to-r from-emerald-500 to-teal-500 text-zinc-950 text-sm sm:text-base font-bold rounded-2xl hover:brightness-110 transition flex items-center justify-center gap-3 shadow-lg shadow-emerald-500/25 disabled:opacity-60 disabled:cursor-not-allowed sticky bottom-4">
                    <span id="run-text"><i class="fas fa-comments mr-1" aria-hidden="true"></i> <?= htmlspecialchars(__('Start chat for AI reply')) ?></span>
                    <span id="run-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</section>
</main>

<script>
(function () {
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    const chatBaseUrl = <?= json_encode($chatBaseUrl, JSON_UNESCAPED_UNICODE) ?>;
    const appsBaseUrl = <?= json_encode($appsBaseUrl, JSON_UNESCAPED_UNICODE) ?>;
    const initialSlug = <?= json_encode($initialAppSlug, JSON_UNESCAPED_UNICODE) ?>;
    const initialSoulTitleSlug = <?= json_encode($ssrSoulTitleSlug !== '' ? $ssrSoulTitleSlug : MiniAppsCatalog::titleToSlug($initialSoulTitleRaw), JSON_UNESCAPED_UNICODE) ?>;
    const initialSoulIdPref = <?= (int)$initialSoulId ?>;
    const catalogSeoTitle = <?= json_encode(__('SEO Title') . ' | SoulMD Hub', JSON_UNESCAPED_UNICODE) ?>;
    const catalogSeoDesc = <?= json_encode(__('SEO Desc'), JSON_UNESCAPED_UNICODE) ?>;
    const seoAppTitleTpl = <?= json_encode(__('SEO App Title'), JSON_UNESCAPED_UNICODE) ?>;
    const seoAppDescTpl = <?= json_encode(__('SEO App Desc'), JSON_UNESCAPED_UNICODE) ?>;
    const seoAppSoulTitleTpl = <?= json_encode(__('SEO App Soul Title'), JSON_UNESCAPED_UNICODE) ?>;
    const seoAppSoulDescTpl = <?= json_encode(__('SEO App Soul Desc'), JSON_UNESCAPED_UNICODE) ?>;
    const i18n = {
        loading: <?= json_encode(__('Loading apps'), JSON_UNESCAPED_UNICODE) ?>,
        empty: <?= json_encode(__('No apps found'), JSON_UNESCAPED_UNICODE) ?>,
        failList: <?= json_encode(__('Failed to load apps'), JSON_UNESCAPED_UNICODE) ?>,
        failApp: <?= json_encode(__('Failed to load app'), JSON_UNESCAPED_UNICODE) ?>,
        network: <?= json_encode(__('Network error'), JSON_UNESCAPED_UNICODE) ?>,
        hot: <?= json_encode(__('Hot'), JSON_UNESCAPED_UNICODE) ?>,
        popular: <?= json_encode(__('Popular'), JSON_UNESCAPED_UNICODE) ?>,
        pickSoul: <?= json_encode(__('Please select an AI soul'), JSON_UNESCAPED_UNICODE) ?>,
        byAuthor: <?= json_encode(__('By :name'), JSON_UNESCAPED_UNICODE) ?>,
        roleLabel: <?= json_encode(__('Role'), JSON_UNESCAPED_UNICODE) ?>,
        noDesc: <?= json_encode(__('No description provided.'), JSON_UNESCAPED_UNICODE) ?>,
        soulsCount: <?= json_encode(__(':n AI options'), JSON_UNESCAPED_UNICODE) ?>,
        noSoulsFound: <?= json_encode(__('No matching souls for this theme'), JSON_UNESCAPED_UNICODE) ?>,
        noFilterMatch: <?= json_encode(__('No personas match your filter'), JSON_UNESCAPED_UNICODE) ?>,
        filterPh: <?= json_encode(__('Filter personas…'), JSON_UNESCAPED_UNICODE) ?>,
        likes: <?= json_encode(__('Likes'), JSON_UNESCAPED_UNICODE) ?>,
        forks: <?= json_encode(__('Forks'), JSON_UNESCAPED_UNICODE) ?>,
        modular: <?= json_encode(__('Modular'), JSON_UNESCAPED_UNICODE) ?>,
        singleFile: <?= json_encode(__('Single file'), JSON_UNESCAPED_UNICODE) ?>,
        viewSoul: <?= json_encode(__('View soul page'), JSON_UNESCAPED_UNICODE) ?>,
        openApp: <?= json_encode(__('Open app'), JSON_UNESCAPED_UNICODE) ?>,
        fieldsCount: <?= json_encode(__('Fields count'), JSON_UNESCAPED_UNICODE) ?>,
        cat: {
            destiny: <?= json_encode(__('cat_destiny'), JSON_UNESCAPED_UNICODE) ?>,
            career: <?= json_encode(__('cat_career'), JSON_UNESCAPED_UNICODE) ?>,
            legal: <?= json_encode(__('cat_legal'), JSON_UNESCAPED_UNICODE) ?>,
            health: <?= json_encode(__('cat_health'), JSON_UNESCAPED_UNICODE) ?>,
            life: <?= json_encode(__('cat_life'), JSON_UNESCAPED_UNICODE) ?>,
            emotion: <?= json_encode(__('cat_emotion'), JSON_UNESCAPED_UNICODE) ?>,
        },
    };

    let activeCategory = '';
    let searchTimer = null;
    let currentSlug = null;
    let selectedSoulId = null;
    let currentAppMeta = { title: '', description: '' };

    /** SEO path segment from soul title (matches PHP MiniAppsCatalog::titleToSlug) */
    function soulTitleSlug(title) {
        let t = String(title || '').trim().toLowerCase();
        if (!t) return 'soul';
        t = t.replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|.\u3000]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
        if (!t) return 'soul';
        return t.slice(0, 80);
    }

    function findSoulByTitleSlug(souls, slug) {
        const want = soulTitleSlug(decodeURIComponent(String(slug || '')));
        if (!want) return null;
        const list = Array.isArray(souls) ? souls : [];
        let hit = list.find(s => soulTitleSlug(s.title) === want);
        if (hit) return hit;
        const loose = decodeURIComponent(String(slug || '')).trim().toLowerCase();
        return list.find(s => String(s.title || '').trim().toLowerCase() === loose) || null;
    }

    function appPath(appSlug, soul) {
        let path = appsBaseUrl + '/' + encodeURIComponent(appSlug);
        if (soul && (soul.title || soul.id)) {
            path += '/' + encodeURIComponent(soulTitleSlug(soul.title || ('soul-' + soul.id)));
        }
        return path;
    }

    function syncAppUrl(appSlug, soul, replace) {
        if (!appSlug) return;
        const path = appPath(appSlug, soul);
        const state = {
            appSlug: appSlug,
            soulSlug: soul ? soulTitleSlug(soul.title) : null,
            soulId: soul ? soul.id : null,
        };
        const cur = window.location.pathname.replace(/\/+$/, '');
        const target = path.replace(/\/+$/, '');
        if (cur !== target) {
            if (replace) history.replaceState(state, '', path);
            else history.pushState(state, '', path);
        } else {
            history.replaceState(state, '', path);
        }
        const appTitle = currentAppMeta.title || appSlug;
        const appDesc = currentAppMeta.description || '';
        if (soul && soul.title) {
            const t = (seoAppSoulTitleTpl || ':app × :soul — AI Mini App')
                .replace(/:app/g, appTitle)
                .replace(/:soul/g, soul.title);
            const d = (seoAppSoulDescTpl || ':desc')
                .replace(/:app/g, appTitle)
                .replace(/:soul/g, soul.title)
                .replace(/:desc/g, appDesc);
            applyPageSeo(t.includes('| SoulMD Hub') ? t : (t + ' | SoulMD Hub'), d, path);
        } else {
            applyPageSeo(buildAppSeoTitle(appTitle), buildAppSeoDesc(appTitle, appDesc), path);
        }
    }
    /** @type {Array<{id:number,title:string,description:string,role:string,username:string}>} */
    let currentSouls = [];

    function escapeHTML(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function randomSessionToken() {
        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);
        return Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
    }

    async function loadApps() {
        const grid = document.getElementById('apps-grid');
        const q = document.getElementById('apps-search').value.trim();
        const params = new URLSearchParams();
        if (activeCategory) params.set('category', activeCategory);
        if (q) params.set('q', q);
        try {
            const res = await fetch('/api/apps' + (params.toString() ? '?' + params.toString() : ''));
            const data = await res.json();
            if (!data.success) throw new Error(data.error || i18n.failList);
            renderCards(data.data || []);
        } catch (e) {
            grid.innerHTML = `<div class="col-span-full text-center py-12 text-red-400 text-sm">${escapeHTML(e.message || i18n.failList)}</div>`;
        }
    }

    function badgeHtml(badge) {
        if (!badge) return '';
        if (badge === 'hot') {
            return `<span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-300 border border-rose-400/30 font-bold">${escapeHTML(i18n.hot)}</span>`;
        }
        if (badge === 'popular') {
            return `<span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-300 border border-amber-400/30 font-bold">${escapeHTML(i18n.popular)}</span>`;
        }
        return `<span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-300 border border-amber-400/30 font-bold">${escapeHTML(badge)}</span>`;
    }

    function catLabel(cat) {
        if (!cat) return '';
        return (i18n.cat && i18n.cat[cat]) ? i18n.cat[cat] : cat;
    }

    function renderCards(apps) {
        const grid = document.getElementById('apps-grid');
        if (!apps.length) {
            grid.innerHTML = `<div class="col-span-full text-center py-16 text-zinc-500 text-sm">${escapeHTML(i18n.empty)}</div>`;
            return;
        }
        grid.innerHTML = apps.map(app => {
            const icon = (app.icon || 'fa-puzzle-piece').replace(/[^a-z0-9-]/gi, '');
            const href = appsBaseUrl + '/' + encodeURIComponent(app.slug);
            const cat = app.category || '';
            const fc = parseInt(app.field_count, 10) || 0;
            const fieldsLabel = (i18n.fieldsCount || ':n fields').replace(':n', String(fc));
            const catChip = cat
                ? `<span class="text-[10px] px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-zinc-400 font-medium">${escapeHTML(catLabel(cat))}</span>`
                : '';
            return `
            <a href="${escapeHTML(href)}" data-slug="${escapeHTML(app.slug)}"
                class="app-card app-card-pro block text-left group bg-zinc-900/70 border border-white/10 rounded-[1.35rem] p-6 transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50">
                <div class="relative flex items-start justify-between gap-3 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-teal-500/5 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg group-hover:scale-105 transition shadow-[0_0_24px_-8px_rgba(16,185,129,0.5)]">
                        <i class="fas ${escapeHTML(icon)}" aria-hidden="true"></i>
                    </div>
                    <div class="flex flex-col items-end gap-1.5">
                        ${badgeHtml(app.badge)}
                        ${catChip}
                    </div>
                </div>
                <h2 class="relative text-lg font-bold text-white mb-2 tracking-tight group-hover:text-emerald-300 transition">${escapeHTML(app.title)}</h2>
                <p class="relative text-sm text-zinc-400 leading-relaxed line-clamp-4 mb-5 min-h-[4.5rem]">${escapeHTML(app.description || '')}</p>
                <div class="relative flex items-center justify-between gap-2 pt-4 border-t border-white/5">
                    <span class="text-[11px] text-zinc-500 font-mono">${escapeHTML(fieldsLabel)}</span>
                    <span class="text-xs font-semibold text-emerald-400 inline-flex items-center gap-1.5 group-hover:gap-2.5 transition-all">
                        ${escapeHTML(i18n.openApp || 'Open')} <i class="fas fa-arrow-right text-[10px]" aria-hidden="true"></i>
                    </span>
                </div>
            </a>`;
        }).join('');

        grid.querySelectorAll('.app-card').forEach(link => {
            link.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                e.preventDefault();
                openApp(link.getAttribute('data-slug'));
            });
        });
    }

    function initialFromTitle(title) {
        const t = (title || '?').trim();
        return escapeHTML(t.charAt(0) || '?');
    }

    function domainTagsHtml(soul, limit) {
        const tags = Array.isArray(soul.domains) ? soul.domains : [];
        if (!tags.length) return '';
        return tags.slice(0, limit || 3).map(t =>
            `<span class="inline-flex px-1.5 py-0.5 rounded-md bg-white/5 border border-white/10 text-[10px] text-zinc-400">${escapeHTML(t)}</span>`
        ).join('');
    }

    function updateSelectedBar() {
        const bar = document.getElementById('soul-selected-bar');
        const titleEl = document.getElementById('soul-selected-title');
        const metaEl = document.getElementById('soul-selected-meta');
        const tagsEl = document.getElementById('soul-selected-tags');
        const statsEl = document.getElementById('soul-selected-stats');
        const descEl = document.getElementById('soul-selected-desc');
        const linkEl = document.getElementById('soul-selected-link');
        const soul = currentSouls.find(s => s.id === selectedSoulId);
        if (!soul) {
            bar.classList.add('hidden');
            return;
        }
        titleEl.textContent = soul.title || ('#' + soul.id);

        const roleLabel = soul.role_name || soul.role || '';
        const metaBits = [];
        if (soul.username) metaBits.push('<i class="fas fa-user text-[9px] opacity-70"></i> @' + escapeHTML(soul.username));
        if (roleLabel) metaBits.push('<i class="fas fa-tag text-[9px] opacity-70"></i> ' + escapeHTML(roleLabel));
        if (soul.file_type === 'full_soul_folder') metaBits.push(escapeHTML(i18n.modular));
        else if (soul.file_type) metaBits.push(escapeHTML(i18n.singleFile));
        metaEl.innerHTML = metaBits.length
            ? metaBits.map(b => `<span class="inline-flex items-center gap-1">${b}</span>`).join('<span class="text-zinc-600">·</span>')
            : '';

        tagsEl.innerHTML = domainTagsHtml(soul, 6);

        const likes = Number(soul.like_count || 0);
        const forks = Number(soul.fork_count || 0);
        statsEl.innerHTML = `
            <span title="${escapeHTML(i18n.likes)}"><i class="fas fa-heart text-rose-400/80"></i> ${likes}</span>
            <span title="${escapeHTML(i18n.forks)}"><i class="fas fa-code-branch text-sky-400/80"></i> ${forks}</span>`;

        const desc = (soul.description && soul.description.trim()) ? soul.description.trim() : i18n.noDesc;
        descEl.textContent = desc;

        const uname = encodeURIComponent(soul.username || 'anonymous');
        const roleSlug = encodeURIComponent((soul.role || 'other').toString().toLowerCase().replace(/\s+/g, '-'));
        const titleSlug = encodeURIComponent((soul.title || 'soul').toString().toLowerCase().replace(/\s+/g, '-').slice(0, 80));
        linkEl.href = `<?= url('/soul/') ?>${uname}/${soul.id}/${roleSlug}/${titleSlug}`;
        linkEl.classList.remove('hidden');
        bar.classList.remove('hidden');
    }

    function renderSoulPicker(souls, preferredSoulId, preferredTitleSlug) {
        currentSouls = Array.isArray(souls) ? souls.slice() : [];
        const filterEl = document.getElementById('soul-filter');
        if (filterEl) filterEl.value = '';

        selectedSoulId = null;
        if (preferredSoulId) {
            const byId = currentSouls.find(s => s.id === preferredSoulId);
            if (byId) selectedSoulId = byId.id;
        }
        if (!selectedSoulId && preferredTitleSlug) {
            const byTitle = findSoulByTitleSlug(currentSouls, preferredTitleSlug);
            if (byTitle) selectedSoulId = byTitle.id;
        }
        if (!selectedSoulId && currentSouls.length) {
            selectedSoulId = currentSouls[0].id;
        }

        paintSoulList();
        updateSelectedBar();
    }

    function paintSoulList() {
        const box = document.getElementById('soul-picker');
        const err = document.getElementById('soul-picker-error');
        const badge = document.getElementById('soul-count-badge');
        err.classList.add('hidden');

        const filter = (document.getElementById('soul-filter')?.value || '').trim().toLowerCase();
        let list = currentSouls;
        if (filter) {
            list = currentSouls.filter(s => {
                const domains = Array.isArray(s.domains) ? s.domains.join(' ') : (s.domain || '');
                const hay = [s.title, s.username, s.role, s.role_name, s.description, domains].join(' ').toLowerCase();
                return hay.includes(filter);
            });
        }

        badge.textContent = i18n.soulsCount.replace(':n', String(list.length));

        if (!currentSouls.length) {
            box.innerHTML = `<div class="px-3 py-8 text-center text-sm text-amber-300/90">${escapeHTML(i18n.noSoulsFound)}</div>`;
            return;
        }
        if (!list.length) {
            box.innerHTML = `<div class="px-3 py-8 text-center text-sm text-zinc-500">${escapeHTML(i18n.noFilterMatch)}</div>`;
            return;
        }

        if (!list.some(s => s.id === selectedSoulId)) {
            selectedSoulId = list[0].id;
        }

        box.innerHTML = list.map(s => {
            const active = s.id === selectedSoulId;
            const author = s.username ? '@' + s.username : '';
            const role = s.role_name || s.role || '';
            const likes = Number(s.like_count || 0);
            const desc = (s.description && s.description.trim()) ? s.description.trim() : '';
            const tags = domainTagsHtml(s, 2);
            return `
            <button type="button" role="option" aria-selected="${active ? 'true' : 'false'}" data-soul-id="${s.id}"
                class="soul-row w-full text-left rounded-xl px-3 py-2.5 transition border ${
                    active
                        ? 'bg-emerald-500/10 border-emerald-400/40 ring-1 ring-emerald-400/20'
                        : 'bg-zinc-950/30 border-white/5 hover:bg-white/[0.04] hover:border-white/10'
                }">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center text-sm font-black mt-0.5 ${
                        active ? 'bg-emerald-500 text-zinc-950' : 'bg-zinc-800 text-zinc-300 border border-white/10'
                    }">${initialFromTitle(s.title)}</span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="text-sm font-semibold text-white leading-snug line-clamp-2">${escapeHTML(s.title || ('#' + s.id))}</span>
                            ${active ? '<i class="fas fa-check text-emerald-400 text-xs shrink-0 mt-1" aria-hidden="true"></i>' : ''}
                        </span>
                        <span class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-zinc-500">
                            ${author ? `<span class="truncate max-w-[9rem]">${escapeHTML(author)}</span>` : ''}
                            ${role ? `<span class="px-1.5 py-0.5 rounded bg-white/5 border border-white/10 text-zinc-400">${escapeHTML(role)}</span>` : ''}
                            ${likes > 0 ? `<span class="text-zinc-500"><i class="fas fa-heart text-rose-400/70 text-[9px]"></i> ${likes}</span>` : ''}
                        </span>
                        ${desc ? `<span class="mt-1.5 block text-[11px] text-zinc-400 leading-relaxed ${active ? 'line-clamp-3' : 'line-clamp-1'}">${escapeHTML(desc)}</span>` : ''}
                        ${tags ? `<span class="mt-1.5 flex flex-wrap gap-1">${tags}</span>` : ''}
                    </span>
                </div>
            </button>`;
        }).join('');

        box.querySelectorAll('.soul-row').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedSoulId = parseInt(btn.getAttribute('data-soul-id'), 10) || null;
                paintSoulList();
                updateSelectedBar();
                err.classList.add('hidden');
                btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                // SEO: /apps/{app}/{soul-title}
                const soul = currentSouls.find(s => s.id === selectedSoulId);
                if (currentSlug && soul) {
                    syncAppUrl(currentSlug, soul, false);
                }
            });
        });
        updateSelectedBar();
    }

    function setMetaContent(selector, attr, value) {
        const el = document.querySelector(selector);
        if (el && value != null) el.setAttribute(attr, value);
    }

    function applyPageSeo(title, description, path) {
        if (title) document.title = title;
        if (description) {
            setMetaContent('meta[name="description"]', 'content', description);
            setMetaContent('meta[property="og:description"]', 'content', description);
            setMetaContent('meta[name="twitter:description"]', 'content', description);
        }
        if (title) {
            setMetaContent('meta[property="og:title"]', 'content', title);
            setMetaContent('meta[name="twitter:title"]', 'content', title);
        }
        if (path) {
            const abs = window.location.origin + path;
            setMetaContent('meta[property="og:url"]', 'content', abs);
            const canon = document.querySelector('link[rel="canonical"]');
            if (canon) canon.setAttribute('href', abs);
        }
    }

    function buildAppSeoTitle(appTitle) {
        const mid = (seoAppTitleTpl || ':title — AI Mini App').replace(':title', appTitle || '');
        return mid.includes('| SoulMD Hub') ? mid : (mid + ' | SoulMD Hub');
    }

    function buildAppSeoDesc(appTitle, appDesc) {
        return (seoAppDescTpl || ':desc')
            .replace(/:title/g, appTitle || '')
            .replace(/:desc/g, appDesc || '');
    }

    async function openApp(slug, opts) {
        opts = opts || {};
        const preferredSoulId = opts.soulId || null;
        const preferredTitleSlug = opts.soulTitleSlug || null;
        const replaceUrl = opts.replaceUrl !== false;

        currentSlug = slug;
        selectedSoulId = null;
        document.getElementById('catalog-view').classList.add('hidden');
        const catalogHeader = document.getElementById('catalog-header');
        if (catalogHeader) catalogHeader.classList.add('hidden');
        document.getElementById('detail-view').classList.remove('hidden');
        document.getElementById('form-error').classList.add('hidden');

        const form = document.getElementById('app-form');
        form.innerHTML = `<div class="text-zinc-500 text-sm py-4">${escapeHTML(i18n.loading)}</div>`;
        document.getElementById('soul-picker').innerHTML = `<div class="px-3 py-8 text-center text-sm text-zinc-500">${escapeHTML(i18n.loading)}</div>`;
        document.getElementById('soul-selected-bar').classList.add('hidden');
        document.getElementById('soul-count-badge').textContent = '';

        try {
            const res = await fetch('/api/apps?slug=' + encodeURIComponent(slug));
            const data = await res.json();
            if (!data.success || !data.data) throw new Error(data.error || i18n.failApp);
            const app = data.data;
            currentAppMeta = { title: app.title || '', description: app.description || '' };
            document.getElementById('detail-title').textContent = app.title;
            document.getElementById('detail-desc').textContent = app.description;
            const icon = (app.icon || 'fa-puzzle-piece').replace(/[^a-z0-9-]/gi, '');
            document.getElementById('detail-icon').innerHTML = `<i class="fas ${escapeHTML(icon)}" aria-hidden="true"></i>`;
            renderSoulPicker(app.souls || [], preferredSoulId, preferredTitleSlug);
            const disc = document.getElementById('app-disclaimer');
            if (app.disclaimer) {
                disc.textContent = app.disclaimer;
                disc.classList.remove('hidden');
            } else {
                disc.textContent = '';
                disc.classList.add('hidden');
            }
            form.innerHTML = (app.fields || []).map(renderField).join('');

            // URL soul segment only if we resolved the preferred persona (not the auto-first fallback)
            let urlSoul = null;
            if (preferredSoulId) {
                urlSoul = currentSouls.find(s => s.id === preferredSoulId) || null;
            } else if (preferredTitleSlug) {
                urlSoul = findSoulByTitleSlug(currentSouls, preferredTitleSlug);
            }
            if (preferredSoulId || preferredTitleSlug) {
                syncAppUrl(slug, urlSoul, true);
            } else {
                // App detail only until user explicitly picks a persona
                syncAppUrl(slug, null, replaceUrl);
            }
        } catch (e) {
            form.innerHTML = `<div class="text-red-400 text-sm py-4">${escapeHTML(e.message || i18n.failApp)}</div>`;
        }
    }

    function renderField(f) {
        const id = 'field_' + f.name;
        const req = f.required ? 'required' : '';
        const max = f.maxlength ? `maxlength="${parseInt(f.maxlength, 10)}"` : '';
        const ph = f.placeholder ? `placeholder="${escapeHTML(f.placeholder)}"` : '';
        const label = escapeHTML(f.label || f.name);
        let control = '';
        if (f.type === 'textarea') {
            control = `<textarea id="${id}" name="${escapeHTML(f.name)}" rows="3" ${req} ${max} ${ph}
                class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-400 transition shadow-inner"></textarea>`;
        } else if (f.type === 'select') {
            const opts = (f.options || []).map(o =>
                `<option value="${escapeHTML(o.value)}">${escapeHTML(o.label)}</option>`
            ).join('');
            control = `<select id="${id}" name="${escapeHTML(f.name)}" ${req}
                class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-400 transition shadow-inner appearance-none">${opts}</select>`;
        } else {
            control = `<input type="text" id="${id}" name="${escapeHTML(f.name)}" ${req} ${max} ${ph}
                class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-400 transition shadow-inner">`;
        }
        return `<div>
            <label for="${id}" class="block text-sm font-medium mb-2 text-zinc-300">${label}${f.required ? ' <span class="text-emerald-500">*</span>' : ''}</label>
            ${control}
        </div>`;
    }

    function showCatalog() {
        currentSlug = null;
        selectedSoulId = null;
        document.getElementById('detail-view').classList.add('hidden');
        document.getElementById('catalog-view').classList.remove('hidden');
        const catalogHeader = document.getElementById('catalog-header');
        if (catalogHeader) catalogHeader.classList.remove('hidden');
        const cleanPath = appsBaseUrl;
        if (window.location.pathname.replace(/\/$/, '') !== cleanPath.replace(/\/$/, '')) {
            history.pushState({ appSlug: null }, '', cleanPath);
        }
        applyPageSeo(catalogSeoTitle, catalogSeoDesc, cleanPath);
    }

    document.getElementById('btn-back').addEventListener('click', showCatalog);

    document.getElementById('soul-filter').addEventListener('input', () => {
        paintSoulList();
    });

    document.querySelectorAll('.cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.cat-btn').forEach(b => {
                b.classList.remove('active', 'border-emerald-400/40', 'bg-emerald-500/15', 'text-emerald-300', 'font-medium');
                b.classList.add('border-white/10', 'text-zinc-400');
            });
            btn.classList.add('active', 'border-emerald-400/40', 'bg-emerald-500/15', 'text-emerald-300', 'font-medium');
            btn.classList.remove('border-white/10', 'text-zinc-400');
            activeCategory = btn.getAttribute('data-cat') || '';
            loadApps();
        });
    });

    document.getElementById('apps-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadApps, 250);
    });

    document.getElementById('app-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!currentSlug) return;

        const formErr = document.getElementById('form-error');
        const soulErr = document.getElementById('soul-picker-error');
        formErr.classList.add('hidden');
        soulErr.classList.add('hidden');

        if (!selectedSoulId) {
            soulErr.textContent = i18n.pickSoul;
            soulErr.classList.remove('hidden');
            document.getElementById('soul-picker')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        const fields = {};
        new FormData(e.target).forEach((v, k) => { fields[k] = String(v).trim(); });

        const runBtn = document.getElementById('run-btn');
        const runText = document.getElementById('run-text');
        const runLoading = document.getElementById('run-loading');
        runBtn.disabled = true;
        runText.classList.add('hidden');
        runLoading.classList.remove('hidden');

        try {
            const res = await fetch('/api/apps', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    slug: currentSlug,
                    soul_id: selectedSoulId,
                    fields,
                }),
            });
            const data = await res.json();
            if (!data.success) {
                formErr.textContent = data.error || i18n.failApp;
                formErr.classList.remove('hidden');
                return;
            }

            const sessionToken = randomSessionToken();
            // sessionStorage is per-tab and does NOT cross to window.open targets.
            // Use localStorage + unique key in the URL so the new chat tab can load prefill.
            const prefillKey = 'soulmd_app_prefill_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
            const prefillPayload = JSON.stringify({
                soulId: data.soul_id,
                content: data.content,
                slug: data.slug,
                ts: Date.now(),
            });
            try {
                localStorage.setItem(prefillKey, prefillPayload);
            } catch (err) {
                formErr.textContent = err.message || i18n.network;
                formErr.classList.remove('hidden');
                return;
            }

            // chatBaseUrl is already lang-aware (/chat or /zh/chat)
            // Do NOT pass "noopener" in the features string — it makes open() return null in Chrome,
            // which incorrectly triggered same-tab navigation while still opening a blank tab.
            const chatUrl = chatBaseUrl.replace(/\/$/, '') + '/' + data.soul_id + '/' + sessionToken
                + '?prefill=' + encodeURIComponent(prefillKey);
            const win = window.open(chatUrl, '_blank');
            if (win) {
                try { win.opener = null; } catch (_) {}
                // Stay on the apps page — do not navigate this tab
            } else {
                // True popup block: same-tab fallback (localStorage still works)
                window.location.assign(chatUrl);
            }
        } catch (err) {
            formErr.textContent = err.message || i18n.network;
            formErr.classList.remove('hidden');
        } finally {
            runBtn.disabled = false;
            runText.classList.remove('hidden');
            runLoading.classList.add('hidden');
        }
    });

    /**
     * Parse /apps/{app} and /apps/{app}/{soul-title} (also /zh/apps/...).
     * @returns {{ appSlug: string, soulTitleSlug: string }}
     */
    function pathParts() {
        const path = window.location.pathname.replace(/\/+$/, '');
        const base = appsBaseUrl.replace(/\/+$/, '');
        if (path === base || path.indexOf(base + '/') !== 0) {
            return { appSlug: '', soulTitleSlug: '' };
        }
        const segs = path.slice(base.length + 1).split('/').filter(Boolean);
        const appSlug = segs[0] || '';
        if (!/^[a-zA-Z0-9_-]+$/.test(appSlug)) {
            return { appSlug: '', soulTitleSlug: '' };
        }
        let soulTitleSlug = '';
        if (segs[1]) {
            try {
                soulTitleSlug = decodeURIComponent(segs[1]);
            } catch (_) {
                soulTitleSlug = segs[1];
            }
        }
        return { appSlug, soulTitleSlug };
    }

    function slugFromPath() {
        return pathParts().appSlug;
    }

    window.addEventListener('popstate', () => {
        const parts = pathParts();
        if (parts.appSlug) {
            openApp(parts.appSlug, {
                soulTitleSlug: parts.soulTitleSlug || null,
                replaceUrl: true,
            });
        } else {
            showCatalog();
        }
    });

    // Legacy hash URLs: /apps#/feng-shui → /apps/feng-shui
    const legacyHash = (location.hash || '').replace(/^#\/?/, '');
    if (legacyHash && /^[a-zA-Z0-9_-]+$/.test(legacyHash) && !slugFromPath() && !initialSlug) {
        history.replaceState(null, '', appsBaseUrl + '/' + encodeURIComponent(legacyHash));
    }

    const bootParts = pathParts();
    const bootSlug = initialSlug || bootParts.appSlug || ((location.hash || '').replace(/^#\/?/, '') || '');
    const bootSoulSlug = initialSoulTitleSlug || bootParts.soulTitleSlug || '';
    loadApps().then(() => {
        if (bootSlug && /^[a-zA-Z0-9_-]+$/.test(bootSlug)) {
            openApp(bootSlug, {
                soulId: initialSoulIdPref || null,
                soulTitleSlug: bootSoulSlug || null,
                replaceUrl: true,
            });
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>
