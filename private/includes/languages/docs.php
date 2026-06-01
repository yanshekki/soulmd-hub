<?php
/**
 * SoulMD Hub - i18n Translation Dictionary
 * Target: docs.php (Grand Unified Ecosystem Documentation)
 */

return [
    'en' => [
        // SEO & Master Headers
        'SEO Title' => 'Documentation & Ecosystem Architecture - SoulMD Hub',
        'SEO Desc' => 'Learn about SoulMD Hub architecture, stateless BYOK proxies, client-side canvas compression, and the AgentFi tokenomics.',
        'Platform Documentation' => 'Platform Documentation',
        'Docs Subtitle' => 'Explore the unredacted technical blueprint, daily workflow use cases, and the deflationary economics driving our Web2.5 hybrid network.',

        // Tab Sidebar Navigation Labels
        'Tab Intro' => '🚀 Introduction & Engine',
        'Tab Solutions' => '🛡️ Problems Solved',
        'Tab UseCases' => '💻 Daily Use Cases',
        'Tab Future' => '💎 AgentFi & Tokenomics',

        // Global Dynamic UI
        'Page' => 'Page',
        'Loading Content...' => 'Loading unredacted module matrix...',
        'Close' => 'Close',

        // =========================================================
        // Tab 1: Introduction (運作原理與用途)
        // =========================================================
        'Intro Title' => 'The Multi-Modal AI Agent SaaS Architecture',
        'Intro Desc' => 'SoulMD Hub is a high-performance, API-first framework designed to serialize, execute, and monetize modular AI personalities (.md format) safely.',
        
        'Core Prompt Modularization' => '1. Core Prompt Modularization',
        'Core Prompt Desc' => 'Traditional monolithic prompts bleed context tokens and break character consistency. SoulMD Hub enforces a rigid 3-file modular layout:',
        'Layout Soul' => '<code>SOUL.md</code>: The foundational engine blueprint, core prompt rules, and character traits setup.',
        'Layout Style' => '<code>STYLE.md</code>: The tone modifier, voice matrices, vocabulary preferences, and syntax structures.',
        'Layout Rules' => '<code>RULES.md</code>: Unbreakable boundaries, explicit formatting rails, and negative constraint filters.',
        
        'Dual Engine Matrix' => '2. Smart Dual-Engine Routing Matrix',
        'Dual Engine Desc' => 'To optimize operational costs and deliver exceptional token efficiency, requests are analyzed instantly inside our headless backend proxy pipeline:',
        'Route Text' => '<strong>Pure Text Reasoning</strong>: Routed straight to the <strong>DeepSeek API Gateway</strong> (VIP members ignite the flash-reasoning tier; PRO members weaponize the deep-thinking Pro chain with internal thoughts mapping).',
        'Route Vision' => '<strong>Multimodal Visual Tracking</strong>: Incoming requests containing visual binary data are automatically routed to the <strong>Together AI Serverless Grid</strong> deploying optimized open-source vision networks (Qwen/Llama Vision), performing complex image OCR under 15 seconds.',

        // =========================================================
        // Tab 2: Problems Solved (解決的痛點)
        // =========================================================
        'Solutions Title' => 'Eradicating SaaS Overheads & Security Vulnerabilities',
        'Solutions Desc' => 'SoulMD Hub deliberately re-engineered Web2 backend boundaries to protect creator intellectual property while offloading token overheads.',
        
        'BYOK Proxy Engine' => '1. Stateless BYOK (Bring Your Own Key) Proxy',
        'BYOK Proxy Desc' => 'SaaS projects traditionally collapse due to severe API token bleeding caused by malicious user prompt manipulation. Our unredacted proxy model shifts the balance entirely:',
        'BYOK Storage' => 'API Keys are double-encrypted with industry-grade <strong>AES-256-CBC</strong> locally inside the data ledger.',
        'BYOK Flow' => 'When active, the backend functions as a stateless bridge. It fetches the prompt from the database, pairs it with your private key in memory, streams the request to LLM, and immediately vaporizes the key from execution memory. <strong>Zero storage liability, infinite dialogue cycles.</strong>',
        
        'Timeout Eradication' => '2. GPU-Accelerated Browser Pre-Compression',
        'Timeout Desc' => 'High-resolution smartphone pictures heavily congest server networks, directly spiking the infamous <strong>Cloudflare 100-Second Deadline (HTTP 524 Timeout Error)</strong>. SoulMD Hub permanently kills this roadblock:',
        'Canvas Compression' => 'Before any packet touches the internet interface, a headless HTML5 <code>&lt;canvas&gt;</code> script interceptor downscales the image layer locally to a maximum dimension of 800px at 60% quality.',
        'Canvas Result' => 'This compresses megabyte-scale raw files into an ultra-lean <strong>40KB - 90KB JPEG Base64 stream</strong>, removing payload congestion and latency by over 90%.',
        
        'Context Bleeding Defense' => '3. Background Sliding Summary Compression',
        'Context Bleeding Desc' => 'As dialogue histories grow exponentially, long-standing context matrices burn creator balances. When thread turn limits are breached, our micro-summarizer triggers automatically:',
        'Summary Logic' => 'It extractions older history, compresses past facts down to an optimized sub-150-word index, and atomically flushes it as an persistent facts system prefix, preserving core memories while reducing prompt token drain.',

        // =========================================================
        // Tab 3: Daily Use Cases (日常應用場景)
        // =========================================================
        'UseCases Title' => 'Unlocking Production Workflows',
        'UseCases Desc' => 'Discover how quantitative researchers, software engineers, and automated customer frameworks leverage our API-first grid daily.',
        
        'Usecase Coder Title' => '🛠️ Advanced Modular Programming Tree',
        'Usecase Coder Desc' => 'Engineers load a multi-file structural tree package directly inside the Visual Workspace. <code>SOUL.md</code> dictates code optimization standards, <code>STYLE.md</code> forces dense code outputs, and <code>RULES.md</code> prohibits ancient deprecated libraries. The result is a specialized code auditor accessible via hot-keys.',
        
        'Usecase Headless Title' => '📡 Headless Cross-Origin Automation Routing',
        'Usecase Headless Desc' => 'VIP and PRO members roll secret authorization tokens inside the control hub to bypass standard browser boundaries. Creators build external apps, mobile widgets, or Discord daemons hitting our public <code>/api/chat</code> endpoint natively, utilizing custom-tuned roles with financial-grade security mapping.',

        // =========================================================
        // Tab 4: AgentFi & Tokenomics (區塊鏈通縮與未來)
        // =========================================================
        'Future Title' => 'AgentFi: Tokenized AI Ownership & Deflationary Spiral',
        'Future Desc' => 'SoulMD Hub moves beyond standard Web2 subscription blocks by transforming AI agent architectures into secure, on-chain liquid assets on the NEAR blockchain.',
        
        'Updatable Agent NFT' => '1. Updatable NFT & Prompt IP Shielding',
        'Updatable Agent Desc' => 'To protect prompt engineers from direct raw piracy or source leaking, AI modules are minted into updatable NEP-171 NFT frames. The public blockchain ledger records only the SHA-256 fingerprint hash: <code>sha256(content + random_salt)</code>. The underlying source prompt stays securely dark inside the stateless backend sandbox.',
        
        'Blackbox Rentals' => '2. Blackbox Leasing & Composable Royalty Trees',
        'Blackbox Rentals Desc' => 'NFT owners can list their tokenized agents for sale or rent on the decentralized Marketplace. Users buy active rental windows to call the agent via the Chat Engine, but can never peep inside the prompt layer. Furthermore, smart contract trees automatically route leasing revenues: 90% streams straight to the active creator wallet, and 5% splits recursively to nested model dependencies.',
        
        'Deflationary Spiral Engine' => '3. The Platform Treasury Deflationary Spiral',
        'Deflationary Spiral Desc' => 'Every lifecycle action across the contract matrix captures value directly into the official treasury vault at <code>soulmd-hub.near</code>:',
        'Spiral Stream 1' => '<strong>Minting Fee</strong>: Direct 0.1 NEAR platform tax injected upon every new mint event.',
        'Spiral Stream 2' => '<strong>Market Taxes</strong>: Automatic 5% trading tax on secondary buyouts and 10% leasing tax on rentals.',
        'Spiral Stream 3' => '<strong>Auto-Buyback & Burn Loop</strong>: The smart contract automatically channels 100% of accumulated NEAR treasury revenues into the Ref Finance AMM pool,市價全數 buyback 我們的 native代幣 <code>$SOUL</code>, and shoots them straight into the blockchain burn blackhole address forever. Increased usage translates directly into hard token deflation.',
    ],
    
    'zh' => [
        // SEO & Master Headers
        'SEO Title' => '官方技術文檔與智能體金融生態 - SoulMD Hub',
        'SEO Desc' => '深入了解 SoulMD Hub 的模組化架構、無狀態 BYOK 代理、前端畫布壓縮技術以及 AgentFi 區塊鏈通縮模型。',
        'Platform Documentation' => '平台官方技術文檔',
        'Docs Subtitle' => '在此探索最底層的架構藍圖、日常生產力應用場景，以及驅動整個去中心化智能體網絡的通縮代幣經濟學。',

        // Tab Sidebar Navigation Labels
        'Tab Intro' => '🚀 運作原理與雙引擎',
        'Tab Solutions' => '🛡️ 解決倒咩痛點',
        'Tab UseCases' => '💻 日常應用場景',
        'Tab Future' => '💎 AgentFi 與通縮螺旋',

        // Global Dynamic UI
        'Page' => '頁數',
        'Loading Content...' => '正在解密載入核心架構模組...',
        'Close' => '關閉',

        // =========================================================
        // Tab 1: Introduction (運作原理與用途)
        // =========================================================
        'Intro Title' => '多模態 AI 智能體 SaaS 開源架構',
        'Intro Desc' => 'SoulMD Hub 是一個專門為自訂 AI 角色設計的高效、API 第一（API-First）架構，實現了對基於 Markdown 格式之 AI 靈魂設定的跨平台序列化、安全調用與資產變現。',
        
        'Core Prompt Modularization' => '一、 全球首創提示詞模組化分離 (Modular Prompt)',
        'Core Prompt Desc' => '傳統「大一統」的長篇 System Prompt 容易導致大模型上下文混亂，並隨著對話拉長迅速漏失設定。SoulMD Hub 強制執行嚴格的三檔案模組化架構：',
        'Layout Soul' => '<code>SOUL.md</code> (靈魂核心): 定義智能體的基礎世界觀、核心指令軌道、專業背景與基礎性格。',
        'Layout Style' => '<code>STYLE.md</code> (對話風格): 鎖定說話語氣、口頭禪、排版偏好（如多用代碼塊）與特定語法結構。',
        'Layout Rules' => '<code>RULES.md</code> (鐵律約束): 劃定絕對不可逾越的行為邊界、禁用變數，以及負面邏輯過濾器（Negative Filters）。',
        
        'Dual Engine Matrix' => '二、 智能分流雙 cURL 推理引擎矩陣',
        'Dual Engine Desc' => '為了幫創作者將算力成本壓縮至零，並確保多模態反應速度，請求抵達後端無狀態網關時會立刻進行秒級智能路由分流：',
        'Route Text' => '<strong>純文字推理軌道</strong>: 請求直接路由至 <strong>DeepSeek 官方高性能 API 網關</strong>（VIP 會員調用高效率閃兌 flash 網絡；PRO 會員解鎖 Pro 深度思考大腦，支持思維鏈 CoT 軌跡追蹤）。',
        'Route Vision' => '<strong>多模態視覺分析軌道</strong>: 系統檢測到帶有 binary 圖片封包時，會自動無縫改道至 <strong>Together AI Serverless 矩陣</strong>，調度經過優化的開源視覺大模型（Qwen/Llama Vision），在 15 秒內完成極速 OCR 圖表分析與程式碼還原。',

        // =========================================================
        // Tab 2: Problems Solved (解決的痛點)
        // =========================================================
        'Solutions Title' => '徹底封殺 SaaS 算力破產危機與隱私漏洞',
        'Solutions Desc' => 'SoulMD Hub 從底層邏輯重構了 Web2 後端與大模型之間的交互邊界，既保護了原創作者的 Prompt 產權，又解決了高昂的 Token 費開支。',
        
        'BYOK Proxy Engine' => '一、 無狀態 BYOK (自備金鑰) 代理機制',
        'BYOK Proxy Desc' => '傳統 AI SaaS 平台極易因惡意用戶刷量或長對話導致後端 API 被燒乾而陷入財務危機。我們的無狀態代理徹底扭轉了這個局面：',
        'BYOK Storage' => '用戶填寫的 OpenAI/DeepSeek 金鑰在寫入資料庫時，會經由伺服器超級 Master Key 進行金融級 <strong>AES-256-CBC</strong> 加密混淆存儲。',
        'BYOK Flow' => '對話時後端完全作為一個無狀態中繼站（Proxy）。在內存中將受保護的明文 Prompt 與用戶解密後的 Key 進行瞬間組裝並發送，推理完成後內存立刻執行 <code>unset()</code> <strong>徹底銷毀，伺服器不留日誌、零密鑰洩漏風險，實現無限次暢聊。</strong>',
        
        'Timeout Eradication' => '二、 前端 GPU 畫布預壓縮引擎（打碎 524 逾時）',
        'Timeout Desc' => '高畫質手機照片體積龐大，直接上傳會嚴重癱瘓網絡頻寬，並觸發 <strong>Cloudflare 100 秒請求硬性生死線（HTTP 524 Timeout Error）</strong>。SoulMD Hub 的常駐攔截器從根本解決了這個難題：',
        'Canvas Compression' => '在圖片接觸網絡接口之前，前端 JS 攔截器會利用瀏覽器 headless HTML5 <code>&lt;canvas&gt;</code> 硬件加速，將圖片強制等比例重繪縮放到最大 800px、質量 60% 的範圍。',
        'Canvas Result' => '原本幾 MB 的大圖會瞬間壓縮為 <strong>40KB - 90KB 的 JPEG Base64 數據流</strong>，網絡延遲與伺服器載入開銷暴跌 90% 以上。',
        
        'Context Bleeping Defense' => '三、 後台自動滑動記憶體摘要壓縮層',
        'Context Bleeping Desc' => '對話歷史隨著來回呈幾何級數暴增，導致 prompt tokens 嚴重超標。當對話輪數超過訂閱階級上限（Memory Threshold）時，智能壓縮雷達會自動運轉：',
        'Summary Logic' => '它會強制抽取前段歷史，呼叫高速度 LLM 將舊紀錄壓縮為一段低於 150 字的精簡事实摘要（Facts Index），並作為 system 的 persistent 前綴常駐注入，在不遺忘核心歷史的前提下，幫用戶省下大筆金鑰開支。',

        // =========================================================
        // Tab 3: Daily Use Cases (日常應用場景)
        // =========================================================
        'UseCases Title' => '全面解鎖頂級生產力工作流程',
        'UseCases Desc' => '看看量化交易員、系統架構工程師以及自動化客服開發者在日常工作中，是如何最大化利用我們的 API-First 網絡：',
        
        'Usecase Coder Title' => '🛠️ 模組化極速代碼審查與架構設計',
        'Usecase Coder Desc' => '工程師直接在瀏覽器「視覺多檔案編輯器」中載入完整智能體。由 <code>SOUL.md</code> 指導系統設計標準，<code>STYLE.md</code> 規定代碼輸出密度（拒絕廢話），<code>RULES.md</code> 嚴禁引入過期廢棄代碼庫。一鍵生成終極提示詞或直接在線對話，成為最強代碼大腦。',
        
        'Usecase Headless Title' => '📡 跨網域 Headless Developer 遠端串接',
        'Usecase Headless Title' => '📡 跨網域 Headless Developer 遠端串接',
        'Usecase Headless Desc' => 'VIP 與 PRO 會員可以在控制台一鍵重置輪替專屬 API 金鑰，繞過標準瀏覽器 CSRF 邊界。開發者可以使用 Bearer Token 直接向全站統一端點 <code>/api/chat</code> 發動跨域 POST 請求，將自訂好的 AI 靈魂無縫串接到自己的外部 Web App、手機 Widget、甚至 Discord 機器人。',

        // =========================================================
        // Tab 4: AgentFi & Tokenomics (區塊鏈通縮與未來)
        // =========================================================
        'Future Title' => 'AgentFi: 智能體資產化與鏈上通縮黑洞',
        'Future Desc' => 'SoulMD Hub 徹底打破傳統 Web2 SaaS 的訂閱牆，基於 NEAR 區塊鏈引入 AgentFi 生態，將 AI 角色真正轉化為具備高流動性的鏈上 NEP-171 NFT 資產。',
        
        'Updatable Agent NFT' => '一、 可進化 NFT 存證與 Prompt 版權黑盒保護',
        'Updatable Agent Desc' => '為了解決區塊鏈完全透明導致的創作者系統提示詞被惡意複製（白嫖）問題，NFT 鑄造上鏈時<strong>絕不儲存明文 Prompt</strong>，僅記錄內容的 SHA-256 雜湊指紋 <code>sha256(content + 隨機隨機鹽)</code>。最核心的原創指令 Prompt 依然安全地鎖在平台後端無狀態沙盒內，從根本捍衛智慧財產權。',
        
        'Blackbox Rentals' => '二、 去中心化黑盒出租市集與 composable 版稅樹',
        'Blackbox Rentals Desc' => '持有人隨時可以將智能體 NFT 放在去中心化市集掛牌買賣或開放出租。租客在市集支付租金後，可以在 Chat 引擎與該黑盒智能體自由對話 30 天，但永遠無法窺探底層 Prompt 明文。同時，智能合約內置 Composable Royalty 樹狀路由，租金或轉手收益中的 90% 將即時自動結算至創作者錢包。',
        
        'Deflationary Spiral Engine' => '三、 國庫代幣自動回購與強通縮經濟 spiral',
        'Deflationary Spiral Desc' => '智能合約在整個智能體生命週期的各個關鍵節點上，都會自動為平台官方國庫地址 <code>soulmd-hub.near</code> 進行捕獲捕獲價值：',
        'Spiral Stream 1' => '<strong>發行鑄造稅</strong>: 每次將 Web2 靈魂升級鑄造為 Web3 NFT 時，合約強制收取 0.1 NEAR 平台稅。',
        'Spiral Stream 2' => '<strong>二級市場交易/租賃稅</strong>: 市集買賣成交自動抽水 5%；每次黑盒出租成交自動扣留 10% 租賃稅。',
        'Spiral Stream 3' => '<strong>AMM 自動回購通縮黑洞</strong>: 最硬核之處在於，國庫 <code>soulmd-hub.near</code> 收集到 的 100% NEAR 收益，會定時全數發動跨合約呼叫，直接衝入 Ref Finance 區塊鏈去中心化交易所的 <strong>$SOUL / $NEAR</strong> 流動性池內，按市價不計成本全數回購我們的原生代幣 <code>$SOUL</code>，並直接打入區塊鏈黑洞地址永久銷毀！市集對話越火熱，代幣通縮速度越呈幾何級數飆升！',
    ]
];