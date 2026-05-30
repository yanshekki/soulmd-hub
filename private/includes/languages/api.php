<?php
/**
 * SoulMD Hub - i18n Translation Dictionary
 * Target: All Backend API Endpoints (/api/*.php & download.php)
 */

return [
    'en' => [
        // Generic Errors
        'Method Not Allowed' => 'Method Not Allowed',
        'Database query failed' => 'Database query failed.',
        'Invalid JSON payload' => 'Invalid JSON payload received.',
        'Missing required parameters' => 'Missing required parameters.',
        'Internal Server Error' => 'Internal Server Error.',
        
        // Auth & Security
        'Unauthorized Session' => 'Unauthorized. Valid Session or API Key required.',
        'Invalid API Key' => 'Invalid API Key provided.',
        'Login or valid API Key required' => 'Login or valid API Key required.',
        'Access Denied Private' => 'Access Denied. This session or soul is private.',
        'Security validation failed' => 'Security validation failed. Direct access blocked, please use the web interface.',
        'Not the session owner' => 'Access Denied. You are not the owner of this session.',
        'All fields required' => 'All fields are required.',
        
        // Rate Limits & Tiers
        'Sending too fast' => 'Sending too fast. Please wait 3 seconds.',
        'Daily limit reached' => 'Daily anti-bot limit reached (:limit messages). Please try again tomorrow.:upgrade',
        'Upgrade suffix' => ' Upgrade your tier to unlock higher daily capacity!',
        'Message exceeds chars' => 'Message exceeds the :limit characters limit for your tier.',
        'Vision AI exclusive' => 'Vision AI (Image Upload) is an exclusive feature for VIP & PRO members.',
        'Free preview capacity reached' => 'You have reached the free preview capacity of :limit messages. Upgrade to unlock completely unlimited conversations.',
        'API restricted expired' => 'Your premium subscription has expired. Direct API access is restricted to active VIP or PRO members. Please renew your plan.',
        'API restricted free' => 'Direct API access is restricted to VIP or PRO members. Free users must use the web interface.',
        
        // AI Engine Errors
        'AI Service timeout' => 'AI Service processing timeout.',
        'Engine Error' => 'Engine Error: :error',
        'DB Sync Error' => 'DB Sync Error: :error',
        'Fatal Server Exception' => 'Fatal Server Exception: :error',
        
        // Login & Register
        'Username min chars' => 'Username must be at least 3 characters.',
        'Username invalid format' => 'Username can only contain alphanumeric characters, underscores, and dashes.',
        'Password min chars' => 'Password must be at least 6 characters.',
        'Username taken' => 'Username is already taken.',
        'Incorrect credentials' => 'Incorrect username or password. Please try again.',
        'Incorrect current password' => 'Incorrect current password.',
        'Passwords do not match' => 'New passwords do not match.',
        
        // Souls Management
        'Fields required title content' => 'Fields "title" and "content" are required.',
        'Invalid Modular JSON content' => 'Invalid Modular JSON structure inside content field: :error',
        'Invalid Modular JSON general' => 'Invalid Modular JSON structure. AI might have generated malformed formatting. Please check the JSON payload.',
        'Soul not found' => 'Soul not found.',
        'Soul not found or no edit perm' => 'Soul not found or you do not have permission to edit it.',
        'Soul not found or no delete perm' => 'Soul not found or you do not have permission to delete it.',
        'Soul not found or access denied' => 'Soul not found or access denied.',
        'Public soul not found' => 'Public soul not found.',
        
        // Social & Rating
        'soul_id required' => 'soul_id is required.',
        'soul_id and rating required' => 'soul_id and rating are required.',
        'Rating must be 1-5' => 'Rating must be between 1 and 5.',
        'User not found' => 'User not found.',
        'Username parameter required' => 'Username parameter is required.',
        
        // Versions
        'version_id and soul_id required' => 'version_id and soul_id are required.',
        'Version not found' => 'Historical version not found.',
        'Restore failed' => 'Version restore failed.',
        
        // Billing & PayPal
        'Auth required for transaction' => 'Authentication required to process transaction.',
        'Malformed transaction' => 'Malformed transaction payload detected.',
        'Downgrade Guard' => 'Downgrade Guard: You currently have an active PRO subscription. Please wait until it expires to switch to VIP. No charges were made.',
        'Gateway auth failure' => 'Gateway authentication failure.',
        'Gateway Error' => 'Gateway Error: :error',
        'Gross amount mismatch' => 'Gross amount mismatch. Transaction halted for security.',
        'Entitlement error' => 'Internal cluster sync error during entitlement allocation.',
        
        // Downloads
        'Invalid request parameters' => 'Invalid request parameters.',
        'Could not create ZIP' => 'Could not create ZIP file.',
        'File not found inside soul' => 'File not found inside this soul.',
        
        // Success Messages
        'Login successful' => 'Login successful',
        'Account created successfully' => 'Account created successfully',
        'Logged out successfully' => 'Logged out successfully',
        'Password successfully updated' => 'Password successfully updated!',
        'API Key regenerated' => 'API Key regenerated successfully!',
        'Soul created successfully' => 'Soul created successfully',
        'Soul updated successfully' => 'Soul updated successfully',
        'Soul deleted successfully' => 'Soul deleted successfully',
        'Soul forked successfully' => 'Soul forked successfully!',
        'Soul unliked successfully' => 'Soul unliked successfully',
        'Soul liked successfully' => 'Soul liked successfully',
        'Rating submitted successfully' => 'Rating submitted successfully',
        'Version restored successfully' => 'Version restored successfully',
        'Transaction already processed' => 'Transaction already processed and logged.',
        'Transaction COMPLETED' => 'Transaction COMPLETED. Premium cluster assets successfully provisioned.',
        'Transaction PENDING' => 'Transaction PENDING. Funds are clearing via PayPal. Assets will provision automatically upon successful settlement.',

        // Self Chat
        'BYOK mode is not enabled on your account.' => 'BYOK mode is not enabled on your account.',
        'Vision BYOK fallback error' => 'Your custom Vision Key is not set, and your platform Vision allowance is exhausted. Please update your settings or upgrade your plan.',
        'Text API Key is not set in your BYOK settings.' => 'Text API Key is not set in your BYOK settings.',
        'Unknown Connection Failure' => 'Unknown Connection Failure',
    ],
    
    'zh' => [
        // Generic Errors
        'Method Not Allowed' => '不允許的請求方法 (Method Not Allowed)。',
        'Database query failed' => '資料庫查詢失敗。',
        'Invalid JSON payload' => '接收到無效的 JSON 負載資料。',
        'Missing required parameters' => '缺少必要的請求參數。',
        'Internal Server Error' => '伺服器內部發生錯誤。',
        
        // Auth & Security
        'Unauthorized Session' => '未經授權。需要有效的 Session 登入狀態或 API 金鑰。',
        'Invalid API Key' => '提供的 API 金鑰無效。',
        'Login or valid API Key required' => '需要登入或提供有效的 API 金鑰。',
        'Access Denied Private' => '拒絕存取。此對話工作階段或模型為私密狀態。',
        'Security validation failed' => '安全性 CSRF 驗證失敗。已封鎖直接存取，請使用網頁介面操作。',
        'Not the session owner' => '拒絕存取。您並非此對話工作階段的擁有者。',
        'All fields required' => '所有欄位均為必填。',
        
        // Rate Limits & Tiers
        'Sending too fast' => '發送速度過快。請等待 3 秒後再試。',
        'Daily limit reached' => '已達每日防機器人請求上限（:limit 則訊息）。請明天再試。:upgrade',
        'Upgrade suffix' => '升級您的會員階級以解鎖更高的每日用量！',
        'Message exceeds chars' => '訊息長度超出了您當前階級的 :limit 字元限制。',
        'Vision AI exclusive' => 'Vision AI (圖片上傳分析) 是 VIP 與 PRO 會員的專屬功能。',
        'Free preview capacity reached' => '您已達到免費試用額度的上限（:limit 則訊息）。升級以解鎖完全無限制的對話次數。',
        'API restricted expired' => '您的尊貴會員訂閱已過期。直接的 Headless API 存取權限僅限有效期的 VIP 或 PRO 會員使用。請續期您的計劃。',
        'API restricted free' => '直接的 Headless API 存取權限僅限 VIP 或 PRO 會員使用。免費使用者必須使用網頁介面。',
        
        // AI Engine Errors
        'AI Service timeout' => 'AI 運算服務處理連線逾時。',
        'Engine Error' => '大模型引擎錯誤：:error',
        'DB Sync Error' => '資料庫同步錯誤：:error',
        'Fatal Server Exception' => '伺服器嚴重例外錯誤：:error',
        
        // Login & Register
        'Username min chars' => '使用者名稱必須至少包含 3 個字元。',
        'Username invalid format' => '使用者名稱只能包含英文字母、數字、底線及連字號。',
        'Password min chars' => '密碼必須至少包含 6 個字元。',
        'Username taken' => '該使用者名稱已被註冊佔用。',
        'Incorrect credentials' => '使用者名稱或密碼錯誤，請重試。',
        'Incorrect current password' => '您輸入的舊密碼不正確。',
        'Passwords do not match' => '兩次輸入的新密碼並不相符。',
        
        // Souls Management
        'Fields required title content' => '「標題」與「內容」欄位為必填。',
        'Invalid Modular JSON content' => '內容欄位中的模組化 JSON 結構無效：:error',
        'Invalid Modular JSON general' => '模組化 JSON 結構無效。AI 可能生成了錯誤的格式，請檢查您的 JSON 負載資料。',
        'Soul not found' => '找不到該靈魂模型。',
        'Soul not found or no edit perm' => '找不到該靈魂模型，或者您沒有權限編輯它。',
        'Soul not found or no delete perm' => '找不到該靈魂模型，或者您沒有權限刪除它。',
        'Soul not found or access denied' => '找不到該靈魂模型，或拒絕存取。',
        'Public soul not found' => '找不到該公開的靈魂模型。',
        
        // Social & Rating
        'soul_id required' => '必須提供 soul_id。',
        'soul_id and rating required' => '必須提供 soul_id 與 rating 評分。',
        'Rating must be 1-5' => '評分必須介於 1 到 5 之間。',
        'User not found' => '找不到該使用者。',
        'Username parameter required' => '必須提供使用者名稱 (Username) 參數。',
        
        // Versions
        'version_id and soul_id required' => '必須提供 version_id 與 soul_id。',
        'Version not found' => '找不到該歷史版本。',
        'Restore failed' => '歷史版本還原失敗。',
        
        // Billing & PayPal
        'Auth required for transaction' => '處理交易需要先登入並進行身分驗證。',
        'Malformed transaction' => '偵測到格式錯誤的交易負載資料。',
        'Downgrade Guard' => '防降級保護機制觸發：您目前擁有生效中的 PRO 訂閱。請等待其過期後再切換至 VIP。系統並未進行任何扣款。',
        'Gateway auth failure' => '支付網關驗證失敗。',
        'Gateway Error' => '支付網關錯誤：:error',
        'Gross amount mismatch' => '訂單總金額不符。為確保安全，交易已被終止。',
        'Entitlement error' => '分配訂閱權限時發生內部叢集同步錯誤。',
        
        // Downloads
        'Invalid request parameters' => '無效的請求參數。',
        'Could not create ZIP' => '無法在伺服器建立 ZIP 壓縮檔。',
        'File not found inside soul' => '在此靈魂模型內找不到指定的檔案。',
        
        // Success Messages
        'Login successful' => '登入成功。',
        'Account created successfully' => '帳號建立成功。',
        'Logged out successfully' => '登出成功。',
        'Password successfully updated' => '密碼已成功更新！',
        'API Key regenerated' => 'API 金鑰已成功重新產生！',
        'Soul created successfully' => '模型建立成功。',
        'Soul updated successfully' => '模型更新成功。',
        'Soul deleted successfully' => '模型已永久刪除。',
        'Soul forked successfully' => '模型分叉 (Fork) 成功！',
        'Soul unliked successfully' => '已取消讚好模型。',
        'Soul liked successfully' => '成功讚好模型！',
        'Rating submitted successfully' => '評分提交成功。',
        'Version restored successfully' => '歷史版本還原成功。',
        'Transaction already processed' => '此交易先前已處理並記錄。',
        'Transaction COMPLETED' => '交易已完成 (COMPLETED)。您的尊貴會員資產已成功配發生效。',
        'Transaction PENDING' => '交易處理中 (PENDING)。資金正透過 PayPal 結算，成功結算後將自動配發權限。',

        // Self Chat
        'BYOK mode is not enabled on your account.' => '您的帳號尚未啟用自訂金鑰 (BYOK) 模式。',
        'Vision BYOK fallback error' => '您的自訂 Vision 金鑰未設定，且平台視覺額度已耗盡，請前往設定頁補全或升級計劃。',
        'Text API Key is not set in your BYOK settings.' => '您尚未在 BYOK 設定中填寫純文字模型的 API 金鑰。',
        'Unknown Connection Failure' => '未知的連線錯誤',
    ]
];