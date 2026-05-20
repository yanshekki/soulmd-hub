<?php
require_once __DIR__ . '/../includes/seo.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

session_start();
$db = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: browse.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND is_public = 1");
$stmt->execute([$id]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    die('Soul not found');
}

setSEO($soul['title'], $soul['description'] ?: 'View this AI soul on SoulMD Hub.');

// Like
if (isset($_POST['like']) && isset($_SESSION['user_id'])) {
    $pdo->prepare("UPDATE souls SET like_count = like_count + 1 WHERE id = ?")->execute([$id]);
    header("Location: soul.php?id=$id");
    exit;
}

// Rating (1-5 stars)
if (isset($_POST['rating']) && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $rating = (int)$_POST['rating'];
    if ($rating >= 1 && $rating <= 5) {
        $pdo->prepare("INSERT INTO soul_ratings (soul_id, user_id, rating) 
                       VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)")
            ->execute([$id, $userId, $rating]);
    }
    header("Location: soul.php?id=$id");
    exit;
}

// Fork
if (isset($_POST['fork'])) {
    $pdo->prepare("UPDATE souls SET fork_count = fork_count + 1 WHERE id = ?")->execute([$id]);
    $message = "Forked successfully!";
}

$isFolder = $soul['file_type'] === 'full_soul_folder';
$contentData = $soul['content'];

if ($isFolder) {
    $files = json_decode($contentData, true) ?: [];
} else {
    $files = ['SOUL.md' => $contentData];
}

// Get average rating
$avgRating = $pdo->prepare("SELECT AVG(rating) as avg FROM soul_ratings WHERE soul_id = ?");
$avgRating->execute([$id]);
$avg = $avgRating->fetch()['avg'] ?? 0;
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .markdown-content { line-height: 1.7; }
        .markdown-content pre { background: #111; padding: 1rem; border-radius: 0.75rem; overflow-x: auto; }
        .star { color: #fbbf24; }
    </style>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <div class="flex justify-between items-start mb-8">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-4xl font-bold"><?= htmlspecialchars($soul['title']) ?></h1>
                    <span class="text-xs px-4 py-1 rounded-full <?= $isFolder ? 'bg-purple-900/60 text-purple-400' : 'bg-emerald-900/60 text-emerald-400' ?>">
                        <?= $isFolder ? 'Full Soul Folder' : 'Single .md' ?>
                    </span>
                </div>
                <div class="text-sm text-zinc-400">
                    <?= date('F j, Y', strtotime($soul['created_at'])) ?> • <?= $soul['fork_count'] ?> forks • <?= $soul['like_count'] ?> likes
                </div>
            </div>

            <div class="flex gap-3">
                <a href="browse.php" class="px-5 py-2 border border-white/30 rounded-2xl text-sm">Back</a>
                <form method="POST">
                    <button type="submit" name="like" class="px-5 py-2 bg-white/10 hover:bg-white/20 rounded-2xl text-sm flex items-center gap-1">
                        ❤️ Like
                    </button>
                </form>
                <form method="POST">
                    <button type="submit" name="fork" class="px-6 py-2 bg-white text-black font-semibold rounded-2xl">
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

        <!-- Rating -->
        <div class="mb-8 flex items-center gap-4">
            <div class="text-sm text-zinc-400">評分：</div>
            <div class="flex text-2xl">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="rating" value="<?= $i ?>">
                        <button type="submit" class="hover:scale-125 transition <?= $i <= round($avg) ? 'star' : 'text-zinc-700' ?>">
                            ★
                        </button>
                    </form>
                <?php endfor; ?>
            </div>
            <div class="text-sm text-zinc-400 ml-2"><?= number_format($avg, 1) ?> / 5</div>
        </div>

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

        <?php if ($soul['description']): ?>
            <div class="text-lg text-zinc-300 mb-10">
                <?= nl2br(htmlspecialchars($soul['description'])) ?>
            </div>
        <?php endif; ?>

        <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
            <?php if ($isFolder && count($files) > 1): ?>
                <div class="flex border-b border-white/20 mb-6">
                    <?php $i = 0; foreach ($files as $filename => $fileContent): $i++; ?>
                        <button onclick="showFileTab(<?= $i ?> )" class="tab-btn px-6 py-3 text-sm font-medium <?= $i === 1 ? 'border-b-2 border-white' : 'text-zinc-400' ?>">
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
                <pre class="markdown-content whitespace-pre-wrap text-sm leading-relaxed"><?= htmlspecialchars($contentData) ?></pre>
            <?php endif; ?>
        </div>

        <div class="mt-8 text-center text-xs text-zinc-500">
            This soul is publicly available. Fork it to customize.
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