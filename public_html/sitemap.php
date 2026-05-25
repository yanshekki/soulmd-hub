<?php
/**
 * SoulMD Hub - Dynamic Sitemap.xml
 */

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$baseUrl = defined('BASE_URL') ? BASE_URL : 'https://soulmd-hub.ysk.hk';

function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

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

// Upgrade (Pricing) page
echo '  <url>' . "\n";
echo '    <loc>' . $baseUrl . '/upgrade</loc>' . "\n";
echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
echo '    <changefreq>weekly</changefreq>' . "\n";
echo '    <priority>0.9</priority>' . "\n";
echo '  </url>' . "\n";

// Souls Pages
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
    $seoPath = "/soul/" . rawurlencode($soul['username']) . "/" . $soul['id'] . "/" . makeSlug($soul['role']) . "/" . makeSlug($soul['title']);
    $url = $baseUrl . $seoPath;
    
    $lastmod = date('Y-m-d', strtotime($soul['last_modified']));
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
    echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>';