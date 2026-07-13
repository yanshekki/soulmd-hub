<?php
/**
 * SoulMD Hub - Core Configuration Matrix
 * (Includes Multi-Tier Permissions, PayPal SDK, DeepSeek Core & Dynamic i18n Engine)
 * 🚀 Web2.5 Mainnet AgentFi Production Edition (RPC Nodes Centralized)
 *
 * Entry points: prefer AppBootstrap::forPage() / forApi() (private/src/AppBootstrap.php)
 * instead of hand-rolling require + session_start + loadTranslations + CSRF.
 * After SSE (LlmStreamProxy::beginSse): never session_start / setcookie.
 */

// 🚨 系統級安全加密金鑰 (請務必將下面堆亂碼換成你自己專屬的 32 位元強密碼)
// 此金鑰用於 AES-256 雙向加密，一旦遺失將無法解密所有用戶的 API Key！
define('APP_ENCRYPTION_KEY', 'xK9vP2mN4qL8zR1wT7jY5cB3hF6dG0sA');

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
define('SITE_BILLING_EMAIL', 'billing@ysk.hk');

define('GOOGLE_ANALYTICS_ID', 'YOUR_GOOGLE_ANALYTICS_ID_HERE');

// ==========================================
// 🌐 Web3 & AgentFi Mainnet Network Matrix
// ==========================================
define('NEAR_NETWORK_ID', 'mainnet');
define('NEAR_CONTRACT_ID', 'soulmd-hub.near');         // 🚀 主網智能合約地址
define('NEAR_TOKEN_CONTRACT_ID', 'soul.tkn.near');     // 🚀 $SOUL 代幣合約地址
define('NEAR_REF_FINANCE_ID', 'v2.ref-finance.near');  // Ref Finance 主網 AMM Router 地址
define('NEAR_POOL_ID', 8546);                          // 🚀 流動性池 ID

// 🌟 全域高可用 RPC 備援池 (Failover Nodes)
define('NEAR_RPC_NODES', [
    "https://free.rpc.fastnear.com",   // 極速、無 CORS 限制
    "https://near.lava.build",         // 去中心化高可用
    "https://rpc.mainnet.pagoda.co",   // 官方企業節點
    "https://rpc.mainnet.near.org"     // 官方預設 (最後備用)
]);

// 保留單一 URL 供舊有單節點調用向後相容
define('NEAR_RPC_URL', NEAR_RPC_NODES[0]);

// ==========================================
// 🌍 i18n Multi-Language Engine (全域動態擴充架構)
// ==========================================
global $SUPPORTED_LANGS;
$SUPPORTED_LANGS = [
    'en' => ['label' => 'EN',   'name' => 'English',  'hreflang' => 'en'],
    'zh' => ['label' => '中文', 'name' => '繁體中文', 'hreflang' => 'zh-Hant']
];
define('DEFAULT_LANG', 'en');

$req_lang = $_GET['lang'] ?? '';
$cookie_lang = $_COOKIE['soulmd_lang'] ?? '';

// 判斷當前是否為後端 API 請求
$is_api = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);

$current_lang = DEFAULT_LANG;

if ($is_api) {
    if (array_key_exists($cookie_lang, $SUPPORTED_LANGS)) {
        $current_lang = $cookie_lang;
    }
} else {
    if (!empty($req_lang) && array_key_exists($req_lang, $SUPPORTED_LANGS)) {
        $current_lang = $req_lang;
    }
}

if ($cookie_lang !== $current_lang && !headers_sent()) {
    setcookie('soulmd_lang', $current_lang, time() + (86400 * 30), '/');
}

define('CURRENT_LANG', $current_lang);

// 全域翻譯陣列儲存
$GLOBALS['i18n_strings'] = [];

/**
 * 載入指定頁面的翻譯檔
 */
function loadTranslations($pageName) {
    $langFile = __DIR__ . "/includes/languages/{$pageName}.php";
    if (file_exists($langFile)) {
        $translations = require $langFile;
        
        if (isset($translations[DEFAULT_LANG])) {
            $GLOBALS['i18n_strings'] = array_merge($GLOBALS['i18n_strings'], $translations[DEFAULT_LANG]);
        }
        if (CURRENT_LANG !== DEFAULT_LANG && isset($translations[CURRENT_LANG])) {
            $GLOBALS['i18n_strings'] = array_merge($GLOBALS['i18n_strings'], $translations[CURRENT_LANG]);
        }
    }
}

/**
 * 全域翻譯助手函數
 */
function __($key, $replacements = []) {
    $str = $GLOBALS['i18n_strings'][$key] ?? $key;
    if (!empty($replacements)) {
        foreach ($replacements as $k => $v) {
            $str = str_replace(':' . $k, $v, $str);
        }
    }
    return $str;
}

