<?php
/**
 * SoulMD Hub - i18n Translation Dictionary
 * Target: my-api.php (Developer API Settings)
 */

return [
    'en' => [
        // SEO & Headers
        'SEO Title' => 'My API Key - SoulMD Hub',
        'SEO Desc' => 'Manage your developer API keys and integration limits.',
        'Developer API Access' => 'Developer API Access',
        'API Subtitle' => 'Integrate your custom AI personas directly into your own applications using our Headless API.',
        'Back to Dashboard' => 'Workspace',
        'API Documentation' => 'API Docs',
        
        // API Key Section
        'Your Secret API Key' => 'Your Secret API Key',
        'API Key Warning' => 'Keep this key safe. Do not expose it in client-side code (like frontend JavaScript). If compromised, contact support immediately to rotate your key.',
        'Reveal Key' => 'Reveal Key',
        'Hide Key' => 'Hide Key',
        'Copy Key' => 'Copy',
        'Copied!' => 'Copied!',

        // Web3 Wallet Binding Section
        'Web3 Wallet Binding' => 'Web3 Wallet Binding',
        'Wallet Binding Desc' => 'Connect your NEAR wallet to unlock AgentFi capabilities (Minting NFTs, Trading, and Renting).',
        'Important Warning:' => 'Important Warning:',
        'Wallet one-time warning' => 'For security and asset integrity, you can only bind your wallet ONCE. After binding, you cannot change the wallet address linked to this account.',
        'Connect & Bind Wallet' => 'Connect & Bind Wallet',
        'Bound' => 'Bound',
        'Wallet cannot be changed' => 'Your wallet is permanently bound to this account and cannot be modified.',
        'Connecting...' => 'Connecting...',
        'Binding...' => 'Binding...',
        'Connecting to RPC...' => 'Connecting to RPC...',
        'Binding Address...' => 'Binding Address...',
        'Bind Failed' => 'Binding Failed: ',
        
        // Admin Treasury Section
        'Platform Treasury' => 'Platform Treasury & Tokenomics (Admin Only)',
        'Treasury Desc' => 'Execute the Deflationary Spiral. Inject platform NEAR revenues into Ref Finance AMM to auto-buyback and burn $SOUL tokens.',
        'Trigger Buyback & Burn' => 'Trigger Buyback & Burn ($SOUL)',
        'Amount to Swap (NEAR)' => 'Amount to Swap (NEAR)',
        
        // Usage & Limits Section
        'API Usage & Tier Limits' => 'API Usage & Tier Limits',
        'Current Tier' => 'Current Tier',
        'Daily Request Limit' => 'Daily Request Limit',
        'Max Input Characters' => 'Max Input Characters',
        'Max Output Tokens' => 'Max Output Tokens',
        'req/day' => 'req/day',
        'chars/req' => 'chars/req',
        'tokens/req' => 'tokens/req',
        
        // Upgrades
        'Need more power?' => 'Need more power?',
        'Upgrade Plan' => 'Upgrade Plan',
        
        // JS Alerts
        'Key Regen Confirm' => 'Are you sure you want to regenerate your platform API Key? The old key will be invalidated immediately!',
        'Key generated successfully!' => 'API Key regenerated successfully!',
        'Network Error' => 'Network error. Please try again.',
        'Please enter a valid NEAR amount.' => 'Please enter a valid NEAR amount.',
        'Buyback Confirm' => "Are you absolutely sure you want to trigger the Deflationary Spiral? \n\nThis will take :amount NEAR from the treasury and swap it into $SOUL to burn forever!",
        'Please connect NEAR wallet first' => 'Please connect NEAR wallet first.',
        'Buyback Failed' => 'Blockchain transaction failed. Make sure you are logged in with the official Treasury account (:contract).',
    ],
    
    'zh' => [
        // SEO & Headers
        'SEO Title' => '我的 API 金鑰 - SoulMD Hub',
        'SEO Desc' => '管理您的開發者 API 金鑰與系統整合限制。',
        'Developer API Access' => '開發者 API 存取權限',
        'API Subtitle' => '使用我們的 Headless API，將您自訂的 AI 角色與智能體直接整合至您專屬的應用程式中。',
        'Back to Dashboard' => '創作者工作區',
        'API Documentation' => 'API 說明文件',
        
        // API Key Section
        'Your Secret API Key' => '您的專屬 Secret API 金鑰',
        'API Key Warning' => '請妥善保管此金鑰，切勿將其暴露於前端程式碼（如瀏覽器端的 JavaScript）或公開的代碼庫中。若懷疑金鑰外洩，請立即聯絡客服進行金鑰輪替 (Key Rotation)。',
        'Reveal Key' => '顯示金鑰',
        'Hide Key' => '隱藏金鑰',
        'Copy Key' => '複製',
        'Copied!' => '已複製！',

        // Web3 Wallet Binding Section
        'Web3 Wallet Binding' => 'Web3 錢包綁定 (AgentFi)',
        'Wallet Binding Desc' => '連接您的 NEAR 錢包以解鎖 AgentFi 進階功能 (包含鑄造 NFT、資產交易及黑盒出租)。',
        'Important Warning:' => '重要警告：',
        'Wallet one-time warning' => '為保障帳戶安全及鏈上資產完整性，每個帳號只能綁定一次錢包。綁定成功後，日後將【絕對無法修改或解除綁定】。請確認您使用的是正確的常用錢包！',
        'Connect & Bind Wallet' => '連接並永久綁定錢包',
        'Bound' => '已綁定',
        'Wallet cannot be changed' => '您的錢包已永久綁定至此帳號，無法再作修改。',
        'Connecting...' => '正在喚起錢包...',
        'Binding...' => '正在寫入綁定資料...',
        'Connecting to RPC...' => '等緊 RPC 連接...',
        'Binding Address...' => '正在綁定地址...',
        'Bind Failed' => '綁定失敗：',
        
        // Admin Treasury Section
        'Platform Treasury' => '平台金庫與代幣經濟學 (僅限管理員)',
        'Treasury Desc' => '觸發通縮螺旋機制。將平台累積的 NEAR 收益投入 Ref Finance AMM 進行自動回購，並永久銷毀 $SOUL 代幣。',
        'Trigger Buyback & Burn' => '啟動回購與銷毀核彈 ($SOUL)',
        'Amount to Swap (NEAR)' => '投入回購金額 (NEAR)',
        
        // Usage & Limits Section
        'API Usage & Tier Limits' => 'API 使用量與階級限制',
        'Current Tier' => '目前會員階級',
        'Daily Request Limit' => '每日 API 請求上限',
        'Max Input Characters' => '單次最大輸入字元數',
        'Max Output Tokens' => '單次最大輸出 Tokens',
        'req/day' => '次 / 每日',
        'chars/req' => '字元 / 單次',
        'tokens/req' => 'Tokens / 單次',
        
        // Upgrades
        'Need more power?' => '需要更高的 API 運算額度？',
        'Upgrade Plan' => '升級您的計劃',
        
        // JS Alerts
        'Key Regen Confirm' => '確定要重新生成平台 API 金鑰嗎？舊金鑰將立即失效！',
        'Key generated successfully!' => '金鑰生成成功！',
        'Network Error' => '網絡異常，請檢查您的連線狀態。',
        'Please enter a valid NEAR amount.' => '請輸入有效的 NEAR 數量。',
        'Buyback Confirm' => "您絕對肯定要啟動「通縮螺旋」嗎？\n\n此操作將從平台國庫中提取 :amount NEAR，全數閃兌為 $SOUL 代幣並打入黑洞永久銷毀！",
        'Please connect NEAR wallet first' => '請先連接您的 NEAR 錢包。',
        'Buyback Failed' => '區塊鏈交易失敗。請確保您已登入官方國庫錢包賬號 (:contract)。',
    ]
];