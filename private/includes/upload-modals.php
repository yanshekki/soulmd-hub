<?php
/**
 * SoulMD Hub - Upload Modals & Core Scripts Component
 * Included dynamically at the bottom of upload.php & edit.php
 * 🚀 Patched: Added loadData() method to support Edit Mode parsing
 */
?>

<div id="add-file-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[500] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border border-white/10 rounded-3xl max-w-md w-full max-h-[calc(100dvh-2rem)] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" id="add-file-content">
        <div class="p-5 sm:p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/30 shrink-0">
            <h3 class="text-lg sm:text-xl font-bold tracking-tight text-white"><i class="fas fa-plus-circle text-emerald-400 mr-2"></i><?= __('Add Module File') ?></h3>
            <button type="button" onclick="closeAddFileModal()" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-lg"></i></button>
        </div>
        <div class="p-5 sm:p-6 space-y-6 overflow-y-auto custom-scrollbar flex-grow">
            <div>
                <label class="block text-sm font-medium mb-3 text-zinc-400"><?= __('Suggested Modules') ?></label>
                <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                    <button type="button" onclick="addSpecificFile('STYLE.md')" class="flex items-center gap-2 p-2.5 sm:p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-purple-400/50 hover:bg-purple-400/10 text-zinc-300 transition text-left text-[11px] sm:text-sm"><i class="fas fa-palette text-purple-400 w-4 text-center"></i> STYLE.md</button>
                    <button type="button" onclick="addSpecificFile('RULES.md')" class="flex items-center gap-2 p-2.5 sm:p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-red-400/50 hover:bg-red-400/10 text-zinc-300 transition text-left text-[11px] sm:text-sm"><i class="fas fa-shield-alt text-red-400 w-4 text-center"></i> RULES.md</button>
                    <button type="button" onclick="addSpecificFile('SKILL.md')" class="flex items-center gap-2 p-2.5 sm:p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-amber-400/50 hover:bg-amber-400/10 text-zinc-300 transition text-left text-[11px] sm:text-sm"><i class="fas fa-tools text-amber-400 w-4 text-center"></i> SKILL.md</button>
                    <button type="button" onclick="addSpecificFile('MEMORY.md')" class="flex items-center gap-2 p-2.5 sm:p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-blue-400/50 hover:bg-blue-400/10 text-zinc-300 transition text-left text-[11px] sm:text-sm"><i class="fas fa-memory text-blue-400 w-4 text-center"></i> MEMORY.md</button>
                    <button type="button" onclick="addSpecificFile('CONTEXT.md')" class="flex items-center gap-2 p-2.5 sm:p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-cyan-400/50 hover:bg-cyan-400/10 text-zinc-300 transition text-left text-[11px] sm:text-sm"><i class="fas fa-globe text-cyan-400 w-4 text-center"></i> CONTEXT.md</button>
                    <button type="button" onclick="addSpecificFile('prompts/user.md')" class="flex items-center gap-2 p-2.5 sm:p-3 bg-zinc-950 border border-white/5 rounded-xl hover:border-green-400/50 hover:bg-green-400/10 text-zinc-300 transition text-left text-[11px] sm:text-sm"><i class="fas fa-folder text-green-400 w-4 text-center"></i> prompts/</button>
                </div>
            </div>
            <div class="relative flex items-center py-1"><div class="flex-grow border-t border-white/10"></div><span class="flex-shrink-0 mx-4 text-zinc-500 text-[10px] uppercase tracking-widest"><?= __('or custom path') ?></span><div class="flex-grow border-t border-white/10"></div></div>
            <div>
                <label class="block text-xs sm:text-sm font-medium mb-2 text-zinc-400"><?= __('Filename / Folder Path') ?></label>
                <div class="flex gap-2">
                    <input type="text" id="custom-filename-input" placeholder="<?= __('e.g. docs/guide.md') ?>" class="flex-1 bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm text-white shadow-inner" onkeydown="if(event.key === 'Enter') { event.preventDefault(); addCustomFile(); }">
                    <button type="button" onclick="addCustomFile()" class="px-4 py-2.5 bg-zinc-800 text-white rounded-xl hover:bg-zinc-700 transition font-medium text-sm border border-white/5 shadow-sm"><?= __('Add') ?></button>
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

    function setupTagInput(inputId) {
        const hiddenInput = document.getElementById(inputId);
        const visibleInput = document.getElementById(inputId + '-input');
        const tagsContainer = document.getElementById(inputId + '-tags');
        let tags = hiddenInput.value ? hiddenInput.value.split(',').map(t => t.trim().replace(/^#+/g, '')).filter(Boolean) : [];

        const renderTags = () => {
            tagsContainer.innerHTML = '';
            tags.forEach((tag, index) => {
                const tagEl = document.createElement('span');
                tagEl.className = 'inline-flex items-center gap-1.5 bg-emerald-900/40 text-emerald-400 px-2.5 sm:px-3 py-0.5 sm:py-1 rounded-full text-[11px] sm:text-xs font-medium border border-emerald-500/20';
                tagEl.innerHTML = `${escapeHTML(tag)} <button type="button" class="hover:text-white focus:outline-none ml-1" onclick="removeTag('${inputId}', ${index})"><i class="fas fa-times text-[10px]"></i></button>`;
                tagsContainer.appendChild(tagEl);
            });
            hiddenInput.value = tags.join(', ');
            
            let ph = '';
            if (tags.length === 0) {
                ph = inputId === 'domain' ? <?= json_encode(__('Domain Placeholder'), JSON_UNESCAPED_UNICODE) ?> : <?= json_encode(__('Compatibility Placeholder'), JSON_UNESCAPED_UNICODE) ?>;
            }
            visibleInput.placeholder = ph;
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
                if(rawVal.trim().startsWith('{')) { this.files = JSON.parse(rawVal); } 
                else if(rawVal.trim() !== '') { this.files['SOUL.md'] = rawVal; }
            } catch(e) {}

            if (Object.keys(this.files).length === 0) { this.files['SOUL.md'] = ''; }

            this.editorEl.addEventListener('input', (e) => {
                if (this.activeFile) this.files[this.activeFile] = e.target.value;
            });

            this.renderFileList();
            this.switchFile(Object.keys(this.files)[0]);
        }

        // 🚀 核心 V5 補漏：為編輯模式提供數據載入與解析引擎
        loadData(rawContent) {
            this.files = {};
            try {
                let cleaned = rawContent.replace(/\\'/g, "'");
                if (cleaned.trim().startsWith('{')) { 
                    this.files = JSON.parse(cleaned); 
                } else { 
                    this.files['SOUL.md'] = rawContent; 
                }
            } catch(e) { 
                this.files['SOUL.md'] = rawContent; 
            }
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
                btn.className = `w-auto md:w-full text-left px-3 py-2 md:px-3 md:py-2 rounded-lg text-xs font-mono transition flex items-center md:items-start gap-1.5 shrink-0 ${isActive ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 md:border-transparent' : 'text-zinc-400 hover:bg-white/5 border border-white/10 md:border-transparent'}`;
                
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
                    displayHtml = `<div class="flex flex-row md:flex-col overflow-hidden items-center md:items-start gap-1 md:gap-0"><span class="text-[9px] text-zinc-500 truncate leading-none md:mb-0.5">${path}/</span><span class="truncate leading-tight">${name}</span></div>`;
                } else {
                    displayHtml = `<span class="truncate md:mt-0.5">${safeFilename}</span>`;
                }

                btn.innerHTML = `<i class="fas ${icon} w-3 text-center shrink-0 md:mt-1"></i> ${displayHtml}`;
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
            if (Object.keys(this.files).length <= 1) return alert(<?= json_encode(__('You must have at least one file.'), JSON_UNESCAPED_UNICODE) ?>);
            if (!confirm(<?= json_encode(__('Delete file check'), JSON_UNESCAPED_UNICODE) ?> + this.activeFile + "?")) return;
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

    function openAddFileModal() {
        document.body.style.overflow = 'hidden';
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
        document.body.style.overflow = '';
        const modal = document.getElementById('add-file-modal');
        const content = document.getElementById('add-file-content');
        modal.classList.add('opacity-0'); 
        content.classList.remove('scale-100'); 
        content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    function processNewFileName(name) {
        if (!name) return;
        name = name.trim().replace(/\\/g, '/').replace(/^\/+|\/+$/g, ''); 
        if(!name.toLowerCase().endsWith('.md') && !name.toLowerCase().endsWith('.txt') && !name.toLowerCase().endsWith('.json')) name += '.md';
        
        if (fileEditor.files[name] !== undefined) return alert(<?= json_encode(__('File already exists!'), JSON_UNESCAPED_UNICODE) ?>);
        fileEditor.files[name] = '';
        fileEditor.switchFile(name);
        closeAddFileModal();
    }

    function addSpecificFile(name) { processNewFileName(name); }
    function addCustomFile() { processNewFileName(document.getElementById('custom-filename-input').value); }

    let uploadedContentStr = '';
    document.getElementById('file-input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const ext = file.name.split('.').pop().toLowerCase();
        
        document.getElementById('tab-zip').innerHTML = `
            <div class="text-emerald-400 flex flex-col items-center justify-center gap-2 py-8 bg-zinc-900/50 rounded-2xl border-2 border-emerald-400/30">
                <i class="fas fa-check-circle text-3xl"></i>
                <span class="font-medium px-4 text-center truncate w-full">${escapeHTML(file.name)}</span>
                <span class="text-xs text-zinc-500">${<?= json_encode(__('Ready to upload'), JSON_UNESCAPED_UNICODE) ?>}</span>
            </div>`;

        if (ext === 'md' || ext === 'txt' || ext === 'json') {
            const reader = new FileReader();
            reader.onload = function(evt) { uploadedContentStr = evt.target.result; };
            reader.readAsText(file);
        } else if (ext === 'zip') {
            const reader = new FileReader();
            reader.onload = function(evt) {
                JSZip.loadAsync(evt.target.result).then(async function(zip) {
                    const extractedFiles = {};
                    const promises = [];
                    zip.forEach(function (relativePath, zipEntry) {
                        if (!zipEntry.dir && (relativePath.endsWith('.md') || relativePath.endsWith('.txt') || relativePath.endsWith('.json'))) {
                            promises.push(zipEntry.async("string").then(function (content) { extractedFiles[relativePath] = content; }));
                        }
                    });
                    await Promise.all(promises);
                    uploadedContentStr = JSON.stringify(extractedFiles, null, 2);
                }).catch(function(err) { alert(<?= json_encode(__('Failed to parse zip'), JSON_UNESCAPED_UNICODE) ?>); });
            };
            reader.readAsArrayBuffer(file);
        } else {
            alert(<?= json_encode(__('Unsupported file extension.'), JSON_UNESCAPED_UNICODE) ?>);
            uploadedContentStr = '';
        }
    });
</script>