/**
 * URL 語言前綴助手函數
 */
function url($path) {
    $path = ltrim($path, '/');
    if (CURRENT_LANG === DEFAULT_LANG) {
        return '/' . $path;
    }
    return '/' . CURRENT_LANG . '/' . $path;
}

// ==========================================
// 🤖 Pure Text Engine Configuration (DeepSeek API)
// ==========================================
define('DEEPSEEK_API_KEY', 'your_deepseek_api_key_here'); 
define('DEEPSEEK_API_URL', 'https://api.deepseek.com/chat/completions');

// ==========================================
// 👁️ Vision AI API Configuration (Together AI)
// ==========================================
define('VISION_API_KEY', 'your_together_api_key_here'); 
define('VISION_API_URL', 'https://api.together.xyz/v1/chat/completions');

define('VIP_VISION_MODEL', 'Qwen/Qwen3.5-9B');    
define('PRO_VISION_MODEL', 'Qwen/Qwen3.5-9B');    

// ==========================================
// 💳 PayPal Payment Gateway SDK Settings
// ==========================================
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id_here');
define('PAYPAL_SECRET', 'your_paypal_secret_here');
define('PAYPAL_MODE', 'sandbox'); 

// ==========================================
// 🪙 NEAR FT On-chain Payment Tokens (USDT / USDC for upgrade.php)
// These replace or supplement PayPal for tier upgrades.
// IMPORTANT: 
// 1. Update the values in contract/src/contract.ts (USDT_CONTRACT / USDC_CONTRACT) 
//    to match BEFORE running `npm run build` in the contract directory.
// 2. The platform account (soulmd-hub.near) MUST call storage_deposit on both token contracts
//    once (one-time ~0.00125 NEAR per token) so the contract can receive FTs as NEP-141 receiver.
// Always verify the latest mainnet addresses on https://explorer.near.org
// ==========================================
define('NEAR_USDT_CONTRACT', 'usdt.tether-token.near');
define('NEAR_USDC_CONTRACT', '17208628f84f5d6ad33f0da3bbbeb27ffcb398eac501a31bd6ad2011e36133a1');

// ==========================================
// 💰 Premium SaaS Tier Pricing Architecture (USD/30 Days)
// ==========================================
// PayPal + display prices. NEAR_UPGRADE_* aliases the same values.
// ⚠️ Changing prices requires redeploying NEAR contract ft_on_transfer thresholds
// (hardcoded 4_990_000 / 14_990_000 raw units = $4.99 / $14.99 @ 6 decimals).
define('PRICE_VIP_MONTHLY', '4.99');
define('PRICE_PRO_MONTHLY', '14.99');

// NEAR on-chain upgrade amounts (same USD; JS multiplies by 1e6 for FT units)
define('NEAR_UPGRADE_VIP_USD_AMOUNT', PRICE_VIP_MONTHLY);
define('NEAR_UPGRADE_PRO_USD_AMOUNT', PRICE_PRO_MONTHLY);

// ==========================================
// 🖼️ Client-Side Asset Compression Rules
// ==========================================
define('IMAGE_MAX_DIMENSION', 800);  
define('IMAGE_QUALITY', 0.6);        

// ==========================================
// 🌱 Tier Limits
// ==========================================
define('FREE_MODEL', 'deepseek-v4-flash'); 
define('FREE_MAX_TURNS', 10);              
define('FREE_DAILY_LIMIT', 20);            
define('FREE_MAX_INPUT_CHARS', 1000);       
define('FREE_MAX_AI_TOKENS', 2000);         
define('FREE_MEMORY_THRESHOLD', 10);       
define('FREE_ALLOW_IMAGE', false);         

define('VIP_MODEL', 'deepseek-v4-flash');  
define('VIP_MAX_TURNS', 999999);           
define('VIP_DAILY_LIMIT', 150);            
define('VIP_MAX_INPUT_CHARS', 3000);       
define('VIP_MAX_AI_TOKENS', 5000);         
define('VIP_MEMORY_THRESHOLD', 20);        
define('VIP_ALLOW_IMAGE', true);           

define('PRO_MODEL', 'deepseek-v4-pro');    
define('PRO_MAX_TURNS', 999999);           
define('PRO_DAILY_LIMIT', 300);            
define('PRO_MAX_INPUT_CHARS', 8000);       
define('PRO_MAX_AI_TOKENS', 10000);         
define('PRO_MEMORY_THRESHOLD', 30);        
define('PRO_ALLOW_IMAGE', true);

// Mini Apps: themes live in MiniAppsCatalog with search_keywords.
// Souls are discovered via public search (no MINI_APP_SOUL_MAP).