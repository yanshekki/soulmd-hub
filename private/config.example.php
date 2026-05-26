<?php
/**
 * SoulMD Hub - Core Configuration Matrix
 * (Includes Multi-Tier Permissions, PayPal SDK, DeepSeek Core & Dynamic i18n Engine)
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
define('SITE_BILLING_EMAIL', 'billing@ysk.hk');

define('GOOGLE_ANALYTICS_ID', 'YOUR_GOOGLE_ANALYTICS_ID_HERE');

// ==========================================
// 🌍 i18n Multi-Language Engine (全域動態擴充架構)
// ==========================================
// 🚨 日後擴充語言，只需在此陣列新增！
global $SUPPORTED_LANGS;
$SUPPORTED_LANGS = [
    'en' => ['label' => 'EN',   'name' => 'English',  'hreflang' => 'en'],
    'zh' => ['label' => '中文', 'name' => '繁體中文', 'hreflang' => 'zh-Hant']
    // 'ja' => ['label' => '日文', 'name' => '日本語',   'hreflang' => 'ja'], // 日後擴充範例
];
define('DEFAULT_LANG', 'en');

$req_lang = $_GET['lang'] ?? '';
$cookie_lang = $_COOKIE['soulmd_lang'] ?? '';

// 判斷當前是否為後端 API 請求
$is_api = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);

$current_lang = DEFAULT_LANG;

if ($is_api) {
    // 🤖 API 請求模式：因為 API 路由沒有語言前綴，所以完全依賴 Cookie 決定回傳語言
    if (array_key_exists($cookie_lang, $SUPPORTED_LANGS)) {
        $current_lang = $cookie_lang;
    }
} else {
    // 🖥️ 網頁渲染模式：URL 是唯一真理 (URL is King)
    if (!empty($req_lang) && array_key_exists($req_lang, $SUPPORTED_LANGS)) {
        $current_lang = $req_lang;
    }
    // 💡 關鍵修復：如果 $req_lang 為空，代表用戶點擊了預設語言 (EN) 的乾淨網址 (例如 /browse)
    // 這時 $current_lang 會維持是 DEFAULT_LANG ('en')，並且會在下方強制覆蓋掉舊的中文 Cookie！
}

// 同步 Cookie，確保接下來的 API 請求能正確對應當前頁面的語言
if ($cookie_lang !== $current_lang) {
    setcookie('soulmd_lang', $current_lang, time() + (86400 * 30), '/'); 
}

define('CURRENT_LANG', $current_lang);

// 全域翻譯陣列儲存
$GLOBALS['i18n_strings'] = [];

/**
 * 載入指定頁面的翻譯檔 (例如: browse)
 */
function loadTranslations($pageName) {
    $langFile = __DIR__ . "/includes/languages/{$pageName}.php";
    if (file_exists($langFile)) {
        $translations = require $langFile;
        
        // 1. 先載入預設語言 (Fallback)
        if (isset($translations[DEFAULT_LANG])) {
            $GLOBALS['i18n_strings'] = array_merge($GLOBALS['i18n_strings'], $translations[DEFAULT_LANG]);
        }
        // 2. 用當前語言覆蓋
        if (CURRENT_LANG !== DEFAULT_LANG && isset($translations[CURRENT_LANG])) {
            $GLOBALS['i18n_strings'] = array_merge($GLOBALS['i18n_strings'], $translations[CURRENT_LANG]);
        }
    }
}

/**
 * 全域翻譯助手函數
 * 支援變數替換: __('hello_name', ['name' => 'Ki'])
 */
function __($key, $replacements = []) {
    $str = $GLOBALS['i18n_strings'][$key] ?? $key;
    if (!empty($replacements)) {
        foreach ($replacements as $k => $v) {
            $str = str_replace(':' . $k, $v, $str);
        }
    }
    // 💡 移除 htmlspecialchars，允許翻譯檔內正常渲染 <br>, <b>, <code> 等 HTML 標籤
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
// 💰 Premium SaaS Tier Pricing Architecture (USD/30 Days)
// ==========================================
define('PRICE_VIP_MONTHLY', '4.99');
define('PRICE_PRO_MONTHLY', '14.99');

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
define('FREE_MAX_INPUT_CHARS', 100);       
define('FREE_MAX_AI_TOKENS', 500);         
define('FREE_MEMORY_THRESHOLD', 10);       
define('FREE_ALLOW_IMAGE', false);         

define('VIP_MODEL', 'deepseek-v4-flash');  
define('VIP_MAX_TURNS', 999999);           
define('VIP_DAILY_LIMIT', 150);            
define('VIP_MAX_INPUT_CHARS', 1000);       
define('VIP_MAX_AI_TOKENS', 2000);         
define('VIP_MEMORY_THRESHOLD', 20);        
define('VIP_ALLOW_IMAGE', true);           

define('PRO_MODEL', 'deepseek-v4-pro');    
define('PRO_MAX_TURNS', 999999);           
define('PRO_DAILY_LIMIT', 300);            
define('PRO_MAX_INPUT_CHARS', 3000);       
define('PRO_MAX_AI_TOKENS', 4000);         
define('PRO_MEMORY_THRESHOLD', 30);        
define('PRO_ALLOW_IMAGE', true);