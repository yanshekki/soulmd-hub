<?php
/**
 * SoulMD Hub - Configuration
 * (Includes Multi-Tier Permissions, PayPal, Vision AI & Rate Limits)
 */

// ==========================================
// 🗄️ Database & Core
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'ki_soulmd_hub');
define('DB_USER', 'root');
define('DB_PASS', ''); // 記得改返你嘅密碼
define('DB_CHARSET', 'utf8mb4');
define('BASE_URL', 'https://soulmd-hub.ysk.hk');
define('SITE_NAME', 'SoulMD Hub');

define('GOOGLE_ANALYTICS_ID', 'YOUR_GOOGLE_ANALYTICS_ID_HERE');

// ==========================================
// 🤖 DeepSeek API Configuration
// ==========================================
define('DEEPSEEK_API_KEY', 'your_deepseek_api_key_here'); // 記得填 API Key
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/chat/completions');

// ==========================================
// 💳 PayPal 支付設定
// ==========================================
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id_here');
define('PAYPAL_SECRET', 'your_paypal_secret_here');
define('PAYPAL_MODE', 'sandbox'); // 測試時用 'sandbox'，上線改為 'live'

// ==========================================
// 💰 會員定價設定 (USD/月)
// ==========================================
define('PRICE_VIP_MONTHLY', '4.99');
define('PRICE_PRO_MONTHLY', '14.99');

// ==========================================
// 🖼️ 圖片上傳限制 (前端 JS 視覺壓縮用)
// ==========================================
define('IMAGE_MAX_DIMENSION', 2000); // 圖片最長邊壓縮至不超過 2000px
define('IMAGE_QUALITY', 0.8);        // 壓縮畫質 80%

// ==========================================
// 🌱 Tier 1: Free (訪客 / 免費用戶)
// ==========================================
define('FREE_MODEL', 'deepseek-v4-flash'); // 最新 Flash 模型代號
define('FREE_MAX_TURNS', 10);          // 單次對話來回上限 (10 句鎖死)
define('FREE_DAILY_LIMIT', 20);        // 【防破產】每日發言硬上限
define('FREE_MAX_INPUT_CHARS', 100);   // 最多 100 字元輸入
define('FREE_MAX_AI_TOKENS', 500);     // 限制 AI 短回覆
define('FREE_MEMORY_THRESHOLD', 10);   // 10 句即觸發記憶壓縮
define('FREE_ALLOW_IMAGE', false);     // 不允許上傳圖片

// ==========================================
// 🌟 Tier 2: VIP (高級會員)
// ==========================================
define('VIP_MODEL', 'deepseek-v4-flash');  
define('VIP_MAX_TURNS', 999999);       // 無限對話
define('VIP_DAILY_LIMIT', 150);        // 【防破產】防腳本每日硬上限
define('VIP_MAX_INPUT_CHARS', 1000);   // 放寬輸入字數
define('VIP_MAX_AI_TOKENS', 2000);     // 容許 AI 長回覆
define('VIP_MEMORY_THRESHOLD', 20);    // 保留更多細節才壓縮
define('VIP_ALLOW_IMAGE', true);       // 🌟 解鎖圖片視覺功能

// ==========================================
// 🔥 Tier 3: PRO (尊貴會員)
// ==========================================
define('PRO_MODEL', 'deepseek-v4-pro'); // 🔥 核心賣點：頂級 R1 深度思考模型
define('PRO_MAX_TURNS', 999999);          // 無限對話
define('PRO_DAILY_LIMIT', 300);           // 【防破產】極高用量上限
define('PRO_MAX_INPUT_CHARS', 3000);      // 支援極長篇大論
define('PRO_MAX_AI_TOKENS', 4000);        // AI 深度思考極長回覆
define('PRO_MEMORY_THRESHOLD', 30);       // 最強上下文記憶
define('PRO_ALLOW_IMAGE', true);          // 🔥 支援多模態圖片分析