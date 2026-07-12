<?php
/**
 * SoulMD Hub - Dynamic Sitemap.xml
 * (Dynamic i18n SEO Multi-Language Edition)
 * 🚀 Patched: Integrated Grand Unified Docs & Tabs for Complete Search Indexing
 */

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://soulmd-hub.ysk.hk';
global $SUPPORTED_LANGS;

function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

function generateAlternates($baseUrl, $path) {
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

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

$staticPages = [
    '' => ['changefreq' => 'daily', 'priority' => '1.0'],
    'browse' => ['changefreq' => 'daily', 'priority' => '0.9'],
    'marketplace' => ['changefreq' => 'hourly', 'priority' => '0.9'],
    'apps' => ['changefreq' => 'weekly', 'priority' => '0.85'],
    'generate' => ['changefreq' => 'weekly', 'priority' => '0.8'],
    'api-docs' => ['changefreq' => 'monthly', 'priority' => '0.7'],
    'upgrade' => ['changefreq' => 'weekly', 'priority' => '0.8'],
    'login' => ['changefreq' => 'monthly', 'priority' => '0.5'],
    'register' => ['changefreq' => 'monthly', 'priority' => '0.5'],
    // 🌟 新增：全域官方文檔及各自 Tab 子路由節點，完美支援 Google 雙語多態抓取
    'docs' => ['changefreq' => 'weekly', 'priority' => '0.8'],
    'docs/intro' => ['changefreq' => 'weekly', 'priority' => '0.7'],
    'docs/solutions' => ['changefreq' => 'weekly', 'priority' => '0.7'],
    'docs/usecases' => ['changefreq' => 'weekly', 'priority' => '0.7'],
    'docs/future' => ['changefreq' => 'weekly', 'priority' => '0.7']
];

$today = date('Y-m-d');

foreach ($staticPages as $path => $meta) {
    $alternates = generateAlternates($baseUrl, $path);
    
    foreach (array_keys($SUPPORTED_LANGS) as $lang) {
        $langPrefix = ($lang === DEFAULT_LANG) ? '' : '/' . $lang;
        $loc = $baseUrl . $langPrefix . ($path === '' ? '' : '/' . $path);
        
        if ($path === '' && $lang === DEFAULT_LANG) {
             $loc = $baseUrl . '/';
        }
        
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        echo $alternates;
        echo "    <lastmod>" . $today . "</lastmod>\n";
        echo "    <changefreq>" . $meta['changefreq'] . "</changefreq>\n";
        echo "    <priority>" . $meta['priority'] . "</priority>\n";
        echo "  </url>\n";
    }
}

// 🚨 效能修復：使用 LEFT JOIN 衍生表代替 SELECT 中的關聯子查詢，將 O(N^2) 降為 O(N)，防止 MySQL CPU 耗盡
$stmt = $pdo->query("
    SELECT s.id, s.title, s.role, u.username,
           COALESCE(v.max_edited, s.created_at) as last_modified
    FROM souls s 
    JOIN users u ON s.user_id = u.id
    LEFT JOIN (
        SELECT soul_id, MAX(edited_at) as max_edited 
        FROM soul_versions 
        GROUP BY soul_id
    ) v ON s.id = v.soul_id
    WHERE s.is_public = 1 
    ORDER BY last_modified DESC
    LIMIT 50000
");

while ($soul = $stmt->fetch()) {
    $seoPath = "soul/" . rawurlencode($soul['username']) . "/" . $soul['id'] . "/" . makeSlug($soul['role']) . "/" . makeSlug($soul['title']);
    $lastmod = date('Y-m-d', strtotime($soul['last_modified']));
    
    $alternates = generateAlternates($baseUrl, $seoPath);
    
    foreach (array_keys($SUPPORTED_LANGS) as $lang) {
        $langPrefix = ($lang === DEFAULT_LANG) ? '' : '/' . $lang;
        $loc = $baseUrl . $langPrefix . '/' . $seoPath;
        
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        echo $alternates;
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
    }
}

echo '</urlset>';