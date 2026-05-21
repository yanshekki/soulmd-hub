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

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$topDomains = $pdo->query("SELECT name FROM tags_domain ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);
$topCompatibilities = $pdo->query("SELECT name FROM tags_compatibility ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT s.*, c.icon as role_icon, c.name as role_name 
    FROM souls s 
    LEFT JOIN categories c ON s.role = c.slug 
    WHERE s.user_id = ? 
    ORDER BY s.created_at DESC
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

$pageTitle = 'My Souls';
$pageDesc = 'Manage and edit your uploaded AI personalities.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-10 border-b border-white/10 pb-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter">My Souls</h1>
            <p class="text-zinc-400 mt-1">Manage and edit your uploaded AI personalities</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="/profile/<?= rawurlencode($_SESSION['username'] ?? '') ?>" target="_blank" class="px-5 py-2.5 text-sm border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition flex items-center gap-2">
                <i class="fas fa-external-link-alt text-[10px] text-zinc-500"></i> View Profile
            </a>
            <a href="/my-api" class="px-5 py-2.5 text-sm border border-emerald-500/30 text-emerald-400 rounded-2xl hover:bg-emerald-900/10 transition">My API Key</a>
            <a href="/upload" class="px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center gap-2 shadow-lg">
                <i class="fas fa-plus"></i> New Soul
            </a>
        </div>
    </div>

    <?php if (empty($mySouls)): ?>
        <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl">
            <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500"><i class="fas fa-folder-open text-3xl"></i></div>
            <h2 class="text-2xl font-semibold mb-2">No souls shared yet</h2>
            <a href="/upload" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg mt-4">Upload your first soul</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="souls-list">
            <?php foreach ($mySouls as $soul): ?>
                <div class="soul-card bg-zinc-900/60 border border-white/10 rounded-3xl p-6 hover:border-emerald-400/40 transition-all flex flex-col justify-between backdrop-blur-sm shadow-lg" data-id="<?= $soul['id'] ?>">
                    <div>
                        <div class="flex justify-between items-start gap-4 mb-3">
                            <div>
                                <div class="font-bold text-xl text-white tracking-tight mb-1"><?= htmlspecialchars($soul['title']) ?></div>
                                <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                    <span><?= htmlspecialchars($soul['role_icon'] ?? '✨') ?> <?= htmlspecialchars($soul['role_name'] ?? 'Unassigned') ?></span><span>•</span><span><?= date('M j, Y', strtotime($soul['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="flex gap-2 shrink-0 flex-col items-end">
                                <span class="text-[11px] px-2.5 py-1 rounded-full font-medium border <?= $soul['is_public'] ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-zinc-800 text-zinc-400 border-white/5' ?>">
                                    <i class="fas <?= $soul['is_public'] ? 'fa-globe' : 'fa-lock' ?> mr-1"></i><?= $soul['is_public'] ? 'Public' : 'Private' ?>
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-medium border <?= $soul['file_type'] === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20' ?>">
                                    <?= $soul['file_type'] === 'full_soul_folder' ? 'Modular' : '.md' ?>
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
                            <span><i class="fas fa-code-branch mr-1 text-emerald-500"></i><b class="text-zinc-300"><?= $soul['fork_count'] ?></b> forks</span>
                            <span><i class="fas fa-heart mr-1 text-red-500"></i><b class="text-zinc-300"><?= $soul['like_count'] ?></b> likes</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <button onclick="editSoul(<?= $soul['id'] ?>)" class="px-4 py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-xl border border-white/5 transition">Edit</button>
                            <a href="/soul-versions/<?= $soul['id'] ?>" class="px-3 py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-zinc-200 rounded-xl border border-white/5 transition"><i class="fas fa-history"></i></a>
                            <button onclick="deleteSoul(<?= $soul['id'] ?>)" class="p-2 text-xs text-zinc-500 hover:text-red-400 transition"><i class="far fa-trash-alt text-base"></i></button>
                            <?php $seoUrl = "/soul/" . rawurlencode($_SESSION['username']) . "/" . $soul['id'] . "/" . makeSlug($soul['role']) . "/" . makeSlug($soul['title']); ?>
                            <a href="<?= $seoUrl ?>" class="px-4 py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow">View</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="edit-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/20 shrink-0">
            <h3 class="text-2xl font-bold tracking-tight">Edit Modular AI Soul</h3>
            <button type="button" onclick="closeModal()" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
        </div>

        <form id="edit-form" onsubmit="handleEdit(event)" class="flex flex-col flex-grow overflow-hidden">
            <div class="p-6 overflow-y-auto custom-scrollbar flex-grow space-y-6">
                <input type="hidden" id="edit-id" name="id">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400">Title</label>
                        <input id="edit-title" type="text" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm shadow-inner">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400">Visibility</label>
                        <select id="edit-public" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm shadow-inner">
                            <option value="1">🌐 Public (Hub)</option>
                            <option value="0">🔒 Private</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400">Short Description</label>
                    <textarea id="edit-description" rows="2" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm shadow-inner"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400">Role</label>
                        <select id="edit-role" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2.5 focus:outline-none focus:border-emerald-400 text-sm shadow-inner">
                            <option value="">Select role</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['icon'] ?? '✨') ?> <?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400">Domain Tags</label>
                        <div class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 min-h-[42px] flex flex-wrap items-center gap-1.5 focus-within:border-emerald-400 transition cursor-text shadow-inner" onclick="document.getElementById('domain-input').focus()">
                            <div id="domain-tags" class="flex flex-wrap gap-1.5 empty:hidden"></div>
                            <input type="text" id="domain-input" list="domain-options" class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[60px] text-xs p-0 m-0 text-white">
                            <input type="hidden" id="edit-domain">
                        </div>
                        <datalist id="domain-options">
                            <?php foreach ($topDomains as $tag): ?>
                                <option value="<?= htmlspecialchars($tag) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400">Compatibility</label>
                        <div class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2 min-h-[42px] flex flex-wrap items-center gap-1.5 focus-within:border-emerald-400 transition cursor-text shadow-inner" onclick="document.getElementById('compatibility-input').focus()">
                            <div id="compatibility-tags" class="flex flex-wrap gap-1.5 empty:hidden"></div>
                            <input type="text" id="compatibility-input" list="compatibility-options" class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[60px] text-xs p-0 m-0 text-white">
                            <input type="hidden" id="edit-compatibility">
                        </div>
                        <datalist id="compatibility-options">
                            <?php foreach ($topCompatibilities as $tag): ?>
                                <option value="<?= htmlspecialchars($tag) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400">Modular Files Editor</label>
                    <div class="border border-white/10 rounded-2xl overflow-hidden flex flex-col md:flex-row bg-zinc-950 min-h-[300px]">
                        <div class="w-full md:w-48 bg-zinc-900 border-b md:border-b-0 md:border-r border-white/10 flex flex-col">
                            <div class="p-2 border-b border-white/10 text-[10px] font-bold text-zinc-500 uppercase tracking-wider flex justify-between items-center">
                                Files <button type="button" onclick="openAddFileModal()" class="text-emerald-400 hover:text-emerald-300 transition"><i class="fas fa-plus"></i></button>
                            </div>
                            <div id="modal-file-list" class="flex-grow overflow-y-auto p-1 space-y-1 custom-scrollbar"></div>
                        </div>
                        <div class="flex-1 flex flex-col relative">
                            <div class="bg-zinc-900 border-b border-white/10 px-3 py-2 text-xs font-mono text-zinc-300 flex justify-between items-center">
                                <span id="modal-current-filename">Loading...</span>
                                <button type="button" id="modal-btn-delete-file" onclick="editModalFileEditor.deleteCurrentFile()" class="text-red-400 hover:text-red-300 hidden transition"><i class="fas fa-trash-alt"></i></button>
                            </div>
                            <textarea id="modal-file-editor-textarea" class="flex-1 bg-transparent p-4 focus:outline-none font-mono text-xs text-zinc-300 resize-none custom-scrollbar"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" id="edit-final-payload" name="content">

            <div class="p-4 border-t border-white/5 bg-zinc-900 shrink-0 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 border border-white/10 rounded-xl text-sm font-medium hover:bg-white/5 transition">Cancel</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg hover:bg-emerald-400 transition">
                    <span id="save-text"><i class="fas fa-save mr-1"></i> Save Changes</span>
                    <span id="loading-spinner" class="hidden animate-spin h-4 w-4 border-2 border-black border-t-transparent rounded-full"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="add-file-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[60] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-md w-full flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" id="add-file-content">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/30">
            <h3 class="text-xl font-bold tracking-tight text-white"><i class="fas fa-plus-circle text-emerald-400 mr-2"></i>Add Module File</h3>
            <button type="button" onclick="closeAddFileModal()" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-lg"></i></button>
        </div>
        <div class="p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium mb-3 text-zinc-400">Suggested Modules</label>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" onclick="addSpecificFile('STYLE.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-purple-400/50 hover:bg-purple-400/10 text-zinc-300 transition text-left text-sm"><i class="fas fa-palette text-purple-400 w-4 text-center"></i> STYLE.md</button>
                    <button type="button" onclick="addSpecificFile('RULES.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-red-400/50 hover:bg-red-400/10 text-zinc-300 transition text-left text-sm"><i class="fas fa-shield-alt text-red-400 w-4 text-center"></i> RULES.md</button>
                    <button type="button" onclick="addSpecificFile('SKILL.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-amber-400/50 hover:bg-amber-400/10 text-zinc-300 transition text-left text-sm"><i class="fas fa-tools text-amber-400 w-4 text-center"></i> SKILL.md</button>
                    <button type="button" onclick="addSpecificFile('MEMORY.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-blue-400/50 hover:bg-blue-400/10 text-zinc-300 transition text-left text-sm"><i class="fas fa-memory text-blue-400 w-4 text-center"></i> MEMORY.md</button>
                    <button type="button" onclick="addSpecificFile('CONTEXT.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-cyan-400/50 hover:bg-cyan-400/10 text-zinc-300 transition text-left text-sm"><i class="fas fa-globe text-cyan-400 w-4 text-center"></i> CONTEXT.md</button>
                    <button type="button" onclick="addSpecificFile('prompts/user.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-green-400/50 hover:bg-green-400/10 text-zinc-300 transition text-left text-sm"><i class="fas fa-folder text-green-400 w-4 text-center"></i> prompts/</button>
                </div>
            </div>
            <div class="relative flex items-center py-2"><div class="flex-grow border-t border-white/10"></div><span class="flex-shrink-0 mx-4 text-zinc-500 text-xs uppercase tracking-widest">or custom path</span><div class="flex-grow border-t border-white/10"></div></div>
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Filename / Folder Path</label>
                <div class="flex gap-2">
                    <input type="text" id="custom-filename-input" placeholder="e.g. docs/guide.md" class="flex-1 bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm text-white" onkeydown="if(event.key === 'Enter') { event.preventDefault(); addCustomFile(); }">
                    <button type="button" onclick="addCustomFile()" class="px-4 py-2.5 bg-zinc-800 text-white rounded-xl hover:bg-zinc-700 transition font-medium text-sm border border-white/5">Add</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }

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
                tagEl.className = 'inline-flex items-center gap-1 bg-emerald-900 text-emerald-400 px-2 py-0.5 rounded text-[11px] font-medium border border-emerald-500/10';
                tagEl.innerHTML = `${escapeHTML(tag)} <button type="button" class="hover:text-white" onclick="removeModalTag('${inputId}', ${idx})"><i class="fas fa-times text-[10px]"></i></button>`;
                tagsContainer.appendChild(tagEl);
            });
            hiddenInput.value = tags.join(', ');
        };
        
        const addTag = (val) => {
            const newTags = val.split(',').map(t => t.trim().replace(/^#+/g, '')).filter(Boolean);
            newTags.forEach(t => { if (!tags.includes(t)) tags.push(t); });
            visibleInput.value = '';
            renderTags();
        };
        
        visibleInput.addEventListener('change', function() { addTag(this.value); });
        visibleInput.addEventListener('keydown', function(e) {
            if (e.key === ',' || e.key === 'Enter') { e.preventDefault(); addTag(this.value); } 
            else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) { tags.pop(); renderTags(); }
        });
        
        modalTagInputs[inputId] = {
            setTags: (str) => { tags = str ? str.split(',').map(t => t.trim().replace(/^#+/g, '')).filter(Boolean) : []; renderTags(); },
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

    class MultiFileEditor {
        constructor() {
            this.files = {};
            this.activeFile = null;
            this.fileListEl = document.getElementById('modal-file-list');
            this.editorEl = document.getElementById('modal-file-editor-textarea');
            this.filenameEl = document.getElementById('modal-current-filename');
            this.btnDelete = document.getElementById('modal-btn-delete-file');
            
            this.editorEl.addEventListener('input', (e) => {
                if (this.activeFile) this.files[this.activeFile] = e.target.value;
            });
        }
        loadData(rawContent) {
            this.files = {};
            try {
                if (rawContent.trim().startsWith('{')) {
                    this.files = JSON.parse(rawContent);
                } else {
                    this.files['SOUL.md'] = rawContent;
                }
            } catch(e) { this.files['SOUL.md'] = rawContent; }
            if (Object.keys(this.files).length === 0) this.files['SOUL.md'] = '';
            this.renderFileList();
            this.switchFile(Object.keys(this.files)[0]);
        }
        renderFileList() {
            this.fileListEl.innerHTML = '';
            Object.keys(this.files).forEach(filename => {
                const btn = document.createElement('button');
                btn.type = 'button';
                const isActive = filename === this.activeFile;
                btn.className = `w-full text-left px-2 py-1.5 rounded text-xs font-mono transition flex items-start gap-1.5 ${isActive ? 'bg-emerald-500/20 text-emerald-400' : 'text-zinc-400 hover:bg-white/5'}`;
                
                let icon = 'fa-file-alt';
                const nameUpper = filename.toUpperCase();
                if(nameUpper.includes('SOUL')) icon = 'fa-brain';
                else if(nameUpper.includes('STYLE')) icon = 'fa-palette text-purple-400';
                else if(nameUpper.includes('RULE')) icon = 'fa-shield-alt text-red-400';
                else if(nameUpper.includes('SKILL')) icon = 'fa-tools text-amber-400';
                else if(nameUpper.includes('MEMORY')) icon = 'fa-memory text-blue-400';
                else if(nameUpper.includes('CONTEXT')) icon = 'fa-globe text-cyan-400';
                else if(nameUpper.includes('PROMPT')) icon = 'fa-terminal text-green-400';
                else if(nameUpper.endsWith('.JSON')) icon = 'fa-code text-yellow-400';

                let displayHtml = '';
                const safeFilename = escapeHTML(filename);
                if (filename.includes('/')) {
                    const parts = filename.split('/');
                    const name = escapeHTML(parts.pop());
                    const path = escapeHTML(parts.join('/'));
                    displayHtml = `<div class="flex flex-col overflow-hidden"><span class="text-[9px] text-zinc-500 truncate leading-none mb-0.5">${path}/</span><span class="truncate leading-tight">${name}</span></div>`;
                } else {
                    displayHtml = `<span class="truncate mt-0.5">${safeFilename}</span>`;
                }

                btn.innerHTML = `<i class="fas ${icon} w-3 text-center shrink-0 mt-1"></i> ${displayHtml}`;
                btn.onclick = () => this.switchFile(filename);
                this.fileListEl.appendChild(btn);
            });
        }
        switchFile(filename) {
            this.activeFile = filename;
            this.filenameEl.innerText = filename;
            this.editorEl.value = this.files[filename] || '';
            this.btnDelete.classList.toggle('hidden', Object.keys(this.files).length <= 1);
            this.renderFileList();
        }
        deleteCurrentFile() {
            if (Object.keys(this.files).length <= 1) return alert("You must have at least one file.");
            if (!confirm(`Delete ${this.activeFile}?`)) return;
            delete this.files[this.activeFile];
            this.switchFile(Object.keys(this.files)[0]);
        }
        getPayload() {
            const keys = Object.keys(this.files);
            if (keys.length === 1 && !keys[0].includes('/')) return this.files[keys[0]]; 
            return JSON.stringify(this.files, null, 2);
        }
    }
    const editModalFileEditor = new MultiFileEditor();

    function openAddFileModal() {
        const modal = document.getElementById('add-file-modal');
        const content = document.getElementById('add-file-content');
        modal.classList.remove('hidden');
        document.getElementById('custom-filename-input').value = '';
        setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); content.classList.add('scale-100'); }, 10);
    }

    function closeAddFileModal() {
        const modal = document.getElementById('add-file-modal');
        const content = document.getElementById('add-file-content');
        modal.classList.add('opacity-0'); content.classList.remove('scale-100'); content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    function processNewFileName(name) {
        if (!name) return;
        name = name.trim().replace(/\\/g, '/').replace(/^\/+|\/+$/g, ''); 
        if(!name.toLowerCase().endsWith('.md') && !name.toLowerCase().endsWith('.txt') && !name.toLowerCase().endsWith('.json')) name += '.md';
        
        if (editModalFileEditor.files[name] !== undefined) return alert("File already exists!");
        editModalFileEditor.files[name] = '';
        editModalFileEditor.switchFile(name);
        closeAddFileModal();
    }

    function addSpecificFile(name) { processNewFileName(name); }
    function addCustomFile() { processNewFileName(document.getElementById('custom-filename-input').value); }

    let currentEditId = null;

    async function editSoul(id) {
        currentEditId = id;
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-title').value = 'Loading...';
        document.getElementById('edit-modal').classList.remove('hidden');

        try {
            const res = await fetch(`/api/soul/${id}`);
            const result = await res.json();
            
            if (result.success) {
                const soul = result.data;
                document.getElementById('edit-title').value = soul.title;
                document.getElementById('edit-description').value = soul.description || '';
                document.getElementById('edit-role').value = soul.role || '';
                document.getElementById('edit-public').value = soul.is_public;
                modalTagInputs['domain'].setTags(soul.domain);
                modalTagInputs['compatibility'].setTags(soul.compatibility);
                
                editModalFileEditor.loadData(soul.content);
            } else {
                alert(result.error || 'Failed to fetch soul details'); 
                closeModal();
            }
        } catch(e) { alert('Network error.'); closeModal(); }
    }

    async function handleEdit(e) {
        e.preventDefault();
        document.getElementById('edit-final-payload').value = editModalFileEditor.getPayload();

        const btn = e.target.querySelector('button[type="submit"]');
        const text = btn.querySelector('#save-text');
        const spinner = btn.querySelector('#loading-spinner');
        text.classList.add('hidden'); spinner.classList.remove('hidden');

        const payload = {
            title: document.getElementById('edit-title').value,
            description: document.getElementById('edit-description').value,
            content: document.getElementById('edit-final-payload').value,
            role: document.getElementById('edit-role').value,
            domain: document.getElementById('edit-domain').value,
            compatibility: document.getElementById('edit-compatibility').value,
            is_public: parseInt(document.getElementById('edit-public').value)
        };

        try {
            const res = await fetch(`/api/soul/${currentEditId}`, { 
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.success) { 
                closeModal(); location.reload(); 
            } else { 
                alert(data.error); text.classList.remove('hidden'); spinner.classList.add('hidden'); 
            }
        } catch(e) { alert('Network error.'); text.classList.remove('hidden'); spinner.classList.add('hidden'); }
    }

    function closeModal() { document.getElementById('edit-modal').classList.add('hidden'); currentEditId = null; }

    async function deleteSoul(id) {
        if (!confirm('Are you sure you want to permanently delete this AI soul?')) return;
        try {
            const res = await fetch(`/api/soul/${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (data.success) { location.reload(); } else { alert(data.error || 'Failed to delete'); }
        } catch(e) { alert('Network error.'); }
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>