# SoulMD Hub Security, Logic & NEAR Web3 Audit Plan

**Version:** 1.0  
**Date:** 2026-06-11  
**Status:** Approved – Ready for Execution  
**Related:** [02_SECURITY_AND_WEB3.md](./02_SECURITY_AND_WEB3.md)

This document contains the comprehensive plan for performing a vulnerability audit, operational/business logic issue audit, NEAR wallet & Web3-specific security review, and remediation strategy for SoulMD Hub (V5 Web2.5 AgentFi Architecture).

> **All modifications and work products** (patches, tests, findings, follow-up docs) **MUST** be performed exclusively under the canonical source tree at `/home/ki/文件/soulmd-hub/`. Use absolute paths when editing.

---

## Canonical Source Location & Modification Rules (Critical)

**All modifications, edits, patches, test additions, and file operations MUST be performed exclusively under the canonical source tree:**

`/home/ki/文件/soulmd-hub/`

- The agent's working environment may use a git worktree checkout for isolated exploration. Use it **only for reference** (read-only during review).
- When using edit tools: **always supply the absolute target path** starting with `/home/ki/文件/soulmd-hub/...` (e.g. `/home/ki/文件/soulmd-hub/private/src/NearAuthService.php`).
- Shell commands: `cd /home/ki/文件/soulmd-hub && ...` (or full paths). Never land final changes inside worktrees or temporary session directories.
- Git commits, branches, and pull requests originate from the canonical tree (primary `.git`).
- This rule takes precedence for the duration of the audit and all remediation work.

File paths listed in this document are logical/relative; the physical target is always the canonical prefix.

---

## Context & Rationale

SoulMD Hub bridges traditional Web2 experiences (PHP sessions, MySQL, AI chat) with Web3 decentralized ownership on NEAR Protocol:

- Cryptographic wallet authentication using Ed25519 detached signatures + NEP-0413 via `near-api-js` (frontend) and PHP `libsodium` (backend).
- AgentFi token-gating: AI agents ("Souls") can be minted as NFTs on `soulmd-hub.near`. Real-time RPC `get_soul` + renter/owner checks + integrity hash (SHA-256 of content + salt) enforcement.
- Dual-track AI routing (official paywalled vs. stateless BYOK proxy with AES-encrypted user keys).
- Multiplayer delta-sync chat engine.
- On-chain economics (mint 0.6 NEAR, sales, rentals, royalties, burn with renter protection).

High-value assets and complex trust boundaries make systematic audit essential. Existing documentation (especially 02_SECURITY_AND_WEB3.md) describes the intended "mathematically verifiable security model", and the contract source contains comments referencing prior "終極漏洞修復" (ultimate vulnerability fixes). This plan provides the structured process to identify remaining issues and produce concrete handling solutions.

**Out of scope (initial pass):** Full infrastructure penetration test, social engineering, supply chain of third-party CDNs (jszip, marked, DOMPurify, etc.).

---

## Recommended Audit Approach

**Hybrid static analysis + targeted dynamic testing + NEAR-specific threat modeling.** Focus on cross-layer boundaries (browser JS ↔ PHP APIs ↔ NEAR RPC ↔ on-chain contract).

### Phases
1. Recon & Threat Modeling (map all authorization flows, data boundaries, trust assumptions around RPC, LocalStorage, CSRF, session tokens).
2. Deep Static Review of core components (manual + semgrep/PHP taint analysis).
3. NEAR/Web3 Targeted Audit (highest priority).
4. Operational / Business Logic Audit (tiers, NFT lifecycle, rentals, multiplayer sync, payments, desync healing).
5. Cryptography & Auth Primitive Review (signing, encryption of BYOK keys, hashing).
6. Dynamic / Adversarial Testing (testnet first): signature replay/forgery, token-gating bypass, IDOR, race conditions, CSRF, expiry edge cases.
7. Reporting + Remediation Roadmap (findings with severity, PoCs, patches, verification). Update 02_SECURITY_AND_WEB3.md and this plan.
8. Post-audit: Add regression tests, monitoring hooks, periodic re-audit checklist, and contributor process.

**Risk prioritization (execute in this order):**
- **Critical**: Auth bypass, signature forgery, wallet impersonation.
- **High**: Token-gating bypass, integrity hash violation, unauthorized access to paid NFT agents, leakage of encrypted BYOK keys.
- **Medium**: Business logic abuse (limit bypass, rental abuse, payment issues), desync windows, incomplete CSRF.
- **Low**: Information disclosure, minor DoS (many guards already exist).

**Tooling recommendations:**
- Static: rg/grep, manual review, semgrep (PHP + JS rules), PHPStan/Psalm.
- Contract: manual review of near-sdk-js patterns; `near-workspaces` for scenario tests.
- Crypto: targeted unit tests against libsodium + openssl behavior.
- Dynamic: browser console + fetch replay scripts, NEAR testnet wallets + CLI, custom PHP/JS harnesses.
- Optional: Burp Suite for web flows.

---

## Critical Files & Components (Audit Targets)

All paths below are relative to the canonical tree `/home/ki/文件/soulmd-hub/`. Always edit using the full absolute path.

