<?php
/**
 * SoulMD Hub - Edit Model Dashboard
 * (DRY Refactored - Unified Form Extracted to soul-form.php)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

// 與 Upload 共用語言包
loadTranslations('upload');

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

$soulId = (int)($_GET['id'] ?? 0);
if (!$soulId) {
    header('Location: ' . url('/my-souls'));
    exit;
}

// 獲取欲編輯的模型數據，如果不是作者本人則拒絕存取
$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND user_id = ?");
$stmt->execute([$soulId, $user_id]);
$soulData = $stmt->fetch();

if (!$soulData) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$isEditMode = true;

$pageTitle = __('Edit Soul') . ' - ' . htmlspecialchars($soulData['title']);
require_once __DIR__ . '/../private/includes/header.php';

// 引入核心表單
require_once __DIR__ . '/../private/includes/soul-form.php';

require_once __DIR__ . '/../private/includes/footer.php';
?>