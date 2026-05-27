<?php
/**
 * SoulMD Hub - Creator Workspace & Model Management Dashboard
 * (Decoupled Modals & Dynamic i18n Internationalization Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('my-souls');

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$topDomains = $pdo->query("SELECT name FROM tags_domain ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);
$topCompatibilities = $pdo->query("SELECT name FROM tags_compatibility ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

// 🚨 支援 PHP 伺服器端多語言排序渲染
$sort = $_GET['sort'] ?? 'newest';
$orderSql = "ORDER BY s.created_at DESC";
if ($sort === 'popular') {
    $orderSql = "ORDER BY s.like_count DESC, s.created_at DESC";
} elseif ($sort === 'forks') {
    $orderSql = "ORDER BY s.fork_count DESC, s.created_at DESC";
}

$stmt = $pdo->prepare("
    SELECT s.*, c.icon as role_icon, c.name as role_name 
    FROM souls s 
    LEFT JOIN categories c ON s.role = c.slug 
    WHERE s.user_id = ? 
    $orderSql
");
$stmt->execute([$user_id]);
$mySouls = $stmt->fetchAll();

// 🚨 PHP 端 SEO 友善助手
function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

$pageTitle = __('My Souls');
$pageDesc = __('Manage and edit your uploaded AI personalities');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10 border-b border-white/10 pb-6">
        <div>
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tighter"><?= __('My Souls') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-2"><?= __('Manage and edit your uploaded AI personalities') ?></p>
        </div>
        
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-3 w-full lg:w-auto">
            <select onchange="window.location.href='?sort=' + this.value" class="col-span-2 sm:col-span-1 w-full sm:w-auto px-4 py-3 sm:py-2.5 text-sm bg-zinc-900 border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition focus:outline-none focus:border-emerald-400 shadow-inner cursor-pointer appearance-none">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= __('✨ Newest') ?></option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>><?= __('❤️ Like Count') ?></option>
                <option value="forks" <?= $sort === 'forks' ? 'selected' : '' ?>><?= __('🌿 Fork Count') ?></option>
            </select>
            
            <a href="<?= url('/profile/' . rawurlencode($_SESSION['username'] ?? '')) ?>" target="_blank" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition flex items-center justify-center gap-2 whitespace-nowrap">
                <i class="fas fa-external-link-alt text-[10px] text-zinc-500"></i> <?= __('Profile') ?>
            </a>
            <a href="<?= url('/my-api') ?>" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-emerald-500/30 text-emerald-400 rounded-2xl hover:bg-emerald-900/10 transition text-center whitespace-nowrap">
                <?= __('My API Key') ?>
            </a>
            <a href="<?= url('/upload') ?>" class="col-span-2 sm:col-span-1 px-6 py-3 sm:py-2.5 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center justify-center gap-2 shadow-lg w-full sm:w-auto">
                <i class="fas fa-plus"></i> <?= __('New Soul') ?>
            </a>
        </div>
    </div>

    <?php if (empty($mySouls)): ?>
        <div class="text-center py-20 sm:py-24 bg-zinc-900/20 border border-white/5 rounded-3xl mx-4 sm:mx-0">
            <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500"><i class="fas fa-folder-open text-3xl"></i></div>
            <h2 class="text-xl sm:text-2xl font-semibold mb-2"><?= __('No souls shared yet') ?></h2>
            <a href="<?= url('/upload') ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg mt-4 w-full sm:w-auto max-w-[200px] mx-auto"><?= __('Upload your first') ?></a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="souls-list">
            <?php foreach ($mySouls as $soul): ?>
                <div class="soul-card bg-zinc-900/60 border border-white/10 rounded-3xl p-5 sm:p-6 hover:border-emerald-400/40 transition-all flex flex-col justify-between backdrop-blur-sm shadow-lg" data-id="<?= $soul['id'] ?>">
                    <div>
                        <div class="flex justify-between items-start gap-3 mb-3">
                            <div>
                                <div class="font-bold text-lg sm:text-xl text-white tracking-tight mb-1 line-clamp-2 leading-tight"><?= htmlspecialchars($soul['title']) ?></div>
                                <div class="text-[10px] sm:text-xs text-zinc-500 flex items-center gap-1.5 flex-wrap">
                                    <span><?= htmlspecialchars($soul['role_icon'] ?? '✨') ?> <?= htmlspecialchars($soul['role_name'] ?? __('Unassigned')) ?></span><span>•</span><span><?= date('M j, Y', strtotime($soul['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="flex gap-2 shrink-0 flex-col items-end">
                                <span class="text-[10px] px-2.5 py-1 rounded-full font-medium border <?= $soul['is_public'] ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-zinc-800 text-zinc-400 border-white/5' ?> shadow-sm">
                                    <i class="fas <?= $soul['is_public'] ? 'fa-globe' : 'fa-lock' ?> mr-1"></i><?= $soul['is_public'] ? __('Public') : __('Private') ?>
                                </span>
                                <span class="text-[9px] px-2 py-0.5 rounded font-medium border <?= $soul['file_type'] === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20' ?> shadow-sm">
                                    <?= $soul['file_type'] === 'full_soul_folder' ? __('Modular') : __('Single .md') ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($soul['description']): ?>
                            <p class="text-xs sm:text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed"><?= htmlspecialchars($soul['description']) ?></p>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-1.5 mb-6">
                            <?php 
                            $cardDomains = array_filter(array_map('trim', explode(',', $soul['domain'])));
                            foreach (array_slice($cardDomains, 0, 3) as $dTag): ?>
                                <span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded shadow-sm">#<?= htmlspecialchars($dTag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-auto">
                        <div class="flex items-center gap-4 text-xs text-zinc-500">
                            <span title="<?= __('Forks') ?>"><i class="fas fa-code-branch mr-1 text-emerald-500"></i><b class="text-zinc-300"><?= $soul['fork_count'] ?></b></span>
                            <span title="<?= __('Likes') ?>"><i class="fas fa-heart mr-1 text-red-500"></i><b class="text-zinc-300"><?= $soul['like_count'] ?></b></span>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="editSoul(<?= $soul['id'] ?>)" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-xl border border-white/5 transition flex-1 sm:flex-auto text-center"><?= __('Edit') ?></button>
                            <a href="<?= url('/soul-versions/' . $soul['id']) ?>" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-zinc-200 rounded-xl border border-white/5 transition flex items-center justify-center" title="<?= __('Version History') ?>"><i class="fas fa-history"></i></a>
                            <button onclick="deleteSoul(<?= $soul['id'] ?>)" class="px-4 py-2.5 sm:p-2 text-xs text-zinc-500 hover:text-red-400 transition bg-zinc-800 sm:bg-transparent rounded-xl sm:rounded-none border border-white/5 sm:border-none flex items-center justify-center"><i class="far fa-trash-alt sm:text-base"></i></button>
                            <?php $seoUrl = url("/soul/" . rawurlencode($_SESSION['username']) . "/" . $soul['id'] . "/" . makeSlug($soul['role']) . "/" . makeSlug($soul['title'])); ?>
                            <a href="<?= $seoUrl ?>" class="px-5 py-2.5 sm:py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow flex-1 sm:flex-auto"><?= __('View') ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php 
// 🚨 動態載入已分拆嘅 Modal 獨立組件
require_once __DIR__ . '/../private/includes/my-souls-modals.php'; 
?>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>