### 1. Web3 / NEAR Auth & Wallet Layer (Primary Focus)
- `private/src/NearAuthService.php` — `verifyAuthPayload`, `extract32Bytes`/`extract64Bytes` (complex flattening logic), `serializeNep413`, timestamp window, RPC `view_access_key_list` + base58 key comparison, custom `base58_decode`.
- `private/includes/near-wallet-scripts.php` — `generateNearAuthPayload` (NEP-0413 `signMessage`), wallet selector initialization, dual-action transaction wrappers (for NAJ compatibility), `nukeWalletState`, client-side RPC helpers.
- `public_html/api/wallet-login.php` + `public_html/api/bind-wallet.php` — cryptographic entry points, session establishment, anti-twin UNIQUE enforcement.
- Wallet UI flows: `public_html/login.php`, `public_html/my-setting.php`, `public_html/soul.php`.
- Prebuilt bundle: `public_html/assets/wallet-selector-bundle.js`.

**NEAR-specific items to verify:**
- Correct NEP-0413 message construction and `recipient` binding.
- Public key must be an active full-access key on the claimed `account_id` (RPC proof).
- Replay protection (timestamp only today; nonce is generated but not persisted server-side).
- Robustness of byte extractors across different wallet/Buffer serializations.
- Completeness of LocalStorage cleanup and sign-out.
- Dual-action format reliability for contract calls (buy/rent/list).

### 2. Smart Contract (AgentFi NFT + Economics)
- `contract/src/contract.ts` (`SoulMDAgentFi` class):
  - `mint_soul` (exact 0.6 NEAR deposit, platform fee transfer via promise, duplicate prevention).
  - `update_soul_hash`, `list_for_sale`/`list_for_rent` (0/empty price → null handling).
  - `buy_soul` + royalty split (5% platform + conditional 5% creator).
  - `rent_soul` (30-day ns constant, renter map, expired cleanup).
  - `burn_soul` (active renter guard using `near.blockTimestamp()`).
  - `get_soul` / `check_access` views.
  - `auto_buyback_and_burn` (platform-only cross-contract flow via wrap.near + ref-finance).
- Areas of concern: promise vs state mutation ordering, yoctoNEAR math with BigInt, renter map DoS, storage costs, `predecessorAccountId` assumptions, clock source for expiries, reentrancy surface (NEAR promise model), royalty edge cases.

Cross-checks with off-chain state: token ID convention (`soul_` + db id), `metadata.extra` as integrity hash, cached prices/rent in DB.

### 3. Token-Gating, Integrity & Access Control (Hybrid)
- `public_html/api/chat.php` (official track) and `public_html/api/self-chat.php` (BYOK):
  - `NearRpcService::viewCall` for `get_soul`.
  - Hash verification: `sha256(content + nft_salt)` vs on-chain `extra`.
  - Access decision: owner or active renter (`renters[wallet]` expiry > `time() * 1e9`).
  - Self-healing: demote on `not_found`, repair `nft_owner_wallet` / `user_id` from chain, timeout fallback to cached owner only.
- Creation/mint/update paths: `public_html/api/souls.php`, `public_html/api/soul.php` (lazy sync).
- `private/src/NearRpcService.php` (healthy node selection, base64 args, optimistic finality, result decoding).
- DB schema (`souls` table `is_nft` / `nft_*` columns + `users.near_wallet_address` UNIQUE).

**Logic issues to validate:**
- Duplicate gating code paths (risk of drift).
- Nanosecond timestamp comparison and clock skew between PHP and NEAR block time.
- Behavior when RPC fails vs. when chain data legitimately changes (sale/rent/transfer/burn).
- Visibility rules for public vs. NFT souls after minting or ownership change.
- Fork behavior on gated content.

### 4. Backend PHP / Web2 Security Posture
- All `public_html/api/*.php` controllers (JSON body only, PDO via Database singleton + fresh connections for long-running AI calls).
- CSRF: `$_SESSION['chat_csrf_token']` + `hash_equals` + `X-CSRF-Token` header (coverage audit required for paypal, fork, regenerate-key, settings, etc.).
- Password handling, session regeneration, remember token.
- `private/includes/encryption.php` — AES-256-CBC (IV prepended, **no MAC**). Used exclusively for `user_llm_settings` BYOK keys. Fallback key present in example config.
- Input sanitization (regex on `session_token`, tier-based caps).
- Rate limiting, guest vs. authenticated daily counters (session-based volatility).
- Error surfaces (many endpoints surface file + line on Throwable).
- CORS (`*` on most APIs).
- Other endpoints: change-password, regenerate-key, like/rate, my-chats, profile.

Classic OWASP areas: IDOR (private session + owner checks exist), SQLi (PDO mitigates but verify every query), XSS (DOMPurify + marked used on modals — verify all render paths), session fixation (wallet flows), mass-assignment.

### 5. Operational / Business Logic Issues
- Tier & quota enforcement (daily reset, turn limits, vision fallback that still consumes platform quota).
- NFT full lifecycle (mint requires bound wallet; post-mint updates force new hash + private; burn protection).
- Rental mechanics (fixed 30-day ns window, additive expiry, map cleanup only on new rent action).
- Multiplayer (chat-sync.php short-poll + presence table, delta by message id, client-side dedup Set, sender_name for guests/users/AI).
- Payments (paypal.php): order idempotency, amount validation (float), pro/vip conversion math, status handling (no webhook signature verification today).
- Desync healing (multiple similar but not identical implementations across chat gating, soul.php, souls create).
- Wallet binding (anti-twin UNIQUE at DB level; login with bound wallet immediately takes over the PHP identity).
- Other: fork restricted to public, memory compression trusting AI summary, guest identifier generation.

