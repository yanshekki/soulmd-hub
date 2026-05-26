<?php
/**
 * SoulMD Hub - i18n Translation Dictionary
 * Target: api-docs.php (API Reference Component)
 */

return [
    'en' => [
        'API Reference' => 'API Reference',
        'Download Postman Collection' => 'Download Postman Collection',
        'View Request Body Sample' => 'View Request Body Sample',
        'View Response Sample' => 'View Response Sample',
        'Auth Required' => 'Auth Required',
        
        // Sections
        'Authentication & Account' => 'Authentication & Account',
        'Interaction & Chat Engine' => 'Interaction & Chat Engine',
        'Core Souls Hub' => 'Core Souls Hub',
        'Profiles & Social Interactions' => 'Profiles & Social Interactions',
        'Internal Web Utilities' => 'Internal Web Utilities',
        
        // Endpoint Descriptions
        'desc_register' => 'Register a new user and generate an API key. Enforces secure alpha-numeric URL constraints.',
        'desc_login' => 'Authenticate user. Returns API Key and sets a secure 30-day web session if requested.',
        'desc_change_password' => 'Change the current logged-in user\'s password securely.',
        'desc_chat_get' => 'Headless API access to retrieve conversation history. Strict permission controls prevent accessing private sessions.',
        'desc_chat_post' => 'Headless API access to interact with the core routing engine. Send messages, optionally attach base64 images (Vision AI), and receive responses. Free tier requests to this endpoint will be strictly rejected with a 403 Forbidden status.',
        'desc_categories' => 'Fetch the complete white-list of roles/categories including their corresponding slug names and emoji icons.',
        'desc_souls_get' => 'List, search and filter public souls. Optimized with strict DB select limits.',
        'desc_soul_single' => 'Retrieve raw architecture files, tags, and stats of a single public or owned soul.',
        'desc_souls_post' => 'Publish a brand new AI agent. Automatically detects single .md prompt or full Modular configuration folders.',
        'desc_soul_put' => 'Update an existing soul module layout. Automatically creates an incremental version timeline backup record.',
        'desc_soul_delete' => 'Permanently delete a soul architecture configuration and gracefully updates relational metadata tracking statistics.',
        'desc_profile' => 'Fetch public indicators (aggregated likes, forks, total models) and public soul array mapping for any developer.',
        'desc_versions_get' => 'Retrieve full historical rollback archive versions of a soul. Protected by strict IDOR multi-tenant permission validation check.',
        'desc_versions_post' => 'Instantly restore active state content layout to a historical milestone setup version identifier point.',
        'desc_fork' => 'Clone a public agent model directly into your workspace account as an independent project fork tree line node.',
        'desc_like' => 'Toggle like/unlike state. Enforces atomic uniqueness index mapping constraints. Returns boolean state indicating if currently liked.',
        'desc_rate' => 'Rate between 1 to 5 stars. Submitting again overrides previous row entry record. Returns updated global live averages for instant interface refresh.',
        
        // Extra Warnings & Tooltips
        'Query params:' => 'Query params:',
        'Subscription Policy:' => 'Subscription Policy:',
        'sub_policy_text' => 'Direct API integration requires an active VIP or PRO license. If your subscription period expires, your integration will be automatically disabled until a renewal is processed.',
        'CRITICAL CONSTRAINT:' => 'CRITICAL CONSTRAINT:',
        'constraint_text' => 'The <code>role</code> field inside the request body MUST strictly use one of the <code>slug</code> values provided by the <code>/api/categories</code> API. Invalid roles will be forcefully fallbacked to \'Other\'.',
        'internal_utils_notice' => 'Note: The following endpoints rely on browser Session Cookies and cannot be authenticated via API Keys. They are excluded from the Postman Collection.',
        'desc_logout' => 'Clear session and remember me tokens for the current browser session.',
        'desc_regen_key' => 'Invalidates the current API key and issues a new 32-byte hex key securely to the logged-in user.',
        'desc_save_preset' => 'Internal endpoint to temporarily save generated AI layouts into current user\'s session cache memory.',
        'View Response Sample (file_type: single_md)' => 'View Response Sample (file_type: single_md)',
        'View Response Sample (file_type: full_soul_folder)' => 'View Response Sample (file_type: full_soul_folder)',
    ],
    
    'zh' => [
        'API Reference' => '開發者 API 說明文檔',
        'Download Postman Collection' => '下載 Postman 測試集 (.json)',
        'View Request Body Sample' => '檢視請求 JSON 欄體範例',
        'View Response Sample' => '檢視回應 JSON 範例',
        'Auth Required' => '需 Bearer 密鑰認證',
        
        // Sections
        'Authentication & Account' => '使用者認證與安全帳號 API',
        'Interaction & Chat Engine' => '核心對話與大模型路由引擎 API',
        'Core Souls Hub' => '模型資產管理 API (Souls Hub)',
        'Profiles & Social Interactions' => '創作者主頁與社群互動 API',
        'Internal Web Utilities' => '前端瀏覽器專用內部工具 (Cookie 依賴)',
        
        // Endpoint Descriptions
        'desc_register' => '註冊新創作者帳號並自動發配 Secret API Key。內部強化網址安全規範校驗。',
        'desc_login' => '使用者身份驗證。成功後回傳開發者金鑰，並可依要求自動綁定 30 天 Cookie 保持登入狀態。',
        'desc_change_password' => '安全地變更當前登入使用者的系統密碼。',
        'desc_chat_get' => '以無頭（Headless）端遠端查詢指定對話工作階段的歷史紀錄訊息陣列。受權限隔離安全機制保護。',
        'desc_chat_post' => '遠端發送對話呼叫。系統會自動調度智能雙引擎（純文字投遞 DeepSeek，帶圖片投遞 Together AI 視覺模態）並自動刷新滑動內存。免費或過期帳戶直接阻斷並回傳 403 Forbidden。',
        'desc_categories' => '拉取目前系統白名單允許的所有 AI 適用角色分類、對應的 Slug 別名及前端 Emoji 圖標。',
        'desc_souls_get' => '分頁撈取、檢索大廳中公開的靈魂模型列表。內部經過極致索引優化。',
        'desc_soul_single' => '獲取單一模型代碼庫的完整結構、原創內容、知識領域標籤、相容性及社群統計指標。',
        'desc_souls_post' => '發佈與上傳全新智能體。系統會自動檢測並兼容單一 `.md` 文件或模組化多檔案的 JSON 樹狀物件。',
        'desc_soul_put' => '更新已存在的智能體佈局。系統會自動觸發觸發器，將舊有結構拷貝快照備份至 `soul_versions` 版本歷史表內。',
        'desc_soul_delete' => '永久刪除指定的靈魂模型，並自動解耦清算相關聯的知識領域標籤之使用率計數器。',
        'desc_profile' => '查詢指定創作者的全局公開看板數據（包含獲讚、被分叉總數）以及他名下的公開作品矩陣。',
        'desc_versions_get' => '撈取指定模型歷史版本時間線檔案庫。內部具備嚴格的越權保護機制。',
        'desc_versions_post' => '執行指令將目前線上的智能體結構內容，秒級還原（Rollback）到指定的歷史版本時間標記點。',
        'desc_fork' => '克隆（Clone）一個公開的模型，分叉複製一份完全獨立的全新複本到您自己的創作者工作區。',
        'desc_like' => '切換讚好與取消讚好狀態。資料庫套用唯一約束，回傳最新按讚狀態布林值。',
        'desc_rate' => '進行 1 至 5 星社交評分。重複提交會覆蓋舊紀錄，回傳全站最新即時平均星數與總評分人數。',
        
        // Extra Warnings & Tooltips
        'Query params:' => '網址參數 (Query params):',
        'Subscription Policy:' => '進階付費政策：',
        'sub_policy_text' => 'Headless API 整合串接為 VIP 與 PRO 會員專屬。若您的尊貴會員通行證到期，遠端 API 存取權限將自動暫停阻斷，直至完成手動續期為止。',
        'CRITICAL CONSTRAINT:' => '重大約束條件：',
        'constraint_text' => '請求體中的 <code>role</code> 欄位必須嚴格匹配 <code>/api/categories</code> 所回傳的 <code>slug</code> 鍵值。不合規的命名將被系統強制歸類為 \'Other\'。',
        'internal_utils_notice' => '重要提示：以下端點完全依賴瀏覽器的會話 Cookie 進行安全鑑權，無法使用 Authorization Bearer 金鑰認證，故其被排除在 Postman 測試集之外。',
        'desc_logout' => '清除當前瀏覽器的 Session 會話與記住我 Remember-Me 自動登入權杖。',
        'desc_regen_key' => '註銷舊的金鑰，並重新為目前登入者簽發一組全新、隨機的 32 位元安全十六進制 API 密鑰。',
        'desc_save_preset' => '前端內部緩存公用程式，用作暫存生成器組合出來的 AI 設定封包到 Session 緩衝記憶體中。',
        'View Response Sample (file_type: single_md)' => '檢視回應範例 (單一 .md 文件 模式)',
        'View Response Sample (file_type: full_soul_folder)' => '檢視回應範例 (模組化資料夾 JSON 模式)',
    ]
];