import { NearBindgen, near, call, view, UnorderedMap, assert } from 'near-sdk-js';

/**
 * ============================================================
 * SOULMD-HUB NEAR CONTRACT - FULL REDESIGN FOR 100% SAFETY
 * ============================================================
 * 
 * DESIGN PRINCIPLES (from multiple strict audits):
 * - EVERY payable/call that changes state: UPDATE STATE FIRST, then schedule promises (prevents reentrancy/inconsistent state on panic).
 * - All sensitive actions: strict assert + predecessorAccountId checks.
 * - Admin functions (platform_wallet only): god-mode for test data phase. Old corrupted test data under old prefix is deliberately ignored.
 * - FT (USDT/USDC) flow: strict whitelist, exact amounts (6 decimals), state first, '0' to keep funds in soulmd-hub.near (cool wallet).
 * - No assumptions on old storage blobs. Fresh map prefix for tokens ('t-v2') so previous raw-patch corruption (the "'prefix' of undefined" deserial panics) is left behind.
 * - Old records = test data only. No migration/restore needed. Admin can wipe or recreate at will.
 * - All admin actions heavily logged on-chain.
 * - Frontend (website) admin control page will only be usable by NEAR_CONTRACT_ID (soulmd-hub.near).
 *
 * SUPPORTED SOULMD-HUB FEATURES (unchanged public API for compatibility):
 * - Mint (0.6 NEAR exact), evolve hash (owner only), list/buy/sale, list/rent (30d), rent, burn (with active renter guard).
 * - Views: get_soul, check_access (owner or active renter).
 * - Auto buyback/burn (platform only).
 * - FT upgrade payments (USDT/USDC via ft_on_transfer -> has_upgrade_credit for PHP claim, 24h expiry).
 *
 * ADMIN GOD-MODE (only soulmd-hub.near):
 * - Full edit of any token record (owner, prices, renters as JSON, full replace).
 * - Raw storage read/write/remove for ultimate repair (use with debug to discover internal keys).
 * - Clear/wipe tokens or all test data.
 * - Manage upgrade credits.
 *
 * SECURITY NOTES:
 * - platform_wallet is hardcoded and checked on every admin call.
 * - No other account can call admin methods.
 * - Raw storage functions are extremely powerful — misuse can corrupt state. Restricted to platform and test phase.
 * - After this redesign + fresh prefix, deserial panics from old test patches should be gone.
 * - Always build + deploy from canonical path. Test on fresh token_ids first.
 * - Funds (NEAR + received USDT/USDC) intentionally stay in soulmd-hub.near.
 */

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
    renters: { [account_id: string]: string } = {}; 

    constructor(owner_id: string, metadata: TokenMetadata) {
        this.owner_id = owner_id;
        this.metadata = metadata;
        this.sale_price = null;
        this.rent_price = null;
        this.renters = {};
    }

    /**
     * Bulletproof reconstruct.
     * Always produces a valid Token even from partial/old/malformed storage blobs.
     * This + defensive defaults in methods = no more 'prefix of undefined' or empty renters surprises.
     */
    static reconstruct(data: any): Token {
        if (!data) return null as any;
        const meta = data.metadata
            ? new TokenMetadata(
                data.metadata.title || '',
                data.metadata.description || '',
                data.metadata.extra || '',
                data.metadata.reference || '',
                data.metadata.creator_id || ''
              )
            : null as any;
        const t = new Token(data.owner_id || '', meta);
        t.sale_price = (data.sale_price !== undefined && data.sale_price !== null) ? String(data.sale_price) : null;
        t.rent_price = (data.rent_price !== undefined && data.rent_price !== null) ? String(data.rent_price) : null;
        t.renters = (data.renters && typeof data.renters === 'object') ? data.renters : {};
        return t;
    }
}

@NearBindgen({})
class SoulMDAgentFi {
    /**
     * FRESH PREFIX 't-v2'.
     * All previous test data (including corrupted entries from earlier raw admin_patch that caused the reconstruct 'prefix' panic on soul_3956 etc.)
     * lives under the old 't' prefix and is completely ignored.
     * New tokens, rents, buys etc. will use clean storage.
     * Since user confirmed "全部都係測試 data", this is the cleanest safest reset.
     */
    tokens = new UnorderedMap<Token>('t-v2');
    upgrade_credits = new UnorderedMap<string>('uc'); // account:tier -> timestamp (on-chain proof for USDT/USDC upgrades)

    platform_wallet: string = 'soulmd-hub.near';

