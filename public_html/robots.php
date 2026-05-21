<?php
/**
 * SoulMD Hub - Dynamic robots.txt
 * SEO friendly - auto reads BASE_URL from config
 */

// 告訴瀏覽器和搜尋引擎這是一個純文字檔案
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../private/config.php';

// 自動讀取 config.php 內定義的 BASE_URL，若未定義則使用預設值
$baseUrl = defined('BASE_URL') ? BASE_URL : 'https://soulmd-hub.ysk.hk';

echo "User-agent: *\n";
echo "Allow: /\n\n";

// 動態生成 Sitemap 網址
echo "Sitemap: " . $baseUrl . "/sitemap.xml\n";