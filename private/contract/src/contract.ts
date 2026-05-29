import { NearBindgen, near, call, view, UnorderedMap, assert } from 'near-sdk-js';

// 1. 定義 NFT Metadata 結構 (只存 Hash，不存明文 Prompt)
class TokenMetadata {
    title: string;
    description: string;
    extra: string;       // 🚀 核心：存放 Prompt 嘅 SHA-256 內容指紋 (Hash)
    reference: string;   // 指向 SoulMD Hub 平台 API，用於驗證數據完整性

    constructor(title: string, description: string, extra: string, reference: string) {
        this.title = title;
        this.description = description;
        this.extra = extra;
        this.reference = reference;
    }
}

// 2. 定義 NFT Token 實體
class Token {
    owner_id: string;
    metadata: TokenMetadata;

    constructor(owner_id: string, metadata: TokenMetadata) {
        this.owner_id = owner_id;
        this.metadata = metadata;
    }
}

@NearBindgen({})
class SoulMDAgentFi {
    tokens = new UnorderedMap<Token>('t');
    
    // 🏦 官方金庫地址 (所有抽水都會自動打入呢個錢包)
    platform_wallet: string = 'soulmd-hub.near'; 

    // ==========================================
    // 🚀 1. 鑄造模型 (Minting) - 內置 0.1 NEAR 平台稅
    // ==========================================
    @call({ payableFunction: true })
    mint_soul({ token_id, title, description, hash, reference }: { token_id: string, title: string, description: string, hash: string, reference: string }) {
        const caller = near.predecessorAccountId();
        const deposit = near.attachedDeposit() as bigint;

        // 規定：鑄造需要 0.6 NEAR (0.5 NEAR 儲存質押 + 0.1 NEAR 平台發行稅)
        // 1 NEAR = 10^24 yoctoNEAR
        const required_deposit = 600000000000000000000000n; // 0.6 NEAR
        assert(deposit >= required_deposit, "Error: Minting requires exactly 0.6 NEAR (0.5 for storage, 0.1 for platform fee).");
        assert(!this.tokens.get(token_id), "Error: Token ID already exists.");

        // 建立 NFT
        const metadata = new TokenMetadata(title, description, hash, reference);
        const token = new Token(caller, metadata);
        this.tokens.set(token_id, token);

        // 💸 平台抽水：將 0.1 NEAR 轉入官方金庫
        const platform_fee = 100000000000000000000000n; // 0.1 NEAR
        const promise = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(promise, platform_fee);

        near.log(`Minted Soul [${token_id}] by ${caller}. Platform earned 0.1 NEAR.`);
    }

    // ==========================================
    // 🚀 2. 模型進化 (Update Hash) - 防篡改機制核心
    // ==========================================
    @call({})
    update_soul_hash({ token_id, new_hash }: { token_id: string, new_hash: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Security Error: Only the current owner can update this AI's hash.");

        // 更新 Hash 並重新儲存
        token.metadata.extra = new_hash;
        this.tokens.set(token_id, token);

        near.log(`Soul [${token_id}] evolved to new hash: ${new_hash} by ${caller}`);
    }

    // ==========================================
    // 🚀 3. 銷毀回收 (Burn) - 內置 0.05 NEAR 手續費
    // ==========================================
    @call({})
    burn_soul({ token_id }: { token_id: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Security Error: Only the owner can burn this token.");

        // 從區塊鏈刪除資料，釋放佔用的儲存空間
        this.tokens.remove(token_id);

        // 💸 分配退款：退 0.45 NEAR 給持有人，抽 0.05 NEAR 給平台
        const refund_amount = 450000000000000000000000n; // 0.45 NEAR
        const burn_fee =       50000000000000000000000n; // 0.05 NEAR

        // 轉錢給 Owner
        const promise1 = near.promiseBatchCreate(caller);
        near.promiseBatchActionTransfer(promise1, refund_amount);

        // 抽水給 Platform
        const promise2 = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(promise2, burn_fee);

        near.log(`Burned Soul [${token_id}]. Refunded 0.45 NEAR to ${caller}, Platform earned 0.05 NEAR.`);
    }

    // ==========================================
    // 🔍 讀取功能 (View)
    // ==========================================
    @view({})
    get_soul({ token_id }: { token_id: string }): Token | null {
        return this.tokens.get(token_id);
    }
}