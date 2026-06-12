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
    upgrade_credits = new UnorderedMap<string>('uc'); // PoC: account:tier -> timestamp for FT upgrade payments (USDT/USDC)
    platform_wallet: string = 'soulmd-hub.near';

    // FT payment token contracts (mainnet) - VERIFY THESE ON EXPLORER BEFORE DEPLOY
    // USDT (Tether NEP-141): https://explorer.near.org/accounts/usdt.tether-token.near
    readonly USDT_CONTRACT: string = 'usdt.tether-token.near';
    // USDC on NEAR (common bridged/official NEP-141 address - confirm current mainnet)
    readonly USDC_CONTRACT: string = '17208628f84f5d6ad33f0da3bbbeb27ffcb398eac501a31bd6ad2011e36133a1';

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

        // state 先更新，然後 schedule 平台費轉帳（安全順序）
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
        
        // 🚨 終極漏洞修復：當價格為 0 時，設定為 null (非賣品)
        if (price === "0" || price === "") {
            token.sale_price = null;
            near.log(`[${token_id}] sale listing cancelled.`);
        } else {
            token.sale_price = price;
            near.log(`[${token_id}] listed for sale at ${price} yoctoNEAR`);
        }
        
        this.tokens.set(token_id, token);
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

        // ✅ C 修復：先更新 state（ownership transfer），然後先 schedule 轉帳 promises
        // 這樣如果 function 後面 panic，state revert，deposit 由 NEAR 處理（通常 refund）
        // 避免 promises 已 schedule 但 ownership 未變（buyer 付錢但無 token）
        token.owner_id = buyer;
        token.sale_price = null;
        token.rent_price = null;
        this.tokens.set(token_id, token);

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

        near.log(`[${token_id}] bought by ${buyer} for ${price}. Active rentals preserved.`);
    }

    @call({})
    list_for_rent({ token_id, price }: { token_id: string, price: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Error: Only owner can list for rent.");
        
        // 🚨 終極漏洞修復：當租金為 0 時，設定為 null (不予出租)
        if (price === "0" || price === "") {
            token.rent_price = null;
            near.log(`[${token_id}] rent listing cancelled.`);
        } else {
            token.rent_price = price;
            near.log(`[${token_id}] listed for rent at ${price} yoctoNEAR / 30 Days`);
        }
        
        this.tokens.set(token_id, token);
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

        const thirty_days_ns = 2592000000000000n;
        const current_time = near.blockTimestamp();
        
        for (const existing_renter in token.renters) {
            if (BigInt(token.renters[existing_renter]) < current_time) {
                delete token.renters[existing_renter];
            }
        }
        
        let current_expiry = token.renters[renter] ? BigInt(token.renters[renter]) : current_time;
        if (current_expiry < current_time) current_expiry = current_time;
        
        token.renters[renter] = (current_expiry + thirty_days_ns).toString();
        this.tokens.set(token_id, token);

        // ✅ C 修復：先更新 rental state（加 renter），再 schedule 付款 promises
        // 確保 rental 授予後才付款（如果中間 panic，state revert，deposit 退回）
        const pPlatform = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(pPlatform, platform_fee);

        const pOwner = near.promiseBatchCreate(token.owner_id);
        near.promiseBatchActionTransfer(pOwner, owner_revenue);

        near.log(`[${token_id}] rented by ${renter}. Expires at ${token.renters[renter]}`);
    }

    @call({})
    burn_soul({ token_id }: { token_id: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Security Error: Only the owner can burn.");

        const current_time = near.blockTimestamp();
        for (const renter in token.renters) {
            assert(BigInt(token.renters[renter]) < current_time, "Rug Pull Prevention: Cannot burn NFT while there are active renters.");
        }

        this.tokens.remove(token_id);

        // state (remove) 先做，然後 schedule 退款（安全順序，避免 money 出但 token 未 burn）
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

    @call({})
    auto_buyback_and_burn({ amount_in_near }: { amount_in_near: string }) {
        const caller = near.predecessorAccountId();
        
        assert(caller === this.platform_wallet, "Security Error: Only platform treasury can trigger buyback.");

        const amount = BigInt(amount_in_near);
        
        const p1 = near.promiseBatchCreate("wrap.near");
        near.promiseBatchActionFunctionCall(p1, "near_deposit", "", amount, 30000000000000n);

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

        near.promiseBatchActionFunctionCall(p1, "ft_transfer_call", transfer_args, 1n, 100000000000000n);
        
        // 無 token state 變更，純 cross-contract（platform 權限）
        near.log(`🌪️ Auto-Buyback triggered! Wrapped ${amount_in_near} NEAR and routed to Pool 8546 for $SOUL burn.`);
    }

    // =====================================================
    // PoC: Accept USDT / USDC for Upgrade payments (replace PayPal flow)
    // User calls ft_transfer_call on the token contract, targeting this contract.
    // msg = "upgrade:vip" or "upgrade:pro"
    // Contract accepts only from whitelisted FT contracts, for minimum amounts (demo 5/15 USD equiv with 6 decimals).
    // On success: record credit in upgrade_credits. Frontend/PHP can claim + apply to DB.
    // Tokens stay in contract (or can be forwarded to platform_wallet via ft_transfer in prod).
    // =====================================================
    @call({})
    ft_on_transfer({ sender_id, amount, msg }: { sender_id: string, amount: string, msg: string }): string {
        const token = near.predecessorAccountId(); // The FT contract that called us (security critical)
        const isUsdt = token === this.USDT_CONTRACT;
        const isUsdc = token === this.USDC_CONTRACT;

        if (!isUsdt && !isUsdc) {
            near.log(`Reject FT: unknown token ${token}`);
            return amount; // full refund to sender
        }

        // Simple PoC parsing (production: use JSON.parse with try/catch + validation)
        let tier = '';
        const lowerMsg = (msg || '').toLowerCase();
        if (lowerMsg.includes('vip') || lowerMsg.includes('standard')) {
            tier = 'vip';
        } else if (lowerMsg.includes('pro') || lowerMsg.includes('advanced')) {
            tier = 'pro';
        }

        if (!tier) {
            near.log(`Reject: no valid tier in msg="${msg}"`);
            return amount;
        }

        // Demo pricing: 5 USDT/USDC for VIP, 15 for PRO (6 decimals)
        const required = tier === 'vip' ? '5000000' : '15000000';
        const paid = BigInt(amount);

        if (paid < BigInt(required)) {
            near.log(`Reject: insufficient ${tier} payment. Paid ${amount}, need ${required}`);
            return amount;
        }

        // Grant credit (idempotent key)
        const creditKey = `${sender_id}:${tier}`;
        const now = near.blockTimestamp().toString();
        this.upgrade_credits.set(creditKey, now);

        near.log(`✅ FT Upgrade credit granted: ${sender_id} paid ${amount} ${isUsdt ? 'USDT' : 'USDC'} for ${tier}`);

        // Keep the funds in the contract for now (PoC). In real: ft_transfer to platform_wallet
        return '0';
    }

    @view({})
    has_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }): boolean {
        const key = `${account_id}:${tier}`;
        const ts = this.upgrade_credits.get(key);
        if (!ts) return false;
        // Optional: expiry on credit itself (e.g. 1 hour), but for PoC just existence
        return true;
    }

    @call({})
    clear_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }) {
        // Called by platform after successful DB upgrade (only platform can clear)
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, 'Only platform can clear credits');
        const key = `${account_id}:${tier}`;
        this.upgrade_credits.remove(key);
        near.log(`Cleared upgrade credit for ${account_id} ${tier}`);
    }
}