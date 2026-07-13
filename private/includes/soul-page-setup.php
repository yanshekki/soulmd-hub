<?php
/**
 * SoulMD Hub - Common setup for upload.php and edit.php
 * (DRY via AppBootstrap — session, CSRF, translations, DB, require login)
 */

if (!class_exists('AppBootstrap', false)) {
    require_once __DIR__ . '/../src/AppBootstrap.php';
}

$app = AppBootstrap::forPage([
    'translations' => 'upload',
    'csrf' => true,
    'db' => true,
    'require_login' => true,
    'seo' => true,
]);

$csrfToken = $app['csrf'];
$pdo = $app['pdo'];
$user_id = (int)$app['user_id'];
?>
