<?php
/**
 * SoulMD Hub - Configuration Template
 * * 1. Copy this file to config.php
 * 2. Fill in your MySQL credentials
 * 3. Never commit config.php to git
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'soulmd_hub');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('BASE_URL', 'https://soulmd-hub.ysk.hk');
define('SITE_NAME', 'SoulMD Hub');

// ==========================================
// DeepSeek API Configuration
// ==========================================
define('DEEPSEEK_API_KEY', 'your_deepseek_api_key_here');
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/chat/completions');
define('DEEPSEEK_MODEL', 'deepseek-v4-flash');

// ==========================================
// AI 對話介面與免費試用參數限制
// ==========================================
define('MAX_FREE_TURNS', 100);  // 🚨 免費試用次數上限（用戶最多可發言幾多次）
define('MAX_AI_TOKENS', 2000);   // 🚨 AI 每次回覆的最大 Token 限制
define('MAX_INPUT_CHARS', 500); // 🚨 用戶每次輸入的最大字元限制