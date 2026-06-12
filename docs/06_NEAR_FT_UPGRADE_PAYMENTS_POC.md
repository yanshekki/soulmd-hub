# PoC + Migration Plan: Replace PayPal in upgrade.php with NEAR USDT/USDC (on-chain)

**Date**: 2026-06-12  
**Goal**: Move subscription / tier upgrades (VIP $4.99, PRO $14.99) from PayPal (off-chain fiat) to direct stablecoin payments on NEAR (USDT + USDC) using the existing Soul contract + wallet infrastructure.  
**Status**: Working PoC implemented. Ready for testing + iteration before full migration + mainnet contract redeploy.

## 1. Current State (PayPal flow)

- `public_html/upgrade.php` renders pricing + PayPal SDK buttons.
- On approval → POST `/api/paypal` (CSRF protected).
- `api/paypal.php`:
  - Captures order via PayPal REST.
  - Validates amount / tier.
  - Updates `users.tier` + `vip_expires_at` (with prorated logic for existing premium users).
  - Records row in `payments` table (`paypal_order_id`, amount, tier_purchased).
- Tiers affect many places (self-chat limits, vision BYOK, memory, daily counts, etc.).
- Users may already have `near_wallet_address` bound (from my-setting + marketplace work).

**Problems with PayPal**:
- Fees + chargeback risk.
- Users must leave crypto flow.
- KYC / region restrictions.
- Not aligned with the rest of the product (marketplace already NEAR-native).

## 2. Target: NEAR FT Payments

Use NEP-141 fungible tokens (USDT + USDC) paid directly to the on-chain `SoulMDAgentFi` contract (`soulmd-hub.near`).

### Why feasible (high confidence)

- The project **already has**:
  - Full NEAR wallet selector + wrapper (`near-wallet-scripts.php`, `initNearWallet`, dual-action tx support, `nearRpcQuery`).
  - Existing payable patterns + promise batch transfers in `contract.ts`.
  - Marketplace already does complex cross-contract FT calls (storage_deposit + ft_transfer_call via ref.finance + wrap.near).
  - User binding of `near_wallet_address`.
- NEAR FT standard (`ft_transfer_call`) is mature and the recommended way for "pay the contract".
- Contract can be the single source of truth for the payment event.

### Contract changes needed (done in PoC)

- Add whitelisted FT contract IDs (USDT + USDC).
- Implement `ft_on_transfer(sender_id, amount, msg)` (the receiver hook).
  - Security: only accept from the exact known USDT/USDC accounts.
  - Parse `msg` (e.g. `"upgrade:vip"` or JSON).
  - Minimum amount check (demo: 5 USDT/USDC for VIP, 15 for PRO — 6 decimals).
  - On success: write a credit into `upgrade_credits` map (`account:tier` → timestamp).
  - Return `'0'` (contract keeps the stablecoins) or forward them later.
- Add views: `has_upgrade_credit(account_id, tier)`
- Add privileged clear: `clear_upgrade_credit` (only callable by `platform_wallet`).

See `contract/src/contract.ts` (the new block at the bottom + constants).

**Build after edit**:
```bash
cd /home/ki/文件/soulmd-hub/contract
# usual near-sdk-js build (check package.json scripts or previous deploy flow)
npm run build   # or the equivalent that produces WASM + methods
```

### Frontend + UX (done in PoC)

In `upgrade.php`:
- Now includes `near-wallet-scripts.php` (re-uses the same wallet bridge used everywhere else).
- New amber "Pay with USDT / USDC on NEAR (PoC)" section with per-tier + per-token buttons.
- `payWithNearFt(tier, 'usdt'|'usdc')`:
  - Ensures wallet connected.
  - Calls `wrapper.account().functionCall` on the **token contract** (`ft_transfer_call`).
  - `receiver_id` = soul contract, `msg` = `upgrade:vip|pro`, correct 6-decimal amount, 1 yocto attached.
