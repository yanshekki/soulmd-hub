<?php
/**
 * SoulMD Hub - Common setup for upload.php and edit.php
 * (Extracted for DRY - shared session, CSRF token, translations, DB, user)
 *
 * Both pages do:
 * - session_start + CSRF token init (for browser mutating calls to api/souls or api/soul)
 * - loadTranslations('upload')
 * - get $pdo + $user_id
 *
 * edit.php has additional logic after this (soul fetch, lazy NFT sync, permission checks, NearRpcService).
 * upload.php sets $isEditMode=false etc. after this.
 */

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

require_once __DIR__ . '/../../private/src/ApiSecurity.php';

// Use the single global function (defined in ApiSecurity.php) --
// this replaces every copy of the if-empty + bin2hex(random_bytes(32)) pattern.
$csrfToken = ensureCsrfToken();

loadTranslations('upload');

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];
?>