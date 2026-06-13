<?php
/**
 * SoulMD Hub - i18n Translation Dictionary
 * Target: my-setting.php (Unified Settings Hub)
 * 🚀 V5 SEO Optimized: Advanced Web3 & BYOK Headless AI Keywords
 */
return [
    'en' => [
        // SEO & Headers
        'SEO Title' => 'Developer Settings & API Management | SoulMD Hub',
        'Account Security' => 'Account & Web3 Security',
        'Web3 Wallet' => 'AgentFi Web3 Wallet Integration',
        'Developer API' => 'Headless Developer API',
        'Custom AI Engine (BYOK)' => 'Stateless Custom AI Engine (BYOK)',
        
        // Tab 1: Account
        'Username' => 'Account Username',
        'Current Password' => 'Current Password',
        'New Password' => 'New Password',
        'Confirm New Password' => 'Confirm New Password',
        'Update Password' => 'Update Security Password',
        
        // Tab 2: Web3
        'Web3 Wallet Binding' => 'AgentFi Web3 Wallet Binding',
        'Wallet Binding Desc' => 'Connect your NEAR blockchain wallet to unlock decentralized AgentFi capabilities (Minting AI NFTs, Trading, and Renting). Each developer account can only bind one wallet permanently.',
        'Bound Permanently' => 'Wallet Permanently Bound',
        'Connect & Bind Wallet' => 'Connect & Bind NEAR Wallet',
        'Important Warning:' => 'Immutable Blockchain Binding:',
        'Wallet one-time warning' => 'For extreme security and Web3 asset integrity, you can only bind your wallet ONCE. After binding, you cannot migrate the wallet address linked to this account.',
        
        // Special for platform owner (NEAR_CONTRACT_ID) in Web3 binding section
        'Platform Owner Badge' => 'PLATFORM OWNER — Contract Treasury',
        'Owner Dashboard Desc' => 'This wallet controls the live soulmd-hub.near smart contract. Click below to open the full system dashboard for token management, upgrade credits, FT revenue, and all buy/sell records.',
        'Open Contract Admin Dashboard' => 'Open Contract Admin Dashboard',
        
        // Tab 3: API
        'Platform API Key' => 'Platform API Secret Key',
        'API Key Desc' => 'This cryptographic key is used to authenticate requests to the SoulMD Hub `/api/chat` Headless API endpoint (subject to your SaaS tier limits). Keep it strictly confidential.',
        'Regenerate Key' => 'Regenerate API Key (Key Rotation)',
        'Key Regen Confirm' => 'Are you absolutely sure you want to regenerate your Headless API Key? The old key will be invalidated immediately, breaking any current integrations!',
        'Key generated successfully!' => 'API Key rotated successfully!',
        
        // Tab 4: BYOK
        'Unlimited Chat Unlock' => 'Unlimited API Chat Unlocked',
        'BYOK Title' => 'Custom Stateless AI Engine (BYOK)',
        'BYOK Desc' => 'Once enabled, the platform acts as a stateless proxy, securely routing requests to your dedicated API provider. No platform token quotas will be consumed! All BYOK credentials are AES-256-CBC encrypted.',
        'Text LLM' => 'Core Text LLM',
        'Provider Preset' => 'LLM Provider Preset',
        'OpenAI (Recommended)' => 'OpenAI (Recommended)',
        'Together AI (Open Source)' => 'Together AI (Open Source Llama)',
        'Groq (Ultra Fast)' => 'Groq (Ultra Fast Inference)',
        'OpenRouter (Claude/Gemini)' => 'OpenRouter (Claude/Gemini/DeepSeek)',
        'Custom (OpenAI Compatible)' => 'Custom Remote (OpenAI Spec Compatible)',
        'API URL' => 'API Endpoint URL',
        'Model Name' => 'Execution Model Name',
        'Your API Key' => 'Your Secret API Key',
        
        'Vision LLM' => 'Multi-Modal Vision LLM',
        'Optional Fallback' => '(Optional Vision Fallback)',
        'Memory Compression' => 'Sliding Context Memory Compression',
        'Memory Desc' => 'To prevent context window overflow from burning your API balance, the system will trigger a secondary API call to compress the chat history into dense factual summaries after reaching the defined threshold.',
        'Save Custom Engine Settings' => 'Deploy Custom Engine Configuration',
        
        // Admin Treasury Section
        'Platform Treasury' => 'Platform Treasury & Protocol Tokenomics (Admin Only)',
        'Treasury Desc' => 'Execute the Deflationary Spiral. Inject platform NEAR revenues into the Ref Finance AMM liquidity pool to auto-buyback and permanently burn $SOUL utility tokens.',
        'Trigger Buyback & Burn' => 'Trigger Buyback & Burn ($SOUL)',
        'Amount to Swap (NEAR)' => 'Amount to Swap (NEAR)',
        'Please enter a valid NEAR amount.' => 'Please enter a valid NEAR integer amount.',
        'Buyback Confirm' => "Are you absolutely sure you want to trigger the Deflationary Protocol Spiral? \n\nThis will extract :amount NEAR from the smart contract treasury and swap it into \$SOUL for an irreversible burn!",
        'Please connect NEAR wallet first' => 'Please connect your NEAR administrator wallet first.',
        'Buyback Failed' => 'Blockchain transaction failed. Ensure you are signed in with the official Treasury smart contract owner account (:contract).',
        'Buyback initiated successfully!' => 'Buyback transaction executed successfully!',
        
        // JS States
        'Saving...' => 'Encrypting & Saving...',
        'Settings Saved' => 'Configuration Saved',
        'Save Failed' => 'Encryption Save Failed: ',
        'Network Error' => 'Network Connection Error!',
        'Updating...' => 'Updating Database...',
        'Password updated successfully!' => 'Security password updated successfully!',
        'Connecting to RPC...' => 'Connecting to Blockchain RPC...',
        'Binding Address...' => 'Verifying Ed25519 Signature...',
        'Bind Failed' => 'Wallet Binding Failed: ',
        'Processing...' => 'Processing On-Chain...',
        'Syncing...' => 'Syncing Nodes...',
        'Connect & Bind NEAR Wallet' => 'Connect & Bind NEAR Wallet',
    ],
    
    'zh' => [
        // SEO & Headers
        'SEO Title' => '開發者設定與 API 金鑰管理 | SoulMD Hub',
        'Account Security' => '帳號與 Web3 安全性',
        'Web3 Wallet' => 'AgentFi Web3 錢包整合',
        'Developer API' => 'Headless 開發者 API',
        'Custom AI Engine (BYOK)' => '無狀態自訂 AI 引擎 (BYOK)',
        
        // Tab 1: Account
        'Username' => '帳號使用者名稱',
        'Current Password' => '目前密碼',
        'New Password' => '新密碼',
        'Confirm New Password' => '確認新密碼',
        'Update Password' => '更新安全密碼',
        
        // Tab 2: Web3
        'Web3 Wallet Binding' => 'AgentFi 區塊鏈錢包綁定',
        'Wallet Binding Desc' => '連接您的 NEAR 區塊鏈錢包以解鎖去中心化 AgentFi 功能（鑄造 AI NFT、交易及租賃）。每個開發者帳號「永久」只能綁定一個 Web3 錢包。',
        'Bound Permanently' => '已永久綁定鏈上地址',
        'Connect & Bind Wallet' => '連接並永久綁定 NEAR 錢包',
        'Important Warning:' => '不可逆的區塊鏈綁定：',
        'Wallet one-time warning' => '為確保極致的安全性與 Web3 資產完整性，您只能綁定錢包「一次」。一旦綁定完成，您將永遠無法轉移或修改連結到此帳號的錢包地址。',
        
        // Special for platform owner (NEAR_CONTRACT_ID) in Web3 binding section (AgentFi 區塊鏈錢包綁定)
        'Platform Owner Badge' => '平台擁有者 — 合約國庫',
        'Owner Dashboard Desc' => '此錢包控制 soulmd-hub.near 智能合約。點擊下方按鈕開啟完整系統管理控制台，可管理代幣、升級信用、查看 FT 收入及所有買賣記錄。',
        'Open Contract Admin Dashboard' => '開啟合約管理控制台',
        
        // Tab 3: API
        'Platform API Key' => '平台 API 專屬金鑰 (Secret Key)',
        'API Key Desc' => '此加密金鑰用於驗證您對 SoulMD Hub <code>/api/chat</code> Headless API 端點的請求（受限於您的 SaaS 訂閱額度）。請嚴格保密。',
        'Regenerate Key' => '重新生成 API 金鑰 (Key Rotation)',
        'Key Regen Confirm' => '您絕對確定要重新生成您的 Headless API 金鑰嗎？舊的金鑰將會立即失效，並可能導致您目前的應用程式整合中斷！',
        'Key generated successfully!' => 'API 金鑰已成功輪替 (Rotated)！',
        
        // Tab 4: BYOK
        'Unlimited Chat Unlock' => '已解鎖無限 API 對話',
        'BYOK Title' => '無狀態自訂 AI 引擎 (BYOK)',
        'BYOK Desc' => '啟用後，本平台將轉化為無狀態代理 (Stateless Proxy)，安全地將請求路由至您指定的 API 供應商。此模式將不會消耗任何平台的 Token 額度！所有 BYOK 憑證皆採用 AES-256-CBC 進行軍規級加密。',
        'Text LLM' => '核心文字 LLM',
        'Provider Preset' => 'LLM 供應商預設',
        'OpenAI (Recommended)' => 'OpenAI (官方推薦)',
        'Together AI (Open Source)' => 'Together AI (開源 Llama 模型)',
        'Groq (Ultra Fast)' => 'Groq (極速推論引擎)',
        'OpenRouter (Claude/Gemini)' => 'OpenRouter (Claude/Gemini/DeepSeek)',
        'Custom (OpenAI Compatible)' => '自訂遠端端點 (支援 OpenAI 格式)',
        'API URL' => 'API 端點 URL',
        'Model Name' => '執行模型名稱',
        'Your API Key' => '您的專屬 API 金鑰',
        
        'Vision LLM' => '多模態視覺 (Vision) LLM',
        'Optional Fallback' => '(可選的視覺備用引擎)',
        'Memory Compression' => '滑動上下文記憶壓縮閥值',
        'Memory Desc' => '為了防止上下文視窗溢出導致您的 API 餘額被大量消耗，系統會在達到定義的對話輪數後，觸發第二次 API 呼叫，將對話歷史壓縮成高密度的總結快照。',
        'Save Custom Engine Settings' => '部署自訂引擎設定',
        
        // Admin Treasury Section
        'Platform Treasury' => '平台國庫與協議代幣經濟學 (僅限管理員)',
        'Treasury Desc' => '執行通縮螺旋。將平台產生的 NEAR 收益注入 Ref Finance AMM 流動性池，以自動回購並永久銷毀 $SOUL 實用代幣。',
        'Trigger Buyback & Burn' => '觸發回購並銷毀 ($SOUL)',
        'Amount to Swap (NEAR)' => '兌換數量 (NEAR)',
        'Please enter a valid NEAR amount.' => '請輸入有效的 NEAR 整數金額。',
        'Buyback Confirm' => "您絕對確定要觸發通縮協議螺旋嗎？\n\n這將從智能合約國庫提取 :amount NEAR，並將其兌換為 \$SOUL 進行不可逆的銷毀！",
        'Please connect NEAR wallet first' => '請先連接您的 NEAR 管理員錢包。',
        'Buyback Failed' => '區塊鏈交易失敗。請確保您是使用官方國庫的智能合約擁有者帳號 (:contract) 登入。',
        'Buyback initiated successfully!' => '回購交易已成功執行！',
        
        // JS States
        'Saving...' => '加密並儲存中...',
        'Settings Saved' => '設定已成功儲存',
        'Save Failed' => '加密儲存失敗：',
        'Network Error' => '網路連線發生錯誤！',
        'Updating...' => '資料庫更新中...',
        'Password updated successfully!' => '安全密碼更新成功！',
        'Connecting to RPC...' => '連接至區塊鏈 RPC 節點...',
        'Binding Address...' => '驗證 Ed25519 加密簽章中...',
        'Bind Failed' => '錢包綁定失敗：',
        'Processing...' => '處理鏈上操作中...',
        'Syncing...' => '同步節點中...',
        'Connect & Bind NEAR Wallet' => '連接並永久綁定 NEAR 錢包',
    ]
];