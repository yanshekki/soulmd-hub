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

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();

$presetTitle = $_SESSION['preset_title'] ?? '';
$presetContent = $_SESSION['preset_content'] ?? '';
$presetRole = $_SESSION['preset_role'] ?? '';

if (!empty($presetRole)) {
    $matched = false;
    foreach ($categories as $cat) {
        if (strcasecmp($presetRole, $cat['name']) === 0 || strcasecmp($presetRole, $cat['slug']) === 0) {
            $presetRole = $cat['slug'];
            $matched = true;
            break;
        }
    }
    if (!$matched) {
        if (stripos($presetRole, 'Engineer') !== false || stripos($presetRole, 'Coder') !== false || stripos($presetRole, 'Developer') !== false) {
            $presetRole = 'Developer';
        } elseif (stripos($presetRole, 'Writer') !== false || stripos($presetRole, 'Copywriter') !== false) {
            $presetRole = 'Writer';
        } elseif (stripos($presetRole, 'Assistant') !== false) {
            $presetRole = 'Personal Assistant';
        } else {
            $presetRole = 'Other';
        }
    }
}

unset($_SESSION['preset_title']);
unset($_SESSION['preset_content']);
unset($_SESSION['preset_role']);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $role = $_POST['role'] ?? '';
    $domain = trim($_POST['domain'] ?? '');
    $compatibility = trim($_POST['compatibility'] ?? '');
    $content = '';

    $validSlugs = array_column($categories, 'slug');
    if (!empty($role) && !in_array($role, $validSlugs) && $role !== 'Other') {
        $error = 'Invalid category/role selected.';
    }

    if (empty($error)) {
        if (!empty($_POST['content'])) {
            $content = $_POST['content'];
        } elseif (!empty($_FILES['soul_file']['tmp_name'])) {
            $file = $_FILES['soul_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($ext === 'md') {
                $content = file_get_contents($file['tmp_name']);
            } elseif ($ext === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === TRUE) {
                    $files = [];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (str_ends_with($filename, '.md') || str_ends_with($filename, '.txt') || str_ends_with($filename, '.json')) {
                            $files[$filename] = $zip->getFromIndex($i);
                        }
                    }
                    $zip->close();
                    $content = json_encode($files, JSON_UNESCAPED_UNICODE);
                } else {
                    $error = 'Could not open zip file';
                }
            } else {
                $error = 'Only .md or .zip files are supported';
            }
        }
    }

    if (empty($error) && !empty($title) && !empty($content)) {
        try {
            $fileType = strpos(trim($content), '{') === 0 ? 'full_soul_folder' : 'single_md';
            $stmt = $pdo->prepare("INSERT INTO souls (user_id, title, description, content, file_type, role, domain, compatibility, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $content, $fileType, $role, $domain, $compatibility]);
            $newId = $pdo->lastInsertId();
            $message = "✅ Soul uploaded successfully! <a href='/soul/$newId' class='underline text-emerald-400'>View it now</a>";
        } catch (Exception $e) {
            $error = 'Failed to save soul';
        }
    } elseif (empty($error)) {
        $error = 'Title and content are required';
    }
}

