<?php
/**
 * SoulMD Hub - Dynamic Sitemap.xml
 * (Dynamic i18n SEO Multi-Language Edition)
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

// 🌍 Hreflang 交叉編譯助手函數：為每一個網址產生全語系映射
function generateAlternates($baseUrl, $path) {
    global $SUPPORTED_LANGS;
    $links = '';
    foreach ($SUPPORTED_LANGS as $lang => $meta) {
        $langPrefix = ($lang === DEFAULT_LANG) ? '' : '/' . $lang;
        $href = $baseUrl . $langPrefix . ($path === '' ? '' : '/' . $path);
        
        // 針對預設首頁進行結尾斜線處理
        if ($path === '' && $lang === DEFAULT_LANG) {
             $href = $baseUrl . '/';
        }
        
        $links .= '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($meta['hreflang']) . '" href="' . htmlspecialchars($href) . '" />' . "\n";
        
        // 將預設語言 (EN) 標記為 x-default
        if ($lang === DEFAULT_LANG) {
            $links .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($href) . '" />' . "\n";
        }
    }
    return $links;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
// 🚨 必須在根節點宣告 xhtml 命名空間，否則 Google Search Console 會報錯
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

$staticPages = [
    '' => ['changefreq' => 'daily', 'priority' => '1.0'],
    'browse' => ['changefreq' => 'daily', 'priority' => '0.9'],
    'marketplace' => ['changefreq' => 'hourly', 'priority' => '0.9'], 
    'generate' => ['changefreq' => 'weekly', 'priority' => '0.8'],
    'api-docs' => ['changefreq' => 'monthly', 'priority' => '0.7'],
    'upgrade' => ['changefreq' => 'weekly', 'priority' => '0.8'],
    'login' => ['changefreq' => 'monthly', 'priority' => '0.5'],
    'register' => ['changefreq' => 'monthly', 'priority' => '0.5']
];

$today = date('Y-m-d');

// 1. 輸出靜態頁面 (Homepage, Browse, Upgrade) 嘅雙語版本
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

// 2. 輸出資料庫中所有公開靈魂模型 (Souls) 嘅雙語版本
$stmt = $pdo->query("
    SELECT s.id, s.title, s.role, u.username,
           COALESCE((SELECT MAX(edited_at) FROM soul_versions v WHERE v.soul_id = s.id), s.created_at) as last_modified
    FROM souls s 
    JOIN users u ON s.user_id = u.id
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