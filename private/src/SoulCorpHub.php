<?php

class SoulCorpHub
{
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

    public static function getUserTier(PDO $pdo, int $userId): array
    {
        self::ensureTables($pdo);
        $stmt = $pdo->prepare('SELECT tier, soul_staked, soul_balance, expires_at FROM user_tiers WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $tier = $stmt->fetch();

        if (!$tier) {
            $pdo->prepare('INSERT INTO user_tiers (user_id, tier, soul_balance) VALUES (?, "free", 0)')->execute([$userId]);
            return ['tier' => 'free', 'soul_staked' => 0, 'soul_balance' => 0, 'expires_at' => null];
        }

        return $tier;
    }

    public static function pushSync(PDO $pdo, int $userId, array $payload): array
    {
        $tier = self::getUserTier($pdo, $userId);
        if (!in_array($tier['tier'], ['pro', 'vip'], true)) {
            throw new RuntimeException('Insufficient tier for sync push.', 4001);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO sync_logs (user_id, direction, payload_json) VALUES (?, "push", ?)'
        );
        $stmt->execute([$userId, json_encode($payload, JSON_UNESCAPED_UNICODE)]);

        return ['accepted' => true, 'sync_id' => (int)$pdo->lastInsertId()];
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