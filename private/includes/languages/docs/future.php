<?php
/**
 * SoulMD Hub - AgentFi Tokenomics & Whitepaper Language Dictionary
 * Target: /docs/future
 * 🚀 Fully synchronized with contract/src/contract.ts mechanics.
 */
return [
    'en' => [
        'AgentFi_Whitepaper_Title' => 'AgentFi Whitepaper: $SOUL Capital Ledger & DAO Governance',
        'AgentFi_Whitepaper_Desc' => 'SoulMD Hub transitions AI prompt engineering from a Web2 SaaS model into a decentralized, high-liquidity Web3 ecosystem. Driven by the $SOUL token, the protocol enforces mathematical scarcity, dynamic treasury revenue capture, and a permanent deflationary AMM burn mechanism.',
        
        // 1. Tokenomics
        'Tokenomics_Allocation_Title' => '1. $SOUL Hard-Capped Capital Allocation Matrix',
        'Tokenomics_Allocation_Desc' => 'The platform\'s native utility asset ($SOUL) has a strict, hard-coded maximum supply of 1,000,000,000 (1 Billion) tokens. Zero inflationary minting functions exist in the smart contract. The supply is strategically stratified to align founders, investors, and the decentralized ecosystem.',
        
        'Alloc_Founder' => 'Founders & Core Developers',
        'Alloc_Founder_Pct' => '30%',
        'Alloc_Founder_Amt' => '300,000,000 $SOUL',
        'Alloc_Founder_Desc' => 'Allocated to the original founding team and core engineers. Locked under a strict multi-year smart contract vesting schedule to align with long-term protocol success and infrastructure development.',
        
        'Alloc_Investor' => 'Early Backers & Seed Investors',
        'Alloc_Investor_Pct' => '30%',
        'Alloc_Investor_Amt' => '300,000,000 $SOUL',
        'Alloc_Investor_Desc' => 'Distributed to strategic seed investors. Secured by a cryptographic time-lock with a 6-month complete cliff, followed by a 24-month linear vesting grid to prevent secondary market dumping.',
        
        'Alloc_Treasury' => 'Platform Treasury & Liquidity Pool',
        'Alloc_Treasury_Pct' => '20%',
        'Alloc_Treasury_Amt' => '200,000,000 $SOUL',
        'Alloc_Treasury_Desc' => 'Reserved for continuous system operations, node scaling, core code maintenance, and injecting foundational liquidity into decentralized Automated Market Makers (AMM).',
        
        'Alloc_Staking' => 'Decentralized Staking Rewards',
        'Alloc_Staking_Pct' => '20%',
        'Alloc_Staking_Amt' => '200,000,000 $SOUL',
        'Alloc_Staking_Desc' => 'Allocated for high-yield validation staking, ecosystem creator mining, and incentivizing users to lock tokens, reducing circulating market supply.',

        // Table Headers for Tokenomics Allocation
        'Table_Header_Segment' => 'Segment',
        'Table_Header_Percentage' => 'Percentage',
        'Table_Header_Supply' => 'Supply',
        'Table_Header_Vesting_Utility' => 'Vesting & Utility',

        // 2. Protocol Revenue Streams (Based on contract.ts)
        'Revenue_Stream_Title' => '2. Native Smart Contract Value Capture (Treasury Inflows)',
        'Revenue_Stream_Desc' => 'The SoulMD smart contract executes non-discretionary value capture at every lifecycle milestone of an AI Agent, directly fortifying the soulmd-hub.near treasury vault.',
        
        'Rev_Mint' => 'Asset Minting Tax',
        'Rev_Mint_Desc' => 'Every time a Web2 agent is minted into a Web3 asset, the contract requires a 0.6 NEAR deposit, permanently extracting a 0.1 NEAR protocol fee into the treasury.',
        'Rev_Buy' => 'Market Buyout Friction',
        'Rev_Buy_Desc' => 'Secondary market acquisitions trigger a strict 5% platform fee deduction from the final sale price, while also routing a 5% royalty to the original creator.',
        'Rev_Rent' => 'Blackbox Leasing Tax',
        'Rev_Rent_Desc' => 'When users purchase a 30-day API rental window, the protocol intercepts the stream, redirecting a 10% rental royalty directly to the treasury vault.',
        'Rev_Burn' => 'Asset Burning Fee',
        'Rev_Burn_Desc' => 'Users destroying an NFT to reclaim liquidity face a 0.05 NEAR burn fee, ensuring deflationary pressure on the underlying asset pool.',

        // New: USDT/USDC Upgrade Revenue (NEAR FT)
        'USDT_USDC_Revenue_Title' => '6. USDT / USDC Upgrade Revenue → 100% $SOUL Buy & Burn',
        'USDT_USDC_Revenue_Desc' => 'All net revenue received in USDT and USDC from users purchasing VIP/PRO upgrades via the on-chain ft_transfer_call mechanism (with "upgrade:vip" / "upgrade:pro" messages sent to soulmd-hub.near) will have bare operational costs deducted. 100% of the remaining stablecoin revenue is automatically converted into $SOUL tokens on the Ref Finance AMM (Pool 8546) and permanently burned, directly amplifying the deflationary pressure on the token supply.',

        // 3. Deflationary Spiral
        'AMM_Burn_Title' => '3. The Deflationary AMM Burn Blackhole',
        'AMM_Burn_Desc' => 'The treasury does not hoard accumulated capital. After deducting bare-metal server operations and core development expenditures, the protocol unleashes a permanent deflationary vortex.',
        'Burn_Execution_Title' => '100% Net Revenue Combustion',
        'Burn_Execution_Desc' => '100% of remaining net profits are programmatically injected into the Ref Finance AMM (Pool 8546) via cross-contract calls. The protocol executes market-buy orders for $SOUL tokens, shooting them instantly into a zero-address dead void, forcing exponential token scarcity.',

        // 4. Anti-Rug Pull
        'Anti_Rug_Title' => '4. Cryptographic Anti-Rug Pull & Renters Protection',
        'Anti_Rug_Desc' => 'To guarantee a trustless leasing ecosystem, the contract physically prevents creators from defrauding active renters.',
        'Anti_Rug_Logic' => 'Stateless Execution Lock',
        'Anti_Rug_Logic_Desc' => 'If an AI Agent is actively leased (30-day window), the blockchain validates the rental timestamp. The contract strictly aborts any `burn_soul` calls, permanently blocking the owner from destroying the asset and ruggedly stealing execution rights from active renters.',

        // 5. DAO Governance
        'DAO_Gov_Title' => '5. Decentralized DAO Governance',
        'DAO_Gov_Desc' => 'To ensure mathematical antifragility, the core operational parameters of the contract will be transitioned to a Token-Gated DAO.',
        'DAO_Prop' => 'Parameter Optimization Consensus',
        'DAO_Prop_Desc' => '$SOUL token holders will form the supreme voting quorum to dynamically adjust the 5% market tax, 10% rental fees, and the 0.6 NEAR minting threshold, ensuring the economy scales efficiently.',
    ],
    
    'zh' => [
        'AgentFi_Whitepaper_Title' => 'AgentFi 白皮書：$SOUL 經濟學與 DAO 治理藍圖',
        'AgentFi_Whitepaper_Desc' => 'SoulMD Hub 徹底打破傳統 Web2 SaaS 的基礎設施限制，將 AI 提示詞架構重塑為具備鏈上剛性稀缺度的去中心化合約資產，並以通縮代幣網絡作為運轉引擎。',
        
        // 1. Tokenomics
        'Tokenomics_Allocation_Title' => '一、$SOUL 原生通縮資產全域分配矩陣',
        'Tokenomics_Allocation_Desc' => '平台原生權益代幣 $SOUL 總發行量上限死鎖為 1,000,000,000 粒 (十億)。合約底層不留任何通脹鑄造接口。資本權重經過高密度的戰略劃分，確保創辦團隊利益對齊、維持底層研發，並推動去中心化生態。',
        
        'Alloc_Founder' => '創辦人與核心開發團隊',
        'Alloc_Founder_Pct' => '30%',
        'Alloc_Founder_Amt' => '300,000,000 $SOUL',
        'Alloc_Founder_Desc' => '專屬分配予原創架構師與核心開發團隊。由智能合約執行嚴格的長期斷崖與線性解鎖，確保團隊與協議長遠發展的利益絕對一致。',
        
        'Alloc_Investor' => '早期戰略與種子輪投資人',
        'Alloc_Investor_Pct' => '30%',
        'Alloc_Investor_Amt' => '300,000,000 $SOUL',
        'Alloc_Investor_Desc' => '分配予早期機構級戰略投資人。由智能合約實施密碼學硬性鎖倉，內置 6 個月的完整斷崖期 (Cliff)，隨後進入 24 個月的線性級聯解鎖釋放。',
        
        'Alloc_Treasury' => '平台國庫與流動性儲備',
        'Alloc_Treasury_Pct' => '20%',
        'Alloc_Treasury_Amt' => '200,000,000 $SOUL',
        'Alloc_Treasury_Desc' => '資金嚴格鎖定於官方多簽金庫中，專門用於核心架構擴容維護、代碼迭代、以及 AMM 去中心化交易所的初始流動性注資與持穩。',
        
        'Alloc_Staking' => '去中心化高收益質押獎勵',
        'Alloc_Staking_Pct' => '20%',
        'Alloc_Staking_Amt' => '200,000,000 $SOUL',
        'Alloc_Staking_Desc' => '用於分配給長期鎖倉質押者、驅動生態創作者開採，並透過高收益利息激勵用戶鎖定代幣，大幅降低市場流通拋壓。',

        // Table Headers for Tokenomics Allocation
        'Table_Header_Segment' => '部分',
        'Table_Header_Percentage' => '百分比',
        'Table_Header_Supply' => '供應量',
        'Table_Header_Vesting_Utility' => '歸屬與用途',

        // 2. Protocol Revenue Streams (Based on contract.ts)
        'Revenue_Stream_Title' => '二、智能合約硬性價值捕獲 (國庫收入流)',
        'Revenue_Stream_Desc' => '底層智能合約在智能體生命的每一個核心商業操作點上強制實施價值捕獲，所有資金流會無差別匯聚至 soulmd-hub.near 金庫：',
        
        'Rev_Mint' => '發行鑄造稅 (Minting Fee)',
        'Rev_Mint_Desc' => '每次將 Web2 角色鑄造為 Web3 資產時，合約強制要求 0.6 NEAR 的質押金，並自動抽取 0.1 NEAR 的平台發行稅打入國庫。',
        'Rev_Buy' => '二級市場交易稅 (Buyout Fee)',
        'Rev_Buy_Desc' => '市集進行智能體產權永久買斷成交時，合約自動抽水 5% 平台成交摩擦稅；若賣家非原創者，額外抽取 5% 遞歸版稅予原創者。',
        'Rev_Rent' => '黑盒租賃稅 (Rental Fee)',
        'Rev_Rent_Desc' => '租客支付租金取得 30 天對話授權時，合約會即時攔截資金流，自動扣留 10% 作為平台網絡租賃稅。',
        'Rev_Burn' => '資產銷毀手續費 (Burn Fee)',
        'Rev_Burn_Desc' => '擁有者主動銷毀 NFT 以取回底層質押金時，合約將硬性扣除 0.05 NEAR 作為銷毀手續費，加劇資產稀缺性。',

        // New: USDT/USDC Upgrade Revenue (NEAR FT)
        'USDT_USDC_Revenue_Title' => '六、USDT / USDC 升級收入 → 100% 回購 $SOUL 並銷毀',
        'USDT_USDC_Revenue_Desc' => '所有透過鏈上 ft_transfer_call 機制（訊息為 "upgrade:vip" / "upgrade:pro"，發送到 soulmd-hub.near）購買 VIP/PRO 升級所收到的 USDT 和 USDC 淨收入，在扣除基礎營運成本後，剩餘的 100% 穩定幣收入將自動透過 Ref Finance AMM (Pool 8546) 按市價全數買入 $SOUL 代幣，並立即打入區塊鏈黑洞地址永久銷毀，直接加強代幣的通縮壓力。',

        // 3. Deflationary Spiral
        'AMM_Burn_Title' => '三、淨收入全量回購與代幣強通縮黑洞',
        'AMM_Burn_Desc' => '官方國庫不會囤積死錢。在確保平台基礎設施與營運安全後，系統將啟動不可逆的永動通縮螺旋：',
        'Burn_Execution_Title' => '100% 淨利潤全量銷毀 (Net Income Burn)',
        'Burn_Execution_Desc' => '協議會實時統計平台營運狀況。在扣除伺服器運作及代碼開發的必要支出後，剩餘的 <strong>100% 平台淨利潤收入</strong> 將定期透過跨合約調用，直接衝入 Ref Finance (Pool 8546) 去中心化交易所的流動性池內，按市價不計成本全數回購 $SOUL 代幣，並直接打入區塊鏈黑洞地址永久銷毀！',

        // 4. Anti-Rug Pull
        'Anti_Rug_Title' => '四、密碼學防惡意欺詐與租客保護機制 (Anti-Rug Pull)',
        'Anti_Rug_Desc' => '為了徹底扼殺資產擁有者在市集上收了租金卻立刻銷毀資產跑路（Rug Pull）的行業亂象，合約內置了時間鎖定防禦網：',
        'Anti_Rug_Logic' => '無狀態租期熔斷鎖 (Stateless Execution Lock)',
        'Anti_Rug_Logic_Desc' => '底層 <code>burn_soul</code> 合約強制輪詢租客時間戳。只要該 NFT 仍有活躍的 30 天租客授權，區塊鏈將直接熔斷並拒絕資產銷毀請求，100% 物理級別捍衛消費者租賃權益。',

        // 5. DAO Governance
        'DAO_Gov_Title' => '五、去中心化 DAO 代幣治理與參數升級',
        'DAO_Gov_Desc' => '為了確保全站架構具備數學上的反脆弱性，全站所有核心參數的控制權將逐步完全移交給原生的 Token-Gated DAO 治理結構。',
        'DAO_Prop' => '成比例系統指標動態微調 (Parameter Optimization)',
        'DAO_Prop_Desc' => '$SOUL 代幣持有者將組成最高決策群體，可對系統變數進行投票微調，包括修改 0.6 NEAR 鑄造質押閾值、調整 5% 交易稅與 10% 租賃稅比例，確保經濟體系健康運轉。',
    ]
];