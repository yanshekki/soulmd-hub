<?php
/**
 * SoulMD Hub - Upload & Publish Dashboard
 * (DRY Refactored - Unified Form Extracted to soul-form.php)
 * 🚀 Patched: Standardized SEO Meta i18n variables
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

loadTranslations('upload');

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

$isEditMode = false;
$soulId = 0;
$soulData = [];

$pageTitle = __('SEO Title Upload');
$pageDesc = __('SEO Desc Upload');
require_once __DIR__ . '/../private/includes/header.php';

// 🚨 引入核心表單 (已包含所有 Loading 效果與防雙擊鎖定)
require_once __DIR__ . '/../private/includes/soul-form.php';

require_once __DIR__ . '/../private/includes/footer.php';
?>