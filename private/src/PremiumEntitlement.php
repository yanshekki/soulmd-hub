<?php
/**
 * Shared premium tier entitlement for PayPal + NEAR upgrade payments.
 *
 * Single source of truth for:
 * - 30-day purchase period
 * - Stacking: max(current_expiry, now) + 30d
 * - VIP→PRO while VIP active: prorate remaining VIP $ into PRO days + 30d PRO
 * - Block PRO→VIP while PRO is still active
 * - DB write: payments + users.tier/vip_expires_at + user_tiers
 */
class PremiumEntitlement
{
    /** Seconds granted per successful purchase (30 days). */
    public const PERIOD_SECONDS = 30 * 24 * 60 * 60;

    /**
     * PRO active user must not buy VIP.
     */
    public static function isDowngradeBlocked(
        string $currentTier,
        int $currentExpiryTs,
        string $purchasedTier,
        ?int $now = null
    ): bool {
        $now = $now ?? time();
        $currentTier = strtolower($currentTier);
        $purchasedTier = strtolower($purchasedTier);
        $isActivePremium = ($currentTier !== 'free' && $currentExpiryTs > $now);
        return $isActivePremium && $currentTier === 'pro' && $purchasedTier === 'vip';
    }

    /**
     * Compute unix expiry after a completed purchase.
     * Call only after isDowngradeBlocked() is false.
     */
    public static function computeNewExpiry(
        string $currentTier,
        int $currentExpiryTs,
        string $purchasedTier,
        ?int $now = null
    ): int {
        $now = $now ?? time();
        $currentTier = strtolower($currentTier);
        $purchasedTier = strtolower($purchasedTier);
        $purchasedSeconds = self::PERIOD_SECONDS;

        // VIP → PRO while VIP still active: convert remaining VIP value to PRO days
        if ($currentTier === 'vip' && $purchasedTier === 'pro' && $currentExpiryTs > $now) {
            $remainingVipSeconds = $currentExpiryTs - $now;
            $vipMonthly = (float)PRICE_VIP_MONTHLY;
            $proMonthly = (float)PRICE_PRO_MONTHLY;
            if ($proMonthly <= 0) {
                $proMonthly = 1.0;
            }
            $conversionRatio = ($vipMonthly / 30) / ($proMonthly / 30);
            $convertedProSeconds = $remainingVipSeconds * $conversionRatio;
            return (int)($now + $purchasedSeconds + $convertedProSeconds);
        }

        // Stack same tier / free→paid / expired premium
        return (int)(max($currentExpiryTs, $now) + $purchasedSeconds);
    }

    /**
     * Apply a verified COMPLETED payment inside a DB transaction.
     *
     * @param string $paymentRef Unique payments.paypal_order_id (PayPal order id or near-ft:…)
     * @param string $amount     Decimal string e.g. "4.99"
     * @param string $currency   e.g. USD, USDT, USDC
     * @param bool   $duplicateIsSuccess  PayPal treats re-claim as success; NEAR as already-claimed error
     * @return array{
     *   ok: bool,
     *   already?: bool,
     *   downgrade?: bool,
     *   new_tier?: string,
     *   expires_at?: string,
     *   user_missing?: bool
     * }
     */
    public static function applyPurchase(
        PDO $pdo,
        int $userId,
        string $purchasedTier,
        string $paymentRef,
        string $amount,
        string $currency,
        bool $duplicateIsSuccess = true
    ): array {
        $purchasedTier = strtolower(trim($purchasedTier));
        if (!in_array($purchasedTier, ['vip', 'pro'], true)) {
            throw new InvalidArgumentException('Invalid purchased tier');
        }
        $paymentRef = trim($paymentRef);
        if ($paymentRef === '') {
            throw new InvalidArgumentException('Empty payment ref');
        }

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) {
            $pdo->beginTransaction();
        }

        try {
            $uStmt = $pdo->prepare('SELECT tier, vip_expires_at FROM users WHERE id = ? FOR UPDATE');
            $uStmt->execute([$userId]);
            $user = $uStmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                if ($ownTx) {
                    $pdo->rollBack();
                }
                return ['ok' => false, 'user_missing' => true];
            }

            $currentTier = (string)($user['tier'] ?? 'free');
            $currentExpiryTs = !empty($user['vip_expires_at']) ? (int)strtotime($user['vip_expires_at']) : 0;
            $now = time();

            if (self::isDowngradeBlocked($currentTier, $currentExpiryTs, $purchasedTier, $now)) {
                if ($ownTx) {
                    $pdo->rollBack();
                }
                return ['ok' => false, 'downgrade' => true];
            }

            // Idempotency under row lock (UNIQUE paypal_order_id; gap lock helps under RR)
            $check = $pdo->prepare('SELECT id, tier_purchased, status FROM payments WHERE paypal_order_id = ? LIMIT 1 FOR UPDATE');
            $check->execute([$paymentRef]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                if ($ownTx) {
                    $pdo->commit();
                }
                return [
                    'ok' => $duplicateIsSuccess,
                    'already' => true,
                    'new_tier' => (string)($existing['tier_purchased'] ?? $purchasedTier),
                ];
            }

            $newExpiryTs = self::computeNewExpiry($currentTier, $currentExpiryTs, $purchasedTier, $now);
            if ($newExpiryTs <= $now) {
                // Defensive: never write a non-future expiry from a completed purchase
                $newExpiryTs = $now + self::PERIOD_SECONDS;
            }
            $newExpiryStr = date('Y-m-d H:i:s', $newExpiryTs);

            $ins = $pdo->prepare(
                'INSERT INTO payments (user_id, paypal_order_id, amount, currency, tier_purchased, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $userId,
                $paymentRef,
                $amount,
                strtoupper($currency),
                $purchasedTier,
                'COMPLETED',
            ]);

            $pdo->prepare('UPDATE users SET tier = ?, vip_expires_at = ? WHERE id = ?')
                ->execute([$purchasedTier, $newExpiryStr, $userId]);

            if (!class_exists('SoulCorpHub')) {
                require_once __DIR__ . '/SoulCorpHub.php';
            }
            SoulCorpHub::applyAccountTier($pdo, $userId, $purchasedTier, $newExpiryStr);

            if ($ownTx) {
                $pdo->commit();
            }

            return [
                'ok' => true,
                'new_tier' => $purchasedTier,
                'expires_at' => $newExpiryStr,
            ];
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function isDuplicateKeyException(Throwable $e): bool
    {
        $errStr = (string)$e;
        return strpos($errStr, 'Duplicate') !== false
            || strpos($errStr, '1062') !== false
            || strpos($errStr, 'Integrity constraint violation') !== false;
    }
}
