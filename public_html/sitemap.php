<?php
/**
 * SoulMD Hub - Dynamic Sitemap.xml
 * SEO friendly - auto includes up to 50,000 recent public souls with precise Last Modified dates
 */

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// 自動讀取 config.php 內定義的 BASE_URL，若未定義則使用預設值作為安全後備
$baseUrl = defined('BASE_URL') ? BASE_URL : 'https://soulmd-hub.ysk.hk';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Homepage
echo '  <url>' . "\n";
echo '    <loc>' . $baseUrl . '</loc>' . "\n";
echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
echo '    <changefreq>daily</changefreq>' . "\n";
echo '    <priority>1.0</priority>' . "\n";
echo '  </url>' . "\n";

// Browse page
echo '  <url>' . "\n";
echo '    <loc>' . $baseUrl . '/browse</loc>' . "\n";
echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
echo '    <changefreq>daily</changefreq>' . "\n";
echo '    <priority>0.9</priority>' . "\n";
echo '  </url>' . "\n";

// Recent 50,000 public souls (達到 Google Sitemap 單一檔案最大上限)
$stmt = $pdo->query("
    SELECT id, 
           COALESCE((SELECT MAX(edited_at) FROM soul_versions v WHERE v.soul_id = souls.id), created_at) as last_modified
    FROM souls 
    WHERE is_public = 1 
    ORDER BY last_modified DESC
    LIMIT 50000
");

while ($soul = $stmt->fetch()) {
    $url = $baseUrl . '/soul/' . $soul['id'];
    $lastmod = date('Y-m-d', strtotime($soul['last_modified']));
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
    echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>';