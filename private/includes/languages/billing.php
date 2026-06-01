<?php
/**
 * SoulMD Hub - i18n Translation Dictionary
 * Target: billing.php (Billing & Subscriptions)
 */

return [
    'en' => [
        // SEO & Headers
        'SEO Title' => 'Billing & Subscriptions - SoulMD Hub',
        'SEO Desc' => 'Manage your premium AI subscription and view billing history.',
        'Billing & Subscriptions' => 'Billing & Subscriptions',
        'Billing Subtitle' => 'Manage your premium tier passes, context token allocations, and transaction receipts.',
        'Renew Subscription' => 'Renew Subscription',
        'Upgrade Plan' => 'Upgrade Plan',
        
        // Subscription Status
        'Current Subscription Status' => 'Current Subscription Status',
        'Tier Member' => ':tier Member',
        'Active Pass' => 'Active Pass',
        'Active Pass Desc' => 'Your enterprise-grade AI session cluster layer is unlocked and fully verified. Operational until: <b class="text-zinc-100 font-mono">:date</b>.',
        'Days Remaining' => 'Days Remaining',
        'Manual Lifecycle Management:' => 'Manual Lifecycle Management:',
        'Lifecycle Desc' => 'To maintain strict data integrity, we enforce zero automatic recurring billings. Your tokens will automatically expire at the set date. Simply purchase a new license pass to continue.',
        
        // Expired Status
        'Subscription Expired' => 'Subscription Expired',
        'Expired Desc' => 'Your premium access ended on <b class="text-zinc-300">:date</b>. Please renew your plan to restore Headless API access, Vision AI, and unlimited chat capabilities.',
        'Renew Plan' => 'Renew Plan',
        
        // Free Tier Status
        'Free Tier Account' => 'Free Tier Account',
        'Free Tier Desc' => 'You are currently running on standard sandbox trial limits. Upgrade to expand context size.',
        'View Premium Plans' => 'View Premium Plans',
        
        // Ledger Tabs
        'Web2 Pass' => 'Platform Subscription (Web2 Pass)',
        'AgentFi Web3' => 'On-Chain Assets (AgentFi Web3)',
        
        // Transaction Ledger (Web2)
        'Transaction Ledger' => 'Transaction Ledger',
        'No billing rows located' => 'No billing rows located',
        'No billing desc' => 'You haven\'t executed any gateway premium orders inside this account cluster yet.',
        
        // Table Headers (Web2)
        'Timestamp' => 'Timestamp',
        'Transaction ID' => 'Transaction ID',
        'Plan' => 'Plan',
        'Status' => 'Status',
        'Gross Amount' => 'Gross Amount',
        'Action' => 'Action',
        'Receipt' => 'Receipt',
        
        // Status Tooltips
        'Unknown status.' => 'Unknown status.',
        'Payment cleared successfully.' => 'Payment cleared successfully.',
        'Awaiting gateway clearance. Assets will provision once settled.' => 'Awaiting gateway clearance. Assets will provision once settled.',
        'Transaction was declined by the issuer or expired.' => 'Transaction was declined by the issuer or expired.',
        'Manual refund executed. Assets revoked.' => 'Manual refund executed. Assets revoked.',
        'Dispute / Chargeback filed. Access terminated.' => 'Dispute / Chargeback filed. Access terminated.',
        
        // AgentFi Web3 Ledger (Web3)
        'No Web3 Wallet Detected' => 'No Web3 Wallet Detected',
        'Wallet bind prompt' => 'You must permanently bind a NEAR mainnet wallet to your account to track decentralized rentals and market positions here.',
        'Go to Bind Wallet' => 'Go to Settings to Bind',
        'Scanning Blockchain...' => 'Scanning Blockchain Contract Ledger...',
        'No Web3 positions' => 'No Web3 positions or transactions related to your current wallet were found on the smart contract.',
        'Blockchain connection failed' => 'Blockchain connection failed. Please try again later.',
        
        // Table Headers & Dynamic Statuses (Web3)
        'Asset Type' => 'Asset Type',
        'Agent Asset' => 'Agent Asset',
        'On-Chain Role' => 'On-Chain Role',
        'Market Status' => 'Market Status',
        'Live Action' => 'Live Action',
        'Ownership' => 'Ownership',
        'Legal Owner' => 'Legal Owner',
        'Listed for Sale' => 'Listed for Sale',
        'Listed for Rent' => 'Listed for Rent',
        'Idle' => 'Idle (Stored)',
        'Active Lease' => 'Active Lease',
        'Active Renter' => 'Active Renter',
        'Lease Expires At' => 'Lease Expires At',
        'Royalty Node' => 'Royalty Node',
        'Creator' => 'Creator',
        'Perpetual 5% Royalty' => 'Perpetual 5% Royalty',
        'Enter Chat' => 'Enter Chat',
        'View Codebase' => 'View Codebase',
        
        // Pagination & Footer
        'Page' => 'Page',
        'Legal Footer 1' => 'All localized frame logs comply with global cryptographic ledger signatures. As locked inside your binding purchase contract, all active or terminated subscription licenses are completely',
        'NON-REFUNDABLE' => 'NON-REFUNDABLE',
    ],
    
    'zh' => [
        // SEO & Headers
        'SEO Title' => '帳單與訂閱紀錄 - SoulMD Hub',
        'SEO Desc' => '管理您的尊貴 AI 訂閱狀態及檢視交易紀錄。',
        'Billing & Subscriptions' => '帳單與訂閱紀錄',
        'Billing Subtitle' => '管理您的尊貴會員通行證、上下文 Tokens 額度以及交易收據。',
        'Renew Subscription' => '續期訂閱',
        'Upgrade Plan' => '升級計劃',
        
        // Subscription Status
        'Current Subscription Status' => '目前訂閱狀態',
        'Tier Member' => ':tier 會員',
        'Active Pass' => '生效中',
        'Active Pass Desc' => '您的企業級 AI 運算叢集已解鎖並通過驗證。有效期限至：<b class="text-zinc-100 font-mono">:date</b>。',
        'Days Remaining' => '剩餘日數',
        'Manual Lifecycle Management:' => '手動續期安全機制：',
        'Lifecycle Desc' => '為保持嚴格的數據與金融安全，本平台不設任何自動扣款機制 (Zero Auto-Renew)。您的使用配額將於到期日自動失效，只需手動購買新的通行證即可繼續使用。',
        
        // Expired Status
        'Subscription Expired' => '訂閱已過期',
        'Expired Desc' => '您的尊貴會員存取權已於 <b class="text-zinc-300">:date</b> 結束。請續期您的計劃以恢復 Headless API 存取、Vision AI 視覺分析以及無限次對話功能。',
        'Renew Plan' => '立即續期',
        
        // Free Tier Status
        'Free Tier Account' => '免費沙盒帳號',
        'Free Tier Desc' => '您目前正在使用標準免費沙盒的額度限制。升級以擴充記憶體與運算效能。',
        'View Premium Plans' => '檢視尊貴計劃',
        
        // Ledger Tabs
        'Web2 Pass' => '平台方案通行證 (Web2 Pass)',
        'AgentFi Web3' => '鏈上智能體資產 (AgentFi Web3)',
        
        // Transaction Ledger (Web2)
        'Transaction Ledger' => '交易分類帳 (Ledger)',
        'No billing rows located' => '找不到任何帳單紀錄',
        'No billing desc' => '您尚未在此帳號內執行過任何支付網關的尊貴訂閱訂單。',
        
        // Table Headers (Web2)
        'Timestamp' => '時間標記',
        'Transaction ID' => '交易編號 (ID)',
        'Plan' => '訂閱計劃',
        'Status' => '狀態',
        'Gross Amount' => '總金額',
        'Action' => '操作',
        'Receipt' => '收據',
        
        // Status Tooltips
        'Unknown status.' => '未知的交易狀態。',
        'Payment cleared successfully.' => '付款已成功結算。',
        'Awaiting gateway clearance. Assets will provision once settled.' => '正在等待網關結算。款項確認後將自動配發權限。',
        'Transaction was declined by the issuer or expired.' => '交易遭發卡行拒絕或已過期。',
        'Manual refund executed. Assets revoked.' => '已執行手動退款。權限已被撤銷。',
        'Dispute / Chargeback filed. Access terminated.' => '已提出交易爭議/退單。帳號存取權限已終止。',
        
        // AgentFi Web3 Ledger (Web3)
        'No Web3 Wallet Detected' => '未綁定區塊鏈錢包',
        'Wallet bind prompt' => '您需要先將帳號永久綁定一個 NEAR 主網錢包，才能在此處追蹤所有去中心化租務與買賣帳本。',
        'Go to Bind Wallet' => '前往綁定錢包',
        'Scanning Blockchain...' => '正在掃描區塊鏈智能合約帳本...',
        'No Web3 positions' => '區塊鏈合約中暫無任何與您目前錢包相關的交易持倉。',
        'Blockchain connection failed' => '連線至區塊鏈失敗，請稍後重試。',
        
        // Table Headers & Dynamic Statuses (Web3)
        'Asset Type' => '資產類型 (Type)',
        'Agent Asset' => '智能體靈魂 (Agent Asset)',
        'On-Chain Role' => '鏈上身分 (On-Chain Role)',
        'Market Status' => '流動性狀態 (Market Status)',
        'Live Action' => '即時操作 (Action)',
        'Ownership' => '買斷產權',
        'Legal Owner' => '合法持有人',
        'Listed for Sale' => '掛售中',
        'Listed for Rent' => '出租中',
        'Idle' => '閒置 (原型內藏)',
        'Active Lease' => '合約租約',
        'Active Renter' => '活躍租客',
        'Lease Expires At' => '租約有效至',
        'Royalty Node' => '版稅樹節點',
        'Creator' => '創作者',
        'Perpetual 5% Royalty' => '永續 5% 鏈上版稅',
        'Enter Chat' => '進入對話',
        'View Codebase' => '查看代碼庫',
        
        // Pagination & Footer
        'Page' => '頁數',
        'Legal Footer 1' => '所有本地化的帳單日誌皆符合全球加密帳本簽章標準。根據具有約束力的購買合約，所有生效中或已終止的訂閱授權均嚴格規定',
        'NON-REFUNDABLE' => '不設退款 (NON-REFUNDABLE)',
    ]
];