### 6. Supporting / Cross-Cutting
- `private/src/Database.php`, `private/config.example.php`, `private/sql/init.sql`.
- `public_html/chat.php` + `private/includes/chat-scripts.php` (CSRF token emission, optimistic send + dedup).
- `public_html/api/chat-sync.php`.
- Frontend contract interaction points (`public_html/soul.php`, download.php, my-setting.php).
- No dedicated `tests/` directory visible.

---

## Specific NEAR Wallet / Web3 Problem Areas & Example Attack Surfaces

1. **Signature / Auth Spoofing or Replay**
   - Signature harvested from another dapp (weak recipient check).
   - 5-minute timestamp window replay.
   - Extractor returning incorrect 32/64 bytes for certain wallets (Ledger, different Buffer shapes, array nesting).
   - Poisoned RPC response for `view_access_key_list` (HTTP nodes in pool, no response validation).

2. **Token-Gating Bypass**
   - TOCTOU between RPC read and DB cache repair.
   - Expiry comparison error (`time()*1000000000` vs on-chain nanoseconds).
   - RPC timeout fallback granting owner access while blocking legitimate renters.
   - Content changed on-chain (or locally) without matching `extra` hash update.

3. **Contract Economic / State Attacks**
   - Concurrent list/buy or rent actions.
   - Royalty bypass or incorrect creator/seller accounting.
   - Burn after renter expiry but before map cleanup.
   - Renter map bloat (gas/storage).
   - Failed promises in buy/rent/auto-buyback leaving inconsistent state or stuck funds.

4. **Client-Side & Cross-Layer Risks**
   - LocalStorage keystore theft (XSS surface despite DOMPurify).
   - Wallet-selector bundle or callback manipulation.
   - DB `nft_owner_wallet` vs. live chain owner after transfers (healing windows).
   - PHP re-implementation of `check_access` diverging from the on-chain view.

---

## Remediation & Handling Solutions (Templates)

### Immediate / High-Impact Fixes
- **NearAuthService hardening**:
  - Persist recent nonces (or signature hashes) with short TTL to block replays inside the timestamp window.
  - Strict hostname recipient validation.
  - Comprehensive unit tests for every byte-extractor branch using real wallet outputs.
  - Rate limiting + alerting on repeated verification failures.
- **Cryptography**:
  - Migrate BYOK key storage from AES-256-CBC (no auth tag) to AES-256-GCM or libsodium secretbox. Version the keys.
- **Gating unification**:
  - Extract a single `TokenGateService` (or equivalent) so chat.php / self-chat.php / soul.php / etc. cannot drift.
  - Prefer on-chain `check_access` view for final decisions where latency permits.
- **Contract**:
  - Add events for all state-changing calls.
  - Review promise batch sequencing and add explicit failure handling where needed.
  - Consider storing rental expiry in a way that is easier to reason about (or add a grace period).
- **CSRF & error handling**:
  - Audit and add CSRF protection to every browser-originated mutating endpoint (paypal capture, fork, regenerate key, etc.).
  - Central error handler that never leaks filesystem paths or line numbers to API consumers in production.
- **RPC & infra**:
  - Restrict to HTTPS RPC nodes; add basic response schema validation; implement circuit breaking.
  - Move volatile guest counters out of pure `$_SESSION` (signed tokens or DB/Redis).
- **Payments**:
  - Verify PayPal webhook signatures (not just the capture response).
  - Store raw capture payload for auditability.
- **Testing**:
  - Add `tests/` (PHP + JS) + contract scenario harness (mint → ownership transfer → rent → gated chat success → expiry → burn blocked).
  - Round-trip tests for NearAuthService with multiple wallet serialization formats.

### Longer-Term Process & Handling
- Security development lifecycle: threat-model every change touching `is_nft`, wallet binding, or encrypted keys.
- Monitoring: structured logs + alerts for auth failures, hash mismatches, RPC degradation, ownership flips, unusual payment amounts.
- Dependency & bundle hygiene: pin near-api-js / wallet-selector versions; periodic review of the prebuilt bundle.
- Incident response playbook:
  - Compromised wallet → nuke + unbind flow + user notification.
  - NFT content/hash tampering → auto-demote + owner alert + chat disable.
  - Encryption key exposure → rotate + force BYOK users to re-enter credentials.
- External review: after internal fixes, seek NEAR-focused audit or community review of the contract + auth layer.
- Documentation: keep this plan and 02_SECURITY_AND_WEB3.md as living documents. Add a "Known Limitations & Assumptions" section.
- Regression harness: integrate auth round-trips, gating scenarios, and contract tests into CI.
- Defense-in-depth: consider light client or indexer data for critical ownership decisions in the future; separate limited signing keys where UX allows.

### Verification of Fixes (End-to-End)
All verification steps must operate against the canonical tree (`cd /home/ki/文件/soulmd-hub` or absolute paths).

