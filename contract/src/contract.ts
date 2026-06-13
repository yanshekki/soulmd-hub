import { NearBindgen, near, call, view, UnorderedMap, assert } from 'near-sdk-js';

/**
 * SoulMD Hub - Clean Contract (fresh start, prefix 't')
 * Features: mint 0.6 NEAR, update hash (owner), list/buy (5%+5% royalty), list/rent 30d, rent_soul (10% fee), burn (renter guard + refund).
 * FT: USDT/USDC via ft_on_transfer -> upgrade_credits (keep funds in soulmd-hub.near), 24h expiry.
 * Safety: state first then promises; reconstruct + !renters defensive; strict platform admin only.
 * Admin god-mode (practical, soulmd-hub.near only): full set any token/renters/raw/credits, clear for zero start.
 * User cleared DB + old on-chain data; new records start here.
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
    tokens = new UnorderedMap<Token>('t');
    upgrade_credits = new UnorderedMap<string>('uc');
    platform_wallet: string = 'soulmd-hub.near';

    // Mainnet FT (must match private/config.php)
    readonly USDT: string = 'usdt.tether-token.near';
    readonly USDC: string = '17208628f84f5d6ad33f0da3bbbeb27ffcb398eac501a31bd6ad2011e36133a1';

    // 30 days in nanoseconds
    readonly RENT_DURATION_NS: bigint = 2592000000000000n;

    @call({ payableFunction: true })
    mint_soul({ token_id, title, description, hash, reference }: { token_id: string, title: string, description: string, hash: string, reference: string }) {
        const caller = near.predecessorAccountId();
        const deposit = near.attachedDeposit() as bigint;

        const required = 600000000000000000000000n; // 0.6 NEAR
        assert(deposit >= required, "Error: Minting requires exactly 0.6 NEAR");
        assert(!this.tokens.get(token_id), "Error: Token ID already exists.");

        const metadata = new TokenMetadata(title, description, hash, reference, caller);
        const token = new Token(caller, metadata);
        this.tokens.set(token_id, token);

        // state first, then platform fee (0.1 NEAR)
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
        if (!token.renters) token.renters = {};
        assert(token.sale_price, "Error: Token not listed for sale.");

        const price = BigInt(token.sale_price);
        assert(deposit >= price, "Error: Insufficient deposit.");

        const prev_owner = token.owner_id;
        const creator = token.metadata.creator_id;

        // transfer ownership + clear listings
        token.owner_id = buyer;
        token.sale_price = null;
        token.rent_price = null;
        this.tokens.set(token_id, token);

        // fees: 5% platform, 5% creator (if not seller), rest to seller
        const platform_fee = (price * 5n) / 100n;
        let creator_value = 0n;
        let seller_value = price - platform_fee;

        if (creator !== prev_owner) {
            creator_value = (price * 5n) / 100n;
            seller_value -= creator_value;
            const cp = near.promiseBatchCreate(creator);
            near.promiseBatchActionTransfer(cp, creator_value);
        }

        const pp = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(pp, platform_fee);

        const sp = near.promiseBatchCreate(prev_owner);
        near.promiseBatchActionTransfer(sp, seller_value);

        near.log(`Soul [${token_id}] bought by ${buyer} from ${prev_owner}`);
    }

    @call({})
    list_for_rent({ token_id, price }: { token_id: string, price: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);
        assert(token !== null, "Error: Token not found.");
        assert(token.owner_id === caller, "Error: Only owner can list for rent.");

        if (price === "0" || price === "") {
            token.rent_price = null;
            near.log(`[${token_id}] rent listing cancelled.`);
        } else {
            token.rent_price = price;
            near.log(`[${token_id}] listed for rent at ${price} yoctoNEAR`);
        }
        this.tokens.set(token_id, token);
    }

    @call({ payableFunction: true })
    rent_soul({ token_id }: { token_id: string }) {
        const renter = near.predecessorAccountId();
        const deposit = near.attachedDeposit() as bigint;
        const token = this.tokens.get(token_id);

        assert(token !== null, "Error: Token not found.");
        if (!token.renters) token.renters = {};
        assert(token.rent_price, "Error: Token not listed for rent.");

        const price = BigInt(token.rent_price);
        assert(deposit >= price, "Error: Insufficient deposit for rent.");

        // 10% platform, 90% owner
        const platform_fee = (price * 10n) / 100n;
        const owner_share = price - platform_fee;

        const now = near.blockTimestamp();

        // clean expired
        for (const r in token.renters) {
            if (BigInt(token.renters[r]) < now) {
                delete token.renters[r];
            }
        }

        // extend or new
        let current_expiry = token.renters[renter] ? BigInt(token.renters[renter]) : now;
        if (current_expiry < now) current_expiry = now;
        token.renters[renter] = (current_expiry + this.RENT_DURATION_NS).toString();

        this.tokens.set(token_id, token);

        const pp = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(pp, platform_fee);

        const op = near.promiseBatchCreate(token.owner_id);
        near.promiseBatchActionTransfer(op, owner_share);

        near.log(`Soul [${token_id}] rented by ${renter} (expiry ${token.renters[renter]})`);
    }

    @call({})
    burn_soul({ token_id }: { token_id: string }) {
        const caller = near.predecessorAccountId();
        const token = this.tokens.get(token_id);

        assert(token !== null, "Error: Token not found.");
        if (!token.renters) token.renters = {};
        assert(token.owner_id === caller, "Error: Only owner can burn.");

        const now = near.blockTimestamp();
        for (const r in token.renters) {
            assert(BigInt(token.renters[r]) < now, "Error: Cannot burn while active renters exist.");
        }

        this.tokens.remove(token_id);

        // refunds: 0.45 NEAR to owner, 0.05 to platform
        const refund_amount = 450000000000000000000000n;
        const platform_burn_fee = 50000000000000000000000n;

        const rp = near.promiseBatchCreate(caller);
        near.promiseBatchActionTransfer(rp, refund_amount);

        const bp = near.promiseBatchCreate(this.platform_wallet);
        near.promiseBatchActionTransfer(bp, platform_burn_fee);

        near.log(`Soul [${token_id}] burned by ${caller}`);
    }

    @view({})
    get_soul({ token_id }: { token_id: string }): Token | null {
        return this.tokens.get(token_id);
    }

    @view({})
    check_access({ token_id, account_id }: { token_id: string, account_id: string }): boolean {
        const token = this.tokens.get(token_id);
        if (!token) return false;
        if (!token.renters) token.renters = {};
        if (token.owner_id === account_id) return true;
        const exp = token.renters[account_id];
        if (exp && BigInt(exp) > near.blockTimestamp()) return true;
        return false;
    }

    @call({})
    auto_buyback_and_burn({ amount_in_near }: { amount_in_near: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "platform only");

        const amount = BigInt(amount_in_near);
        const p1 = near.promiseBatchCreate("wrap.near");
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

        // record credit, keep funds (return "0")
        this.upgrade_credits.set(`${sender_id}:${tier}`, near.blockTimestamp().toString());
        near.log(`FT credit granted: ${sender_id} ${tier}`);
        return "0";
    }

    @view({})
    has_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }): string {
        const key = `${account_id}:${tier}`;
        const ts = this.upgrade_credits.get(key);
        if (!ts) return "0";
        if (near.blockTimestamp() - BigInt(ts) > 86400000000000n) return "0"; // 24h
        return ts;
    }

    // === Practical admin (platform_wallet only) - for fresh start / recovery ===
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
        const t = Token.reconstruct(obj);
        this.tokens.set(token_id, t);
        near.log(`admin_set_token ${token_id}`);
    }

    @call({})
    admin_remove_token({ token_id }: { token_id: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        this.tokens.remove(token_id);
        near.log(`admin_remove_token ${token_id}`);
    }

    @call({})
    admin_update_renters({ token_id, renters_json }: { token_id: string, renters_json: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        let t = this.tokens.get(token_id);
        if (!t) {
            t = new Token(this.platform_wallet, new TokenMetadata("", "", "", "", this.platform_wallet));
        }
        t.renters = JSON.parse(renters_json || "{}");
        this.tokens.set(token_id, t);
        near.log(`admin_update_renters ${token_id}`);
    }

    @call({})
    admin_clear_all_tokens() {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        this.tokens.clear();
        near.log("admin_clear_all_tokens");
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
        const key = `${account_id}:${tier}`;
        this.upgrade_credits.set(key, ts || near.blockTimestamp().toString());
        near.log(`admin_set_upgrade_credit ${key}`);
    }

    @call({})
    admin_remove_upgrade_credit({ account_id, tier }: { account_id: string, tier: string }) {
        const caller = near.predecessorAccountId();
        assert(caller === this.platform_wallet, "Security: only platform_wallet");
        const key = `${account_id}:${tier}`;
        this.upgrade_credits.remove(key);
        near.log(`admin_remove_upgrade_credit ${key}`);
    }
}
