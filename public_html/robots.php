<?php
/**
 * SoulMD Hub - Dynamic robots.txt
 * SEO friendly - auto reads BASE_URL from config
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../private/config.php';

$baseUrl = defined('BASE_URL') ? BASE_URL : 'https://soulmd-hub.ysk.hk';

echo "User-agent: *\n";
echo "Allow: /\n";

// 🚨 Prevent search engines from indexing private areas & APIs
echo "Disallow: /chat/\n";
echo "Disallow: /my-chats\n";
echo "Disallow: /my-souls\n";
echo "Disallow: /my-setting\n";
echo "Disallow: /my-api\n";
echo "Disallow: /billing\n";
echo "Disallow: /invoice\n";
echo "Disallow: /api/\n\n";

echo "Sitemap: " . $baseUrl . "/sitemap.xml\n";