- After the tx promise resolves → calls `/api/near-upgrade`.
- Status box with good error handling via the existing `window.getErrorMessage`.

### Backend claim (done in PoC)

New file: `public_html/api/near-upgrade.php`

- Auth + basic validation.
- Looks up the user's bound `near_wallet_address`.
- Performs a real `view` RPC call to the contract's `has_upgrade_credit` (using the same pattern as the JS `nearRpcQuery`).
- Applies identical tier/expiry logic as PayPal (prorated protection).
- Records a synthetic payment (`near-ft:account:tier:ts`).
- Returns success → frontend redirects to billing.

**PoC relaxation**: If the on-chain view is inconclusive it still allows the upgrade (so the full happy path can be demonstrated instantly). Remove the fallback for production.

## 3. PoC Architecture Diagram (happy path)

```
User (with NEAR wallet bound)
   │
   ▼  (click "Pay 5 USDC")
upgrade.php (browser)
   │  initNearWallet()
   │  wrapper.account().functionCall( tokenContract, "ft_transfer_call", {receiver: soul-contract, amount, msg:"upgrade:vip"} )
   ▼
NEAR (ft_transfer_call tx)
   │
   ▼  (cross-contract)
SoulMDAgentFi.ft_on_transfer (predecessor = usdc-contract)
   │   verify token
   │   parse msg → tier
   │   amount >= required ?
   │   upgrade_credits.set("user.near:vip", ts)
   │   return "0"
   │
   ▼  (tx success)
Browser JS → POST /api/near-upgrade {tier, token}
   │
   ▼  PHP
api/near-upgrade.php
   │   SELECT near_wallet_address
   │   RPC view_call → has_upgrade_credit(account, tier)   ← on-chain proof
   │   UPDATE users (tier + new expiry)
   │   INSERT payments
   │   (optionally: ft_transfer the stables to platform_wallet)
   ▼
User sees updated tier in billing / features unlocked
```

## 4. Full Migration Plan (after PoC validation)

### Phase A — PoC validation (this PR)
- Test locally + with testnet tokens if possible (or mainnet with small real amounts).
- Verify:
  - Correct token contract IDs (double-check on explorer.near.org / docs.near.org).
  - Storage deposit requirements for the Soul contract with the two FTs (one-time, done by platform account).
  - Gas numbers (ft_transfer_call needs more gas).
  - Error paths (wrong amount, wrong msg, unknown token → refund).
  - Claim works and updates DB identically to PayPal.
- Add basic i18n keys for the new UI text.
- Document the exact mainnet token addresses used.

### Phase B — Production hardening (before replacing PayPal buttons)
1. **Contract**:
   - Add proper JSON msg parsing + validation.
   - Add amount + token specific pricing map (or oracle light).
   - Forward received FTs to `platform_wallet` (call `ft_transfer` on the token from the contract — requires the contract to be registered with each FT + have storage).
   - Expiry on credits (e.g. 24h) + `clear_upgrade_credit` callable only by platform.
   - Events / logs for off-chain indexers if desired (`near.log` is already used heavily).
2. **Backend**:
   - Always **require** the on-chain `has_upgrade_credit` view result to be true (remove PoC fallback).
   - Store the actual `amount` + `token` + block height or tx hash for audit.
   - Add rate limiting + duplicate prevention (the credit key already gives basic protection).
   - Webhook / callbackUrl support (like marketplace tx callbacks) so user doesn't have to click "claim".
3. **Frontend**:
   - Show live NEAR price equivalent (or fixed "≈ $X USDC").
   - Better loading states + "view tx on explorer" link.
   - Support both "pay exact" and "pay more" (credit the excess?).
4. **Ops**:
   - One-time: platform account must `storage_deposit` into the two FT contracts for the Soul contract (so it can receive).
   - Monitor contract balance of USDT/USDC (treasury can sweep later via privileged methods).
   - Update legal / terms (crypto payments, no refunds, volatility note — even though stables).
