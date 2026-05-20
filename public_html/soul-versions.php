<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

setSEO('Version History', 'View and restore previous versions of your soul.');

$db = Database::getInstance();
$pdo = $db->getConnection();

$soulId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$soulId) {
    header('Location: my-souls.php');
    exit;
}

$stmt = $pdo->prepare("SELECT title FROM souls WHERE id = ? AND user_id = ?");
$stmt->execute([$soulId, $userId]);
$soul = $stmt->fetch();

if (!$soul) {
    die('Soul not found or access denied');
}

if (isset($_POST['ajax_restore'])) {
    $versionId = (int)$_POST['version_id'];
    $vStmt = $pdo->prepare("SELECT title, content FROM soul_versions WHERE id = ? AND soul_id = ?");
    $vStmt->execute([$versionId, $soulId]);
    $version = $vStmt->fetch();

    if ($version) {
        $currentStmt = $pdo->prepare("SELECT title, content FROM souls WHERE id = ?");
        $currentStmt->execute([$soulId]);
        $current = $currentStmt->fetch();

        if ($current) {
            $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
                ->execute([$soulId, $current['title'], $current['content']]);
        }

        $pdo->prepare("UPDATE souls SET title = ?, content = ? WHERE id = ?")
            ->execute([$version['title'], $version['content'], $soulId]);

        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

$versionsStmt = $pdo->prepare("SELECT * FROM soul_versions WHERE soul_id = ? ORDER BY edited_at DESC");
$versionsStmt->execute([$soulId]);
$versions = $versionsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Version History - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold tracking-tighter">Version History</h1>
                <p class="text-zinc-400 mt-1"><?= htmlspecialchars($soul['title']) ?></p>
            </div>
            <a href="my-souls.php" class="text-sm text-zinc-400 hover:text-white flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> My Souls
            </a>
        </div>

        <?php if (empty($versions)): ?>
            <div class="text-center py-24 text-zinc-400">
                <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 rounded-3xl mb-6">
                    <i class="fas fa-history text-4xl"></i>
                </div>
                <h2 class="text-2xl font-semibold mb-2">No versions yet</h2>
                <p class="text-sm">Every edit automatically saves the previous version</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($versions as $index => $version): ?>
                    <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <div class="font-semibold text-xl mb-1">
                                    Version #<?= count($versions) - $index ?>
                                    <span class="text-xs text-zinc-500 ml-3"><?= date('M j, Y • H:i', strtotime($version['edited_at'])) ?></span>
                                </div>
                                <div class="text-sm text-zinc-400"><?= htmlspecialchars($version['title']) ?></div>
                            </div>

                            <button onclick="restoreVersion(<?= $version['id'] ?>)" class="px-6 py-2 text-sm border border-emerald-400 text-emerald-400 rounded-3xl hover:bg-emerald-900/20 transition flex items-center gap-2">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </div>

                        <button onclick="toggleContent(this)" class="text-xs text-zinc-400 hover:text-white underline flex items-center gap-2">
                            <i class="fas fa-eye"></i> Show content
                        </button>
                        <div class="hidden mt-4 bg-black/50 p-6 rounded-3xl text-sm whitespace-pre-wrap font-mono max-h-80 overflow-auto">
                            <?= htmlspecialchars($version['content']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        async function restoreVersion(versionId) {
            if (!confirm('Restore this version? Current version will be saved as new.')) return;

            const res = await fetch('soul-versions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ajax_restore=1&version_id=' + versionId
            });

            const data = await res.json();

            if (data.success) {
                location.reload();
            } else {
                alert('Restore failed');
            }
        }

        function toggleContent(btn) {
            const content = btn.nextElementSibling;
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                btn.innerHTML = `<i class="fas fa-eye-slash"></i> Hide content`;
            } else {
                content.classList.add('hidden');
                btn.innerHTML = `<i class="fas fa-eye"></i> Show content`;
            }
        }
    </script>
</body>
</html>