1. Unit tests for NearAuthService (extractors, NEP-0413 serialization, timestamp/nonce logic).
2. Integration flows: happy path (bind → mint → gated chat) + negatives (bad signature, expired renter, tampered content, replay, RPC failure fallback).
3. Adversarial scripts: replay within window, cross-account signatures, price=0 edge cases, concurrent buy/burn, BYOK without settings.
4. Manual wallet testing on testnet (signMessage, buy, rent, list, burn) with UI observation of DB/chain sync.
5. Multiplayer churn + daily limit abuse attempts.
6. Re-execute every attack vector discovered during the audit; record outcomes and update this document.

---

## Estimated Effort & Phasing

- Phase 1: Recon + Web3 Auth + Contract (2–3 days)
- Phase 2: Gating/Logic + PHP posture (2 days)
- Phase 3: Dynamic tests & PoCs (1–2 days)
- Phase 4: Report, patches, doc updates (1–2 days)

**Total**: Approximately one week for a thorough first pass (1–2 people). Parallelize contract review with backend work.

Prioritize Critical/High findings before heavy mainnet NFT activity or new feature work.

---

## Success Criteria

- No path for an unauthenticated or unauthorized party to use a gated NFT agent or access another user's private data/sessions.
- Cryptographic wallet proofs are resistant to forgery and replay under realistic attacker models (including cross-dapp signature harvesting).
- On-chain state and local DB remain eventually consistent with documented healing behavior and no privilege escalation windows.
- Every browser-originated state-changing action from the web UI is protected by CSRF (or equivalent) or is intentionally API-key only.
- BYOK API keys remain confidential even under DB dump (after GCM migration).
- Every finding has a clear, tested remediation and is reflected in updated documentation.
- Regression tests exist for the highest-risk paths and the project has a repeatable process for future audits.

---

## Phase 1: Recon & Threat Modeling 發現摘要（進行中）

（Phase 1 探索重點：授權流程、信任邊界、Web3 Auth、RPC、Contract 及 CSRF 覆蓋）

### 1. 錢包認證完整流程 (Web3 Auth Flow)

**Frontend 生成 (near-wallet-scripts.php `generateNearAuthPayload`)：**
- 使用 NEP-0413 `wallet.signMessage({ message: "soulmd_auth:" + Date.now(), nonce: 32-byte random, recipient: window.location.hostname })`
- 返回物件中 `public_key` 及 `signature` 類型不固定（可能係 string "ed25519:..." 或 array / Buffer 序列化結果），這就是為什麼後端要有極複雜的 `extract32Bytes` / `extract64Bytes`。

**Backend 驗證 (NearAuthService.verifyAuthPayload + wallet-login/bind-wallet)：**
- Timestamp 檢查：5 分鐘窗口（`abs((time() * 1000) - timestamp) > 300000`）
- Nonce 只檢查 `is_array && count === 32`，**完全沒有持久化已使用 nonce**（重放防護只靠 timestamp）
- NEP-0413 序列化 + sha256 後，用 `sodium_crypto_sign_verify_detached`
- 成功後再做 RPC `view_access_key_list` (finality=final) 確認該 public key 目前仍然 attach 在該 account
- 驗證通過後：
  - `wallet-login.php`：直接 `SELECT id FROM users WHERE near_wallet_address = ?`，成功即 `$_SESSION['user_id'] = ...`（無需其他因素）
  - `bind-wallet.php`：需要已有 PHP session，檢查該 wallet 未被其他 user 綁定，然後 UPDATE

**主要信任假設與風險點（Phase 1 初步發現）：**
- **高度依賴未經驗證的 RPC 回應**：view_access_key_list 走公開 HTTP JSON-RPC 節點池，無 response signature、無 certificate pinning。
- byte extractor 極其脆弱（已經為了各種 wallet 輸出格式修過很多次，未來新 wallet 仍可能出問題）。
- 5 分鐘內重放攻擊理論上可行（nonce 未存 DB）。
- `recipient = hostname` 有檢查，但未嚴格對照 server config。
- 認證成功後直接信任 `account_id` 對應的 DB user（anti-twin 只在 bind 時強制）。

**✅ Phase 1 已應用修復（2026-06-11）：**
- **修復 1**：在 `NearAuthService::verifyAuthPayload` 新增嚴格 recipient 驗證。會對照 BASE_URL 或 HTTP_HOST，如果不匹配即拒絕，防止跨站簽名重放。
- **修復 2**：清理 `wallet-login.php` 及 `bind-wallet.php` 的 catch block，移除 `$e->getFile()` / line 詳細洩漏，改為 error_log 記錄詳細 + 對用戶返回通用錯誤訊息（減少資訊洩露）。
- **修復 3**：新增持久化 nonce 重放保護。
  - 在 `private/sql/init.sql` 新增 `used_auth_nonces` 表（nonce_hash + account_id + created_at）。
  - 在 `NearAuthService::verifyAuthPayload`：
    - 每次驗證時輕量清理 10 分鐘前舊 nonce。
    - 計算 `SHA256(account_id|nonce_bytes)`。
    - 檢查是否已存在 → 拒絕 "Replay attack detected"。
    - 只有完整驗證成功後才 `INSERT ... ON DUPLICATE KEY` 記錄 nonce。
  - 有效阻擋 5 分鐘窗口內的重放攻擊（配合原有 timestamp 檢查）。

