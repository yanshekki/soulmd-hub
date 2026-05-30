<?php
/**
 * SoulMD Hub - Creator Workspace & Model Management Dashboard
 * (V5 Specification Dual-Section Web2/Web3 Separation Edition)
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

// 撈取當前用戶資料（包含 Web3 錢包地址）
$uStmt = $pdo->prepare("SELECT username, near_wallet_address FROM users WHERE id = ?");
$uStmt->execute([$user_id]);
$currentUserRow = $uStmt->fetch();
$nearWallet = $currentUserRow['near_wallet_address'] ?? null;

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$topDomains = $pdo->query("SELECT name FROM tags_domain ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);
$topCompatibilities = $pdo->query("SELECT name FROM tags_compatibility ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

// 處理排序條件
$sort = $_GET['sort'] ?? 'newest';
$orderSql = "ORDER BY s.created_at DESC";
if ($sort === 'popular') {
    $orderSql = "ORDER BY s.like_count DESC, s.created_at DESC";
} elseif ($sort === 'forks') {
    $orderSql = "ORDER BY s.fork_count DESC, s.created_at DESC";
}

// 🚀 Section A: 撈取未鑄造的 Web2 原創靈魂模型
$stmtA = $pdo->prepare("
    SELECT s.*, c.icon as role_icon, c.name as role_name 
    FROM souls s 
    LEFT JOIN categories c ON s.role = c.slug 
    WHERE s.user_id = ? AND s.is_nft = 0
    $orderSql
");
$stmtA->execute([$user_id]);
$web2Souls = $stmtA->fetchAll();

// 🚀 Section B: 撈取當前鏈上歸屬屬於自己的 AgentFi NFT 資產 (Lazy Sync 緩存池)
$web3Souls = [];
if (!empty($nearWallet)) {
    $stmtB = $pdo->prepare("
        SELECT s.*, c.icon as role_icon, c.name as role_name 
        FROM souls s 
        LEFT JOIN categories c ON s.role = c.slug 
        WHERE s.nft_owner_wallet = ? AND s.is_nft = 1
        $orderSql
    ");
    $stmtB->execute([$nearWallet]);
    $web3Souls = $stmtB->fetchAll();
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
            
            <a href="<?= url('/profile/' . rawurlencode($currentUserRow['username'] ?? '')) ?>" target="_blank" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition flex items-center justify-center gap-2 whitespace-nowrap">
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

    <div class="mb-14">
        <h2 class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-emerald-400 pl-3">
            <i class="fas fa-tools text-emerald-400"></i> 🛠️ 我的原創模型庫 (Web2 Prototype Box)
        </h2>
        
        <?php if (empty($web2Souls)): ?>
            <div class="text-center py-12 bg-zinc-900/20 border border-dashed border-white/5 rounded-3xl">
                <div class="mx-auto w-12 h-12 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-xl mb-4 text-zinc-500"><i class="fas fa-code text-xl"></i></div>
                <p class="text-zinc-400 text-sm"><?= __('No souls shared yet') ?></p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($web2Souls as $soul): ?>
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
                                    <?php if (!$soul['is_public']): ?>
                                        <span class="text-[10px] px-2.5 py-1 rounded-full font-bold border bg-red-500/10 text-red-400 border-red-500/20 shadow-sm">
                                            <i class="fas fa-lock mr-1"></i>🔒 Private (僅自己可見)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] px-2.5 py-1 rounded-full font-medium border bg-emerald-500/10 text-emerald-400 border-emerald-500/20 shadow-sm">
                                            <i class="fas fa-globe mr-1"></i><?= __('Public') ?>
                                        </span>
                                    <?php endif; ?>
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
                                <?php $seoUrl = url("/soul/" . rawurlencode($currentUserRow['username']) . "/" . $soul['id'] . "/" . makeSlug($soul['role']) . "/" . makeSlug($soul['title'])); ?>
                                <a href="<?= $seoUrl ?>" class="px-5 py-2.5 sm:py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow flex-1 sm:flex-auto"><?= __('View') ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <h2 class="text-xl font-extrabold mb-6 flex items-center gap-2 text-white border-l-4 border-purple-500 pl-3">
            <i class="fas fa-gem text-purple-400"></i> 💎 我的鏈上智能體資產 (AgentFi NFT Asset Inventory)
        </h2>
        
        <?php if (empty($nearWallet)): ?>
            <div class="text-center py-12 bg-purple-950/10 border border-dashed border-purple-500/30 rounded-3xl p-8">
                <i class="fas fa-wallet text-purple-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-bold text-white mb-2">未檢測到綁定的 Web3 錢包</h3>
                <p class="text-sm text-zinc-400 max-w-md mx-auto mb-6">請先前往控制台設定綁定您的 NEAR 主網錢包。綁定後，系統將自動同步並加載您在鏈上持有的 AI 智能體 NFT 資產。</p>
                <a href="<?= url('/my-setting') ?>" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-purple-500/20"><i class="fas fa-cog"></i> 前往設定綁定</a>
            </div>
        <?php elseif (empty($web3Souls)): ?>
            <div class="text-center py-12 bg-zinc-900/20 border border-dashed border-white/5 rounded-3xl">
                <div class="mx-auto w-12 h-12 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-xl mb-4 text-zinc-500"><i class="fas fa-box-open text-xl"></i></div>
                <p class="text-zinc-400 text-sm">您的錢包名下暫無持有的 AI 智能體 NFT。前往模型庫選擇一個創作者模型鑄造吧！</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($web3Souls as $soul): ?>
                    <div class="soul-card bg-zinc-900/60 border border-purple-500/20 rounded-3xl p-5 sm:p-6 hover:border-purple-400/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-lg relative overflow-hidden" data-id="<?= $soul['id'] ?>">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-purple-500 to-transparent"></div>
                        <div>
                            <div class="flex justify-between items-start gap-3 mb-3">
                                <div>
                                    <div class="font-bold text-lg sm:text-xl text-white tracking-tight mb-1 line-clamp-2 leading-tight"><?= htmlspecialchars($soul['title']) ?></div>
                                    <div class="text-[10px] sm:text-xs text-zinc-500 flex items-center gap-1.5 flex-wrap">
                                        <span><?= htmlspecialchars($soul['role_icon'] ?? '✨') ?> <?= htmlspecialchars($soul['role_name'] ?? __('Unassigned')) ?></span><span>•</span><span><?= date('M j, Y', strtotime($soul['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="flex gap-2 shrink-0 flex-col items-end">
                                    <span class="text-[10px] px-2.5 py-1 rounded-full font-bold border bg-purple-500/10 text-purple-400 border-purple-500/20 shadow-sm">
                                        <i class="fas fa-cube mr-1"></i>Agent NFT Asset
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
                            <div class="flex items-center gap-4 text-xs text-zinc-500 font-mono">
                                <span><i class="fas fa-code-branch mr-1 text-emerald-500"></i><b><?= $soul['fork_count'] ?></b></span>
                                <span><i class="fas fa-heart mr-1 text-red-500"></i><b><?= $soul['like_count'] ?></b></span>
                            </div>
                            
                            <div class="flex flex-wrap items-center gap-2">
                                <button onclick="editSoul(<?= $soul['id'] ?>)" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-purple-300 border border-purple-500/20 rounded-xl transition flex-1 sm:flex-auto text-center"><i class="fas fa-store-alt mr-1"></i> 市集上架 / 租務</button>
                                <button onclick="deleteSoul(<?= $soul['id'] ?>)" class="px-4 py-2.5 sm:p-2 text-xs text-zinc-500 hover:text-red-400 transition bg-zinc-800 sm:bg-transparent rounded-xl sm:rounded-none border border-white/5 sm:border-none flex items-center justify-center" title="銷毀 NFT 並退還質押金"><i class="fas fa-fire-alt sm:text-base"></i></button>
                                <?php $seoUrl = url("/soul/" . rawurlencode($soul['username'] ?? 'anonymous') . "/" . $soul['id'] . "/" . makeSlug($soul['role']) . "/" . makeSlug($soul['title'])); ?>
                                <a href="<?= $seoUrl ?>" class="px-5 py-2.5 sm:py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow flex-1 sm:flex-auto"><?= __('View') ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
// 🚨 動態載入優化後的 Modals 腳本組件
require_once __DIR__ . '/../private/includes/my-souls-modals.php'; 
?>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>