import { NearBindgen, near, call, view, UnorderedMap, assert } from 'near-sdk-js';

class TokenMetadata {
    title: string;
    description: string;
    extra: string;       
    reference: string;   
    creator_id: string;  

    constructor(title: string, description: string, extra: string, reference: string, creator_id: string) {
        this.title = title;
        this.description = description;
        this.extra = extra;
        this.reference = reference;
        this.creator_id = creator_id;
    }
}

class Token {
    owner_id: string;
    metadata: TokenMetadata;
    sale_price: string | null;  
    rent_price: string | null;  
    renters: { [account_id: string]: string }; 

    constructor(owner_id: string, metadata: TokenMetadata) {
        this.owner_id = owner_id;
        this.metadata = metadata;
        this.sale_price = null;
        this.rent_price = null;
        this.renters = {};
    }
}

@NearBindgen({})
class SoulMDAgentFi {
    tokens = new UnorderedMap<Token>('t');
    platform_wallet: string = 'soulmd-hub.near'; 

    @call({ payableFunction: true })
    mint_soul({ token_id, title, description, hash, reference }: { token_id: string, title: string, description: string, hash: string, reference: string }) {
        const caller = near.predecessorAccountId();
        const deposit = near.attachedDeposit() as bigint;

        const required_deposit = 600000000000000000000000n; 
        assert(deposit >= required_deposit, "Error: Minting requires exactly 0.6 NEAR");
        assert(!this.tokens.get(token_id), "Error: Token ID already exists.");

        const metadata = new TokenMetadata(title, description, hash, reference, caller);
        const token = new Token(caller, metadata);
        this.tokens.set(token_id, token);

        const platform_fee = 100000000000000000000000n; 
        const promise = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(promise, platform_fee);

        near.log(`Minted Soul [${token_id}] by ${caller}`);
    }

