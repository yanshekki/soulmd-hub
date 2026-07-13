<?php
/**
 * SoulMD Hub - SEO sitemaps (quality-first).
 *
 * /sitemap.xml              → sitemapindex
 * /sitemap-static.xml       → hub pages, docs, mini app shells
 * /sitemap-apps-souls.xml   → /apps/{slug}/{soul-title} theme pairings
 * /sitemap-souls.xml        → small set of high-signal public soul pages
 *
 * Why: dumping tens of thousands of thin public souls burns crawl budget
 * and tanks Search Console indexed/discovered ratio.
 */

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/../private/src/AppBootstrap.php';
require_once __DIR__ . '/../private/src/MiniAppsCatalog.php';

AppBootstrap::loadConfig(false);
require_once __DIR__ . '/../private/src/Database.php';

$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://soulmd-hub.ysk.hk';
global $SUPPORTED_LANGS;

/** Max curated public souls in the souls sitemap (both languages still listed). */
const SITEMAP_SOUL_LIMIT = 400;
/** Minimum content length (chars) to be considered non-thin. */
const SITEMAP_MIN_CONTENT_LEN = 1000;
/** Theme-matched souls per mini app for /apps/{slug}/{title-slug} URLs. */
const SITEMAP_APP_SOULS_PER_APP = 8;

$part = isset($_GET['part']) ? strtolower(trim((string)$_GET['part'])) : '';
// Allow clean paths rewritten as ?part=
if ($part === '' && !empty($_SERVER['REQUEST_URI'])) {
    $uri = (string)$_SERVER['REQUEST_URI'];
    if (preg_match('#sitemap-static\.xml#', $uri)) {
        $part = 'static';
    } elseif (preg_match('#sitemap-apps-souls\.xml#', $uri) || preg_match('#sitemap-app-souls\.xml#', $uri)) {
        $part = 'apps-souls';
    } elseif (preg_match('#sitemap-souls\.xml#', $uri)) {
        $part = 'souls';
    }
}

if ($part === '' || $part === 'index') {
    emitSitemapIndex($baseUrl);
    exit;
}

if ($part === 'static') {
    emitStaticUrlset($baseUrl);
    exit;
}

if ($part === 'apps-souls' || $part === 'app-souls') {
    emitAppsSoulsUrlset($baseUrl);
    exit;
}

if ($part === 'souls') {
    emitSoulsUrlset($baseUrl);
    exit;
}

// Unknown part → index
emitSitemapIndex($baseUrl);
exit;

// ---------------------------------------------------------------------------

