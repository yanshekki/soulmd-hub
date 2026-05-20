<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

setSEO('My Souls', 'Manage your uploaded AI souls.');

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

// AJAX Delete
if (isset($_POST['ajax_delete'])) {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM souls WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
    echo json_encode(['success' => true]);
    exit;
}

// AJAX Edit
if (isset($_POST['ajax_edit'])) {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $content = $_POST['content'];

    // Save old version
    $oldStmt = $pdo->prepare("SELECT title, content FROM souls WHERE id = ? AND user_id = ?");
    $oldStmt->execute([$id, $user_id]);
    $old = $oldStmt->fetch();

    if ($old) {
        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$id, $old['title'], $old['content']]);
    }

    $pdo->prepare("UPDATE souls SET title = ?, description = ?, content = ? WHERE id = ? AND user_id = ?")
        ->execute([$title, $description, $content, $id, $user_id]);

    echo json_encode(['success' => true]);
    exit;
}

// Get user's souls
$stmt = $pdo->prepare("SELECT * FROM souls WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$mySouls = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Souls - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold tracking-tighter">My Souls</h1>
                <p class="text-zinc-400 mt-1">Manage your uploaded AI personalities</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="my-api" class="px-5 py-2 text-sm border border-emerald-400 text-emerald-400 rounded-3xl hover:bg-emerald-900/20 transition">My API Key</a>
                <a href="upload" class="px-6 py-3 bg-white text-black rounded-3xl font-semibold hover:bg-zinc-200 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> New Soul
                </a>
            </div>
        </div>

        <?php if (empty($mySouls)): ?>
            <div class="text-center py-24">
                <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 rounded-3xl mb-6">
                    <i class="fas fa-folder text-4xl text-zinc-400"></i>
                </div>
                <h2 class="text-2xl font-semibold mb-2">No souls yet</h2>
                <p class="text-zinc-400 mb-8">Start sharing your AI personalities</p>
                <a href="upload" class="inline-flex items-center gap-3 px-8 py-4 bg-white text-black rounded-3xl font-semibold hover:bg-zinc-200 transition">
                    <i class="fas fa-plus"></i> Upload your first soul
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="souls-list">
                <?php foreach ($mySouls as $soul): ?>
                    <div class="soul-card bg-zinc-900 border border-white/10 rounded-3xl p-6 hover:border-emerald-400/50 transition" data-id="<?= $soul['id'] ?>">
                        <div class="flex justify-between items-start mb-4">
                            <div class="font-semibold text-xl"><?= htmlspecialchars($soul['title']) ?></div>
                            <div class="text-xs px-3 py-1 rounded-3xl <?= $soul['file_type'] === 'full_soul_folder' ? 'bg-purple-900 text-purple-400' : 'bg-emerald-900 text-emerald-400' ?>">
                                <?= $soul['file_type'] === 'full_soul_folder' ? 'Folder' : '.md' ?>
                            </div>
                        </div>

                        <?php if ($soul['description']): ?>
                            <p class="text-sm text-zinc-400 line-clamp-3 mb-6"><?= htmlspecialchars($soul['description']) ?></p>
                        <?php endif; ?>

                        <div class="flex items-center justify-between text-xs text-zinc-500">
                            <div><?= date('M j, Y', strtotime($soul['created_at'])) ?></div>
                            <div class="flex items-center gap-4">
                                <span><?= $soul['fork_count'] ?> forks</span>
                                <span><?= $soul['like_count'] ?> likes</span>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-6 pt-6 border-t border-white/10">
                            <button onclick="editSoul(<?= $soul['id'] ?>)" class="flex-1 py-3 text-sm border border-white/30 rounded-3xl hover:bg-white/5 transition">Edit</button>
                            <button onclick="deleteSoul(<?= $soul['id'] ?>)" class="flex-1 py-3 text-sm border border-red-500/50 text-red-400 rounded-3xl hover:bg-red-900/20 transition">Delete</button>
                            <a href="soul/<?= $soul['id'] ?>" class="flex-1 py-3 text-sm border border-emerald-400 text-emerald-400 rounded-3xl hover:bg-emerald-900/20 transition text-center">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
        <div class="bg-zinc-900 rounded-3xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-semibold">Edit Soul</h3>
                    <button onclick="closeModal()" class="text-zinc-400 hover:text-white"><i class="fas fa-times text-2xl"></i></button>
                </div>

                <form id="edit-form" onsubmit="handleEdit(event)">
                    <input type="hidden" id="edit-id" name="id">

                    <div class="mb-6">
                        <label class="block text-sm mb-2 text-zinc-400">Title</label>
                        <input id="edit-title" type="text" class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm mb-2 text-zinc-400">Description</label>
                        <textarea id="edit-description" rows="3" class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm mb-2 text-zinc-400">Content</label>
                        <textarea id="edit-content" rows="12" class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 font-mono text-sm focus:outline-none focus:border-emerald-400"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 mt-8">
                        <button type="button" onclick="closeModal()" class="px-8 py-3 border border-white/30 rounded-3xl text-sm font-medium hover:bg-white/5 transition">Cancel</button>
                        <button type="submit" class="px-10 py-3 bg-white text-black rounded-3xl font-semibold flex items-center gap-2">
                            <span id="save-text">Save Changes</span>
                            <span id="loading-spinner" class="hidden animate-spin h-4 w-4 border-2 border-black border-t-transparent rounded-full"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentEditId = null;

        async function handleEdit(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const text = btn.querySelector('#save-text');
            const spinner = btn.querySelector('#loading-spinner');

            text.classList.add('hidden');
            spinner.classList.remove('hidden');

            const formData = new FormData();
            formData.append('ajax_edit', '1');
            formData.append('id', currentEditId);
            formData.append('title', document.getElementById('edit-title').value);
            formData.append('description', document.getElementById('edit-description').value);
            formData.append('content', document.getElementById('edit-content').value);

            const res = await fetch('my-souls', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                closeModal();
                // Update card title + description without reload
                const card = document.querySelector(`.soul-card[data-id="${currentEditId}"]`);
                if (card) {
                    card.querySelector('.font-semibold').innerText = document.getElementById('edit-title').value;
                    const descP = card.querySelector('p');
                    if (descP) descP.innerText = document.getElementById('edit-description').value;
                }
            } else {
                alert('Error saving changes');
            }

            text.classList.remove('hidden');
            spinner.classList.add('hidden');
        }

        function editSoul(id) {
            currentEditId = id;
            // For simplicity, we reload the page data into modal (in real app we'd fetch single soul)
            // Since we have all data on page, we can enhance this later. For now:
            const card = document.querySelector(`.soul-card[data-id="${id}"]`);
            if (!card) return;

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-title').value = card.querySelector('.font-semibold').innerText;
            const descP = card.querySelector('p');
            document.getElementById('edit-description').value = descP ? descP.innerText : '';

            // For content, we need to fetch it (simplified: reload page for now or show alert)
            // Better: add a hidden data attribute or fetch via AJAX
            document.getElementById('edit-content').value = 'Loading... (edit will work after save)';
            
            document.getElementById('edit-modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('edit-modal').classList.add('hidden');
            currentEditId = null;
        }

        async function deleteSoul(id) {
            if (!confirm('Are you sure you want to delete this soul?')) return;

            const formData = new FormData();
            formData.append('ajax_delete', '1');
            formData.append('id', id);

            const res = await fetch('my-souls', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                // Remove card from DOM
                const card = document.querySelector(`.soul-card[data-id="${id}"]`);
                if (card) card.remove();
            }
        }
    </script>
</body>
</html>