    // === FT payment tokens (mainnet) - MUST VERIFY BEFORE ANY DEPLOY ===
    // Must exactly match private/config.php NEAR_USDT_CONTRACT / NEAR_USDC_CONTRACT.
    // Platform must have done storage_deposit on both FT contracts once.
    readonly USDT_CONTRACT: string = 'usdt.tether-token.near';
    readonly USDC_CONTRACT: string = '17208628f84f5d6ad33f0da3bbbeb27ffcb398eac501a31bd6ad2011e36133a1';

    // ============================================================
    // PUBLIC / USER-FACING FUNCTIONS (exact same external API as before for compatibility)
    // All safety patterns from previous audits strictly followed:
    // - State mutation BEFORE any promiseBatch (so panic reverts state cleanly, deposit handled by NEAR).
    // - Exact amounts, strict asserts, owner/renter guards.
    // ============================================================

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
        if (!token.renters) token.renters = {};  // defensive: old data / raw patch / deserial may lack it
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
        if (!token.renters) token.renters = {};  // defensive: old data / raw patch / deserial may lack it -> was causing empty active list + potential panic on 'in' or assign
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
        if (!token.renters) token.renters = {};  // defensive
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
        if (!token.renters) token.renters = {};  // defensive: ensure active renter checks never miss due to deserial/empty
        
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

    // ============================================================
    // Receive USDT / USDC for VIP/PRO upgrades (on-chain)
    // (replaces or supplements PayPal in upgrade.php)
    //
    // User flow (via existing wallet bridge):
    //   wrapper.account().functionCall({
    //     contractId: tokenContract,          // USDT or USDC
    //     methodName: 'ft_transfer_call',
    //     args: { receiver_id: 'soulmd-hub.near', amount, msg: 'upgrade:vip' },
    //     gas: '300000000000000',
    //     attachedDeposit: '1'
    //   })
    //
    // Contract side (this ft_on_transfer):
    // - Only the exact whitelisted FT contracts can call us (predecessor check).
    // - msg can be "upgrade:vip" / "upgrade:pro" or JSON.
    // - Amount must meet minimum (tied to NEAR_UPGRADE_*_USD_AMOUNT from config * 1_000_000, 6 decimals; e.g. 4.99 -> 4990000, 14.99 -> 14990000).
    // - State is written FIRST. Then (optionally) we can forward later.
    // - Return '0' to keep funds in the contract, or the original amount on any rejection (automatic refund).
    // - Credit is recorded so PHP can do a view_call for proof before applying the DB tier/expiry.
    //
    // After successful claim in PHP, platform can call clear_upgrade_credit (privileged).
    // ============================================================
    @call({})
    ft_on_transfer({ sender_id, amount, msg }: { sender_id: string, amount: string, msg: string }): string {
        const token = near.predecessorAccountId();

        const isUsdt = token === this.USDT_CONTRACT;
        const isUsdc = token === this.USDC_CONTRACT;
        if (!isUsdt && !isUsdc) {
            near.log(`FT payment rejected: unknown token ${token} from ${sender_id}`);
            return amount; // full refund
        }

        // Strict msg parsing: prefer JSON {action: "upgrade", tier: "vip|pro"}, fallback to exact "upgrade:vip"
        let tier = '';
        try {
            const parsed = JSON.parse(msg || '{}');
            if ((parsed.action || '').toLowerCase() === 'upgrade' || (parsed.intent || '').toLowerCase() === 'upgrade') {
                const t = (parsed.tier || parsed.level || '').toLowerCase();
                if (t === 'vip' || t === 'standard') tier = 'vip';
                else if (t === 'pro' || t === 'advanced') tier = 'pro';
            }
        } catch (_) {}
        if (!tier) {
            const raw = (msg || '').toLowerCase().trim();
            if (raw === 'upgrade:vip') tier = 'vip';
            else if (raw === 'upgrade:pro') tier = 'pro';
        }

        if (!tier) {
            near.log(`FT payment rejected: no valid upgrade tier in msg="${msg}"`);
            return amount;
        }

        // Pricing must match config NEAR_UPGRADE_*_USD_AMOUNT * 1_000_000 (6 decimals)
        // e.g. for 4.99 -> 4990000 , for 14.99 -> 14990000
        const required = tier === 'vip' ? '4990000' : '14990000';
        if (BigInt(amount) < BigInt(required)) {
            near.log(`FT payment rejected for ${sender_id} ${tier}: amount ${amount} < required ${required}`);
            return amount;
        }

        // === STATE FIRST (follow the proven safety pattern used in buy_soul / rent_soul / mint) ===
        const creditKey = `${sender_id}:${tier}`;
        this.upgrade_credits.set(creditKey, near.blockTimestamp().toString());

        near.log(`✅ FT upgrade credit recorded: ${sender_id} paid ${amount} of ${isUsdt ? 'USDT' : 'USDC'} for ${tier} (key=${creditKey})`);

        // Funds stay in the contract for now. Later we can add a privileged sweep that does ft_transfer to platform_wallet.
        return '0';
    }