function makeSlug($str): string
{
    if ($str === null || $str === '') {
        return 'unassigned';
    }
    $str = mb_strtolower((string)$str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

function generateAlternates(string $baseUrl, string $path): string
{
    global $SUPPORTED_LANGS;
    $links = '';
    foreach ($SUPPORTED_LANGS as $lang => $meta) {
        $langPrefix = ($lang === DEFAULT_LANG) ? '' : '/' . $lang;
        $href = $baseUrl . $langPrefix . ($path === '' ? '' : '/' . $path);
        if ($path === '' && $lang === DEFAULT_LANG) {
            $href = $baseUrl . '/';
        }
        $links .= '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($meta['hreflang']) . '" href="' . htmlspecialchars($href) . '" />' . "\n";
        if ($lang === DEFAULT_LANG) {
            $links .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($href) . '" />' . "\n";
        }
    }
    return $links;
}

function emitUrl(string $loc, string $alternates, string $lastmod, string $changefreq, string $priority): void
{
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($loc) . "</loc>\n";
    echo $alternates;
    echo '    <lastmod>' . htmlspecialchars($lastmod) . "</lastmod>\n";
    echo '    <changefreq>' . htmlspecialchars($changefreq) . "</changefreq>\n";
    echo '    <priority>' . htmlspecialchars($priority) . "</priority>\n";
    echo "  </url>\n";
}

function emitSitemapIndex(string $baseUrl): void
{
    $today = date('Y-m-d');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (['sitemap-static.xml', 'sitemap-apps-souls.xml', 'sitemap-souls.xml'] as $file) {
        echo "  <sitemap>\n";
        echo '    <loc>' . htmlspecialchars($baseUrl . '/' . $file) . "</loc>\n";
        echo '    <lastmod>' . $today . "</lastmod>\n";
        echo "  </sitemap>\n";
    }
    echo '</sitemapindex>';
}

function staticPagesList(): array
{
    // High-intent hub pages only (no login/register — low SEO value)
    $pages = [
        '' => ['changefreq' => 'daily', 'priority' => '1.0'],
        'browse' => ['changefreq' => 'daily', 'priority' => '0.9'],
        'marketplace' => ['changefreq' => 'daily', 'priority' => '0.85'],
        'apps' => ['changefreq' => 'weekly', 'priority' => '0.9'],
        'generate' => ['changefreq' => 'weekly', 'priority' => '0.7'],
        'api-docs' => ['changefreq' => 'monthly', 'priority' => '0.65'],
        'upgrade' => ['changefreq' => 'weekly', 'priority' => '0.75'],
        'docs' => ['changefreq' => 'weekly', 'priority' => '0.8'],
        'docs/intro' => ['changefreq' => 'weekly', 'priority' => '0.7'],
        'docs/solutions' => ['changefreq' => 'weekly', 'priority' => '0.7'],
        'docs/usecases' => ['changefreq' => 'weekly', 'priority' => '0.7'],
        'docs/future' => ['changefreq' => 'weekly', 'priority' => '0.7'],
    ];

    foreach (MiniAppsCatalog::allRaw() as $app) {
        if (empty($app['enabled']) || empty($app['slug'])) {
            continue;
        }
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$app['slug']);
        if ($slug === '') {
            continue;
        }
        // Intent pages: mini apps beat random souls for rankings
        $pages['apps/' . $slug] = ['changefreq' => 'weekly', 'priority' => '0.8'];
    }

    return $pages;
}

function emitStaticUrlset(string $baseUrl): void
{
    global $SUPPORTED_LANGS;
    $today = date('Y-m-d');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    foreach (staticPagesList() as $path => $meta) {
        $alternates = generateAlternates($baseUrl, $path);
        foreach (array_keys($SUPPORTED_LANGS) as $lang) {
            $langPrefix = ($lang === DEFAULT_LANG) ? '' : '/' . $lang;
            $loc = $baseUrl . $langPrefix . ($path === '' ? '' : '/' . $path);
            if ($path === '' && $lang === DEFAULT_LANG) {
                $loc = $baseUrl . '/';
            }
            emitUrl($loc, $alternates, $today, $meta['changefreq'], $meta['priority']);
        }
    }

    echo '</urlset>';
}

/**
 * Intent URLs: /apps/{mini-app}/{soul-title-slug}
 * One curated pairing per theme soul — high commercial intent for SEO.
 */
function emitAppsSoulsUrlset(string $baseUrl): void
{
    global $SUPPORTED_LANGS;

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    $today = date('Y-m-d');
    $perApp = max(1, min(20, (int)SITEMAP_APP_SOULS_PER_APP));

    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        foreach (MiniAppsCatalog::allRaw() as $app) {
            if (empty($app['enabled']) || empty($app['slug'])) {
                continue;
            }
            $appSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$app['slug']);
            if ($appSlug === '') {
                continue;
            }

            $keywords = MiniAppsCatalog::searchKeywordsForApp($app);
            // searchPublicSouls caps at 50; request a bit more then take top N unique title-slugs
            $souls = MiniAppsCatalog::searchPublicSouls($pdo, $keywords, min(50, max($perApp * 2, $perApp)));
            if ($souls === []) {
                continue;
            }

            $seenSlugs = [];
            $emitted = 0;
            foreach ($souls as $soul) {
                if ($emitted >= $perApp) {
                    break;
                }
                if (!is_array($soul)) {
                    continue;
                }
                $title = trim((string)($soul['title'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $titleSlug = MiniAppsCatalog::titleToSlug($title);
                if ($titleSlug === '' || $titleSlug === 'soul') {
                    continue;
                }
                // Dedupe by title-slug within this app
                if (isset($seenSlugs[$titleSlug])) {
                    continue;
                }
                $seenSlugs[$titleSlug] = true;

                // Path segment: encode for safe loc; apps.php rawurldecodes on resolve
                $path = 'apps/' . $appSlug . '/' . rawurlencode($titleSlug);
                $alternates = generateAlternates($baseUrl, $path);

                $likes = (int)($soul['like_count'] ?? 0);
                $forks = (int)($soul['fork_count'] ?? 0);
                // Above bare soul pages, below app hub shell
                $priority = ($likes + $forks >= 3) ? '0.72' : '0.68';

                foreach (array_keys($SUPPORTED_LANGS) as $lang) {
                    $langPrefix = ($lang === DEFAULT_LANG) ? '' : '/' . $lang;
                    $loc = $baseUrl . $langPrefix . '/' . $path;
                    emitUrl($loc, $alternates, $today, 'weekly', $priority);
                }
                $emitted++;
            }
        }
    } catch (Throwable $e) {
        error_log('sitemap apps-souls error: ' . $e->getMessage());
    }

    echo '</urlset>';
}

function emitSoulsUrlset(string $baseUrl): void
{
    global $SUPPORTED_LANGS;

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();

        // Curated public non-NFT souls only:
        // - min content length (avoid thin shells)
        // - rank by engagement + content size
        // - hard cap for crawl budget
        $limit = (int)SITEMAP_SOUL_LIMIT;
        $minLen = (int)SITEMAP_MIN_CONTENT_LEN;

        $sql = "
            SELECT s.id, s.title, s.role, u.username,
                   s.like_count, s.fork_count,
                   COALESCE(v.max_edited, s.created_at) AS last_modified
            FROM souls s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN (
                SELECT soul_id, MAX(edited_at) AS max_edited
                FROM soul_versions
                GROUP BY soul_id
            ) v ON s.id = v.soul_id
            WHERE s.is_public = 1
              AND (s.is_nft = 0 OR s.is_nft IS NULL)
              AND CHAR_LENGTH(s.content) >= :min_len
              AND (
                    s.like_count >= 1
                 OR s.fork_count >= 1
                 OR CHAR_LENGTH(s.content) >= 2000
                 OR CHAR_LENGTH(COALESCE(s.description, '')) >= 60
              )
            ORDER BY
                (COALESCE(s.like_count, 0) * 3 + COALESCE(s.fork_count, 0) * 5) DESC,
                CHAR_LENGTH(s.content) DESC,
                last_modified DESC
            LIMIT {$limit}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':min_len', $minLen, PDO::PARAM_INT);
        $stmt->execute();

        while ($soul = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $seoPath = 'soul/' . rawurlencode((string)$soul['username']) . '/' . (int)$soul['id']
                . '/' . makeSlug($soul['role']) . '/' . makeSlug($soul['title']);
            $lastmod = date('Y-m-d', strtotime((string)$soul['last_modified']));
            $alternates = generateAlternates($baseUrl, $seoPath);

            // Lower priority than hub / apps so Google spends crawl on money pages first
            $likes = (int)($soul['like_count'] ?? 0);
            $forks = (int)($soul['fork_count'] ?? 0);
            $priority = ($likes + $forks >= 5) ? '0.55' : '0.45';

            foreach (array_keys($SUPPORTED_LANGS) as $lang) {
                $langPrefix = ($lang === DEFAULT_LANG) ? '' : '/' . $lang;
                $loc = $baseUrl . $langPrefix . '/' . $seoPath;
                emitUrl($loc, $alternates, $lastmod, 'weekly', $priority);
            }
        }
    } catch (Throwable $e) {
        // Fail soft: empty souls set is better than a broken index
        error_log('sitemap souls error: ' . $e->getMessage());
    }

    echo '</urlset>';
}
