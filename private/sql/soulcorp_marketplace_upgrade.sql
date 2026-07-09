-- SoulCorp marketplace upgrade (existing soulmd-hub databases)
-- Run AFTER soulcorp_marketplace.sql if tables already existed from an earlier deploy.
-- phpMyAdmin: Import this file into the same database.
-- Safe to re-run: ALTER is idempotent when enum already includes in_progress.

-- 1) Add in_progress to gigs.status (required for desktop start-gig flow)
ALTER TABLE gigs
  MODIFY status ENUM(
    'open','assigned','in_progress','in_qc','completed','disputed','cancelled'
  ) DEFAULT 'open';

-- 2) Tier backfill: sync PayPal / NEAR premium from users.tier into user_tiers
INSERT INTO user_tiers (user_id, tier, soul_balance, expires_at)
SELECT u.id, u.tier, 0, u.vip_expires_at
FROM users u
WHERE u.tier IN ('pro', 'vip')
  AND u.vip_expires_at > NOW()
  AND NOT EXISTS (SELECT 1 FROM user_tiers ut WHERE ut.user_id = u.id);

UPDATE user_tiers ut
JOIN users u ON u.id = ut.user_id
SET ut.tier = u.tier, ut.expires_at = u.vip_expires_at, ut.updated_at = NOW()
WHERE u.tier IN ('pro', 'vip')
  AND u.vip_expires_at > NOW()
  AND (
    ut.tier = 'free'
    OR (u.tier = 'vip' AND ut.tier = 'pro')
  );