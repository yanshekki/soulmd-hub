import { NearBindgen, near, call, view, assert } from 'near-sdk-js';

/**
 * SoulMD Hub - RAW STORAGE ONLY (full bypass of near-sdk-js#358 + SO "prefix of undefined")
 * SEE THE LINKS THE USER PROVIDED.
 * Root: ANY UnorderedMap (even 'uc' for credits) + class field initializer causes SDK reconstruct()
 * to do `new UnorderedMap(data.prefix)` with data=undefined when STATE lacks the collection blob
 * (after zero-start clear, only platform_wallet in STATE, fresh deploy, prior raw patches).
 * 
 * THIS VERSION: ZERO collections. 100% direct storageRead/Write/Remove.
 * - tokens: "t:" + token_id  (plain JSON, 5 fields from frontend mint payload exactly)
 * - upgrade credits: "uc:" + `${account}:${tier}`  (plain ts string, 24h)
 * 
 * No Token.reconstruct. No upgrade_credits = new UnorderedMap. No import of UnorderedMap.
 * No prefix to ever change again. Chinese in title/desc = fine (JSON strings).
 * Frontend mint {token_id, title, description, hash, reference} -> direct store, no side pulls.
 * All records fresh start. On-chain was verified clean (only STATE) before this.
 * 
 * Admin: practical god-mode from soulmd-hub.near (cool secure wallet) only. Use to fix any desync.
 * 
 * AUDIT: state-mutate before promises on all pay; predecessor + strict asserts; raw bypass eliminates the reconstruct panic vector entirely.
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
    // FULL RAW BYPASS - NO UnorderedMap ANYWHERE (tokens + credits).
    // This is the actual fix for the exact panic in the 2 links you gave (near-sdk-js#358 + SO).
    // Old versions (including the "raw attempt") still left upgrade_credits = new UnorderedMap + import + Token.reconstruct.
    // That is why "咪一柒樣" - the reconstruct vector for missing collection data remained.
    // Now: 0 collections, 0 reconstruct, 0 prefixes. Direct keys only. Safe for clean STATE (only platform_wallet) + zero-start + redeploy.
    platform_wallet: string = 'soulmd-hub.near';

    // Mainnet FT (must match private/config.php)
    readonly USDT: string = 'usdt.tether-token.near';
    readonly USDC: string = '17208628f84f5d6ad33f0da3bbbeb27ffcb398eac501a31bd6ad2011e36133a1';

    // 30 days in nanoseconds
    readonly RENT_DURATION_NS: bigint = 2592000000000000n;

    // ---- RAW token storage: "t:" + token_id (plain JSON, matches frontend 5-field payload exactly) ----
    private _tokenKey(token_id: string): string { return "t:" + token_id; }

    private _loadToken(token_id: string): Token | null {
        const s = near.storageRead(this._tokenKey(token_id));
        if (!s) return null;
        try {
            const o = JSON.parse(s);
            if (!o) return null;
            const meta = o.metadata
                ? new TokenMetadata(
                    o.metadata.title || '',
                    o.metadata.description || '',
                    o.metadata.extra || '',
                    o.metadata.reference || '',
                    o.metadata.creator_id || ''
                  )
                : new TokenMetadata('', '', '', '', '');
            const t = new Token(o.owner_id || '', meta);
            t.sale_price = this.safePriceString(o.sale_price);
            t.rent_price = this.safePriceString(o.rent_price);
            t.renters = (o.renters && typeof o.renters === 'object') ? o.renters : {};
            // sanitize renters values too, old data may have bad expiries
            if (t.renters) {
                for (const k in t.renters) {
                    if (t.renters[k] !== undefined && t.renters[k] !== null) {
                        let vs = String(t.renters[k]);
                        if (/[eE]/.test(vs)) {
                            delete t.renters[k];
                        } else {
                            t.renters[k] = vs;
                        }
                    } else {
                        delete t.renters[k];
                    }
                }
            }
            return t;
        } catch (e) {
            return null;
        }
    }

    private _saveToken(token_id: string, t: any): void {
        near.storageWrite(this._tokenKey(token_id), JSON.stringify({
            owner_id: t.owner_id,
            metadata: {
                title: t.metadata ? t.metadata.title : '',
                description: t.metadata ? t.metadata.description : '',
                extra: t.metadata ? t.metadata.extra : '',
                reference: t.metadata ? t.metadata.reference : '',
                creator_id: t.metadata ? t.metadata.creator_id : ''
            },
            sale_price: t.sale_price != null ? String(t.sale_price) : null,
            rent_price: t.rent_price != null ? String(t.rent_price) : null,
            renters: t.renters || {}
        }));
    }

    // ---- RAW upgrade credits (was the remaining UnorderedMap('uc') that would still panic). Now per-key too. ----
    private _creditKey(account_id: string, tier: string): string { return "uc:" + account_id + ":" + tier; }

    private _getCredit(account_id: string, tier: string): string | null {
        return near.storageRead(this._creditKey(account_id, tier));
    }

    private _setCredit(account_id: string, tier: string, ts: string): void {
        near.storageWrite(this._creditKey(account_id, tier), ts);
    }

    private _removeCredit(account_id: string, tier: string): void {
        near.storageRemove(this._creditKey(account_id, tier));
    }

    // helper to safely convert price from storage (handles old polluted data saved as number causing '1e+23' which BigInt can't parse)
    private safePriceString(val: any): string | null {
        if (val === undefined || val === null) return null;
        let s = String(val);
        if (/[eE]/.test(s)) {
            // old data from JSON number scientific notation - treat as invalid, user must re-list
            return null;
        }
        return s;
    }

    // Safe BigInt wrapper: never panics on invalid/non-numeric input (defensive for historical bad data from old saves)
    // Returns 0n for null/undefined/empty/invalid. We still enforce business rules (e.g. price > 0) after.
    // No problem for this use case: prices/renters are either valid yocto strings or legacy garbage.
    // Graceful degradation > crash. For new data it's never hit.
    private safeBigInt(val: any): bigint {
        if (val === undefined || val === null || val === '') {
            return 0n;
        }
        try {
            return BigInt(val);
        } catch (e) {
            // Polluted legacy data (e.g. "1e+23", objects, etc.) -> treat as 0 to avoid panic
            return 0n;
        }
    }

    @call({ payableFunction: true })
    mint_soul({ token_id, title, description, hash, reference }: { token_id: string, title: string, description: string, hash: string, reference: string }) {
        const caller = near.predecessorAccountId();
        const deposit = BigInt(near.attachedDeposit().toString());

        const required = 600000000000000000000000n; // 0.6 NEAR
        assert(deposit >= required, "Error: Minting requires exactly 0.6 NEAR");

        // Raw check - no SDK map, no reconstruct panic possible
        const key = this._tokenKey(token_id);
        assert(!near.storageRead(key), "Error: Token ID already exists.");

        // Create from YOUR input only. No pulling old metadata from storage.
        const metadata = new TokenMetadata(title, description, hash, reference, caller);
        const token = new Token(caller, metadata);

        // Store plain JSON under t: key
        this._saveToken(token_id, token);

        // state first, then platform fee (0.1 NEAR)
        const platform_fee = 100000000000000000000000n;
        const promise = BigInt(near.promiseBatchCreate(this.platform_wallet).toString());
        near.promiseBatchActionTransfer(promise, platform_fee);

        near.log(`Minted Soul [${token_id}] by ${caller}`);
    }

    @call({})
    update_soul_hash({ token_id, new_hash }: { token_id: string, new_hash: string }) {
        const caller = near.predecessorAccountId();
        const token = this._loadToken(token_id);

        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Security Error: Only the current owner can update hash.");

        token.metadata.extra = new_hash;
        this._saveToken(token_id, token);

        near.log(`Soul [${token_id}] evolved to new hash: ${new_hash}`);
    }

    @call({})
    list_for_sale({ token_id, price }: { token_id: string, price: string }) {
        const caller = near.predecessorAccountId();
        const token = this._loadToken(token_id);
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Error: Only owner can list for sale.");

        // Validate price to prevent later BigInt panics on buy (garbage price would brick the token until admin fix)
        let sale_price: string | null = null;
        if (price !== "0" && price !== "") {
            if (this.safeBigInt(price) > 0n) sale_price = price;
        }
        token.sale_price = sale_price;
        near.log(`[${token_id}] sale ${sale_price ? 'listed at ' + sale_price : 'cancelled'}`);
        this._saveToken(token_id, token);
    }

    @call({ payableFunction: true })
    buy_soul({ token_id }: { token_id: string }) {
        const buyer = near.predecessorAccountId();
        const deposit = BigInt(near.attachedDeposit().toString());
        const token = this._loadToken(token_id);

        assert(token !== null, "Error: Token not found.");
        if (!token.renters) token.renters = {};
        assert(token.sale_price, "Error: Token not listed for sale.");

        const price = this.safeBigInt(token.sale_price);
        assert(price > 0n, "Error: Invalid sale price.");
        assert(deposit >= price, "Error: Insufficient deposit.");

        const prev_owner = token.owner_id;
        const creator = token.metadata.creator_id;

        // transfer ownership + clear listings
        // IMPORTANT: active renters (leases) are intentionally preserved across sale.
        // This allows trading of encumbered souls (the lease claim stays until expiry or burn).
        // New owner inherits the token subject to existing renter access via check_access.
        token.owner_id = buyer;
        token.sale_price = null;
        token.rent_price = null;
        this._saveToken(token_id, token);

        // fees: 5% platform, 5% creator (if not seller), rest to seller
        const platform_fee = (price * 5n) / 100n;
        let creator_value = 0n;
        let seller_value = price - platform_fee;

        if (creator !== prev_owner) {
            creator_value = (price * 5n) / 100n;
            seller_value -= creator_value;
            const cp = BigInt(near.promiseBatchCreate(creator).toString());
            near.promiseBatchActionTransfer(cp, creator_value);
        }

        const pp = BigInt(near.promiseBatchCreate(this.platform_wallet).toString());
        near.promiseBatchActionTransfer(pp, platform_fee);

        const sp = BigInt(near.promiseBatchCreate(prev_owner).toString());
        near.promiseBatchActionTransfer(sp, seller_value);

        near.log(`Soul [${token_id}] bought by ${buyer} from ${prev_owner}`);
    }

    @call({})
    list_for_rent({ token_id, price }: { token_id: string, price: string }) {
        const caller = near.predecessorAccountId();
        const token = this._loadToken(token_id);
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Error: Only owner can list for rent.");

        // Validate price (see list_for_sale)
        let rent_price: string | null = null;
        if (price !== "0" && price !== "") {
            if (this.safeBigInt(price) > 0n) rent_price = price;
        }
        token.rent_price = rent_price;
        near.log(`[${token_id}] rent ${rent_price ? 'listed at ' + rent_price : 'cancelled'}`);
        this._saveToken(token_id, token);
    }

    @call({ payableFunction: true })
    rent_soul({ token_id }: { token_id: string }) {
        const renter = near.predecessorAccountId();
        
        // 🛡️ 防禦 1：強制將 deposit 轉做確實嘅 BigInt，防禦 SDK 傳回 String/Number
        const deposit = BigInt(near.attachedDeposit().toString()); 
        
        const token = this._loadToken(token_id);

        assert(token !== null, "Error: Token not found.");
        if (!token.renters) token.renters = {};
        assert(token.rent_price, "Error: Token not listed for rent.");

        const price = this.safeBigInt(token.rent_price);
        assert(price > 0n, "Error: Invalid rent price.");
        assert(deposit >= price, "Error: Insufficient deposit for rent.");

        // 10% platform, 90% owner
        const platform_fee = (price * 10n) / 100n;
        const owner_share = price - platform_fee;

        const now = BigInt(near.blockTimestamp().toString());

        // clean expired (collect first to avoid for-in delete during iteration issues)
        const toDelete: string[] = [];
        for (const r in token.renters) {
            const exp = this.safeBigInt(token.renters[r]);
            if (exp < now) {
                toDelete.push(r);
            }
        }
        for (const r of toDelete) {
            delete token.renters[r];
        }

        // extend or new (adds full 30d on top of any remaining — renews lease)
        let current_expiry = this.safeBigInt(token.renters[renter] || now);
        if (current_expiry < now) current_expiry = now;
        token.renters[renter] = (current_expiry + this.RENT_DURATION_NS).toString();

        this._saveToken(token_id, token);

        // 🛡️ 防禦 2：強制將 Promise Index 轉做 BigInt，解決底層 C++ 崩潰 Bug
        const pp = BigInt(near.promiseBatchCreate(this.platform_wallet).toString());
        near.promiseBatchActionTransfer(pp, platform_fee);

        const op = BigInt(near.promiseBatchCreate(token.owner_id).toString());
        near.promiseBatchActionTransfer(op, owner_share);

        near.log(`Soul [${token_id}] rented by ${renter} (expiry ${token.renters[renter]})`);
    }

    @call({})
    burn_soul({ token_id }: { token_id: string }) {
        const caller = near.predecessorAccountId();
        const token = this._loadToken(token_id);

        assert(token !== null, "Error: Token not found.");
        if (!token.renters) token.renters = {};
        assert(token.owner_id === caller, "Error: Only owner can burn.");

        const now = BigInt(near.blockTimestamp().toString());
        for (const r in token.renters) {
            const exp = this.safeBigInt(token.renters[r]);
            assert(exp < now, "Error: Cannot burn while active renters exist.");
        }

        near.storageRemove(this._tokenKey(token_id));

        // refunds: 0.45 NEAR to owner, 0.05 to platform
        const refund_amount = 450000000000000000000000n;
        const platform_burn_fee = 50000000000000000000000n;

        const rp = BigInt(near.promiseBatchCreate(caller).toString());
        near.promiseBatchActionTransfer(rp, refund_amount);

        const bp = BigInt(near.promiseBatchCreate(this.platform_wallet).toString());
        near.promiseBatchActionTransfer(bp, platform_burn_fee);

        near.log(`Soul [${token_id}] burned by ${caller}`);
    }

    @view({})
    get_soul({ token_id }: { token_id: string }): Token | null {
        return this._loadToken(token_id);
    }

    @view({})
    check_access({ token_id, account_id }: { token_id: string, account_id: string }): boolean {
        const token = this._loadToken(token_id);
        if (!token) return false;
        const renters = token.renters || {};
        if (token.owner_id === account_id) return true;
        const exp = renters[account_id];
        if (exp && this.safeBigInt(exp) > near.blockTimestamp()) return true;
        return false;
    }

    @call({ payableFunction: true })
    auto_buyback_and_burn({ amount_in_near }: { amount_in_near: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "platform only");

        const amount = BigInt(amount_in_near);
        const attached = BigInt(near.attachedDeposit().toString());
        assert(attached >= amount, "Error: attach exactly the NEAR amount to use for buyback (funds the wrap + swap)");

        const p1 = BigInt(near.promiseBatchCreate("wrap.near").toString());
        near.promiseBatchActionFunctionCall(p1, "near_deposit", "", amount, 30000000000000n);

        const msg = JSON.stringify({
            force: 0,
            actions: [{
                pool_id: 8546,
                token_in: "wrap.near",
                token_out: "soul.tkn.near",
                amount_in: amount_in_near,
                min_amount_out: "1"
            }]
        });
        near.promiseBatchActionFunctionCall(
            p1,
            "ft_transfer_call",
            JSON.stringify({ receiver_id: "v2.ref-finance.near", amount: amount_in_near, msg }),
            1n,
            100000000000000n
        );
    }

    @call({})
    ft_on_transfer({ sender_id, amount, msg }: { sender_id: string, amount: string, msg: string }): string {
        const token = near.predecessorAccountId();
        if (token !== this.USDT && token !== this.USDC) {
            return amount; // reject others
        }

        let tier = "";
        try {
            const j = JSON.parse(msg || "{}");
            const t = (j.tier || j.level || "").toLowerCase();
            if (t === "vip" || t === "standard") tier = "vip";
            else if (t === "pro" || t === "advanced") tier = "pro";
        } catch (e) {}

        if (!tier) {
            const raw = (msg || "").toLowerCase().trim();
            if (raw === "upgrade:vip") tier = "vip";
            else if (raw === "upgrade:pro") tier = "pro";
        }
        if (!tier) {
            return amount; // unknown, refund
        }

        const required = tier === "vip" ? "4990000" : "14990000"; // 4.99 / 14.99 USD (6 decimals)
        if (BigInt(amount) < BigInt(required)) {
            return amount; // insufficient, refund
        }

        // record credit, keep funds (return "0") - RAW
        this._setCredit(sender_id, tier, near.blockTimestamp().toString());
        near.log(`FT credit granted: ${sender_id} ${tier}`);
        return "0";
    }

    @view({})
    has_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }): string {
        const ts = this._getCredit(account_id, tier);
        if (!ts) return "0";
        if (near.blockTimestamp() - BigInt(ts) > 86400000000000n) return "0"; // 24h
        return ts;
    }

    // === Practical admin (platform_wallet only) - for fresh start / recovery ===
    // These are god-mode. They can arbitrarily rewrite tokens, renters, raw storage keys, or credits.
    // Risk: if soulmd-hub.near account is compromised (full access key leaked), attacker can steal all data, mint fakes, or corrupt maps.
    // Mitigation in practice: soulmd-hub.near is described as "cool wallet", hardware / high security, only used via audited admin-contract.php with wallet-selector.
    // Raw write/remove can be used to repair after storage corruption but can also be used to destroy. Use with extreme care + on-chain logging.
    @view({})
    debug({ token_id }: { token_id: string }): any[] {
        const res: any[] = [];
        const prefixes = ["t", "t:"];
        for (const pr of prefixes) {
            const k = pr + token_id;
            const r = near.storageRead(k);
            if (r) res.push({ key: k, val: r.substring(0, 120) });
        }
        return res;
    }

    @call({})
    admin_set_token({ token_id, token_json }: { token_id: string, token_json: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        const obj = JSON.parse(token_json || "{}");
        // manual construct (no Token.reconstruct, no map)
        const meta = obj.metadata ? new TokenMetadata(
            obj.metadata.title || '', obj.metadata.description || '', obj.metadata.extra || '',
            obj.metadata.reference || '', obj.metadata.creator_id || ''
        ) : new TokenMetadata('', '', '', '', '');
        const t = new Token(obj.owner_id || this.platform_wallet, meta);
        t.sale_price = obj.sale_price || null;
        t.rent_price = obj.rent_price || null;
        t.renters = obj.renters || {};
        this._saveToken(token_id, t);
        near.log(`admin_set_token ${token_id}`);
    }

    @call({})
    admin_remove_token({ token_id }: { token_id: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        near.storageRemove(this._tokenKey(token_id));
        near.log(`admin_remove_token ${token_id}`);
    }

    @call({})
    admin_update_renters({ token_id, renters_json }: { token_id: string, renters_json: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        let t = this._loadToken(token_id);
        if (!t) {
            t = new Token(this.platform_wallet, new TokenMetadata("", "", "", "", this.platform_wallet));
        }
        t.renters = JSON.parse(renters_json || "{}");
        this._saveToken(token_id, t);
        near.log(`admin_update_renters ${token_id}`);
    }

    @call({})
    admin_clear_all_tokens() {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        // No map to clear. For zero-start: call admin_raw or individual admin_remove_token for the ids you know.
        // Or after DB clear, just start minting new (old soul_ ids will simply not exist).
        near.log("admin_clear_all_tokens: raw/individual remove (full raw storage, no map)");
    }

    @call({})
    admin_raw_storage_write({ key, value }: { key: string, value: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        near.storageWrite(key, value || "");
        near.log(`admin_raw_storage_write ${key}`);
    }

    @call({})
    admin_raw_storage_remove({ key }: { key: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        near.storageRemove(key);
        near.log(`admin_raw_storage_remove ${key}`);
    }

    @call({})
    admin_set_upgrade_credit({ account_id, tier, ts }: { account_id: string, tier: string, ts: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        this._setCredit(account_id, tier, ts || near.blockTimestamp().toString());
        near.log(`admin_set_upgrade_credit ${account_id}:${tier}`);
    }

    @call({})
    admin_remove_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        this._removeCredit(account_id, tier);
        near.log(`admin_remove_upgrade_credit ${account_id}:${tier}`);
    }
}
