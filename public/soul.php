<?php
require_once __DIR__ . '/../includes/seo.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: browse.php');
    exit;
}

// Get soul
$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND is_public = 1");
$stmt->execute([$id]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    die('Soul not found');
}

// SEO
setSEO($soul['title'], $soul['description'] ?: 'View this AI soul on SoulMD Hub.');

// Handle Fork
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fork'])) {
    $pdo->prepare("UPDATE souls SET fork_count = fork_count + 1 WHERE id = ?")->execute([$id]);
    $message = "Forked successfully! You can now edit it in your collection.";
}

// Render content
$isFolder = $soul['file_type'] === 'full_soul_folder';
$contentData = $soul['content'];

if ($isFolder) {
    $files = json_decode($contentData, true) ?: [];
} else {
    $files = ['SOUL.md' => $contentData];
}
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .markdown-content { line-height: 1.7; }
        .markdown-content h1, .markdown-content h2, .markdown-content h3 { font-weight: 600; margin-top: 1.5em; }
        .markdown-content pre { background: #111; padding: 1rem; border-radius: 0.75rem; overflow-x: auto; }
        .markdown-content code { font-family: ui-monospace, monospace; }
    </style>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-4xl font-bold"><?= htmlspecialchars($soul['title']) ?></h1>
                    <span class="text-xs px-4 py-1 rounded-full <?= $isFolder ? 'bg-purple-900/60 text-purple-400' : 'bg-emerald-900/60 text-emerald-400' ?>">
                        <?= $isFolder ? 'Full Soul Folder' : 'Single .md' ?>
                    </span>
                </div>
                <div class="text-sm text-zinc-400">
                    <?= date('F j, Y', strtotime($soul['created_at'])) ?> • <?= $soul['fork_count'] ?> forks
                </div>
            </div>

            <div class="flex gap-3">
                <a href="browse.php" class="px-5 py-2 border border-white/30 rounded-2xl text-sm hover:bg-white/5 transition">Back to Browse</a>
                <form method="POST">
                    <button type="submit" name="fork" 
                            class="px-6 py-2 bg-white text-black font-semibold rounded-2xl hover:bg-zinc-200 transition flex items-center gap-2">
                        🔄 Fork
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-6">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Metadata -->
        <div class="flex flex-wrap gap-2 mb-8">
            <?php if ($soul['role']): ?>
                <span class="px-4 py-1 bg-white/10 rounded-full text-sm"><?= $soul['role'] ?></span>
            <?php endif; ?>
            <?php if ($soul['domain']): ?>
                <span class="px-4 py-1 bg-white/10 rounded-full text-sm"><?= $soul['domain'] ?></span>
            <?php endif; ?>
            <?php if ($soul['compatibility']): ?>
                <span class="px-4 py-1 bg-white/10 rounded-full text-sm"><?= $soul['compatibility'] ?></span>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <?php if ($soul['description']): ?>
            <div class="text-lg text-zinc-300 mb-10">
                <?= nl2br(htmlspecialchars($soul['description'])) ?>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
            <?php if ($isFolder && count($files) > 1): ?>
                <!-- Tabs for full folder -->
                <div class="flex border-b border-white/20 mb-6">
                    <?php $i = 0; foreach ($files as $filename => $fileContent): $i++; ?>
                        <button onclick="showFileTab(<?= $i ?> )" 
                                class="tab-btn px-6 py-3 text-sm font-medium <?= $i === 1 ? 'border-b-2 border-white text-white' : 'text-zinc-400' ?>">
                            <?= htmlspecialchars($filename) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php $i = 0; foreach ($files as $filename => $fileContent): $i++; ?>
                    <div id="file-tab-<?= $i ?>" class="file-tab <?= $i === 1 ? '' : 'hidden' ?>">
                        <pre class="markdown-content whitespace-pre-wrap text-sm leading-relaxed"><?= htmlspecialchars($fileContent) ?></pre>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Single file -->
                <pre class="markdown-content whitespace-pre-wrap text-sm leading-relaxed"><?= htmlspecialchars($contentData) ?></pre>
            <?php endif; ?>
        </div>

        <div class="mt-8 text-center text-xs text-zinc-500">
            This soul is publicly available. Fork it to customize for your own AI.
        </div>
    </div>

    <script>
        function showFileTab(tabNum) {
            document.querySelectorAll('.file-tab').forEach(el => el.classList.add('hidden'));
            document.getElementById('file-tab-' + tabNum).classList.remove('hidden');
            
            document.querySelectorAll('.tab-btn').forEach((btn, index) => {
                btn.classList.toggle('border-b-2', index + 1 === tabNum);
                btn.classList.toggle('border-white', index + 1 === tabNum);
                btn.classList.toggle('text-white', index + 1 === tabNum);
                btn.classList.toggle('text-zinc-400', index + 1 !== tabNum);
            });
        }
    </script>
</body>
</html>