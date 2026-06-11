# SoulMD Hub - Phase 3 Dynamic/Adversarial Testing Harness

## Structure
- `dynamic/`: Adversarial PoC scripts (replay, gating bypass, CSRF, races/IDOR)
- `integration/`: (Future) unit/integration for services (NearAuth, gate, etc.)

## How to Run
1. Set up local dev (LAMP + test DB from private/sql/init.sql with sample NFT souls + users).
2. Run scripts: `php tests/dynamic/replay_test.php http://localhost/api/wallet-login.php`
3. For real adversarial: 
   - Browser devtools to capture real payloads (generateNearAuthPayload).
   - Testnet NEAR wallets for sig forgery/expiry tests.
   - `ab` or GNU parallel for races.
4. Expected results post-fixes (see plan):
   - Replay: blocked by nonce table.
   - Gating bypass: hash/interception, access denied, owner-only on timeout.
   - CSRF: 403 on state-changing browser paths.
   - Races/IDOR: mitigated by DB conditions + ownership checks.

## Notes
- Scripts are self-documenting with comments on vectors.
- No live server assumed; adapt URLs/payloads.
- Update plan doc with actual run results.
- Add more PoCs as needed (e.g. payment replay, memory poison).

Run from canonical: cd /home/ki/文件/soulmd-hub