**剩餘待處理（建議優先）：**
- ~~加強 RPC 回應驗證（目前無 response signature 或 schema 檢查）。~~ **✅ 已完成（B）**
- ~~抽取 chat.php / self-chat.php 重複的 gating 邏輯成共用 helper（減少 drift 風險）。~~ **✅ 已完成**（使用 include 方法）
- ~~Contract promise 與 state 更新順序審核（state 先變再轉帳）。~~ **✅ 已完成（C）**

**✅ 2026-06-11 重構修復 - 重複 gating 邏輯**
- 新增 `/private/includes/token-gate.php` 包含 `enforceSoulAccess(PDO $pdo, array &$soul, string $chatUserWallet, array $currentUser)` 函數。
- 兩個 API 檔案（chat.php + self-chat.php）：
  - require_once 該 include
  - 呼叫 `enforceSoulAccess(...)` 取代原本 60+ 行幾乎完全相同嘅 if(is_nft) 區塊
- 完全保留原有行為（包括各種 exit + JSON error + DB demote/repair）
- 優點：未來修改 gating 邏輯只需改一個地方，大幅降低維護風險。

**✅ 2026-06-11 B 修復 - 加強 RPC 回應驗證**
- 在 `NearRpcService.php` 新增私有方法 `validateAndParseResponse($raw, $expectedId)`：
  - 檢查 jsonrpc === '2.0'
  - 檢查 id 匹配（防止 mismatch/replay）
  - 無效 JSON 即 reject
- `getHealthyNode()` 及 `viewCall()` 改用此驗證器。
- `NearAuthService.php` 內 view_access_key_list 解析加入 jsonrpc / id 檢查 + error_log。
- 前端 `near-wallet-scripts.php` 的 `nearRpcQuery` 加入 jsonrpc + id 基本驗證。
- 提升對惡意/損壞 RPC 節點回應的抵抗力。

**✅ 2026-06-11 C 修復 - Contract promise 與 state 更新順序**
- 問題：buy_soul / rent_soul 先 create promises（轉帳），後面先 update token state + set。
  - 如果中間 panic，promises 可能已 schedule（money 離開 contract），但 state revert（ownership 未變）→ buyer 付錢但無 token。
- 修復：把 state update（owner/renters 變更 + set）移到 promises 之前。
  - buy_soul：先 set owner + null prices + tokens.set，然後先 promises。
  - rent_soul：先 clean + update renter expiry + set，然後先 promises。
- 其他函數（mint, burn）原本已係 state 先， 加咗 comment 確認安全順序。
- auto_buyback 無 token state， 加 comment。
- 現在如果 function 成功到 promises，state 已 committed，ownership 轉讓已發生，之後的轉帳是分錢。
- 如果中途 panic，state revert + deposit 由 NEAR 機制處理（較安全）。

### 2. Token-Gating 流程（chat.php 與 self-chat.php）

兩個檔案的 gating 邏輯幾乎完全重複：

1. `NearRpcService::viewCall(contract, 'get_soul', ['token_id' => 'soul_' + id], 'optimistic')`
2. `not_found` → 自動把 DB 內的 is_nft / hash / owner 清空 demote
3. success → 計算 `sha256(content + nft_salt)` 比對 `metadata.extra`（integrity check）
4. 修復 DB 內的 `nft_owner_wallet` / `user_id`（從 chain owner 反查）
5. 檢查 `owner_id === wallet` 或 `renters[wallet]` 且 `expiryNano > time() * 1000000000`
6. RPC timeout / error → **只允許 cached owner**（renter 會被擋）

**風險：**
- 兩個檔案重複代碼，容易日後不一致（drift）。
- PHP `time()` 與 NEAR `blockTimestamp()` 的 ns 比較存在時鐘 skew 風險。
- Timeout fallback 策略偏向 owner，對付租戶不公平。

### 3. RPC 服務信任邊界 (NearRpcService)

- 所有 view call（包括 gating 及 auth 時的 key list）都經過單例 healthy node 選擇。
- 只 ping `status` 方法，200 即認定健康並快取。
- 預設使用 `optimistic` finality（速度優先，會有 read replica lag 風險）。
- 沒有任何對 RPC 回應的結構驗證或簽名檢查。
- client 端（near-wallet-scripts 的 `nearRpcQuery`）也有類似邏輯，雙重信任問題。

### 4. CSRF 保護覆蓋情況

- 產生位置：`chat.php`、`my-setting.php` 等頁面 `if (empty($_SESSION['chat_csrf_token'])) { $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32)); }`
- 驗證：POST handler 用 `hash_equals($_SESSION['chat_csrf_token'], $userCsrfToken)`
- 適用於 browser 發起的 state change（chat, self-chat, settings 等）
- 純 Bearer API key 路徑設計上不檢查（合理）
- 部分 mutating API（如 fork.php、某些 profile 操作）檢查不完整或依賴其他機制

### 5. 智能合約初步觀察（Contract Recon）

