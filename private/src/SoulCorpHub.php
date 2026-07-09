<?php

class SoulCorpHub
{
    public const EXECUTIVE_LOUNGE_BUDGET_USDT = 5000.0;

    public static function executiveLoungeForBudget(float $budgetUsdt): bool
    {
        return $budgetUsdt >= self::EXECUTIVE_LOUNGE_BUDGET_USDT;
    }

    public static function ensureTables(PDO $pdo): void
    {
        $sqlPath = __DIR__ . '/../sql/20260630_soulcorp_marketplace.sql';
        if (!file_exists($sqlPath)) {
            return;
        }

        $sql = file_get_contents($sqlPath);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                error_log('SoulCorpHub migration warning: ' . $e->getMessage());
            }
        }

        self::ensureSchemaUpgrades($pdo);
    }

    private static function ensureSchemaUpgrades(PDO $pdo): void
    {
        try {
            $pdo->exec(
                "ALTER TABLE gigs MODIFY status ENUM('open','assigned','in_progress','in_qc','completed','disputed','cancelled') DEFAULT 'open'"
            );
        } catch (PDOException $e) {
            error_log('SoulCorpHub schema upgrade (gigs.status): ' . $e->getMessage());
        }
    }

    /** @return array<string, int> */
    private static function tierRankMap(): array
    {
        return ['free' => 1, 'pro' => 2, 'vip' => 3];
    }

    private static function tierRank(string $tier): int
    {
        return self::tierRankMap()[strtolower($tier)] ?? 1;
    }

    /**
     * Mirror account-level premium (users.tier) into marketplace user_tiers.
     *
     * @param array<string, mixed> $tierRow
     * @return array<string, mixed>
     */
    private static function mergeAccountTierIntoRow(PDO $pdo, int $userId, array $tierRow): array
    {
        $accountStmt = $pdo->prepare('SELECT tier, vip_expires_at FROM users WHERE id = ? LIMIT 1');
        $accountStmt->execute([$userId]);
        $account = $accountStmt->fetch();
        if (!$account) {
            return $tierRow;
        }

        $accountTier = strtolower((string)($account['tier'] ?? 'free'));
        $expiresAt = $account['vip_expires_at'] ?? null;
        $isActivePremium = $accountTier !== 'free'
            && $expiresAt
            && strtotime((string)$expiresAt) > time();

        $marketplaceTier = strtolower((string)($tierRow['tier'] ?? 'free'));

        if ($isActivePremium && self::tierRank($accountTier) >= self::tierRank($marketplaceTier)) {
            $stmt = $pdo->prepare(
                'UPDATE user_tiers SET tier = ?, expires_at = ?, updated_at = NOW() WHERE user_id = ?'
            );
            $stmt->execute([$accountTier, $expiresAt, $userId]);
            $tierRow['tier'] = $accountTier;
            $tierRow['expires_at'] = $expiresAt;
        }

        return $tierRow;
    }

    public static function syncAccountTierToUserTiers(PDO $pdo, int $userId): array
    {
        self::ensureTables($pdo);
        $stmt = $pdo->prepare('SELECT tier, soul_staked, soul_balance, expires_at FROM user_tiers WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $tierRow = $stmt->fetch();
        if (!$tierRow) {
            $pdo->prepare('INSERT INTO user_tiers (user_id, tier, soul_balance) VALUES (?, "free", 0)')->execute([$userId]);
            $tierRow = ['tier' => 'free', 'soul_staked' => 0, 'soul_balance' => 0, 'expires_at' => null];
        }

        return self::mergeAccountTierIntoRow($pdo, $userId, $tierRow);
    }

    /**
     * Called after PayPal / NEAR entitlement updates users.tier.
     */
    public static function applyAccountTier(PDO $pdo, int $userId, string $tier, ?string $expiresAt): void
    {
        self::ensureTables($pdo);
        self::getUserTier($pdo, $userId);

        $stmt = $pdo->prepare(
            'UPDATE user_tiers SET tier = ?, expires_at = ?, updated_at = NOW() WHERE user_id = ?'
        );
        $stmt->execute([strtolower($tier), $expiresAt, $userId]);
    }

    public static function createGig(PDO $pdo, int $userId, array $input): array
    {
        self::ensureTables($pdo);

        $title = trim((string)($input['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Title is required.');
        }

        $skills = $input['required_skills'] ?? [];
        if (!is_array($skills)) {
            $skills = [];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO gigs (poster_user_id, title, description, budget_usdt, required_skills, deadline, status)
             VALUES (?, ?, ?, ?, ?, ?, "open")'
        );
        $stmt->execute([
            $userId,
            $title,
            (string)($input['description'] ?? ''),
            (float)($input['budget_usdt'] ?? 0),
            json_encode($skills, JSON_UNESCAPED_UNICODE),
            !empty($input['deadline']) ? date('Y-m-d H:i:s', strtotime((string)$input['deadline'])) : null,
        ]);

        return [
            'gig_id' => (int)$pdo->lastInsertId(),
            'status' => 'open',
        ];
    }

    public static function listGigs(PDO $pdo, string $status = 'open'): array
    {
        self::ensureTables($pdo);
        $stmt = $pdo->prepare(
            'SELECT id, poster_user_id, title, description, budget_usdt, status, required_skills, deadline, created_at
             FROM gigs WHERE status = ? ORDER BY created_at DESC LIMIT 100'
        );
        $stmt->execute([$status]);
        $rows = $stmt->fetchAll();

        return array_map(static function (array $row): array {
            $row['required_skills'] = json_decode($row['required_skills'] ?? '[]', true) ?: [];
            $row['gig_id'] = (int)$row['id'];
            $row['executive_lounge'] = self::executiveLoungeForBudget((float)($row['budget_usdt'] ?? 0));
            unset($row['id']);
            return $row;
        }, $rows);
    }

    public static function assignGig(PDO $pdo, int $userId, int $gigId): array
    {
        self::ensureTables($pdo);
        $stmt = $pdo->prepare('SELECT id, status FROM gigs WHERE id = ? LIMIT 1');
        $stmt->execute([$gigId]);
        $gig = $stmt->fetch();
        if (!$gig) {
            throw new RuntimeException('Gig not found.', 404);
        }
        if ($gig['status'] !== 'open') {
            throw new RuntimeException('Gig already assigned.', 4002);
        }

        $pdo->prepare('UPDATE gigs SET status = "assigned" WHERE id = ?')->execute([$gigId]);
        $pdo->prepare(
            'INSERT INTO gig_assignments (gig_id, assignee_user_id, status) VALUES (?, ?, "assigned")'
        )->execute([$gigId, $userId]);

        return ['gig_id' => $gigId, 'status' => 'assigned'];
    }

    public static function startGig(PDO $pdo, int $userId, int $gigId): array
    {
        self::ensureTables($pdo);

        $stmt = $pdo->prepare(
            'SELECT g.id, g.status, ga.id AS assignment_id, ga.status AS assignment_status
             FROM gigs g
             INNER JOIN gig_assignments ga ON ga.gig_id = g.id
             WHERE g.id = ? AND ga.assignee_user_id = ?
             ORDER BY ga.id DESC
             LIMIT 1'
        );
        $stmt->execute([$gigId, $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Gig assignment not found.', 404);
        }
        if (!in_array($row['status'], ['assigned', 'in_progress'], true)) {
            throw new RuntimeException('Gig is not ready to start.', 4003);
        }
        if (!in_array($row['assignment_status'], ['assigned', 'qc_rejected'], true)) {
            throw new RuntimeException('Assignment already in progress.', 4004);
        }

        $pdo->prepare('UPDATE gigs SET status = "in_progress" WHERE id = ?')->execute([$gigId]);
        $pdo->prepare(
            'UPDATE gig_assignments SET status = "assigned" WHERE id = ?'
        )->execute([(int)$row['assignment_id']]);

        return ['gig_id' => $gigId, 'status' => 'in_progress'];
    }

    public static function submitGigForQc(PDO $pdo, int $userId, int $gigId, array $input = []): array
    {
        self::ensureTables($pdo);

        $stmt = $pdo->prepare(
            'SELECT g.id, g.status, ga.id AS assignment_id, ga.status AS assignment_status
             FROM gigs g
             INNER JOIN gig_assignments ga ON ga.gig_id = g.id
             WHERE g.id = ? AND ga.assignee_user_id = ?
             ORDER BY ga.id DESC
             LIMIT 1'
        );
        $stmt->execute([$gigId, $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Gig assignment not found.', 404);
        }
        if (!in_array($row['status'], ['assigned', 'in_progress'], true)) {
            throw new RuntimeException('Gig must be in progress before QC submission.', 4006);
        }
        if (!in_array($row['assignment_status'], ['assigned', 'qc_rejected'], true)) {
            throw new RuntimeException('Assignment is not ready for QC submission.', 4007);
        }

        $qcScore = $input['qc_score'] ?? ['overall' => 0.85];
        if (!is_array($qcScore)) {
            $qcScore = ['overall' => 0.85];
        }
        $deliverableUrl = trim((string)($input['deliverable_url'] ?? ''));

        $pdo->prepare('UPDATE gigs SET status = "in_qc" WHERE id = ?')->execute([$gigId]);
        $assignmentStmt = $pdo->prepare(
            'UPDATE gig_assignments
             SET status = "submitted", qc_score = ?, deliverable_url = ?
             WHERE id = ?'
        );
        $assignmentStmt->execute([
            json_encode($qcScore, JSON_UNESCAPED_UNICODE),
            $deliverableUrl !== '' ? $deliverableUrl : null,
            (int)$row['assignment_id'],
        ]);

        return [
            'gig_id' => $gigId,
            'status' => 'in_qc',
            'qc_score' => $qcScore,
            'deliverable_url' => $deliverableUrl !== '' ? $deliverableUrl : null,
        ];
    }

    public static function rejectGigQc(PDO $pdo, int $userId, int $gigId, array $input = []): array
    {
        self::ensureTables($pdo);

        $stmt = $pdo->prepare(
            'SELECT g.id, g.status, ga.id AS assignment_id, ga.status AS assignment_status
             FROM gigs g
             INNER JOIN gig_assignments ga ON ga.gig_id = g.id
             WHERE g.id = ? AND ga.assignee_user_id = ?
             ORDER BY ga.id DESC
             LIMIT 1'
        );
        $stmt->execute([$gigId, $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Gig assignment not found.', 404);
        }
        if ($row['status'] !== 'in_qc') {
            throw new RuntimeException('Gig is not awaiting QC.', 4008);
        }
        if ($row['assignment_status'] !== 'submitted') {
            throw new RuntimeException('Assignment has not been submitted for QC.', 4009);
        }

        $pdo->prepare('UPDATE gigs SET status = "in_progress" WHERE id = ?')->execute([$gigId]);
        $pdo->prepare(
            'UPDATE gig_assignments SET status = "qc_rejected" WHERE id = ?'
        )->execute([(int)$row['assignment_id']]);

        return [
            'gig_id' => $gigId,
            'status' => 'in_progress',
            'qc_notes' => trim((string)($input['qc_notes'] ?? 'Revision requested.')),
        ];
    }

    public static function disputeGig(PDO $pdo, int $userId, int $gigId, array $input = []): array
    {
        self::ensureTables($pdo);

        $stmt = $pdo->prepare(
            'SELECT g.id, g.status, ga.id AS assignment_id, ga.status AS assignment_status
             FROM gigs g
             INNER JOIN gig_assignments ga ON ga.gig_id = g.id
             WHERE g.id = ? AND ga.assignee_user_id = ?
             ORDER BY ga.id DESC
             LIMIT 1'
        );
        $stmt->execute([$gigId, $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Gig assignment not found.', 404);
        }
        if (!in_array($row['status'], ['assigned', 'in_progress', 'in_qc'], true)) {
            throw new RuntimeException('Gig cannot be disputed from current status.', 4010);
        }

        $pdo->prepare('UPDATE gigs SET status = "disputed" WHERE id = ?')->execute([$gigId]);
        $pdo->prepare(
            'UPDATE gig_assignments SET status = "qc_rejected" WHERE id = ?'
        )->execute([(int)$row['assignment_id']]);

        return [
            'gig_id' => $gigId,
            'status' => 'disputed',
            'qc_notes' => trim((string)($input['qc_notes'] ?? 'Dispute opened.')),
        ];
    }

    public static function cancelGig(PDO $pdo, int $userId, int $gigId): array
    {
        self::ensureTables($pdo);

        $stmt = $pdo->prepare('SELECT id, poster_user_id, status FROM gigs WHERE id = ? LIMIT 1');
        $stmt->execute([$gigId]);
        $gig = $stmt->fetch();
        if (!$gig) {
            throw new RuntimeException('Gig not found.', 404);
        }
        if ((int)$gig['poster_user_id'] !== $userId) {
            throw new RuntimeException('Only the gig poster can cancel this listing.', 4012);
        }
        if ($gig['status'] !== 'open') {
            throw new RuntimeException('Only open gigs can be cancelled.', 4013);
        }

        $pdo->prepare('UPDATE gigs SET status = "cancelled" WHERE id = ?')->execute([$gigId]);

        return ['gig_id' => $gigId, 'status' => 'cancelled'];
    }

    public static function completeGig(PDO $pdo, int $userId, int $gigId): array
    {
        self::ensureTables($pdo);

        $stmt = $pdo->prepare(
            'SELECT g.id, g.status, g.budget_usdt, g.poster_user_id, ga.id AS assignment_id, ga.status AS assignment_status
             FROM gigs g
             INNER JOIN gig_assignments ga ON ga.gig_id = g.id
             WHERE g.id = ? AND ga.assignee_user_id = ?
             ORDER BY ga.id DESC
             LIMIT 1'
        );
        $stmt->execute([$gigId, $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Gig assignment not found.', 404);
        }
        if ($row['status'] !== 'in_qc') {
            throw new RuntimeException('Gig must pass QC before payout.', 4005);
        }
        if ($row['assignment_status'] !== 'submitted') {
            throw new RuntimeException('Assignment has not been submitted for QC.', 4011);
        }

        $budget = (float)$row['budget_usdt'];
        $tier = self::getUserTier($pdo, $userId);
        $feePercent = self::platformFeePercent((string)($tier['tier'] ?? 'free'));
        $feeUsdt = round($budget * ($feePercent / 100), 8);
        $payoutUsdt = round($budget - $feeUsdt, 8);

        $pdo->prepare('UPDATE gigs SET status = "completed" WHERE id = ?')->execute([$gigId]);
        $pdo->prepare(
            'UPDATE gig_assignments SET status = "qc_passed" WHERE id = ?'
        )->execute([(int)$row['assignment_id']]);
        $pdo->prepare(
            'INSERT INTO platform_transactions (gig_id, from_user_id, to_user_id, amount_usdt, fee_usdt)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $gigId,
            (int)$row['poster_user_id'],
            $userId,
            $payoutUsdt,
            $feeUsdt,
        ]);

        return [
            'gig_id' => $gigId,
            'status' => 'completed',
            'payout_usdt' => $payoutUsdt,
            'fee_usdt' => $feeUsdt,
            'platform_fee_percent' => $feePercent,
        ];
    }

    private static function platformFeePercent(string $tier): float
    {
        return match (strtolower($tier)) {
            'pro' => 8.0,
            'vip' => 5.0,
            default => 10.0,
        };
    }

    public static function getUserTier(PDO $pdo, int $userId): array
    {
        return self::syncAccountTierToUserTiers($pdo, $userId);
    }

    /**
     * @param list<array<string, mixed>> $queue
     * @return array{processed: int, errors: list<string>}
     */
    public static function processSyncQueue(PDO $pdo, int $userId, array $queue): array
    {
        $processed = 0;
        $errors = [];

        foreach ($queue as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = (string)($item['type'] ?? '');
            if ($type === '' && isset($item['title'])) {
                $type = 'gig_create';
            }

            try {
                match ($type) {
                    'gig_create' => self::createGig($pdo, $userId, $item),
                    'gig_assign' => self::assignGig($pdo, $userId, (int)($item['gig_id'] ?? 0)),
                    'gig_start' => self::startGig($pdo, $userId, (int)($item['gig_id'] ?? 0)),
                    'gig_qc_submit' => self::submitGigForQc($pdo, $userId, (int)($item['gig_id'] ?? 0), $item),
                    'gig_complete' => self::completeGig($pdo, $userId, (int)($item['gig_id'] ?? 0)),
                    'gig_reject_qc' => self::rejectGigQc($pdo, $userId, (int)($item['gig_id'] ?? 0), $item),
                    'gig_dispute' => self::disputeGig($pdo, $userId, (int)($item['gig_id'] ?? 0), $item),
                    default => throw new InvalidArgumentException("Unsupported sync queue item: {$type}"),
                };
                $processed++;
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['processed' => $processed, 'errors' => $errors];
    }

    public static function pushSync(PDO $pdo, int $userId, array $payload): array
    {
        self::ensureTables($pdo);

        $queue = $payload['queue'] ?? [];
        if (!is_array($queue)) {
            $queue = [];
        }

        $result = ['processed' => 0, 'errors' => []];
        if ($queue !== []) {
            $result = self::processSyncQueue($pdo, $userId, $queue);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO sync_logs (user_id, direction, payload_json) VALUES (?, "push", ?)'
        );
        $stmt->execute([$userId, json_encode($payload, JSON_UNESCAPED_UNICODE)]);

        return [
            'accepted' => true,
            'sync_id' => (int)$pdo->lastInsertId(),
            'processed' => $result['processed'],
            'errors' => $result['errors'],
        ];
    }

    public static function pullSync(PDO $pdo, int $userId): array
    {
        $tier = self::getUserTier($pdo, $userId);
        $gigs = self::listGigs($pdo, 'open');

        return [
            'tier' => $tier,
            'open_gigs' => $gigs,
            'soul_balance' => (float)($tier['soul_balance'] ?? 0),
        ];
    }
}