    @call({})
    update_soul_hash({ token_id, new_hash }: { token_id: string, new_hash: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Security Error: Only the current owner can update hash.");

        token.metadata.extra = new_hash;
        this.tokens.set(token_id, token);

        near.log(`Soul [${token_id}] evolved to new hash: ${new_hash}`);
    }

    @call({})
    list_for_sale({ token_id, price }: { token_id: string, price: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Error: Only owner can list for sale.");
        
        token.sale_price = price;
        this.tokens.set(token_id, token);
        near.log(`[${token_id}] listed for sale at ${price} yoctoNEAR`);
    }

    @call({ payableFunction: true })
    buy_soul({ token_id }: { token_id: string }) {
        const buyer = near.predecessorAccountId();
        const deposit = near.attachedDeposit() as bigint;
        const token = this.tokens.get(token_id);
        
        assert(token !== null, "Error: Token not found.");
        assert(token.sale_price !== null, "Error: Token is not for sale.");
        
        const price = BigInt(token.sale_price);
        assert(deposit >= price, `Error: Not enough deposit. Price is ${price}`);

        const previous_owner = token.owner_id;
        const creator = token.metadata.creator_id;

        const platform_fee = (price * 5n) / 100n;
        let creator_royalty = 0n;
        let seller_revenue = price - platform_fee;

        if (creator !== previous_owner) {
            creator_royalty = (price * 5n) / 100n;
            seller_revenue -= creator_royalty;
            const pCreator = near.promiseBatchCreate(creator);
            near.promiseBatchActionTransfer(pCreator, creator_royalty);
        }

        const pPlatform = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(pPlatform, platform_fee);

        const pSeller = near.promiseBatchCreate(previous_owner);
        near.promiseBatchActionTransfer(pSeller, seller_revenue);

        token.owner_id = buyer;
        token.sale_price = null;
        token.rent_price = null;
        token.renters = {}; 
        this.tokens.set(token_id, token);

        near.log(`[${token_id}] bought by ${buyer} for ${price}`);
    }

    @call({})
    list_for_rent({ token_id, price }: { token_id: string, price: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Error: Only owner can list for rent.");
        
        token.rent_price = price;
        this.tokens.set(token_id, token);
        near.log(`[${token_id}] listed for rent at ${price} yoctoNEAR / 30 Days`);
    }

    @call({ payableFunction: true })
    rent_soul({ token_id }: { token_id: string }) {
        const renter = near.predecessorAccountId();
        const deposit = near.attachedDeposit() as bigint;
        const token = this.tokens.get(token_id);
        
        assert(token !== null, "Error: Token not found.");
        assert(token.rent_price !== null, "Error: Token is not for rent.");
        
        const price = BigInt(token.rent_price);
        assert(deposit >= price, `Error: Not enough deposit. Rent is ${price}`);

        const platform_fee = (price * 10n) / 100n;
        const owner_revenue = price - platform_fee;

        const pPlatform = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(pPlatform, platform_fee);

        const pOwner = near.promiseBatchCreate(token.owner_id);
        near.promiseBatchActionTransfer(pOwner, owner_revenue);

        const thirty_days_ns = 2592000000000000n;
        const current_time = near.blockTimestamp();
        
        // 🧹 垃圾回收：清理已過期的舊租客，釋放合約記憶體防止 Storage Leak
        for (const existing_renter in token.renters) {
            if (BigInt(token.renters[existing_renter]) < current_time) {
                delete token.renters[existing_renter];
            }
        }
        
        let current_expiry = token.renters[renter] ? BigInt(token.renters[renter]) : current_time;
        if (current_expiry < current_time) current_expiry = current_time;
        
        token.renters[renter] = (current_expiry + thirty_days_ns).toString();
        this.tokens.set(token_id, token);

        near.log(`[${token_id}] rented by ${renter}. Expires at ${token.renters[renter]}`);
    }

    @call({})
    burn_soul({ token_id }: { token_id: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Security Error: Only the owner can burn.");

        this.tokens.remove(token_id);

        const refund_amount = 450000000000000000000000n; 
        const burn_fee =       50000000000000000000000n; 

        const p1 = near.promiseBatchCreate(caller);
        near.promiseBatchActionTransfer(p1, refund_amount);

        const p2 = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(p2, burn_fee);

        near.log(`Burned [${token_id}]. Refunded 0.45 NEAR to ${caller}`);
    }

    @view({})
    get_soul({ token_id }: { token_id: string }): Token | null {
        return this.tokens.get(token_id);
    }

    @view({})
    check_access({ token_id, account_id }: { token_id: string, account_id: string }): boolean {
        const token = this.tokens.get(token_id);
        if (!token) return false;
        
        if (token.owner_id === account_id) return true;
        
        if (token.renters[account_id]) {
            const expiry = BigInt(token.renters[account_id]);
            if (expiry > near.blockTimestamp()) {
                return true;
            }
        }
        return false;
    }

    // ==========================================
    // 🌪️ 6. 通縮螺旋：AMM 自動回購與銷毀 ($SOUL Burn)
    // ==========================================
    @call({})
    auto_buyback_and_burn({ amount_in_near }: { amount_in_near: string }) {
        const caller = near.predecessorAccountId();
        
        assert(caller === this.platform_wallet, "Security Error: Only platform treasury can trigger buyback.");

        const amount = BigInt(amount_in_near);
        
        // 1. 創建 Batch Promise 目標為 wrap.near (官方包裝合約)
        const p1 = near.promiseBatchCreate("wrap.near");
        
        // 2. 第一步：將夾帶的 Native NEAR 存入 wrap.near 轉換為 wNEAR
        near.promiseBatchActionFunctionCall(p1, "near_deposit", "", amount, 30000000000000n);

        // 3. 準備發送給 Ref Finance 進行 Swap 的指令結構
        const swap_msg = JSON.stringify({
            force: 0,
            actions: [{
                pool_id: 8546, 
                token_in: "wrap.near",
                token_out: "soul.tkn.near", 
                amount_in: amount_in_near,
                min_amount_out: "1" 
            }]
        });

        const transfer_args = JSON.stringify({
            receiver_id: "v2.ref-finance.near",
            amount: amount_in_near,
            msg: swap_msg
        });

        // 4. 第二步：將剛才轉好的 wNEAR 透過 ft_transfer_call 射入 Ref Finance 觸發交易
        // 必須夾帶 1 yoctoNEAR 以符合安全規範，並提供充足 Gas (100 TGas)
        near.promiseBatchActionFunctionCall(p1, "ft_transfer_call", transfer_args, 1n, 100000000000000n);
        
        near.log(`🌪️ Auto-Buyback triggered! Wrapped ${amount_in_near} NEAR and routed to Pool 8546 for $SOUL burn.`);
    }
}