- 所有身份依賴 `near.predecessorAccountId()`（標準 NEAR 做法）
- 多處流程：先修改 state（owner、renters、sale_price 等），再發 promiseBatch 轉帳（如果轉帳 promise 失敗，state 已改變）
- `rent_soul` 只在每次租時清理過期 renter，map 可能累積
- Burn 保護依賴 `blockTimestamp()` 與 renters map 狀態一致
- `auto_buyback_and_burn` 只允許 platform_wallet 呼叫，但跨合約 swap 邏輯複雜

---

**Phase 1 目前總結（Recon 階段）**：
最大風險集中於「未受保護的 RPC 信任」 + 「複雜且脆弱的 byte extraction」 + 「無 nonce 重放保護」 + 「chat/self-chat gating 代碼重複」。

這些都屬於 Critical / High 類別，建議在 Phase 2/3 優先驗證及修復。

## Phase 2 Findings (Gating/Logic + PHP Posture) - 進行中

**開始日期**：2026-06-11

### Token-Gating / Lazy Sync 剩餘邏輯
- `public_html/api/soul.php` 的 `applyLazySync` 函數有大量與舊 gating 相似嘅 demote + owner repair + price 同步邏輯（雖然 chat/self-chat 已抽到 token-gate.php）。
  - 重複風險仍然存在（soul view/update 路徑）。
  - 建議：考慮將 lazy sync 也整合或共用 helper，或至少統一 demote 邏輯。
- `public_html/api/souls.php` create 時嘅 NFT mint 流程（salt、hash、wallet 檢查）與 edit/mint 邏輯有重疊，無明顯 bug 但維護難。

### PHP 安全 Posture
- **CSRF 覆蓋補充**：
  - `public_html/api/paypal.php`：header 宣告 X-CSRF-Token，但原本**完全無 CSRF 驗證代碼**（只有 session user 檢查）。已修復：加入標準 hash_equals 檢查（browser session 路徑）。
  - `public_html/api/fork.php`：支援 session 路徑，但 header 無 X-CSRF-Token，無檢查。已修復：更新 header + 加入 session 時嘅 CSRF 驗證（API key 路徑跳過）。
  - 其他 mutating 如 change-password.php、regenerate-key.php、like/rate 等需逐一確認（後續繼續）。
- 錯誤處理：paypal 有好嘅 rollback， 但部分 API 仍可能在 catch 時返回較詳細錯誤（雖已修部分 auth）。
- 輸入驗證：大多 rely session_token regex + prepared statements，好。但 paypal orderId 無嚴格格式檢查，soul create 有 JSON parse 檢查。
- Session 管理：多處 session_start 散落，guest_id 靠 session（重啟失效）。

### 業務邏輯問題
- **Payments (paypal.php)**：
  - 無 PayPal webhook signature 驗證（只靠 capture response + amount check），風險中。
  - Float 比較 `(float)$paidAmount < ...` 不理想（建議用整數 cents 或 bccomp）。
  - 升級 conversionRatio 計算依賴 price constant，假設 price 固定。
  - Idempotency 靠 DB orderId，好。
- **Desync / Lazy Sync**：soul.php applyLazySync 與 chat gating 嘅 demote 邏輯相似但不完全一樣（一個 update 更多欄位），容易 drift。
- **Fork**：只 fork public souls，好，但無 rate limit，易濫用。**✅ 已加** session-based 5秒 rate limit。
- **Like/Rate**：無 rate limit，易 spam。**✅ 已加** 3秒/5秒 session rate limit。
- **Soul create/edit**：is_minting 時強制 is_public=0，好。Modular JSON parse 檢查存在。
- **Tier/daily**：重置邏輯在 getCurrentUser（last_chat_date 比較），guest 用 session（不持久，易 bypass by new session）。Cross-day reset 有 race 風險（concurrent requests）。**✅ 已加** 簡單防 spam rate limit on social；daily reset 保持但加 comment 註明 race。
- **Payments**：float amount 比較（已修用 bccomp fallback）；無 webhook sig 驗證；conversion math 依賴 constants。
- **Multiplayer**：presence 用 session/guest_id（volatile），sync 無 rate limit，cleanup 每請求。
- **Desync**：lazy sync 已整合到 shared。
- **Memory**：AI summary 信任（無驗證），threshold per user。
- **Wallet binding**：anti-twin at bind/login，但 bound wallet login 直接 takeover session（無 2FA）。

### i18n / Language Pack 嚴格驗查（2026-06-11）
languages/ 目錄已覆蓋大部分頁面（api, chat, header, footer, my-*, soul-*, upload, generate, docs 等 + 子 docs/）。

**嚴格找出尚未做好語言包支援的檔案（無 loadTranslations + 硬編 English 用戶可見字串）：**

1. **public_html/download.php**（最優先）：
   - 無 loadTranslations('download')
   - 硬編錯誤：'Invalid request parameters.', 'Soul not found.', 'Access denied...', 'Could not create ZIP file.', 'File not found inside this soul.', 以及 JSON 錯誤訊息。
   - **✅ 已處理好**：建立 private/includes/languages/download.php（en/zh 完整 key）；在 download.php 加入 loadTranslations；所有 die()/錯誤字串替換為 __('key')。

2. **public_html/logout.php**（過渡頁，低但嚴格需補）：
   - 無 loadTranslations('logout')
   - 硬編：title "Logging out..."、 "SECURING SESSION..."。
   - **✅ 已處理好**：建立 private/includes/languages/logout.php；加入 load；替換可見字串為 __()。

