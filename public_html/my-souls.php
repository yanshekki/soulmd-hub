<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

if (isset($_POST['ajax_get'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $soul = $stmt->fetch();
    
    if ($soul) {
        echo json_encode(['success' => true, 'data' => $soul]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Soul not found or unauthorized']);
    }
    exit;
}

if (isset($_POST['ajax_delete'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM souls WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized to delete this soul or it does not exist.']);
    }
    exit;
}

if (isset($_POST['ajax_edit'])) {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $content = $_POST['content'];
    $role = $_POST['role'];
    $domain = trim($_POST['domain']);
    $compatibility = trim($_POST['compatibility']);
    $is_public = isset($_POST['is_public']) ? (int)$_POST['is_public'] : 0;

    $oldStmt = $pdo->prepare("SELECT title, content FROM souls WHERE id = ? AND user_id = ?");
    $oldStmt->execute([$id, $user_id]);
    $old = $oldStmt->fetch();

    if ($old) {
        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$id, $old['title'], $old['content']]);

        $fileType = strpos($content, '{') === 0 ? 'full_soul_folder' : 'single_md';
        
        $updStmt = $pdo->prepare("UPDATE souls SET title = ?, description = ?, content = ?, role = ?, domain = ?, compatibility = ?, is_public = ?, file_type = ? WHERE id = ? AND user_id = ?");
        $updStmt->execute([$title, $description, $content, $role, $domain, $compatibility, $is_public, $fileType, $id, $user_id]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized to edit this soul.']);
    }
    exit;
}

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();

$stmt = $pdo->prepare("
    SELECT s.*, c.icon as role_icon, c.name as role_name 
    FROM souls s 
    LEFT JOIN categories c ON s.role = c.slug 
    WHERE s.user_id = ? 
    ORDER BY s.created_at DESC
");
$stmt->execute([$user_id]);
$mySouls = $stmt->fetchAll();

$pageTitle = 'My Souls';
$pageDesc = 'Manage and edit your uploaded AI personalities.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-10 border-b border-white/10 pb-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter">My Souls</h1>
            <p class="text-zinc-400 mt-1">Manage and edit your uploaded AI personalities</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="/my-api" class="px-5 py-2.5 text-sm border border-emerald-500/30 text-emerald-400 rounded-2xl hover:bg-emerald-900/10 transition">My API Key</a>
            <a href="/upload" class="px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center gap-2 shadow-lg">
                <i class="fas fa-plus"></i> New Soul
            </a>
        </div>
    </div>

    <?php if (empty($mySouls)): ?>
        <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl">
            <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500">
                <i class="fas fa-folder-open text-3xl"></i>
            </div>
            <h2 class="text-2xl font-semibold mb-2">No souls shared yet</h2>
            <p class="text-zinc-400 mb-8 max-w-sm mx-auto text-sm">Start uploading your SOUL.md files or folder configs to share them with the hub.</p>
            <a href="/upload" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg">
                <i class="fas fa-cloud-upload-alt"></i> Upload your first soul
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="souls-list">
            <?php foreach ($mySouls as $soul): ?>
                <div class="soul-card bg-zinc-900/60 border border-white/10 rounded-3xl p-6 hover:border-emerald-400/40 transition-all flex flex-col justify-between backdrop-blur-sm" data-id="<?= $soul['id'] ?>">
                    <div>
                        <div class="flex justify-between items-start gap-4 mb-3">
                            <div>
                                <div class="font-bold text-xl text-white tracking-tight mb-1"><?= htmlspecialchars($soul['title']) ?></div>
                                <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                    <span><?= htmlspecialchars($soul['role_icon'] ?? '✨') ?> <?= htmlspecialchars($soul['role_name'] ?? 'Unassigned') ?></span>
                                    <span>•</span>
                                    <span><?= date('M j, Y', strtotime($soul['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="flex gap-2 shrink-0 flex-col items-end">
                                <span class="text-[11px] px-2.5 py-1 rounded-full font-medium border <?= $soul['is_public'] ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-zinc-800 text-zinc-400 border-white/5' ?>">
                                    <i class="fas <?= $soul['is_public'] ? 'fa-globe' : 'fa-lock' ?> mr-1"></i><?= $soul['is_public'] ? 'Public' : 'Private' ?>
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-medium border <?= $soul['file_type'] === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20' ?>">
                                    <?= $soul['file_type'] === 'full_soul_folder' ? 'Folder' : '.md' ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($soul['description']): ?>
                            <p class="text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed"><?= htmlspecialchars($soul['description']) ?></p>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-1.5 mb-6">
                            <?php 
                            $cardDomains = array_filter(array_map('trim', explode(',', $soul['domain'])));
                            foreach (array_slice($cardDomains, 0, 3) as $dTag): ?>
                                <span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded">#<?= htmlspecialchars($dTag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4 text-xs text-zinc-500">
                            <span><i class="fas fa-code-branch mr-1 text-emerald-500"></i><b><?= $soul['fork_count'] ?></b> forks</span>
                            <span><i class="fas fa-heart mr-1 text-red-500"></i><b><?= $soul['like_count'] ?></b> likes</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <button onclick="editSoul(<?= $soul['id'] ?>)" class="px-4 py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-xl border border-white/5 transition">Edit</button>
                            <a href="/soul-versions/<?= $soul['id'] ?>" class="px-3 py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-zinc-200 rounded-xl border border-white/5 transition" title="Version History"><i class="fas fa-history"></i></a>
                            <button onclick="deleteSoul(<?= $soul['id'] ?>)" class="p-2 text-xs text-zinc-500 hover:text-red-400 transition" title="Delete"><i class="far fa-trash-alt text-base"></i></button>
                            <a href="/soul/<?= $soul['id'] ?>" class="px-4 py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="edit-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-2xl w-full max-h-[92vh] flex flex-col overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/20">
            <h3 class="text-2xl font-bold tracking-tight">Edit AI Soul</h3>
            <button onclick="closeModal()" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
        </div>

        <form id="edit-form" onsubmit="handleEdit(event)" class="p-6 overflow-y-auto space-y-6 flex-grow">
            <input type="hidden" id="edit-id" name="id">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400">Title</label>
                    <input id="edit-title" type="text" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400">Visibility</label>
                    <select id="edit-public" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm">
                        <option value="1">🌐 Public (Hub)</option>
                        <option value="0">🔒 Private</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium mb-1.5 text-zinc-400">Short Description</label>
                <textarea id="edit-description" rows="2" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400">Role</label>
                    <select id="edit-role" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2.5 focus:outline-none focus:border-emerald-400 text-sm">
                        <option value="">Select role</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['icon'] ?? '✨') ?> <?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400">Domain Tags</label>
                    <div class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 min-h-[42px] flex flex-wrap items-center gap-1.5 focus-within:border-emerald-400 transition cursor-text" onclick="document.getElementById('domain-input').focus()">
                        <div id="domain-tags" class="flex flex-wrap gap-1.5 empty:hidden"></div>
                        <input type="text" id="domain-input" list="domain-options" class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[60px] text-xs p-0 m-0 text-white">
                        <input type="hidden" id="edit-domain">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400">Compatibility</label>
                    <div class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 min-h-[42px] flex flex-wrap items-center gap-1.5 focus-within:border-emerald-400 transition cursor-text" onclick="document.getElementById('compatibility-input').focus()">
                        <div id="compatibility-tags" class="flex flex-wrap gap-1.5 empty:hidden"></div>
                        <input type="text" id="compatibility-input" list="compatibility-options" class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[60px] text-xs p-0 m-0 text-white">
                        <input type="hidden" id="edit-compatibility">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium mb-1.5 text-zinc-400">Soul Prompt Content (.md / JSON Folder)</label>
                <textarea id="edit-content" rows="10" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-3 font-mono text-xs focus:outline-none focus:border-emerald-400 leading-relaxed"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-white/5 bg-zinc-900 sticky bottom-0 z-10">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 border border-white/10 rounded-xl text-sm font-medium hover:bg-white/5 transition">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg hover:bg-emerald-400 transition">
                    <span id="save-text"><i class="fas fa-save mr-1"></i> Save Changes</span>
                    <span id="loading-spinner" class="hidden animate-spin h-4 w-4 border-2 border-black border-t-transparent rounded-full"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<datalist id="domain-options">
    <option value="Tech"><option value="Content Creation"><option value="Finance & Business"><option value="Coding & Dev"><option value="Gaming"><option value="Education">
</datalist>
<datalist id="compatibility-options">
    <option value="Claude 3.5 Sonnet"><option value="GPT-4o"><option value="GPT-4"><option value="Gemini 1.5 Pro"><option value="DeepSeek-V3"><option value="General LLM">
</datalist>

<script>
    let currentEditId = null;

    const modalTagInputs = {};

    function setupModalTagInput(inputId) {
        const hiddenInput = document.getElementById('edit-' + inputId);
        const visibleInput = document.getElementById(inputId + '-input');
        const tagsContainer = document.getElementById(inputId + '-tags');
        
        let tags = [];

        const renderTags = () => {
            tagsContainer.innerHTML = '';
            tags.forEach((tag, idx) => {
                const tagEl = document.createElement('span');
                tagEl.className = 'inline-flex items-center gap-1 bg-emerald-950 text-emerald-400 px-2 py-0.5 rounded text-[11px] font-medium border border-emerald-500/10';
                tagEl.innerHTML = `${tag} <button type="button" class="hover:text-white" onclick="removeModalTag('${inputId}', ${idx})"><i class="fas fa-times text-[10px]"></i></button>`;
                tagsContainer.appendChild(tagEl);
            });
            hiddenInput.value = tags.join(', ');
        };

        const addTag = (val) => {
            const newTags = val.split(',').map(t => t.trim()).filter(Boolean);
            newTags.forEach(t => { if (!tags.includes(t)) tags.push(t); });
            visibleInput.value = '';
            renderTags();
        };

        visibleInput.addEventListener('change', function() { addTag(this.value); });
        visibleInput.addEventListener('keydown', function(e) {
            if (e.key === ',' || e.key === 'Enter') {
                e.preventDefault();
                addTag(this.value);
            } else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
                tags.pop();
                renderTags();
            }
        });

        modalTagInputs[inputId] = {
            setTags: (str) => {
                tags = str ? str.split(',').map(t => t.trim()).filter(Boolean) : [];
                renderTags();
            },
            getTags: () => tags
        };
    }

    window.removeModalTag = function(inputId, index) {
        const instance = modalTagInputs[inputId];
        let currentTags = instance.getTags();
        currentTags.splice(index, 1);
        instance.setTags(currentTags.join(', '));
        document.getElementById(inputId + '-input').focus();
    };

    setupModalTagInput('domain');
    setupModalTagInput('compatibility');

    async function editSoul(id) {
        currentEditId = id;
        
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-title').value = 'Loading...';
        document.getElementById('edit-description').value = 'Loading...';
        document.getElementById('edit-content').value = 'Loading actual database payload...';

        document.getElementById('edit-modal').classList.remove('hidden');

        const formData = new FormData();
        formData.append('ajax_get', '1');
        formData.append('id', id);

        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const result = await res.json();

            if (result.success) {
                const soul = result.data;
                document.getElementById('edit-title').value = soul.title;
                document.getElementById('edit-description').value = soul.description;
                document.getElementById('edit-content').value = soul.content;
                document.getElementById('edit-role').value = soul.role || '';
                document.getElementById('edit-public').value = soul.is_public;
                
                modalTagInputs['domain'].setTags(soul.domain);
                modalTagInputs['compatibility'].setTags(soul.compatibility);
            } else {
                alert(result.error || 'Failed to retrieve soul details.');
                closeModal();
            }
        } catch(e) {
            alert('Network error while fetching soul properties.');
            closeModal();
        }
    }

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
        formData.append('role', document.getElementById('edit-role').value);
        formData.append('domain', document.getElementById('edit-domain').value);
        formData.append('compatibility', document.getElementById('edit-compatibility').value);
        formData.append('is_public', document.getElementById('edit-public').value);

        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                closeModal();
                location.reload(); 
            } else {
                alert(data.error || 'Error saving changes');
                text.classList.remove('hidden');
                spinner.classList.add('hidden');
            }
        } catch(e) {
            alert('Network error while saving.');
            text.classList.remove('hidden');
            spinner.classList.add('hidden');
        }
    }

    function closeModal() {
        document.getElementById('edit-modal').classList.add('hidden');
        currentEditId = null;
    }

    async function deleteSoul(id) {
        if (!confirm('Are you sure you want to permanently delete this AI soul? This cannot be undone.')) return;

        const formData = new FormData();
        formData.append('ajax_delete', '1');
        formData.append('id', id);

        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const card = document.querySelector(`.soul-card[data-id="${id}"]`);
                if (card) card.remove();
                if (document.querySelectorAll('.soul-card').length === 0) {
                    location.reload();
                }
            } else {
                alert(data.error || 'Failed to delete soul.');
            }
        } catch(e) {
            alert('Network error while deleting.');
        }
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>