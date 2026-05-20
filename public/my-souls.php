<?php
session_start();
require_once __DIR__ . '/../includes/seo.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

setSEO('My Souls', 'Manage your uploaded AI souls.');

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

$message = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM souls WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
    $message = 'Soul 已刪除';
}

// Handle Edit + Save Version History
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $newContent = $_POST['content'];

    // Save old version first
    $oldStmt = $pdo->prepare("SELECT title, content FROM souls WHERE id = ? AND user_id = ?");
    $oldStmt->execute([$id, $user_id]);
    $old = $oldStmt->fetch();

    if ($old) {
        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$id, $old['title'], $old['content']]);
    }

    // Update current soul
    $pdo->prepare("UPDATE souls SET title = ?, description = ?, content = ? WHERE id = ? AND user_id = ?")
        ->execute([$title, $description, $newContent, $id, $user_id]);

    $message = '已更新成功！（舊版本已自動儲存）';
}

// Get user's souls
$stmt = $pdo->prepare("SELECT * FROM souls WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$mySouls = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-5xl mx-auto px-6 py-12">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold">My Souls</h1>
                <p class="text-zinc-400 mt-1">管理你上傳嘅所有 AI souls</p>
            </div>
            <div class="flex gap-3">
                <a href="my-api.php" class="px-5 py-2.5 text-sm border border-emerald-500/50 text-emerald-400 rounded-2xl hover:bg-emerald-900/20 transition">My API Key</a>
                <a href="upload.php" class="px-6 py-3 bg-white text-black rounded-2xl font-semibold hover:bg-zinc-200 transition">+ 上傳新 Soul</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-6">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (empty($mySouls)): ?>
            <div class="text-center py-16">
                <div class="text-6xl mb-4">📁</div>
                <p class="text-xl text-zinc-400 mb-4">你仲未上傳任何 Soul</p>
                <a href="upload.php" class="inline-block px-8 py-3 bg-white text-black rounded-2xl font-semibold">立即上傳第一個</a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($mySouls as $soul): ?>
                    <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <div class="font-semibold text-2xl mb-1"><?= htmlspecialchars($soul['title']) ?></div>
                                <div class="text-sm text-zinc-500">
                                    <?= date('Y-m-d H:i', strtotime($soul['created_at'])) ?> • <?= $soul['fork_count'] ?> forks
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <button onclick="editSoul(<?= $soul['id'] ?>, '<?= addslashes($soul['title']) ?>', '<?= addslashes($soul['description']) ?>', `<?= addslashes($soul['content']) ?>`)" 
                                        class="px-5 py-2 text-sm border border-white/30 rounded-2xl hover:bg-white/5 transition">
                                    編輯
                                </button>
                                <a href="?delete=<?= $soul['id'] ?>" 
                                   onclick="return confirm('確定要刪除嗎？')"
                                   class="px-5 py-2 text-sm border border-red-500/50 text-red-400 rounded-2xl hover:bg-red-900/20 transition">
                                    刪除
                                </a>
                            </div>
                        </div>

                        <?php if ($soul['description']): ?>
                            <p class="text-zinc-400 mb-4"><?= htmlspecialchars($soul['description']) ?></p>
                        <?php endif; ?>

                        <div class="flex gap-2">
                            <a href="soul.php?id=<?= $soul['id'] ?>" class="text-emerald-400 text-sm hover:underline">查看詳情 →</a>
                            <a href="soul-versions.php?id=<?= $soul['id'] ?>" class="text-xs text-zinc-400 hover:text-white underline">版本歷史</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50">
        <div class="bg-zinc-900 rounded-3xl p-8 w-full max-w-2xl">
            <h3 class="text-2xl font-semibold mb-6">編輯 Soul</h3>
            <form method="POST" id="edit-form">
                <input type="hidden" name="edit_id" id="edit_id">
                
                <div class="mb-4">
                    <label class="block text-sm mb-2">標題</label>
                    <input type="text" name="title" id="edit_title" class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-4 py-3">
                </div>

                <div class="mb-4">
                    <label class="block text-sm mb-2">描述</label>
                    <textarea name="description" id="edit_description" rows="2" class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-4 py-3"></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm mb-2">內容</label>
                    <textarea name="content" id="edit_content" rows="12" class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-4 py-3 font-mono text-sm"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-6 py-3 border border-white/30 rounded-2xl">取消</button>
                    <button type="submit" class="px-8 py-3 bg-white text-black font-semibold rounded-2xl">儲存更改</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editSoul(id, title, description, content) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_content').value = content;
            document.getElementById('edit-modal').classList.remove('hidden');
            document.getElementById('edit-modal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('edit-modal').classList.add('hidden');
            document.getElementById('edit-modal').classList.remove('flex');
        }
    </script>
</body>
</html>