<?php
require_once __DIR__ . '/../includes/seo.php';

// Dynamic SEO based on search/filters
$seoTitle = 'Browse Souls';
$seoDesc = 'Discover and explore AI agent souls shared by the community.';

if (!empty($_GET['q'])) {
    $seoTitle = 'Search: ' . $_GET['q'];
    $seoDesc = 'Search results for "' . $_GET['q'] . '" on SoulMD Hub.';
} elseif (!empty($_GET['role'])) {
    $seoTitle = $_GET['role'] . ' Souls';
    $seoDesc = 'Browse all ' . $_GET['role'] . ' souls on SoulMD Hub.';
}

setSEO($seoTitle, $seoDesc);
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-7xl mx-auto px-6 py-12">
        <!-- Header -->
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold">Browse Souls</h1>
                <p class="text-zinc-400 mt-1">發現其他人同 AI 分享嘅 .md souls</p>
            </div>
            <a href="upload.php" class="px-6 py-3 bg-white text-black rounded-2xl font-semibold hover:bg-zinc-200 transition">+ 上傳 Soul</a>
        </div>

        <!-- Search + Filters -->
        <form method="GET" class="mb-10 flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" 
                       placeholder="搜尋標題、描述或內容..." 
                       class="w-full bg-zinc-900 border border-white/20 rounded-2xl px-5 py-3 text-lg focus:outline-none focus:border-white">
            </div>

            <select name="role" class="bg-zinc-900 border border-white/20 rounded-2xl px-5 py-3">
                <option value="">所有角色</option>
                <?php 
                $roles = $pdo->query("SELECT DISTINCT role FROM souls WHERE role != '' AND is_public = 1 ORDER BY role")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($roles as $r): ?>
                    <option value="<?= $r ?>" <?= ($_GET['role'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                <?php endforeach; ?>
            </select>

            <select name="file_type" class="bg-zinc-900 border border-white/20 rounded-2xl px-5 py-3">
                <option value="">所有類型</option>
                <option value="single_md" <?= ($_GET['file_type'] ?? '') === 'single_md' ? 'selected' : '' ?>>單一 .md</option>
                <option value="full_soul_folder" <?= ($_GET['file_type'] ?? '') === 'full_soul_folder' ? 'selected' : '' ?>>完整 Soul Folder</option>
            </select>

            <button type="submit" class="px-8 py-3 bg-white text-black font-semibold rounded-2xl hover:bg-zinc-200 transition">
                搜尋
            </button>
        </form>

        <!-- Results -->
        <div class="mb-6 text-sm text-zinc-400">
            找到 <?= count($souls) ?> 個 souls
        </div>

        <?php if (empty($souls)): ?>
            <div class="text-center py-16 text-zinc-400">
                <div class="text-6xl mb-4">🔍</div>
                <p>冇搵到相關 souls</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($souls as $soul): ?>
                    <div class="bg-zinc-900 border border-white/10 rounded-3xl p-6 hover:border-white/30 transition group">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <div class="font-semibold text-xl mb-1 group-hover:text-emerald-400 transition">
                                    <?= htmlspecialchars($soul['title']) ?>
                                </div>
                                <div class="text-xs text-zinc-500">
                                    <?= date('Y-m-d', strtotime($soul['created_at'])) ?>
                                </div>
                            </div>
                            <div class="text-xs px-3 py-1 rounded-full <?= $soul['file_type'] === 'full_soul_folder' ? 'bg-purple-900/60 text-purple-400' : 'bg-emerald-900/60 text-emerald-400' ?>">
                                <?= $soul['file_type'] === 'full_soul_folder' ? 'Folder' : '.md' ?>
                            </div>
                        </div>

                        <?php if ($soul['description']): ?>
                            <p class="text-sm text-zinc-400 line-clamp-2 mb-4">
                                <?= htmlspecialchars($soul['description']) ?>
                            </p>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php if ($soul['role']): ?>
                                <span class="text-xs px-3 py-1 bg-white/10 rounded-full"><?= $soul['role'] ?></span>
                            <?php endif; ?>
                            <?php if ($soul['domain']): ?>
                                <span class="text-xs px-3 py-1 bg-white/10 rounded-full"><?= $soul['domain'] ?></span>
                            <?php endif; ?>
                            <?php if ($soul['compatibility']): ?>
                                <span class="text-xs px-3 py-1 bg-white/10 rounded-full"><?= $soul['compatibility'] ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <div class="text-zinc-500">
                                <?= $soul['fork_count'] ?> forks
                            </div>
                            <a href="soul.php?id=<?= $soul['id'] ?>" 
                               class="text-emerald-400 hover:text-emerald-300 font-medium flex items-center gap-1">
                                查看詳情 →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>