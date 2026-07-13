<?php
/**
 * SoulMD Hub - Dynamic robots.txt Generator
 * (SEO friendly - auto reads BASE_URL from config Matrix)
 * 🚀 Step 6: Finalization of Crawler Permissions Layer
 */

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=86400'); // 緩存 24 小時，減輕爬蟲高頻存取負載

require_once __DIR__ . '/../private/src/AppBootstrap.php';
AppBootstrap::loadConfig(false);

$baseUrl = defined('BASE_URL') ? BASE_URL : 'https://soulmd-hub.ysk.hk';

echo "User-agent: *\n";
echo "Allow: /\n";

// 🌟 核心文檔區塊：明確允許搜尋引擎編制索引，提升全站權重 (Domain Authority)
echo "Allow: /docs\n";
echo "Allow: /docs/\n";

// 🚨 安全與私隱防火牆：嚴格禁止搜尋引擎建立私密區域、對話工作階段及無頭 API 的索引
echo "Disallow: /chat/\n";
echo "Disallow: /my-chats\n";
echo "Disallow: /my-souls\n";
echo "Disallow: /my-setting\n";
echo "Disallow: /my-api\n";
echo "Disallow: /billing\n";
echo "Disallow: /invoice\n";
echo "Disallow: /api/\n\n";

// 🌍 Sitemap index (static hub pages + curated high-signal souls only — not all public souls)
$baseUrl = rtrim($baseUrl, '/');
echo "Sitemap: " . $baseUrl . "/sitemap.xml\n";
