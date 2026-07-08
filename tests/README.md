# SoulMD Hub - Security & Adversarial Testing

## Structure
- `dynamic/`: Adversarial PoC scripts (replay, gating bypass, CSRF, races/IDOR)
- `integration/`: Pure-function integration tests for gate logic and ApiSecurity CSRF helpers

## Quick Run (no server or DB required)
From the SoulCorp repo root:

```bash
bash scripts/run-hub-security-tests.sh
```

Or from `hub/soulmd-hub`:

```bash
php tests/integration/gate_integration_test.php
php tests/integration/api_security_test.php
php tests/dynamic/replay_test.php
```

Integration tests use `config.example.php` when `private/config.php` is absent.

## Dynamic / Adversarial (optional live server)
1. Set up local dev (LAMP + test DB from `private/sql/init.sql` with sample NFT souls + users).
2. Run scripts: `php tests/dynamic/replay_test.php http://localhost/api/wallet-login.php`
3. For real adversarial:
   - Browser devtools to capture real payloads (`generateNearAuthPayload`).
   - Testnet NEAR wallets for sig forgery/expiry tests.
   - `ab` or GNU parallel for races.
4. Expected results post-fixes:
   - Replay: blocked by nonce table.
   - Gating bypass: hash/interception, access denied, owner-only on timeout.
   - CSRF: 403 on state-changing browser paths.
   - Races/IDOR: mitigated by DB conditions + ownership checks.

## Notes
- Integration helpers live in `integration/helpers/test_runner.php` (no PHPUnit).
- Dynamic scripts are self-documenting with comments on attack vectors.
- Update plan docs with actual run results when exercising live endpoints.