<?php
/**
 * SoulMD Hub - Unified application bootstrap
 *
 * Single place for: config load, safe session start, page vs API setup.
 * New entry points should call forPage() or forApi() instead of hand-rolling
 * require_once + session_start + loadTranslations + CSRF.
 *
 * Hard rules:
 * - sessionStart() only when session is idle AND headers not sent (SSE-safe)
 * - forPage() never forces Content-Type: application/json
 * - forApi() uses ApiSecurity::initialize() for auth/CSRF/rate-limit
 * - After LlmStreamProxy::beginSse(): never session_start / setcookie
 */

class AppBootstrap
{
    /** @var bool */
    private static $configLoaded = false;

    /**
     * Load private/config.php (or example fallback) and assert DB + i18n helpers.
     * Safe to call multiple times.
     *
     * @param bool $jsonErrors  If true, misconfig exits as JSON (API paths)
     */
    public static function loadConfig(bool $jsonErrors = false): void
    {
        // Already bootstrapped in this request (also covers path-variant double includes)
        if (defined('DB_HOST') && defined('APP_ENCRYPTION_KEY') && function_exists('loadTranslations')) {
            self::$configLoaded = true;
            return;
        }
        if (self::$configLoaded && defined('DB_HOST') && function_exists('loadTranslations')) {
            return;
        }

        $configPath = __DIR__ . '/../config.php';
        if (!is_file($configPath)) {
            $configPath = __DIR__ . '/../config.example.php';
        }
        if (!is_file($configPath)) {
            self::fail(500, 'Server misconfigured: private/config.php missing', $jsonErrors);
        }

        // Prefer realpath so require_once treats symlink/path variants as one file
        $resolved = realpath($configPath) ?: $configPath;
        require_once $resolved;

        if (!defined('DB_HOST')) {
            self::fail(500, 'Server misconfigured: private/config.php missing DB constants', $jsonErrors);
        }
        if (!function_exists('loadTranslations')) {
            self::fail(500, 'Server misconfigured: loadTranslations() missing from config', $jsonErrors);
        }

        self::$configLoaded = true;
    }

    /**
     * Safe session start. No-op if already active or headers already sent.
     * Prefer this over raw session_start() everywhere.
     */
    public static function sessionStart(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        if (session_status() !== PHP_SESSION_NONE) {
            return false;
        }
        if (headers_sent()) {
            return false;
        }
        return session_start();
    }

    /**
     * Close session early (e.g. before long SSE) so other requests are not blocked.
     */
    public static function sessionClose(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /**
     * Whether session is writable (active and headers not flushed).
     */
    public static function sessionWritable(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE && !headers_sent();
    }

    /**
     * HTML / SSR page bootstrap. Never sets application/json.
     *
     * Options:
     * - translations: string|list of language pack names
     * - csrf: bool (default false) — ApiSecurity::ensureCsrfToken, no JSON Content-Type
     * - db: bool (default false)
     * - require_login: bool (default false) — redirect to /login
     * - seo: bool (default false) — require includes/seo.php
     *
     * @param array<string, mixed> $opts
     * @return array{pdo: ?PDO, csrf: ?string, user_id: ?int, username: ?string}
     */
    public static function forPage(array $opts = []): array
    {
        self::loadConfig(false);

        if (!empty($opts['seo'])) {
            require_once __DIR__ . '/../includes/seo.php';
        }

        require_once __DIR__ . '/Database.php';

        self::sessionStart();

        $translations = $opts['translations'] ?? null;
        if ($translations !== null) {
            self::loadTranslationPacks($translations);
        }

        $csrf = null;
        if (!empty($opts['csrf'])) {
            require_once __DIR__ . '/ApiSecurity.php';
            $csrf = ApiSecurity::ensureCsrfToken();
        }

        $pdo = null;
        if (!empty($opts['db'])) {
            $pdo = Database::getInstance()->getConnection();
        }

        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $username = isset($_SESSION['username']) ? (string)$_SESSION['username'] : null;

        if (!empty($opts['require_login']) && !$userId) {
            $login = function_exists('url') ? url('/login') : '/login';
            if (!headers_sent()) {
                header('Location: ' . $login);
            }
            exit;
        }

        return [
            'pdo' => $pdo,
            'csrf' => $csrf,
            'user_id' => $userId,
            'username' => $username,
        ];
    }

    /**
     * JSON API bootstrap via ApiSecurity::initialize.
     *
     * Options:
     * - require_user: bool (default true)
     * - translations: string|list (default 'api')
     * - json_header: bool (default true) — set Content-Type if not sent
     *
     * Callers that need custom CORS should send those headers BEFORE forApi().
     *
     * @param array<string, mixed> $opts
     * @return array{user_id: ?int, is_api_key: bool, pdo: PDO, api_key: ?string, csrf: string}
     */
    public static function forApi(array $opts = []): array
    {
        self::loadConfig(true);

        if (!empty($opts['json_header']) || !array_key_exists('json_header', $opts)) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
        }

        require_once __DIR__ . '/Database.php';
        require_once __DIR__ . '/ApiSecurity.php';

        if (!class_exists('ApiSecurity')) {
            self::fail(500, 'ApiSecurity class not loaded', true);
        }

        $translations = $opts['translations'] ?? 'api';
        self::loadTranslationPacks($translations);

        $requireUser = array_key_exists('require_user', $opts) ? (bool)$opts['require_user'] : true;
        // login/register: ensure token exists but do not enforce CSRF on the auth POST itself
        $enforceCsrf = array_key_exists('enforce_csrf', $opts) ? (bool)$opts['enforce_csrf'] : true;
        $security = ApiSecurity::initialize($requireUser, $enforceCsrf);

        return [
            'user_id' => $security['user_id'],
            'is_api_key' => !empty($security['is_api_key']),
            'pdo' => $security['pdo'],
            'api_key' => $security['api_key'] ?? null,
            'csrf' => ApiSecurity::ensureCsrfToken(),
        ];
    }

    /**
     * @param string|list<string>|null $packs
     */
    private static function loadTranslationPacks($packs): void
    {
        if ($packs === null || $packs === '') {
            return;
        }
        if (!is_array($packs)) {
            $packs = [$packs];
        }
        foreach ($packs as $pack) {
            $pack = trim((string)$pack);
            if ($pack !== '' && function_exists('loadTranslations')) {
                loadTranslations($pack);
            }
        }
    }

    private static function fail(int $code, string $message, bool $json): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            if ($json) {
                header('Content-Type: application/json; charset=utf-8');
            } else {
                header('Content-Type: text/plain; charset=utf-8');
            }
        }
        if ($json) {
            echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
        } else {
            echo $message;
        }
        exit;
    }
}
