<?php
/**
 * SoulMD Hub - i18n Translation Dictionary
 * Target: my-setting.php (Unified Settings Hub)
 */

return [
    'en' => [
        // SEO & Headers
        'SEO Title' => 'My Settings - SoulMD Hub',
        'Account Security' => 'Account Security',
        'Web3 Wallet' => 'Web3 Wallet',
        'Developer API' => 'Developer API',
        'Custom AI Engine (BYOK)' => 'Custom AI Engine (BYOK)',
        
        // Tab 1: Account
        'Username' => 'Username',
        'Current Password' => 'Current Password',
        'New Password' => 'New Password',
        'Confirm New Password' => 'Confirm New Password',
        'Update Password' => 'Update Password',
        
        // Tab 2: Web3
        'Web3 Wallet Binding' => 'Web3 Wallet Binding',
        'Wallet Binding Desc' => 'Connect your NEAR wallet to unlock AgentFi capabilities (Minting NFTs, Trading, and Renting). Each account can only bind one wallet permanently.',
        'Bound Permanently' => 'Bound Permanently',
        'Connect & Bind NEAR Wallet' => 'Connect & Bind NEAR Wallet',
        'Important Warning:' => 'Important Warning:',
        'Wallet one-time warning' => 'For security and asset integrity, you can only bind your wallet ONCE. After binding, you cannot change the wallet address linked to this account.',
        
        // Tab 3: API
        'Platform API Key' => 'Platform API Key',
        'API Key Desc' => 'This key is used to call the SoulMD Hub /api/chat endpoint (subject to platform tier limits). Keep it safe.',
        'Regenerate Key' => 'Regenerate Key',
        'Key Regen Confirm' => 'Are you sure you want to regenerate your platform API Key? The old key will be invalidated immediately!',
        'Key generated successfully!' => 'API Key regenerated successfully!',
        
        // Tab 4: BYOK
        'Unlimited Chat Unlock' => 'Unlimited Chat Unlocked',
        'BYOK Title' => 'Custom AI Engine (BYOK)',
        'BYOK Desc' => 'Once enabled, the platform will act as a stateless proxy sending requests to your dedicated API. No platform quotas will be deducted! All keys are securely AES-256 encrypted.',
        'Text LLM' => 'Text LLM',
        'Provider Preset' => 'Provider Preset',
        'OpenAI (Recommended)' => 'OpenAI (Recommended)',
        'Together AI (Open Source)' => 'Together AI (Open Source)',
        'Groq (Ultra Fast)' => 'Groq (Ultra Fast)',
        'OpenRouter (Claude/Gemini)' => 'OpenRouter (Claude/Gemini)',
        'Custom (OpenAI Compatible)' => 'Custom (OpenAI Compatible)',
        'API URL' => 'API URL (OpenAI Compatible)',
        'Model Name' => 'Model Name',
        'Your API Key' => 'Your API Key',
        
        'Vision LLM' => 'Vision LLM',
        'Optional Fallback' => '(Optional Fallback)',
        'Memory Compression' => 'Context Memory Compression',
        'Memory Desc' => 'To prevent context from burning your API balance, the system will call your key to compress chat history into summaries after reaching the defined turns.',
        'Save Custom Engine Settings' => 'Save Custom Engine Settings',
        
        // Admin Treasury Section
        'Platform Treasury' => 'Platform Treasury & Tokenomics (Admin Only)',
        'Treasury Desc' => 'Execute the Deflationary Spiral. Inject platform NEAR revenues into Ref Finance AMM to auto-buyback and burn $SOUL tokens.',
        'Trigger Buyback & Burn' => 'Trigger Buyback & Burn ($SOUL)',
        'Amount to Swap (NEAR)' => 'Amount to Swap (NEAR)',
        'Please enter a valid NEAR amount.' => 'Please enter a valid NEAR amount.',
        'Buyback Confirm' => "Are you absolutely sure you want to trigger the Deflationary Spiral? \n\nThis will take :amount NEAR from the treasury and swap it into \$SOUL to burn forever!",
        'Please connect NEAR wallet first' => 'Please connect NEAR wallet first.',
        'Buyback Failed' => 'Blockchain transaction failed. Make sure you are logged in with the official Treasury account (:contract).',
        'Buyback initiated successfully!' => 'Buyback initiated successfully!',
        
        // JS States
        'Saving...' => 'Saving...',
        'Settings Saved' => 'Settings Saved',
        'Save Failed' => 'Save Failed: ',
        'Network Error' => 'Network Error!',
        'Updating...' => 'Updating...',
        'Password updated successfully!' => 'Password updated successfully!',
        'Connecting RPC...' => 'Connecting to RPC...',
        'Binding Address...' => 'Binding Address...',
        'Bind Failed' => 'Binding Failed: ',
        'Processing...' => 'Processing...',
        'Syncing...' => 'Syncing...',
    ],
    
    'zh' => [
        // SEO & Headers
        'SEO Title' => '我的設定 - SoulMD Hub',
        'Account Security' => '帳戶安全',
        'Web3 Wallet' => 'Web3 錢包',
        'Developer API' => '開發者 API',
        'Custom AI Engine (BYOK)' => '自訂 AI 引擎 (BYOK)',
        
        // Tab 1: Account
        'Username' => '使用者名稱',
        'Current Password' => '舊密碼',
        'New Password' => '新密碼',
        'Confirm New Password' => '確認新密碼',
        'Update Password' => '更新密碼',
        
        // Tab 2: Web3
        'Web3 Wallet Binding' => 'Web3 錢包綁定',
        'Wallet Binding Desc' => '綁定錢包後解鎖 AgentFi 功能，包含購買、租借 AI 模型。每個帳號僅可永久綁定一次，且無法修改。',
        'Bound Permanently' => '已永久綁定',
        'Connect & Bind NEAR Wallet' => '連接並綁定 NEAR 錢包',
        'Important Warning:' => '重要警告：',
        'Wallet one-time warning' => '為保障帳戶安全及鏈上資產完整性，每個帳號只能綁定一次錢包。綁定成功後，日後將【絕對無法修改或解除綁定】。請確認您使用的是正確的常用錢包！',
        
        // Tab 3: API
        'Platform API Key' => '平台 API 金鑰',
        'API Key Desc' => '此金鑰用於呼叫 SoulMD 平台的 /api/chat 端點 (受平台計畫額度限制)。請妥善保管。',
        'Regenerate Key' => '重新生成金鑰',
        'Key Regen Confirm' => '確定要重新生成平台 API 金鑰嗎？舊金鑰將立即失效！',
        'Key generated successfully!' => '金鑰生成成功！',
        
        // Tab 4: BYOK
        'Unlimited Chat Unlock' => '無限暢聊解鎖',
        'BYOK Title' => '自訂 AI 引擎 (BYOK)',
        'BYOK Desc' => '啟動後，平台將「左手交右手」代發請求至您專屬的 API。不扣除任何平台次數配額！金鑰均經 AES-256 加密存儲。',
        'Text LLM' => '文字推理模型 (Text LLM)',
        'Provider Preset' => '主流平台預設配置',
        'OpenAI (Recommended)' => 'OpenAI (推薦)',
        'Together AI (Open Source)' => 'Together AI (開源模型)',
        'Groq (Ultra Fast)' => 'Groq (極速推理)',
        'OpenRouter (Claude/Gemini)' => 'OpenRouter (Claude/Gemini聚合)',
        'Custom (OpenAI Compatible)' => '自訂 (Custom - OpenAI 相容)',
        'API URL' => 'API URL (OpenAI 相容端點)',
        'Model Name' => '模型名稱 (Model Name)',
        'Your API Key' => '您的 API 金鑰 (API Key)',
        
        'Vision LLM' => '圖像分析模型 (Vision LLM)',
        'Optional Fallback' => '(選填：Fallback 用)',
        'Memory Compression' => '上下文記憶體壓縮頻率 (Memory Compression)',
        'Memory Desc' => '為了防止 Context 過長燒乾您的 API 額度，系統會在對話達到特定輪數時，呼叫您的金鑰將舊紀錄壓縮成摘要。建議設定為 10-20 輪，節省 Token 花費。',
        'Save Custom Engine Settings' => '儲存自訂引擎設定',
        
        // Admin Treasury Section
        'Platform Treasury' => '平台金庫與代幣經濟學 (僅限管理員)',
        'Treasury Desc' => '觸發通縮螺旋機制。將平台累積的 NEAR 收益投入 Ref Finance AMM 進行自動回購，並永久銷毀 $SOUL 代幣。',
        'Trigger Buyback & Burn' => '啟動回購與銷毀核彈 ($SOUL)',
        'Amount to Swap (NEAR)' => '投入回購金額 (NEAR)',
        'Please enter a valid NEAR amount.' => '請輸入有效的 NEAR 數量。',
        'Buyback Confirm' => "您絕對肯定要啟動「通縮螺旋」嗎？\n\n此操作將從平台國庫中提取 :amount NEAR，全數閃兌為 \$SOUL 代幣並打入黑洞永久銷毀！",
        'Please connect NEAR wallet first' => '請先連接您的 NEAR 錢包。',
        'Buyback Failed' => '區塊鏈交易失敗。請確保您已登入官方國庫錢包賬號 (:contract)。',
        'Buyback initiated successfully!' => '回購與銷毀程序已成功啟動！',
        
        // JS States
        'Saving...' => '儲存中...',
        'Settings Saved' => '設定已儲存',
        'Save Failed' => '儲存失敗：',
        'Network Error' => '網絡錯誤！',
        'Updating...' => '更新中...',
        'Password updated successfully!' => '密碼更新成功！',
        'Connecting RPC...' => '等緊 RPC 連接...',
        'Binding Address...' => '正在綁定地址...',
        'Bind Failed' => '綁定失敗：',
        'Processing...' => '處理中...',
        'Syncing...' => '同步中...',
    ]
];