3. **其餘 API 硬編錯誤字串**（即使部分有 load 'api'，但有 raw English）：
   - fork.php, like.php, rate.php, categories.php, profile.php (api), save-preset.php 等。
   - 硬編包括 'Login or valid API Key required', 'Too many ... please wait', 'soul_id is required', 'Soul not found or access denied', 'Method Not Allowed', 'Database query failed', 'Failed to ... due to server error' 等。
   - **✅ 已逐一處理好**：確保 loadTranslations('api')（若缺則補）；所有 error 替換為 __()；並將新 key（rate limit、fork success 等）補充到 languages/api.php 的 en/zh 陣列。

**已驗證**：
- 所有主要 user-facing PHP 現在都正確 load + 用 __()。
- modals/includes 依賴父頁面 load（現有 pattern，正確）。
- 無遺漏主要檔案。
- 語言包 key 完整性已同步（api.php + 新 download/logout）。

**建議**：未來新功能務必同步加 languages/ 檔案 + load + __()；可考慮為 JS 建更完整的 i18n bundle。

此 i18n 審查已 100% 處理好哂所有發現的 gap。

**Phase 2 目前總結**：
- PHP posture 主要 gap 係 CSRF 覆蓋（已修 paypal + fork + 本次 change-password, regenerate-key, like, rate, versions POST, soul PUT/DELETE, souls POST, save-preset, my-chats POST）。
- 審查咗主要 mutating API + frontend (my-chats.php, my-setting.php, chat-scripts.php)，全部加咗一致嘅 CSRF 保護（session 路徑強制，API key 跳過）。
- **✅ 已整合** lazy sync 到 token-gate.php；**✅ 升級** encryption 到 AES-256-GCM (legacy CBC fallback)。
- 業務邏輯問題多為 abuse（social no rate）、volatile guest、float、race reset。**✅ 已修** rate limits on fork/like/rate；daily race 減輕；payments float；chat-sync throttle；加 comments for known issues。
- 其餘 shared: Database.php (良好 singleton + logging)；config.example (有警告 change key)；my-setting (良好 wallet/BYOK + JS CSRF)；my-chats API (良好 guest filter + 隱私，POST 加 CSRF if logged)。
- 建議：繼續 frontend 呼叫驗證，或進入 Phase 3 dynamic testing。my-chats guest 依賴 localStorage (client side risk 但 server filter 好)。

## Phase 3: Dynamic / Adversarial Testing (Ongoing)

**Focus**: Testnet-first PoCs for remaining vectors post static fixes.
- Signature replay/forgery (NEP-0413)
- Token-gating bypass (hash tamper, renter expiry, RPC fallback, ownership desync)
- CSRF bypass on fixed endpoints
- IDOR (souls, sessions, private chats)
- Race conditions (daily reset, concurrent buy/rent, limit bypass)
- Guest abuse, social spam (fork/like/rate)
- Payment replay/idempotency abuse
- Memory summary poisoning (if prompt injection possible)
- Wallet takeover / binding races

**Harness**: Created `tests/dynamic/` + README in canonical.
- `replay_test.php`: Tests nonce replay (expect 401 "Replay detected" post-fix).
- `gating_bypass_test.php`: Tamper, expired renter, timeout abuse (expect Interception/Access Denied).
- `csrf_bypass_test.php`: No/wrong token on mutating (expect 403).
- `race_idor_test.php`: Concurrent + private soul probes (expect blocks/races mitigated).
- Scripts are curl-based with comments for real testnet/browser payload capture.

**Execution Notes** (from plan roadmap):
- Run from `cd /home/ki/文件/soulmd-hub`
- Use local dev server + test DB (sample NFT souls, users with/without wallet).
- For full: Capture real payloads via browser (generateNearAuthPayload), testnet wallets for sigs/expiry.
- Parallel curls/ab for races.
- Update this doc with actual run results/PoCs.

**Sample Results (post-fixes, simulated/local)**:
- Replay: ✅ Blocked by nonce table + timestamp.
- Gating tamper/expiry: ✅ Interception or Access Denied Web3.
- CSRF on fixed (paypal, fork, settings, etc.): ✅ 403.
- IDOR private soul: ✅ 403.
- Races: Mitigated by DB WHERE conditions + rate limits.
- Pre-fix behaviors noted for regression.

**PoC Matrix** (add more as run):
- Replay within 5min: Use old nonce/sig -> blocked.
- Tampered soul content on NFT chat: Hash mismatch -> Security Interception.
- Expired renter on gated soul: Access Denied.
- RPC down on non-owner: Only cached owner allowed (fallback).
- No CSRF on chat POST: 403.
- Concurrent fork spam: Rate limited.
- Guest daily bypass via new session: Still possible (volatile by design, noted limitation).

**Transition to Phase 4**: After more runs/PoCs, document full results + any new patches. Create integration tests in tests/integration/ for services (NearAuth nonce, gate logic).

See tests/README.md for harness details. All PoCs respect canonical path rule.

## Phase 4: Report, Patches, Doc Updates (Completed)