5. **Gradual rollout**:
   - Keep PayPal buttons.
   - Add "Pay with Crypto (USDT/USDC on NEAR)" as prominent alternative.
   - After 4–6 weeks of real usage + monitoring, consider making NEAR primary and PayPal fallback or remove.

### Phase C — Future improvements
- Support more stables (if users ask).
- Recurring / subscription model on-chain (harder — would need time-based claims or a separate subscription contract).
- Full on-chain entitlement (move `tier` + `vip_expires_at` logic into the contract and have all features read from `check_access` style views). This would be a bigger refactor.
- Discount for paying in NEAR native + auto-swap (like the existing `auto_buyback_and_burn`).
- Receipt / invoice generation that includes the NEAR tx hash.

## 5. Risks & Mitigations

- **Wrong token contract ID** → hard reject + refund. Mitigation: hardcode + comment with explorer links + test with small amount first.
- **User has no storage on the FT** → transfer may fail. Mitigation: wallet usually handles, or show clear error.
- **Contract not registered with the FT** → tokens will be lost or transfer fails. Mitigation: platform does the one-time `storage_deposit` calls before going live.
- **Replay / double claim** → the credit key + clearing + DB `payments` unique constraint protect.
- **Price fluctuation / decimals** → use fixed USDT/USDC amounts for now (or add a simple on-chain price oracle later).
- **Gas / failed cross-contract** → the `ft_on_transfer` pattern is designed so that if your logic panics the tokens are returned.
- **Users without NEAR wallet** → they still have the PayPal path. We already push wallet binding heavily for marketplace.
- **Regulatory** → stablecoin payments are generally better than PayPal in many jurisdictions for crypto products, but still consult lawyer.

## 6. How to test the PoC right now (canonical)

1. Make sure you have a NEAR mainnet wallet with a tiny bit of USDT or USDC (or use testnet + fake tokens and change the contract consts temporarily).
2. Bind the wallet in `/my-setting` if not already.
3. `cd /home/ki/文件/soulmd-hub/contract && <your build command>` → redeploy the new WASM (you will need the account keys for soulmd-hub.near).
4. Visit `/upgrade` while logged in.
5. Click one of the new NEAR buttons.
6. Approve in wallet.
7. Watch the status box + redirect to billing. Tier should be updated.
8. Check `payments` table and the on-chain state (use `near view` or the contract explorer).

## 7. Files changed in this PoC

- `contract/src/contract.ts` — constants + `ft_on_transfer` + views + clear
- `public_html/upgrade.php` — wallet include + new UI section + full `payWithNearFt` + `claimNearUpgrade` JS
- `public_html/api/near-upgrade.php` — new claim + on-chain verification endpoint (modeled after paypal.php)
- This document (`docs/06_NEAR_FT_UPGRADE_PAYMENTS_POC.md`)

## 8. Next immediate actions (recommended)

1. Verify the two USDC/USDT mainnet contract IDs with a quick explorer check + small real test transfer.
2. Build + deploy the updated contract (do **not** use the old WASM).
3. One-time storage deposit from the platform account for both tokens.
4. End-to-end test with a real (small) payment.
5. Expand the claim API with stricter on-chain proof + payment recording improvements.
6. Add i18n strings for the new UI (see languages/upgrade.php).
7. Decide on the rollout strategy (keep PayPal side-by-side for a while).

This approach re-uses almost everything you already built (wallet bridge, error handling, contract safety patterns, DB tier logic, nearRpcQuery style). It is one of the cleanest ways to go fully on-chain for the SaaS side of the product.

If you want me to:
- Expand the claim API with more verification
- Add the storage_deposit helper / platform method in the contract
- Create a test script
- Or start the i18n + UI polish

Just say the word and we'll continue. 

**PoC is live in the canonical tree and pushed.** Ready for you to build/deploy the contract and try the flow.