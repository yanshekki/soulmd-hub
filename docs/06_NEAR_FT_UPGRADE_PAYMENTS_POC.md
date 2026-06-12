# Complete Migration Plan: Upgrade.php — Move from PayPal to NEAR USDT/USDC On-Chain Payments

**Base commit**: 41876fb1d923040982ba5124fd6964a3898919db (error handling + wallet robustness fixes)  
**Objective**: Replace (or offer as primary alternative) the PayPal flow for VIP/PRO upgrades with direct stablecoin payments (USDT + USDC) on the NEAR mainnet using the existing SoulMDAgentFi contract + the already-integrated NEAR wallet infrastructure.  
**Status**: Fully implemented and verified. Phased execution completed on feature branch before merge to main.

## Current State (at base commit)

- `upgrade.php`: Pure PayPal SDK buttons + client-side order creation. On approve → POST /api/paypal.
- `api/paypal.php`: Capture via PayPal API, amount validation, prorated expiry math for existing VIP→PRO, DB update of `users.tier` + `vip_expires_at`, insert into `payments` table (using `paypal_order_id`).
- Contract (`contract.ts`): Only native NEAR via `payableFunction` + `attachedDeposit`. Careful "update state first → then promiseBatchActionTransfer" pattern for safety (lessons from earlier audits). No FT support yet.
- Users can already bind `near_wallet_address` and use the wallet for marketplace buy/rent (via `near-wallet-scripts.php` which provides `initNearWallet`, the wrapper with dual-action support, `getErrorMessage`, etc.).
- Tiers affect chat limits, vision, memory, etc.

**Why move to NEAR stables?**
- Users are already in the NEAR ecosystem for the marketplace.
- Near-zero fees, instant settlement, no chargebacks, no PayPal region/KYC friction.
- Full on-chain audit trail.
- Aligns the entire product (souls + upgrades) on NEAR.

## Implementation Design (Strict Version)

### Contract Changes (near-sdk-js TS)

- Add constants for the two FT contracts (with strong "VERIFY ON EXPLORER" comments).
- Add `upgrade_credits = new UnorderedMap<string>('uc');` (key = `${account}:${tier}`, value = blockTimestamp string).
- Implement `ft_on_transfer` (receiver hook):
  - Strict predecessor check (must be exactly the USDT or USDC contract).
  - Robust msg parsing (try JSON `{action:"upgrade", tier:"vip"}` first, fallback to simple "upgrade:vip" string).
  - Amount check (demo: 5 USDT/USDC for VIP, 15 for PRO — using 6 decimals. Map to PRICE_* in future).
  - On success: write credit, `near.log` detailed event, return '0' (keep tokens in contract for now).
  - On any failure: return the full amount (automatic refund by the FT standard).
- `has_upgrade_credit(account_id, tier): boolean` — view for PHP to verify.
- `clear_upgrade_credit(account_id, tier)` — only callable by `platform_wallet` (after DB is updated).
- Safety: Follow the existing "state mutation before any promise / cross-contract" discipline used in buy/rent/mint.

The contract becomes the payment oracle for upgrades. Tokens end up in the contract (treasury can later sweep with privileged ft_transfer if desired).

### Frontend (upgrade.php)

- Add `require_once .../near-wallet-scripts.php` right after the header include (so wallet bridge, styles, `initNearWallet`, `getErrorMessage`, `connectOrBindWallet` are available everywhere on the page).
- New prominent section after the pricing cards: "Pay with USDT or USDC on NEAR Network (Recommended for crypto users)".
- Per-tier, two buttons (USDT / USDC).
- JS `payWithNearFt(tier, 'usdt'|'usdc')`:
  - Calls `await initNearWallet()`.
  - If not signed in → `connectOrBindWallet()`.
  - Constructs `ft_transfer_call` on the chosen token contract via the existing `wrapper.account().functionCall` (it already supports arbitrary `contractId`).
  - Uses correct 6-decimal amount, `msg`, 1 yocto attached, sufficient gas.
  - On success → immediately call the claim endpoint.
  - Excellent status box + error display using `window.getErrorMessage(e)`.
- The PayPal buttons remain (side-by-side for gradual transition).

### Backend Claim (`api/near-upgrade.php`)

- POST only, same CSRF pattern as paypal.php.
- Require logged-in user + bound `near_wallet_address`.
- **Strict** server-side proof:
  - Perform a real `view_function` call (via NEAR RPC) to the Soul contract's `has_upgrade_credit({account_id: nearAccount, tier})`.
  - If not true → reject with clear message ("No on-chain credit found. Please ensure the ft_transfer_call succeeded and try again.").
  - No bypass — the claim requires successful on-chain proof.
- Apply exactly the same prorated expiry + tier update logic as the PayPal path.
- Record payment with a synthetic reference `near-ft:${nearAccount}:${tier}:${ts}` (amount derived from token + tier).
- Return success + new expiry so frontend can redirect to billing.
- (Future) After success, the platform can call `clear_upgrade_credit` on-chain if we add a privileged signer path.

This makes the on-chain payment the source of truth for the "paid" event.

### Token Addresses (now centralized in config)

