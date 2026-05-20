<?php
session_start();
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../includes/seo.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

setSEO('Version History', 'View version history of your soul.');

$db = Database::getInstance();
$pdo = $db->getConnection();

$soulId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$soulId) {
    header('Location: my-souls.php');
    exit;
}

// Verify ownership
$stmt = $pdo->prepare("SELECT title FROM souls WHERE id = ? AND user_id = ?");
$stmt->execute([$soulId, $userId]);
$soul = $stmt->fetch();

if (!$soul) {
    die('Soul not found or access denied');
}

// Handle Restore
if (isset($_POST['restore_id'])) {
    $versionId = (int)$_POST['restore_id'];

    // Get version content
    $vStmt = $pdo->prepare("SELECT title, content FROM soul_versions WHERE id = ? AND soul_id = ?");
    $vStmt->execute([$versionId, $soulId]);
    $version = $vStmt->fetch();

    if ($version) {
        // Save current as new version first
        $currentStmt = $pdo->prepare("SELECT title, content FROM souls WHERE id = ?");
        $currentStmt->execute([$soulId]);
        $current = $currentStmt->fetch();

        if ($current) {
            $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
                ->execute([$soulId, $current['title'], $current['content']]);
        }

        // Restore old version
        $pdo->prepare("UPDATE souls SET title = ?, content = ? WHERE id = ?")
            ->execute([$version['title'], $version['content'], $soulId]);

        $message = '✅ Restored to the selected version successfully!';
    }
}

// Get all versions (newest first)
$versionsStmt = $pdo->prepare("SELECT * FROM soul_versions WHERE soul_id = ? ORDER BY edited_at DESC");
$versionsStmt->execute([$soulId]);
$versions = $versionsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Version History - <?= htmlspecialchars($soul['title']) ?> - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold">Version History</h1>
                <p class="text-zinc-400 mt-1"><?= htmlspecialchars($soul['title']) ?></p>
            </div>
            <a href="my-souls.php" class="text-sm text-zinc-400 hover:text-white">← Back to My Souls</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-6">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (empty($versions)): ?>
            <div class="text-center py-16 text-zinc-400">
                <div class="text-6xl mb-4">📁</div>
                <p>No version history yet</p>
                <p class="text-sm mt-2">Every edit automatically saves the previous version</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($versions as $index => $version): ?>
                    <div class="bg-zinc-900 border border-white/10 rounded-3xl p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-lg mb-1">
                                    Version #<?= count($versions) - $index ?>
                                    <span class="text-xs text-zinc-500 ml-2"><?= date('Y-m-d H:i', strtotime($version['edited_at'])) ?></span>
                                </div>
                                <div class="text-sm text-zinc-400"><?= htmlspecialchars($version['title']) ?></div>
                            </div>

                            <form method="POST" onsubmit="return confirm('Are you sure you want to restore this version?')">
                                <input type="hidden" name="restore_id" value="<?= $version['id'] ?>">
                                <button type="submit" class="px-5 py-2 text-sm border border-emerald-500/50 text-emerald-400 rounded-2xl hover:bg-emerald-900/20 transition">
                                    Restore
                                </button>
                            </form>
                        </div>

                        <div class="mt-4">
                            <button onclick="toggleContent(this)" class="text-xs text-zinc-400 hover:text-white underline">
                                Show Content
                            </button>
                            <div class="hidden mt-3 bg-black/50 p-4 rounded-2xl text-sm whitespace-pre-wrap font-mono max-h-64 overflow-auto">
                                <?= htmlspecialchars($version['content']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleContent(btn) {
            const content = btn.nextElementSibling;
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                btn.innerText = 'Hide Content';
            } else {
                content.classList.add('hidden');
                btn.innerText = 'Show Content';
            }
        }
    </script>
</body>
</html>