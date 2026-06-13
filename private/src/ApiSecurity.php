<?php
/**
 * SoulMD Hub - Centralized API Security & Access Control
 * 
 * All API endpoints should call ApiSecurity::initialize() at the very beginning
 * (after setting their specific CORS headers if needed, before business logic).
 * 
 * Benefits:
 * - Single place for auth (api_key vs session)
 * - CSRF is skipped for valid api_key calls (as requested)
 * - Hard rate limit: max 10 calls per minute PER api_key
 * - Central point to add future cross-cutting concerns (logging, quotas, etc.)
 * - Removes massive duplication of getAuthUserId + CSRF boilerplate
 */

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/Database.php';

class ApiSecurity {
    public const RATE_LIMIT_PER_MINUTE = 10;

    /**
     * Main entry point. Call this early in every api/*.php
     *
     * @param bool $requireUser  Whether to force a valid user (most endpoints true)
     * @return array  ['user_id' => int|null, 'is_api_key' => bool, 'pdo' => PDO, 'api_key' => string|null]
     *                Exits with JSON error (401/403/429) on any failure.
     */
    public static function initialize(bool $requireUser = true): array {
        // Always ensure JSON content type for APIs (files can override before calling if they want)
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        // Ensure session is available for browser paths (also ensures CSRF token exists)
        self::ensureCsrfToken();

        $db = Database::getInstance();
        $pdo = $db->getConnection();

        // Load common api translations (safe to call multiple times)
        if (function_exists('loadTranslations')) {
            loadTranslations('api');
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $apiKey = trim(str_replace('Bearer ', '', $authHeader));

        $userId = null;
        $isApiKey = false;
        $rawApiKey = null;

        if (!empty($apiKey)) {
            // === API KEY PATH (headless / external tools) ===
            $isApiKey = true;
            $rawApiKey = $apiKey;

            $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ? LIMIT 1");
            $stmt->execute([$apiKey]);
            $row = $stmt->fetch();

            if (!$row) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid or unknown API key']);
                exit;
            }

            $userId = (int)$row['id'];

            // Enforce per-key rate limit (10/min)
            self::enforceRateLimit($pdo, $apiKey);

            // IMPORTANT: Completely skip CSRF for valid api_key calls
            // (CSRF is only for browser/session without api key)
        } else {
            // === BROWSER SESSION PATH ===
            if (isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
            }

            // CSRF enforcement only for non-GET mutating requests in session mode
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
                self::enforceCsrfCheck();
            }
        }

        if ($requireUser && empty($userId)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Please provide a valid API key (Authorization: Bearer ...) or log in.']);
            exit;
        }

        return [
            'user_id'    => $userId,
            'is_api_key' => $isApiKey,
            'pdo'        => $pdo,
            'api_key'    => $rawApiKey,
        ];
    }

    /**
     * Global helper to ensure the chat_csrf_token exists in session.
     * Replaces all the repeated:
     *   if (empty($_SESSION['chat_csrf_token'])) {
     *       $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32));
     *   }
     *   $csrfToken = $_SESSION['chat_csrf_token'];
     *
     * Call this early in page/API that needs to pass the token to JS
     * or for CSRF-protected browser mutating calls.
     */
    public static function ensureCsrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['chat_csrf_token'])) {
            $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['chat_csrf_token'];
    }

    /**
     * CSRF check (only called for session/browser mutating calls)
     */
    private static function enforceCsrfCheck(): void {
        $serverCsrfToken = self::ensureCsrfToken();

        $userCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (empty($userCsrfToken) && function_exists('getallheaders')) {
            $headers = getallheaders();
            $userCsrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
        }

        if (empty($serverCsrfToken) || empty($userCsrfToken) || !hash_equals($serverCsrfToken, $userCsrfToken)) {
            http_response_code(403);
            $msg = function_exists('__') ? __('Security validation failed') : 'Security validation failed. Direct access blocked.';
            echo json_encode(['success' => false, 'error' => $msg]);
            exit;
        }
    }

    /**
     * Rate limiting: max 10 calls per minute per individual api_key.
     * Uses a lightweight bucket table (created on first use).
     */
    private static function enforceRateLimit(PDO $pdo, string $apiKey): void {
        $keyHash = hash('sha256', $apiKey);
        $currentWindow = (int)(time() / 60);   // minute bucket

        // Create tracking table if it doesn't exist (one-time, safe)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_rate_limits (
                api_key_hash   VARCHAR(64) PRIMARY KEY,
                window_minute  INT UNSIGNED NOT NULL,
                call_count     TINYINT UNSIGNED NOT NULL DEFAULT 1,
                updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (window_minute)
            ) ENGINE=InnoDB
        ");

        // Atomic increment or reset on new minute window
        $stmt = $pdo->prepare("
            INSERT INTO api_rate_limits (api_key_hash, window_minute, call_count)
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE
                call_count = IF(window_minute = VALUES(window_minute), call_count + 1, 1),
                window_minute = VALUES(window_minute)
        ");
        $stmt->execute([$keyHash, $currentWindow]);

        // Check current count for this window
        $stmt = $pdo->prepare("
            SELECT call_count FROM api_rate_limits 
            WHERE api_key_hash = ? AND window_minute = ?
        ");
        $stmt->execute([$keyHash, $currentWindow]);
        $count = (int)$stmt->fetchColumn();

        if ($count > self::RATE_LIMIT_PER_MINUTE) {
            http_response_code(429);
            echo json_encode([
                'success'     => false,
                'error'       => 'Rate limit exceeded. Each API key is limited to 10 requests per minute.',
                'retry_after' => 60 - (time() % 60)
            ]);
            exit;
        }
    }

    /**
     * Optional helper: regenerate a new api_key for a user (used by /api/regenerate-key)
     */
    public static function regenerateApiKey(PDO $pdo, int $userId): string {
        $newKey = bin2hex(random_bytes(24)); // 48 char key
        $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$newKey, $userId]);
        return $newKey;
    }

    /**
     * Example of centralizing another repeated operation (tag usage counting).
     * Call ApiSecurity::incrementTagUsage($pdo, 'tags_domain', $domain);
     */
    public static function incrementTagUsage(PDO $pdo, string $table, string $tagsString): void {
        $tags = array_filter(array_map('trim', explode(',', $tagsString)));
        foreach ($tags as $tag) {
            if (empty($tag)) continue;
            $pdo->prepare("INSERT INTO {$table} (name, usage_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1")->execute([$tag]);
        }
    }
}

/**
 * Convenience global function so you can just call ensureCsrfToken() anywhere
 * (after requiring ApiSecurity). This eliminates all the repeated
 * $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32)) blocks.
 */
if (!function_exists('ensureCsrfToken')) {
    function ensureCsrfToken(): string {
        return ApiSecurity::ensureCsrfToken();
    }
}