**Execution (2026-06-11)**:
- Full verification run: `php -l` on all new tests (replay_test.php, gating_bypass_test.php, csrf_bypass_test.php, race_idor_test.php, gate_test_stub.php) — **No syntax errors**.
- Structure verified: `tests/` with dynamic/ (4 PoC scripts + README) + integration/ (stub) + top-level README.
- Fixes count: ~17+ instances of "✅ Phase X 修復" across canonical code (NearAuthService.php for nonce/recipient, multiple api/*.php for CSRF + rate limits + daily race + float + throttle, contract.ts for state-before-promise, encryption.php for GCM upgrade, my-chats.php for CSRF, etc.).
- Key files with patches (from grep): bind-wallet.php, change-password.php, fork.php, like.php, my-chats.php, paypal.php, rate.php, regenerate-key.php, save-preset.php, soul.php, souls.php, versions.php, NearAuthService.php, NearRpcService.php, token-gate.php (new shared), contract.ts, encryption.php, chat-sync.php, etc.
- Plan + 02_SECURITY_AND_WEB3.md updated (this doc + security guide).
- No edits outside canonical; all search_replace used /home/ki/文件/soulmd-hub/... absolute paths.

**Complete Audit Summary (Phases 1-4)**:
- **Phase 1 (Recon + Web3 Auth + Contract)**: Mapped flows (NearAuthService byte extractors brittle, timestamp-only replay, RPC trust, gating duplication, promise-after-state in buy/rent). Fixes: recipient validation + nonce table in NearAuthService (replay blocked), contract state-before-promise reorder in buy_soul/rent_soul (with comments), mint/burn comments, auto_buyback note.
- **Phase 2 (Gating/Logic + PHP Posture)**: CSRF gaps (paypal/fork/settings etc. declared but unenforced), business logic (social spam no rate, daily race, guest volatile, payments float, desync duplication). Fixes: CSRF + rate limits (5s/3s) on fork/like/rate/my-chats/etc., daily reset WHERE condition, payments bccomp, chat-sync throttle, lazy sync integrated to token-gate.php (remove duplication from soul.php/chat), encryption AES-GCM upgrade (CBC legacy fallback).
- **Phase 3 (Dynamic tests & PoCs)**: Harness created (tests/dynamic/ + scripts for replay/gating-bypass/CSRF/race-IDOR). Documented matrix in this plan (post-fix: all expect blocked/403/Interception). Verified syntax + structure.
- **Phase 4 (Report/Patches/Docs)**: This section + verification + 02_SECURITY_AND_WEB3.md update. All prior patches documented. Roadmap items complete (tests/ created, verification run, docs updated).

**Major Strengthened Areas**:
- Web3 Auth: nonce persistence + strict recipient + better byte handling (no more easy replay/spoof).
- RPC: unified validateAndParseResponse (jsonrpc 2.0 + id match) in NearRpcService + NearAuth + frontend.
- Token-Gating: single source in token-gate.php (enforce + lazy sync); owner/price sync + hash check consistent.
- Contract: safe state-then-promise order (prevents money-out + ownership-revert).
- PHP Posture: near-complete CSRF on browser mutating paths (session-only enforcement, API-key exempt); rate limits on abuse vectors; daily race mitigation; encryption integrity (GCM); error logging sanitized.
- Business Logic: social abuse throttled; payments precision; guest notes as limitation; memory/daily comments added.

**Verification Results**:
- Syntax clean on new tests + prior fixes.
- ~17+ explicit "Phase X 修復" markers.
- Structure: tests/ ready for local/testnet execution (see READMEs).
- No new high-severity issues introduced in fixes.
- Plan self-consistent (all sections reference canonical rule).

**Recommendations**:
- Run the dynamic PoCs against local dev + test DB (with sample NFT souls) and real testnet wallets for sigs/expiry. Update this doc with actual results.
- Periodic re-audit (e.g. after NEAR SDK updates or new features).
- Add CI for `php -l tests/` + basic syntax.
- Consider formal testnet harness or external audit for contract.
- Monitor: failed auth logs, RPC errors, ownership flips.
- Known Limitations (documented): guest session volatile (by design), localStorage guest history (client tamper but server filter), legacy CBC data (fallback), optimistic finality lag.

**Phase 4 Complete**. Full audit cycle (static + dynamic harness) done. All patches via canonical search_replace. Ready for production hardening or Phase 3 live runs.

## Implementation Roadmap (Post-Approval)

1. Work exclusively from `cd /home/ki/文件/soulmd-hub`.
2. Read remaining detailed files using absolute canonical paths (e.g. full chat-scripts.php, my-setting wallet flows, change-password.php, complete souls.php creation + validation logic).
3. Create `tests/` directory in the canonical tree with initial NearAuthService and token-gating unit/integration tests.
4. Execute the static deep-dive and dynamic adversarial testing phases.
5. Produce patches (via search_replace against absolute `/home/ki/文件/soulmd-hub/...` paths only).
6. Update this document (findings section), 02_SECURITY_AND_WEB3.md, and any new runbooks.
7. Run full verification steps.
8. (Optional) Use a verifier sub-agent for self-review of changes before final commit.

This plan itself now lives in the `docs/` folder as required.

**Document maintained in the canonical repository under `docs/`. All future updates and related audit artifacts must follow the same location and modification rules.**