$pageTitle = 'Upload Soul';
$pageDesc = 'Upload your AI personality as .md or full modular folder.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 w-full">
    <div class="flex justify-between items-center mb-10">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter">Upload Soul</h1>
            <p class="text-zinc-400 mt-1">Publish single prompts or modular agent architectures.</p>
        </div>
        <a href="/my-souls" class="text-sm text-zinc-400 hover:text-white flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> My Souls
        </a>
    </div>

    <?php if ($message): ?>
        <div class="bg-emerald-900/50 border border-emerald-500 p-6 rounded-3xl mb-8 text-lg shadow-lg"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-900/50 border border-red-500 p-6 rounded-3xl mb-8 shadow-lg"><i class="fas fa-exclamation-circle mr-2"></i><?= $error ?></div>
    <?php endif; ?>

    <form id="upload-form" enctype="multipart/form-data" class="space-y-8">
        <div>
            <label class="block text-sm font-medium mb-2 text-zinc-300">Soul Title <span class="text-red-400">*</span></label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? $presetTitle) ?>" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 text-lg focus:outline-none focus:border-emerald-400 shadow-inner">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2 text-zinc-300">Short Description</label>
            <textarea id="description" name="description" rows="2" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400 shadow-inner"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Role</label>
                <select id="role" name="role" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-5 py-4 focus:outline-none focus:border-emerald-400 shadow-inner">
                    <?php $selectedRole = $_POST['role'] ?? $presetRole; ?>
                    <option value="">Select role</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $selectedRole === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['icon'] ?? '✨') ?> <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="Other" <?= $selectedRole === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Domain Tags</label>
                <div class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-4 py-3 min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition cursor-text shadow-inner" onclick="document.getElementById('domain-input').focus()">
                    <div id="domain-tags" class="flex flex-wrap gap-2 empty:hidden"></div>
                    <input type="text" id="domain-input" list="domain-options" placeholder="Tech, Content..." class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[100px] text-sm p-0 m-0 text-white">
                    <input type="hidden" id="domain" name="domain" value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>">
                </div>
                <datalist id="domain-options"><option value="Tech"><option value="Content Creation"><option value="Finance & Business"><option value="Coding & Dev"><option value="Gaming"><option value="Education"><option value="Marketing"><option value="Productivity"><option value="Healthcare"></datalist>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Compatibility</label>
                <div class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-4 py-3 min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition cursor-text shadow-inner" onclick="document.getElementById('compatibility-input').focus()">
                    <div id="compatibility-tags" class="flex flex-wrap gap-2 empty:hidden"></div>
                    <input type="text" id="compatibility-input" list="compatibility-options" placeholder="Claude, GPT-4o..." class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[100px] text-sm p-0 m-0 text-white">
                    <input type="hidden" id="compatibility" name="compatibility" value="<?= htmlspecialchars($_POST['compatibility'] ?? '') ?>">
                </div>
                <datalist id="compatibility-options"><option value="Claude 3.5 Sonnet"><option value="GPT-4o"><option value="GPT-4"><option value="Gemini 1.5 Pro"><option value="DeepSeek-V3"><option value="Llama 3"><option value="Qwen 2.5"><option value="General LLM"></datalist>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-3 text-zinc-300">Content <span class="text-red-400">*</span></label>
            
            <div class="flex border-b border-white/20 mb-6 overflow-x-auto">
                <button type="button" onclick="switchUploadTab(0)" class="upload-tab-btn flex-1 py-4 text-sm font-medium border-b-2 border-emerald-400 text-emerald-400 whitespace-nowrap"><i class="fas fa-layer-group mr-2"></i> Visual Editor</button>
                <button type="button" onclick="switchUploadTab(1)" class="upload-tab-btn flex-1 py-4 text-sm font-medium text-zinc-400 border-b-2 border-transparent hover:text-white whitespace-nowrap"><i class="fas fa-code mr-2"></i> Raw JSON / Paste</button>
                <button type="button" onclick="switchUploadTab(2)" class="upload-tab-btn flex-1 py-4 text-sm font-medium text-zinc-400 border-b-2 border-transparent hover:text-white whitespace-nowrap"><i class="fas fa-file-archive mr-2"></i> Upload .ZIP</button>
            </div>

            <div id="tab-visual" class="upload-tab-content">
                <div class="border border-white/10 rounded-2xl overflow-hidden flex flex-col md:flex-row bg-zinc-950/50 shadow-inner min-h-[400px]">
                    <div class="w-full md:w-56 bg-zinc-900 border-b md:border-b-0 md:border-r border-white/10 flex flex-col">
                        <div class="p-3 border-b border-white/10 text-xs font-bold text-zinc-500 uppercase tracking-wider flex justify-between items-center">
                            Files <button type="button" onclick="openAddFileModal()" class="text-emerald-400 hover:text-emerald-300 transition"><i class="fas fa-plus"></i></button>
                        </div>
                        <div id="file-list" class="flex-grow overflow-y-auto p-2 space-y-1 custom-scrollbar"></div>
                    </div>
                    <div class="flex-1 flex flex-col relative">
                        <div class="bg-zinc-900 border-b border-white/10 px-4 py-2 text-sm font-mono text-zinc-300 flex justify-between items-center">
                            <span id="current-filename">SOUL.md</span>
                            <button type="button" id="btn-delete-file" onclick="fileEditor.deleteCurrentFile()" class="text-xs text-red-400 hover:text-red-300 hidden transition"><i class="fas fa-trash-alt"></i></button>
                        </div>
                        <textarea id="file-editor-textarea" class="flex-1 bg-transparent p-4 focus:outline-none font-mono text-sm text-zinc-300 resize-none custom-scrollbar" placeholder="Start typing..."></textarea>
                    </div>
                </div>
            </div>

            <div id="tab-raw" class="upload-tab-content hidden">
                <textarea id="content-raw" rows="14" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-5 font-mono text-sm focus:outline-none focus:border-emerald-400 shadow-inner" placeholder="Paste single markdown text OR full JSON folder object here..."><?= htmlspecialchars($_POST['content'] ?? $presetContent) ?></textarea>
            </div>

            <div id="tab-zip" class="upload-tab-content hidden">
                <div onclick="document.getElementById('file-input').click()" class="border-2 border-dashed border-white/30 rounded-3xl p-12 text-center hover:border-emerald-400 transition cursor-pointer bg-zinc-900/50">
                    <input type="file" id="file-input" name="soul_file" accept=".md,.txt,.zip,.json" class="hidden">
                    <i class="fas fa-cloud-upload-alt text-5xl mb-4 text-zinc-400"></i>
                    <div class="font-medium text-lg">Drag & drop or click to upload</div>
                    <div class="text-xs text-zinc-400 mt-2">.md or .zip (full modular folder)</div>
                </div>
            </div>
            
            <input type="hidden" name="content" id="final-payload">
        </div>

        <button type="submit" id="submit-btn" class="w-full py-6 bg-emerald-500 text-zinc-950 font-bold text-xl rounded-3xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg hover:scale-[1.01] transform duration-200">
            <span id="submit-text"><i class="fas fa-cloud-upload-alt mr-2"></i>Upload Soul</span>
            <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
        </button>
    </form>
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
                    <button type="button" onclick="addSpecificFile('STYLE.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-purple-400/50 hover:bg-purple-400/10 text-zinc-300 transition text-left text-sm">
                        <i class="fas fa-palette text-purple-400 w-4 text-center"></i> STYLE.md
                    </button>
                    <button type="button" onclick="addSpecificFile('RULES.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-red-400/50 hover:bg-red-400/10 text-zinc-300 transition text-left text-sm">
                        <i class="fas fa-shield-alt text-red-400 w-4 text-center"></i> RULES.md
                    </button>
                    <button type="button" onclick="addSpecificFile('SKILL.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-amber-400/50 hover:bg-amber-400/10 text-zinc-300 transition text-left text-sm">
                        <i class="fas fa-tools text-amber-400 w-4 text-center"></i> SKILL.md
                    </button>
                    <button type="button" onclick="addSpecificFile('MEMORY.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-blue-400/50 hover:bg-blue-400/10 text-zinc-300 transition text-left text-sm">
                        <i class="fas fa-memory text-blue-400 w-4 text-center"></i> MEMORY.md
                    </button>
                    <button type="button" onclick="addSpecificFile('CONTEXT.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-cyan-400/50 hover:bg-cyan-400/10 text-zinc-300 transition text-left text-sm">
                        <i class="fas fa-globe text-cyan-400 w-4 text-center"></i> CONTEXT.md
                    </button>
                    <button type="button" onclick="addSpecificFile('PROMPTS.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-green-400/50 hover:bg-green-400/10 text-zinc-300 transition text-left text-sm">
                        <i class="fas fa-terminal text-green-400 w-4 text-center"></i> PROMPTS.md
                    </button>
                </div>
            </div>
            
            <div class="relative flex items-center py-2">
                <div class="flex-grow border-t border-white/10"></div>
                <span class="flex-shrink-0 mx-4 text-zinc-500 text-xs uppercase tracking-widest">or custom</span>
                <div class="flex-grow border-t border-white/10"></div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Custom Filename</label>
                <div class="flex gap-2">
                    <input type="text" id="custom-filename-input" placeholder="e.g. DATA.json" class="flex-1 bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm text-white" onkeydown="if(event.key === 'Enter') { event.preventDefault(); addCustomFile(); }">
                    <button type="button" onclick="addCustomFile()" class="px-4 py-2.5 bg-zinc-800 text-white rounded-xl hover:bg-zinc-700 transition font-medium text-sm border border-white/5">Add</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- Tags System ---
    function setupTagInput(inputId) {
        const hiddenInput = document.getElementById(inputId);
        const visibleInput = document.getElementById(inputId + '-input');
        const tagsContainer = document.getElementById(inputId + '-tags');
        let tags = hiddenInput.value ? hiddenInput.value.split(',').map(t => t.trim()).filter(Boolean) : [];

        const renderTags = () => {
            tagsContainer.innerHTML = '';
            tags.forEach((tag, index) => {
                const tagEl = document.createElement('span');
                tagEl.className = 'inline-flex items-center gap-1.5 bg-emerald-900/40 text-emerald-400 px-3 py-1 rounded-full text-xs font-medium border border-emerald-500/20';
                tagEl.innerHTML = `${tag} <button type="button" class="hover:text-white focus:outline-none" onclick="removeTag('${inputId}', ${index})"><i class="fas fa-times"></i></button>`;
                tagsContainer.appendChild(tagEl);
            });
            hiddenInput.value = tags.join(', ');
            visibleInput.placeholder = tags.length > 0 ? '' : (inputId === 'domain' ? 'Tech, Content...' : 'Claude, GPT-4o...');
        };

        const addTag = (val) => {
            const newTags = val.split(',').map(t => t.trim()).filter(Boolean);
            newTags.forEach(t => { if (!tags.includes(t)) tags.push(t); });
            visibleInput.value = '';
            renderTags();
        };

        visibleInput.addEventListener('change', function() { addTag(this.value); });
        visibleInput.addEventListener('keydown', function(e) {
            if (e.key === ',' || e.key === 'Enter') { e.preventDefault(); addTag(this.value); } 
            else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) { tags.pop(); renderTags(); }
        });
        visibleInput.closest('form').addEventListener('submit', function() { if (visibleInput.value.trim()) addTag(visibleInput.value); });
        renderTags();
    }

    window.removeTag = function(inputId, index) {
        const hiddenInput = document.getElementById(inputId);
        let tags = hiddenInput.value.split(',').map(t => t.trim()).filter(Boolean);
        tags.splice(index, 1);
        hiddenInput.value = tags.join(', ');
        document.getElementById(inputId + '-input').focus();
        setupTagInput(inputId); 
    };

    setupTagInput('domain');
    setupTagInput('compatibility');

    // --- Tab Switcher ---
    let activeMainTab = 0;
    function switchUploadTab(n) {
        activeMainTab = n;
        document.querySelectorAll('.upload-tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById(['tab-visual', 'tab-raw', 'tab-zip'][n]).classList.remove('hidden');
        document.querySelectorAll('.upload-tab-btn').forEach((btn, i) => {
            btn.classList.toggle('border-emerald-400', i === n);
            btn.classList.toggle('text-emerald-400', i === n);
            btn.classList.toggle('border-transparent', i !== n);
            btn.classList.toggle('text-zinc-400', i !== n);
        });
    }

    // --- Multi-File Visual Builder Logic ---
    class MultiFileEditor {
        constructor() {
            this.files = {};
            this.activeFile = null;
            this.fileListEl = document.getElementById('file-list');
            this.editorEl = document.getElementById('file-editor-textarea');
            this.filenameEl = document.getElementById('current-filename');
            this.btnDelete = document.getElementById('btn-delete-file');
            
            const rawVal = document.getElementById('content-raw').value;
            try {
                if(rawVal.trim().startsWith('{')) {
                    this.files = JSON.parse(rawVal);
                } else if(rawVal.trim() !== '') {
                    this.files['SOUL.md'] = rawVal;
                }
            } catch(e) {}

            if (Object.keys(this.files).length === 0) {
                this.files['SOUL.md'] = '';
            }

            this.editorEl.addEventListener('input', (e) => {
                if (this.activeFile) this.files[this.activeFile] = e.target.value;
            });

            this.renderFileList();
            this.switchFile(Object.keys(this.files)[0]);
        }

        renderFileList() {
            this.fileListEl.innerHTML = '';
            Object.keys(this.files).forEach(filename => {
                const btn = document.createElement('button');
                btn.type = 'button';
                const isActive = filename === this.activeFile;
                btn.className = `w-full text-left px-3 py-2 rounded-lg text-sm font-mono transition flex items-center gap-2 ${isActive ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-zinc-400 hover:bg-white/5 border border-transparent'}`;
                
                let icon = 'fa-file-alt';
                const name = filename.toUpperCase();
                if(name.includes('SOUL')) icon = 'fa-brain';
                else if(name.includes('STYLE')) icon = 'fa-palette text-purple-400';
                else if(name.includes('RULE')) icon = 'fa-shield-alt text-red-400';
                else if(name.includes('SKILL')) icon = 'fa-tools text-amber-400';
                else if(name.includes('MEMORY')) icon = 'fa-memory text-blue-400';
                else if(name.includes('CONTEXT')) icon = 'fa-globe text-cyan-400';
                else if(name.includes('PROMPT')) icon = 'fa-terminal text-green-400';
                else if(name.endsWith('.JSON')) icon = 'fa-code text-yellow-400';

                btn.innerHTML = `<i class="fas ${icon} w-4 text-center"></i> <span class="truncate">${filename}</span>`;
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
            if (keys.length === 1) return this.files[keys[0]];
            return JSON.stringify(this.files, null, 2);
        }
    }

    const fileEditor = new MultiFileEditor();

    // --- Add File Modal Logic ---
    function openAddFileModal() {
        const modal = document.getElementById('add-file-modal');
        const content = document.getElementById('add-file-content');
        modal.classList.remove('hidden');
        document.getElementById('custom-filename-input').value = '';
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    function closeAddFileModal() {
        const modal = document.getElementById('add-file-modal');
        const content = document.getElementById('add-file-content');
        modal.classList.add('opacity-0');
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    function processNewFileName(name) {
        if (!name) return;
        name = name.trim();
        if(!name.toLowerCase().endsWith('.md') && !name.toLowerCase().endsWith('.txt') && !name.toLowerCase().endsWith('.json')) name += '.md';
        
        if (fileEditor.files[name] !== undefined) {
            alert("File already exists!");
            return;
        }
        fileEditor.files[name] = '';
        fileEditor.switchFile(name);
        closeAddFileModal();
    }

    function addSpecificFile(name) {
        processNewFileName(name);
    }

    function addCustomFile() {
        const name = document.getElementById('custom-filename-input').value;
        if(name) processNewFileName(name);
    }

    // --- Form Submit Interceptor ---
    const form = document.getElementById('upload-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const finalPayloadInput = document.getElementById('final-payload');
        if (activeMainTab === 0) {
            finalPayloadInput.value = fileEditor.getPayload();
        } else if (activeMainTab === 1) {
            finalPayloadInput.value = document.getElementById('content-raw').value;
        } else {
            finalPayloadInput.value = '';
        }

        const btn = document.getElementById('submit-btn');
        document.getElementById('submit-text').classList.add('hidden');
        document.getElementById('submit-loading').classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-not-allowed');

        const formData = new FormData(form);
        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const html = await res.text();
            document.body.innerHTML = html;
        } catch(e) {
            alert("Network Error. Please try again.");
            document.getElementById('submit-text').classList.remove('hidden');
            document.getElementById('submit-loading').classList.add('hidden');
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });

    document.getElementById('file-input').addEventListener('change', function() {
        if (this.files.length) {
            document.getElementById('tab-zip').innerHTML = `<div class="text-emerald-400 flex items-center justify-center gap-3 py-8 bg-zinc-900/50 rounded-3xl border-2 border-emerald-400/30"><i class="fas fa-check-circle text-3xl"></i><span class="font-medium">${this.files[0].name}</span></div>`;
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>