<?php
/**
 * Dynamic Sitemap.xml for SoulMD Hub
 * Access: https://soulmd-hub.ysk.hk/sitemap.xml
 */

header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Get all public souls
$stmt = $pdo->query("SELECT id, title, created_at FROM souls WHERE is_public = 1 ORDER BY created_at DESC");
$souls = $stmt->fetchAll();

$baseUrl = 'https://soulmd-hub.ysk.hk';

 echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Homepage
echo '<url><loc>' . $baseUrl . '</loc><changefreq>daily</changefreq><priority>1.0</priority></url>';

// Browse page
echo '<url><loc>' . $baseUrl . '/browse</loc><changefreq>daily</changefreq><priority>0.9</priority></url>';

// All public souls
foreach ($souls as $soul) {
    $url = $baseUrl . '/soul/' . $soul['id'];
    $lastmod = date('Y-m-d', strtotime($soul['created_at']));
    echo '<url>';
    echo '<loc>' . htmlspecialchars($url) . '</loc>';
    echo '<lastmod>' . $lastmod . '</lastmod>';
    echo '<changefreq>weekly</changefreq>';
    echo '<priority>0.8</priority>';
    echo '</url>';
}

echo '</urlset>';