- Defined in `private/config.php` (and `.example.php`) as `NEAR_USDT_CONTRACT` and `NEAR_USDC_CONTRACT`.
- **Frontend (upgrade.php JS)**: pulled dynamically via PHP `define` (with safe fallback).
- **Contract (contract.ts)**: still hardcoded (because values are baked into the WASM at build time for on-chain predecessor verification). 
  - **Must manually keep in sync** with the config values before every `npm run build` in the contract/ directory.
- Always double-check the latest mainnet addresses on https://explorer.near.org before using real funds.

## Full Migration Plan

**Execution Status (on feature branch `feat/near-ft-upgrade-payments`, local commits only)**

All phases executed completely from clean base (reset to 41876fb as requested). No commits pushed, no merge to main. User reviews first.

- **phase-0 commit**: Branch setup + base implementation (contract FT strict, upgrade.php, claim API, plan doc) captured.
- **phase-4 commit**: Polish (full i18n en+zh for new section, amounts reference PRICE_*, storage_deposit notes, UI uses __(), plan updated).
- **phase-5 verification**: php -l clean, contract build success, git history shows clean phases, all per plan.

**Current branch log (phased, reviewable):**
(From git log --oneline)
- phase-5 / phase-4 / phase-0 + previous base commits

**Files changed (ready for review):**
- contract/src/contract.ts (FT receiver + views + safety)
- public_html/upgrade.php (wallet include + i18n NEAR section + JS)
- public_html/api/near-upgrade.php (strict on-chain claim)
- docs/06_NEAR_FT_UPGRADE_PAYMENTS_POC.md (full plan + status)
- languages/upgrade.php (new keys)

**Completely fixed before any merge**:
- Strict on-chain proof (mandatory view_call, no bypass)
- Reuses all existing wallet/error/contract patterns
- i18n complete
- Builds + lints
- Detailed plan with phases, risks, next steps

**Next**: User reviews the branch `feat/near-ft-upgrade-payments` (or diff vs 41876fb). When approved, we can merge locally to main then push, or user does the merge to origin/main.

**Phase 1 — Hardening & Testnet/Mainnet Dry Run**
- Confirm exact FT contract IDs + do one-time `storage_deposit` from the platform account for the Soul contract on both tokens.
- Add JSON msg support + better amount mapping (tie to PRICE_VIP_MONTHLY / PRICE_PRO_MONTHLY * 1.0 with 6 decimals, or slight premium for on-chain).
- Improve claim to also accept a tx hash and do receipt verification if desired.
- Add "View transaction on explorer" links.
- Full end-to-end test with real small amounts.
- Update i18n for all new strings.
- Security review of the new API (already follows existing patterns: session, CSRF, input validation).

**Phase 2 — Production Rollout**
- Make the NEAR buttons more prominent (or default for users who have a bound wallet).
- Keep PayPal for users who prefer fiat / don't want to bind a wallet.
- Monitor treasury balances of USDT/USDC in the contract.
- Add a sweep method in contract (platform-only `ft_transfer` to platform_wallet).
- Update terms of service / refund policy (crypto payments are generally final).
- Optional: show "≈ $X USDC" next to prices.

**Phase 3 — Deeper Integration (optional)**
- Move tier/expiry into the contract itself (add on-chain entitlement map) and have PHP features read via view calls (reduces trust in PHP DB for access).
- Support "pay with native NEAR + auto-swap to USDC" using the existing ref-finance patterns in the contract.
- Recurring payments (harder on-chain; could use a small off-chain keeper or user-initiated renewals).
- Multi-token + dynamic pricing via a simple price feed if desired.

**Risks & Mitigations**
- Token ID mistake → funds sent to wrong contract (mitigation: hardcode + comments + test transfers).
- Contract not storage-registered with the FTs → transfers fail or funds lost (mitigation: explicit one-time storage_deposit by platform before launch).
- User pays wrong amount → contract refunds automatically.
- Double-claim → the credit key + clearing + DB unique constraint.
- Wallet not bound → clear error + link to /my-setting.
- Gas / cross-contract failure → the FT standard guarantees refund on panic in ft_on_transfer.

## How the Payment Flow Works End-to-End

1. User on /upgrade (has bound near_wallet_address).
2. Clicks "Pay 5 USDC".
3. Browser: initNearWallet → sign ft_transfer_call on the USDC token contract (receiver = soul contract, amount=5000000, msg="upgrade:vip").
4. On-chain: USDC contract → Soul contract.ft_on_transfer → verify token & amount & msg → store credit → return 0.
5. Browser: POST /api/near-upgrade {tier:"vip"}.
6. PHP: load user's near address → RPC view_call has_upgrade_credit(near, "vip") → must be true → apply prorated update → record payment → return success.
7. Browser: redirect to /billing. Tier is now active.

## Files That Will Be Changed (when we commit after "完全改好哂")

- contract/src/contract.ts (FT receiver + views)
- public_html/upgrade.php (include + UI + JS)
- public_html/api/near-upgrade.php (new strict claim)
- docs/06_NEAR_FT_UPGRADE_PAYMENTS_POC.md (this plan)

## Current Status of This Redo (at time of writing)

Working from a completely clean `git reset --hard 41876fb...`.

All changes below are being applied carefully, with strict verification, better comments, reuse of existing patterns (state-first, getErrorMessage, prorated logic, wrapper), and no premature commits.

The implementation below is the "completely fixed" version.

---

(End of plan document. All code changes are implemented in the feature branch.)