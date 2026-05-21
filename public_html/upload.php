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

// 前端渲染需要的基礎數據 (維持現有功能)
$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$topDomains = $pdo->query("SELECT name FROM tags_domain ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);
$topCompatibilities = $pdo->query("SELECT name FROM tags_compatibility ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

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
        if (stripos($presetRole, 'Engineer') !== false || stripos($presetRole, 'Coder') !== false || stripos($presetRole, 'Developer') !== false) { $presetRole = 'Developer'; }
        elseif (stripos($presetRole, 'Writer') !== false || stripos($presetRole, 'Copywriter') !== false) { $presetRole = 'Writer'; }
        elseif (stripos($presetRole, 'Assistant') !== false) { $presetRole = 'Personal Assistant'; }
        else { $presetRole = 'Other'; }
    }
}

unset($_SESSION['preset_title'], $_SESSION['preset_content'], $_SESSION['preset_role']);

$pageTitle = 'Upload Soul';
$pageDesc = 'Upload your AI personality as .md or full modular folder.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

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

    <div id="success-box" class="hidden bg-emerald-900/50 border border-emerald-500 p-6 rounded-3xl mb-8 text-lg shadow-lg"></div>
    <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-6 rounded-3xl mb-8 shadow-lg"><i class="fas fa-exclamation-circle mr-2"></i><span id="error-msg"></span></div>

    <form id="upload-form" class="space-y-8">
        <div>
            <label class="block text-sm font-medium mb-2 text-zinc-300">Soul Title <span class="text-red-400">*</span></label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($presetTitle) ?>" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 text-lg focus:outline-none focus:border-emerald-400 shadow-inner">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2 text-zinc-300">Short Description</label>
            <textarea id="description" name="description" rows="2" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400 shadow-inner"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Role</label>
                <select id="role" name="role" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-5 py-4 focus:outline-none focus:border-emerald-400 shadow-inner">
                    <option value="">Select role</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $presetRole === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['icon'] ?? '✨') ?> <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="Other" <?= $presetRole === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Domain Tags</label>
                <div class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-4 py-3 min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition cursor-text shadow-inner" onclick="document.getElementById('domain-input').focus()">
                    <div id="domain-tags" class="flex flex-wrap gap-2 empty:hidden"></div>
                    <input type="text" id="domain-input" list="domain-options" placeholder="Tech, Content..." class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[100px] text-sm p-0 m-0 text-white">
                    <input type="hidden" id="domain" name="domain" value="">
                </div>
                <datalist id="domain-options">
                    <?php foreach ($topDomains as $tag): ?>
                        <option value="<?= htmlspecialchars($tag) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Compatibility</label>
                <div class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-4 py-3 min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition cursor-text shadow-inner" onclick="document.getElementById('compatibility-input').focus()">
                    <div id="compatibility-tags" class="flex flex-wrap gap-2 empty:hidden"></div>
                    <input type="text" id="compatibility-input" list="compatibility-options" placeholder="Claude, GPT-4o..." class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[100px] text-sm p-0 m-0 text-white">
                    <input type="hidden" id="compatibility" name="compatibility" value="">
                </div>
                <datalist id="compatibility-options">
                    <?php foreach ($topCompatibilities as $tag): ?>
                        <option value="<?= htmlspecialchars($tag) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-3 text-zinc-300">Content <span class="text-red-400">*</span></label>
            
            <div class="flex border-b border-white/20 mb-6 overflow-x-auto">
                <button type="button" onclick="switchUploadTab(0)" class="upload-tab-btn flex-1 py-4 text-sm font-medium border-b-2 border-emerald-400 text-emerald-400 whitespace-nowrap"><i class="fas fa-layer-group mr-2"></i> Visual Editor</button>
                <button type="button" onclick="switchUploadTab(1)" class="upload-tab-btn flex-1 py-4 text-sm font-medium text-zinc-400 border-b-2 border-transparent hover:text-white whitespace-nowrap"><i class="fas fa-code mr-2"></i> Raw JSON / Paste</button>
                <button type="button" onclick="switchUploadTab(2)" class="upload-tab-btn flex-1 py-4 text-sm font-medium text-zinc-400 border-b-2 border-transparent hover:text-white whitespace-nowrap"><i class="fas fa-file-archive mr-2"></i> Upload File (.md/.zip)</button>
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
                <textarea id="content-raw" rows="14" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-5 font-mono text-sm focus:outline-none focus:border-emerald-400 shadow-inner" placeholder="Paste single markdown text OR full JSON folder object here..."><?= htmlspecialchars($presetContent) ?></textarea>
            </div>

            <div id="tab-zip" class="upload-tab-content hidden">
                <div onclick="document.getElementById('file-input').click()" class="border-2 border-dashed border-white/30 rounded-3xl p-12 text-center hover:border-emerald-400 transition cursor-pointer bg-zinc-900/50">
                    <input type="file" id="file-input" accept=".md,.txt,.zip,.json" class="hidden">
                    <i class="fas fa-cloud-upload-alt text-5xl mb-4 text-zinc-400"></i>
                    <div class="font-medium text-lg">Drag & drop or click to upload</div>
                    <div class="text-xs text-zinc-400 mt-2">Supports single .md file or a full configuration .zip bundle</div>
                </div>
            </div>
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
                    <button type="button" onclick="addSpecificFile('prompts/user.md')" class="flex items-center gap-2 p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-green-400/50 hover:bg-green-400/10 text-zinc-300 transition text-left text-sm">
                        <i class="fas fa-folder text-green-400 w-4 text-center"></i> prompts/
                    </button>
                </div>
            </div>
            
            <div class="relative flex items-center py-2">
                <div class="flex-grow border-t border-white/10"></div>
                <span class="flex-shrink-0 mx-4 text-zinc-500 text-xs uppercase tracking-widest">or custom path</span>
                <div class="flex-grow border-t border-white/10"></div>
            </div>

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
    // --- Tags Input System ---
    function setupTagInput(inputId) {
        const hiddenInput = document.getElementById(inputId);
        const visibleInput = document.getElementById(inputId + '-input');
        const tagsContainer = document.getElementById(inputId + '-tags');
        // 🚨 完美修復：初始化讀取時，過濾已存在的髒資料 (去掉前綴 #)
        let tags = hiddenInput.value ? hiddenInput.value.split(',').map(t => t.trim().replace(/^#+/g, '')).filter(Boolean) : [];

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
            // 🚨 完美修復：利用正則表達式自動去走用家手殘打入的 '#'，確保 DB 純淨
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
        renderTags();
    }

    window.removeTag = function(inputId, index) {
        const hiddenInput = document.getElementById(inputId);
        let tags = hiddenInput.value.split(',').map(t => t.trim().replace(/^#+/g, '')).filter(Boolean);
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
                btn.className = `w-full text-left px-3 py-2 rounded-lg text-sm font-mono transition flex items-start gap-2 ${isActive ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'text-zinc-400 hover:bg-white/5 border border-transparent'}`;
                
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
                if (filename.includes('/')) {
                    const parts = filename.split('/');
                    const name = parts.pop();
                    const path = parts.join('/');
                    displayHtml = `<div class="flex flex-col overflow-hidden"><span class="text-[9px] text-zinc-500 truncate leading-none mb-0.5">${path}/</span><span class="truncate leading-tight">${name}</span></div>`;
                } else {
                    displayHtml = `<span class="truncate mt-0.5">${filename}</span>`;
                }

                btn.innerHTML = `<i class="fas ${icon} w-4 text-center shrink-0 mt-1"></i> ${displayHtml}`;
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

    const fileEditor = new MultiFileEditor();

    // --- Add File Modal (Popup Control) ---
    function openAddFileModal() {
        const modal = document.getElementById('add-file-modal');
        modal.classList.remove('hidden');
        document.getElementById('custom-filename-input').value = '';
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.firstElementChild.classList.remove('scale-95');
            modal.firstElementChild.classList.add('scale-100');
        }, 10);
    }

    function closeAddFileModal() {
        const modal = document.getElementById('add-file-modal');
        modal.classList.add('opacity-0');
        modal.firstElementChild.classList.remove('scale-100');
        modal.firstElementChild.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    function processNewFileName(name) {
        if (!name) return;
        name = name.trim().replace(/\\/g, '/');
        name = name.replace(/^\/+|\/+$/g, ''); 
        if(!name.toLowerCase().endsWith('.md') && !name.toLowerCase().endsWith('.txt') && !name.toLowerCase().endsWith('.json')) name += '.md';
        
        if (fileEditor.files[name] !== undefined) {
            alert("File already exists!");
            return;
        }
        fileEditor.files[name] = '';
        fileEditor.switchFile(name);
        closeAddFileModal();
    }

    function addSpecificFile(name) { processNewFileName(name); }
    function addCustomFile() { processNewFileName(document.getElementById('custom-filename-input').value); }

    // --- Browser File Reading & JSZip Extraction Logic ---
    let uploadedContentStr = '';

    document.getElementById('file-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const ext = file.name.split('.').pop().toLowerCase();
        
        // UI 更新顯示已選取檔案
        document.getElementById('tab-zip').innerHTML = `
            <div class="text-emerald-400 flex flex-col items-center justify-center gap-2 py-8 bg-zinc-900/50 rounded-3xl border-2 border-emerald-400/30">
                <i class="fas fa-check-circle text-3xl"></i>
                <span class="font-medium">${file.name}</span>
                <span class="text-xs text-zinc-500">Ready to upload</span>
            </div>`;

        if (ext === 'md' || ext === 'txt' || ext === 'json') {
            const reader = new FileReader();
            reader.onload = function(evt) {
                uploadedContentStr = evt.target.result;
            };
            reader.readAsText(file);
        } else if (ext === 'zip') {
            const reader = new FileReader();
            reader.onload = function(evt) {
                JSZip.loadAsync(evt.target.result).then(async function(zip) {
                    const extractedFiles = {};
                    const promises = [];

                    zip.forEach(function (relativePath, zipEntry) {
                        if (!zipEntry.dir && (relativePath.endsWith('.md') || relativePath.endsWith('.txt') || relativePath.endsWith('.json'))) {
                            const promise = zipEntry.async("string").then(function (content) {
                                extractedFiles[relativePath] = content;
                            });
                            promises.push(promise);
                        }
                    });

                    await Promise.all(promises);
                    uploadedContentStr = JSON.stringify(extractedFiles, null, 2);
                }).catch(function(err) {
                    alert("Failed to parse zip file structure on client side.");
                });
            };
            reader.readAsArrayBuffer(file);
        } else {
            alert("Unsupported file extension.");
            uploadedContentStr = '';
        }
    });

    // --- AJAX Form Submission to API Endpoint ---
    const form = document.getElementById('upload-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('submit-btn');
        const text = document.getElementById('submit-text');
        const loading = document.getElementById('submit-loading');
        const errorBox = document.getElementById('error-box');
        const errorMsg = document.getElementById('error-msg');
        const successBox = document.getElementById('success-box');

        errorBox.classList.add('hidden');
        successBox.classList.add('hidden');

        // 根據目前切換的 Tab 決定提取哪種 Payload
        let finalContent = '';
        if (activeMainTab === 0) {
            finalContent = fileEditor.getPayload();
        } else if (activeMainTab === 1) {
            finalContent = document.getElementById('content-raw').value;
        } else {
            finalContent = uploadedContentStr;
        }

        if (!finalContent || finalContent.trim() === '') {
            errorMsg.innerText = "Soul Content is empty or hasn't loaded yet.";
            errorBox.classList.remove('hidden');
            return;
        }

        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-not-allowed');

        // 建構純 JSON 格式 Payload
        const payload = {
            title: document.getElementById('title').value,
            description: document.getElementById('description').value,
            role: document.getElementById('role').value,
            domain: document.getElementById('domain').value,
            compatibility: document.getElementById('compatibility').value,
            content: finalContent
        };

        try {
            // 呼叫純 JSON API 端點
            const res = await fetch('/api/souls', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                successBox.innerHTML = `✅ Soul uploaded successfully! <a href="${data.url}" class="underline text-emerald-400 font-bold">View it now</a>`;
                successBox.classList.remove('hidden');
                form.reset();
                // 還原虛擬檔案編輯器
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                errorMsg.innerText = data.error || "Failed to save soul.";
                errorBox.classList.remove('hidden');
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        } catch(err) {
            errorMsg.innerText = "Network Error. Please try again.";
            errorBox.classList.remove('hidden');
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>