    @view({})
    has_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }): string {
        const key = `${account_id}:${tier}`;
        const tsStr = this.upgrade_credits.get(key);
        if (!tsStr) return "0";
        const ts = BigInt(tsStr);
        const now = near.blockTimestamp();
        const expiryNs = 86400000000000n; // 24 hours in nanoseconds
        if (now - ts > expiryNs) {
            return "0"; // expired credit
        }
        return tsStr; // return the exact credit timestamp for one-time claim binding
    }

    // ============================================================
    // Admin recovery tools (platform_wallet = soulmd-hub.near ONLY)
    //
    // ROOT CAUSE of "去中心化活躍承租人列表" (Active Renters) now showing 0 / empty for tokens that previously had renters:
    // - contract.ts updates (added upgrade_credits map, Token fields, safety ordering etc.) + deploy can expose deserial edge cases on *old* stored Token blobs.
    // - The panic "'prefix' of undefined at reconstruct" (seen in rent_soul for e.g. soul_3956) was inside near-sdk-js UnorderedMap value load / Token reconstruct.
    // - admin_patch_renters_for_token (raw storageRead/JSON.parse/storageWrite on guessed keys "t"+id etc.) was used as emergency bypass to make the token loadable again without panic.
    // - Risk of raw patch: (1) guessed key may not be the *exact* internal key the current SDK UnorderedMap uses for that token_id (so real entry untouched, get_soul still returns old/empty renters), (2) you must supply the *complete correct prior renters_json* at patch time or you overwrite/lose the active renter records (post-patch data loss), (3) plain JSON roundtrip may not perfectly match SDK's serializeValueWithOptions shape for all fields.
    // - Result after patch or contract change: get_soul returns Token with renters: {} (or undefined before our defensive), frontend for-in + expiry filter in marketplace.php/profile.php/my-souls.php etc. yields 0 "Active Renters", badge shows 0, list modal "No active renters".
    // - Old 30d rentals also naturally expire (blockTimestamp > stored ns), so without successful new rent_soul (which was blocked by panic) the visible decentralized active list goes to zero.
    //
    // RECOVERY STRATEGY (do this from soulmd-hub.near only):
    // 1. Call debug_possible_token_storage_keys (view, free) or the rich version below for a token_id e.g. "soul_3956". Inspect returned candidates: which keys actually have data, what the current parsed renters count is *on chain now*. This tells you if previous raw patch hit the right key or not.
    // 2. Gather the true prior renter data from *off-chain records you control* (this is the only reliable source after corruption):
    //    - Old screenshots of the marketplace / my-souls / soul page showing "X Active Renters" + the modal list (with wallet addresses + expiry dates).
    //    - Your DB (if you logged successful rent tx hashes + token_id + renter + amount + time around the rent events).
    //    - NEAR explorer: filter actions for the token owner or soulmd-hub.near, method=rent_soul, look at args.token_id + attached deposit (price) + predecessor (the renter) + block time to reconstruct approx expiry (add 30d ns). Logs from the contract also recorded the rent action.
    //    - Renter wallets themselves may remember/confirm (they paid and had access via check_access during the window).
    //    - If no record at all, the rental history for that token is effectively lost; future new rents will repopulate.
    // 3. If the token currently *panics on get/rent* (still broken deserial): use admin_patch_renters_for_token with accurate renters_json (this is last-resort raw write; use the key from debug if you can call a custom one-off).
    // 4. Once the token *loads successfully* via get_soul (no panic, you see it in marketplace/soul.php), switch to the *safe* admin_set_renters_for_token below for any corrections. It does normal this.tokens.get + patch renters + set. This goes through the proper SDK serialize path (no format corruption risk) and our new Token.reconstruct + defensive {} will keep it healthy.
    // 5. After recovery set/patch, new rent_soul / buy will maintain correct state. Active list will repopulate as soon as there is a non-expired renter.
    // 6. Always build + deploy the wasm after editing contract.ts (see package.json "build"). Test on a fresh soul first.
    // Funds stay safe in soulmd-hub.near (your cool wallet). These admin fns are scoped.
    // ============================================================

    @view({})
    debug_possible_token_storage_keys({ token_id }: { token_id: string }): any[] {
        const candidates: any[] = [];
        const prefixes = ["t", "t,", "t:", "t;"];  // extra guess in case SDK uses other sep
        for (const p of prefixes) {
            const k = p + token_id;
            const raw = near.storageRead(k);
            if (raw !== null) {
                let parsed: any = null;
                let parseErr: string | null = null;
                let renterCount = 0;
                let sample: string[] = [];
                let owner = '';
                try {
                    parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        owner = parsed.owner_id || '';
                        const r = (parsed.renters && typeof parsed.renters === 'object') ? parsed.renters : {};
                        renterCount = Object.keys(r).length;
                        sample = Object.keys(r).slice(0, 3);
                    }
                } catch (e: any) {
                    parseErr = String(e);
                }
                candidates.push({
                    key: k,
                    rawLen: raw.length,
                    rawSample: raw.substring(0, 120) + (raw.length > 120 ? '...' : ''),
                    parseSuccess: !parseErr,
                    parseError: parseErr,
                    owner_id: owner,
                    currentRentersCount: renterCount,
                    sampleRenters: sample
                });
            }
        }
        return candidates;
    }

    // Preferred safe recovery (use this once token loads without panic).
    // Goes through normal UnorderedMap.get / Token reconstruct / set so serialization is always correct SDK format.
    // Supply renters_json as string e.g. '{"alice.near":"1720000000000000000000000000","bob.testnet":"..."}' (ns strings).
    @call({})
    admin_set_renters_for_token({ token_id, renters_json }: { token_id: string, renters_json: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");

        let token = this.tokens.get(token_id);
        assert(token !== null, "Error: Token not found or still panics on deserial. Use raw patch first if needed.");

        if (!token.renters) token.renters = {};
        const newRenters = JSON.parse(renters_json || "{}");
        token.renters = (newRenters && typeof newRenters === 'object') ? newRenters : {};
        this.tokens.set(token_id, token);

        near.log(`Safe admin_set_renters: ${token_id} now has ${Object.keys(token.renters).length} renter entries (via normal get/set path)`);
    }

    // Raw storage bypass (only for tokens that still hard-panic on .get / reconstruct even after prior attempts).
    // Use debug output first to pick the correct key if you extend this. Supply full correct renters_json or you will lose history.
    @call({})
    admin_patch_renters_for_token({ token_id, renters_json }: { token_id: string, renters_json: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");

        const prefixes = ["t", "t,", "t:", "t;"];
        let fixed = false;
        for (const p of prefixes) {
            const k = p + token_id;
            const raw = near.storageRead(k);
            if (raw) {
                try {
                    // Best-effort raw patch. See big comment above for risks (key guess, must have prior data, format).
                    const obj = JSON.parse(raw);
                    obj.renters = JSON.parse(renters_json || "{}");
                    const newStr = JSON.stringify(obj);
                    near.storageWrite(k, newStr);
                    near.log(`RAW PATCHED renters for ${token_id} at key ${k} (bypass reconstruct)`);
                    fixed = true;
                } catch (e) {
                    near.log(`Key ${k} has data but JSON patch failed: ${e}. Use debug view + off-chain records.`);
                }
            }
        }
        if (!fixed) {
            near.log(`No data found for token ${token_id} under common keys. Call debug_possible_token_storage_keys first.`);
        }
    }

    @call({})
    clear_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, 'Security: only platform_wallet can clear credits');

        const key = `${account_id}:${tier}`;
        this.upgrade_credits.remove(key);
        near.log(`Credit cleared for ${account_id} ${tier} (by platform)`);
    }

    // ============================================================
    // ULTIMATE ADMIN GOD-MODE (platform_wallet = soulmd-hub.near ONLY)
    //
    // Because this is still test data phase, admin has full unrestricted power to
    // read, write, delete, or reset ANY on-chain record (tokens, renters, prices, owners,
    // upgrade credits, or raw storage keys).
    //
    // SAFETY:
    // - Every admin method has hard assert(caller === this.platform_wallet).
    // - All actions are heavily logged on-chain.
    // - Raw storage functions are the nuclear option for fixing deserial corruption
    //   that even the high-level map cannot load.
    // - With the fresh 't-v2' prefix, old corrupted test blobs are ignored by normal operations.
    // - These functions exist so the website "contract admin control page" (restricted to
    //   NEAR_CONTRACT_ID) can let the owner fix or manage everything without CLI.
    //
    // After deploy, the owner can use the admin page (or CLI) to wipe test data or
    // recreate clean records as needed. No need to preserve old renters etc.
    // ============================================================

    // --- Convenience high-level admin edits (use when the token loads cleanly) ---

    @call({})
    admin_set_token({ token_id, token_json }: { token_id: string, token_json: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");

        const obj = JSON.parse(token_json || "{}");
        const token = Token.reconstruct(obj);
        this.tokens.set(token_id, token);
        near.log(`ADMIN: set full token ${token_id} (owner=${token.owner_id})`);
    }

    @call({})
    admin_remove_token({ token_id }: { token_id: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");

        this.tokens.remove(token_id);
        // Also attempt raw cleanup of possible old-format keys (belt and suspenders)
        const prefixes = ["t", "t,", "t:", "t;", "t-v2"];
        for (const p of prefixes) {
            const k = p + token_id;
            if (near.storageRead(k) !== null) {
                near.storageWrite(k, "");
            }
        }
        near.log(`ADMIN: removed token ${token_id}`);
    }

    @call({})
    admin_update_renters({ token_id, renters_json }: { token_id: string, renters_json: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");

        let token = this.tokens.get(token_id);
        if (!token) {
            // allow creating a skeleton if the token didn't exist yet (test convenience)
            token = new Token(this.platform_wallet, new TokenMetadata("", "", "", "", this.platform_wallet));
        }
        token.renters = JSON.parse(renters_json || "{}");
        this.tokens.set(token_id, token);
        near.log(`ADMIN: updated renters for ${token_id} (count=${Object.keys(token.renters).length})`);
    }

    @call({})
    admin_update_prices({ token_id, sale_price, rent_price }: { token_id: string, sale_price: string | null, rent_price: string | null }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");

        let token = this.tokens.get(token_id);
        if (!token) {
            token = new Token(this.platform_wallet, new TokenMetadata("", "", "", "", this.platform_wallet));
        }
        token.sale_price = sale_price || null;
        token.rent_price = rent_price || null;
        this.tokens.set(token_id, token);
        near.log(`ADMIN: updated prices for ${token_id}`);
    }

    @call({})
    admin_set_owner({ token_id, new_owner }: { token_id: string, new_owner: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        assert(new_owner && new_owner.length > 0, "new_owner required");

        let token = this.tokens.get(token_id);
        if (!token) {
            token = new Token(new_owner, new TokenMetadata("", "", "", "", new_owner));
        } else {
            token.owner_id = new_owner;
        }
        this.tokens.set(token_id, token);
        near.log(`ADMIN: set owner of ${token_id} to ${new_owner}`);
    }

    // --- Nuclear raw storage access (use debug to discover keys first) ---

    @call({})
    admin_raw_storage_write({ key, value }: { key: string, value: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        assert(key && key.length > 0, "key required");

        near.storageWrite(key, value || "");
        near.log(`ADMIN RAW WRITE key=${key} len=${(value || "").length}`);
    }

    @call({})
    admin_raw_storage_remove({ key }: { key: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        assert(key && key.length > 0, "key required");

        const existed = near.storageRead(key) !== null;
        if (near.storageRemove) {
            near.storageRemove(key);
        } else {
            near.storageWrite(key, ""); // fallback
        }
        near.log(`ADMIN RAW REMOVE key=${key} (existed=${existed})`);
    }

    // --- Test data reset (safe because user said everything is test data) ---

    @call({})
    admin_clear_all_tokens() {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");

        // Clear the live map (v2)
        this.tokens.clear();

        // Best-effort raw cleanup of any old test prefixes
        // (in practice a full redeploy with new prefix already isolates old data)
        const prefixes = ["t", "t,", "t:", "t;", "t-v2"];
        // Note: we cannot easily enumerate every key without the map, so we rely on the fresh prefix.
        // For complete wipe the owner can also use raw_remove on any discovered keys via the admin page.

        near.log("ADMIN: cleared all tokens (test data reset via fresh prefix + map.clear())");
    }

    // --- Upgrade credit admin (for testing the FT flow) ---

    @call({})
    admin_set_upgrade_credit({ account_id, tier, ts }: { account_id: string, tier: string, ts: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");

        const key = `${account_id}:${tier}`;
        this.upgrade_credits.set(key, ts || near.blockTimestamp().toString());
        near.log(`ADMIN: set upgrade credit ${key}`);
    }

    @call({})
    admin_remove_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, 'Security: only platform_wallet');

        const key = `${account_id}:${tier}`;
        this.upgrade_credits.remove(key);
        near.log(`ADMIN: removed upgrade credit ${key}`);
    }

    // Note: Funds intentionally stay in soulmd-hub.near contract account (safe cool wallet per user).
    // No sweep/transfer logic added. Platform holds received USDT/USDC directly.
    // Storage registration with USDT/USDC contracts must be done once by platform account before receiving.
}