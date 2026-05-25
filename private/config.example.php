<?php
/**
 * SoulMD Hub - Core Configuration Matrix
 * (Includes Multi-Tier Permissions, PayPal SDK, DeepSeek Core & Together AI Smart Vision Routing)
 */

// ==========================================
// 🗄️ Database & Core Server Environment
// ==========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'ki_soulmd_hub');
define('DB_USER', 'root');
define('DB_PASS', ''); // 🛠️ 請在此填入你的生產環境數據庫密碼
define('DB_CHARSET', 'utf8mb4');
define('BASE_URL', 'https://soulmd-hub.ysk.hk');
define('SITE_NAME', 'SoulMD Hub');
define('SITE_BILLING_EMAIL', 'billing@ysk.hk'); // 帳務對接專用 Email

// 📊 數據統計與第三方追蹤
define('GOOGLE_ANALYTICS_ID', 'YOUR_GOOGLE_ANALYTICS_ID_HERE');

// ==========================================
// 🤖 Pure Text Engine Configuration (DeepSeek API)
// ==========================================
define('DEEPSEEK_API_KEY', 'your_deepseek_api_key_here'); // 🛠️ 請在此填入你的 DeepSeek 官方 API Key
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/chat/completions');

// ==========================================
// 👁️ Vision AI API Configuration (Together AI)
// ==========================================
define('VISION_API_KEY', 'your_together_api_key_here'); 
define('VISION_API_URL', 'https://api.together.xyz/v1/chat/completions');

// 🚨 終極雙軌商業模型分配矩陣 (100% 支援 Together AI Serverless)
// 推薦設定 (質素優先，PRO 用戶專享 Kimi 頂級大腦)：
define('VIP_VISION_MODEL', 'Qwen/Qwen3.5-9B');    // VIP 用 Google 31B 模型 (高質素，成本安全)
define('PRO_VISION_MODEL', 'Qwen/Qwen3.5-9B');     // PRO 用 Kimi K2.6 (最強中文與圖片解析，吸引升級)

// ⚠️ (如果你想極致壓縮成本防破產，可以改為：)
// define('VIP_VISION_MODEL', 'Qwen/Qwen3.5-9B');
// define('PRO_VISION_MODEL', 'google/gemma-4-31B-it');

// ==========================================
// 💳 PayPal Payment Gateway SDK Settings
// ==========================================
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id_here');
define('PAYPAL_SECRET', 'your_paypal_secret_here');
define('PAYPAL_MODE', 'sandbox'); // 🛠️ 測試時保持 'sandbox'，正式上線收錢請改為 'live'

// ==========================================
// 💰 Premium SaaS Tier Pricing Architecture (USD/30 Days)
// ==========================================
define('PRICE_VIP_MONTHLY', '4.99');
define('PRICE_PRO_MONTHLY', '14.99');

// ==========================================
// 🖼️ Client-Side Asset Compression Rules
// ==========================================
define('IMAGE_MAX_DIMENSION', 2000); // 圖片最長邊本機端自動壓縮至 2000px，杜絕極端大圖塞爆帶寬
define('IMAGE_QUALITY', 0.8);        // 畫質本地壓縮率 80% (Canvas Render 消耗零伺服器運算)

// ==========================================
// 🌱 Tier 1: Free (Standard Sandbox Trial / Guests)
// ==========================================
define('FREE_MODEL', 'deepseek-v4-flash'); // 免費用戶分配高效基礎模型
define('FREE_MAX_TURNS', 10);              // 嚴格鎖死單次對話歷史上限 10 句
define('FREE_DAILY_LIMIT', 20);            // 【防惡意刷爆】每日每帳號發言硬上限
define('FREE_MAX_INPUT_CHARS', 100);       // 輸入框字數限制 (最多 100 字)
define('FREE_MAX_AI_TOKENS', 500);         // 限制模型最大輸出長度，節省 API 開銷
define('FREE_MEMORY_THRESHOLD', 10);       // 10 句即觸發上下文滑動 window 壓縮
define('FREE_ALLOW_IMAGE', false);         // 嚴禁上傳圖片功能

// ==========================================
// 🌟 Tier 2: VIP (Standard License Premium Pass)
// ==========================================
define('VIP_MODEL', 'deepseek-v4-flash');  // 純文字沿用高效模型，降低底層開銷
define('VIP_MAX_TURNS', 999999);           // 解鎖完全無限對話來回次數
define('VIP_DAILY_LIMIT', 150);            // 【安全閾值】防自動腳本防爬蟲每日硬上限
define('VIP_MAX_INPUT_CHARS', 1000);       // 放寬用戶輸入長度至 1,000 字元
define('VIP_MAX_AI_TOKENS', 2000);         // 允許 AI 生成長篇大論的高品質內容
define('VIP_MEMORY_THRESHOLD', 20);        // 累積 20 句才進行高保真記憶滾動壓縮
define('VIP_ALLOW_IMAGE', true);           // 🌟 解鎖多模態萬字夾圖片功能 (自動重定向至 VIP_VISION_MODEL)

// ==========================================
// 🔥 Tier 3: PRO (Ultimate Logic Brain & Reasoning Pass)
// ==========================================
define('PRO_MODEL', 'deepseek-v4-pro');    // 🔥 核心賣點：頂級 R1 深度思考與強邏輯推理大腦
define('PRO_MAX_TURNS', 999999);           // 解鎖完全無限對話來回次數
define('PRO_DAILY_LIMIT', 300);            // 極高日用量上限，滿足高強度開發者與極客
define('PRO_MAX_INPUT_CHARS', 3000);       // 支援直接貼上極長源碼或超長篇文案分析
define('PRO_MAX_AI_TOKENS', 4000);         // 容許大模型吐出極長、包含深度思維鏈 (CoT) 的解答
define('PRO_MEMORY_THRESHOLD', 30);        // 最強上下文生命週期，保留高達 30 層記憶網絡才壓縮
define('PRO_ALLOW_IMAGE', true);           // 🔥 支援頂級視覺分析 (自動重定向至 PRO_VISION_MODEL)

// ==========================================
// 🖼️ Client-Side Asset Compression Rules (極致防 Cloudflare Timeout 設定)
// ==========================================
define('IMAGE_MAX_DIMENSION', 800);  // 🚨 由 1120px 降至 800px：大幅縮減解像度，確保長闊不過 800
define('IMAGE_QUALITY', 0.6);        // 🚨 由 0.8 降至 0.6：60% JPEG 壓縮率