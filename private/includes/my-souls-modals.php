<?php
/**
 * SoulMD Hub - My Souls Modals & Scripts Component
 * Included dynamically at the bottom of my-souls.php
 * 🚀 Patched: Added Loading UI + 2s Delay + API Sync for Silent Transactions
 */
?>

<div id="edit-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[500] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-4xl w-full max-h-[calc(100dvh-2rem)] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="p-5 sm:p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/20 shrink-0">
            <h3 class="text-xl sm:text-2xl font-bold tracking-tight"><?= __('Edit Modular AI Soul') ?></h3>
            <button type="button" onclick="closeModal()" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
        </div>

        <form id="edit-form" onsubmit="handleEdit(event)" class="flex flex-col flex-grow overflow-hidden">
            <div class="p-5 sm:p-6 overflow-y-auto custom-scrollbar flex-grow space-y-6">
                <input type="hidden" id="edit-id" name="id">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400"><?= __('Title') ?></label>
                        <input id="edit-title" type="text" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm shadow-inner">
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400"><?= __('Visibility') ?></label>
                        <select id="edit-public" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm shadow-inner appearance-none cursor-pointer">
                            <option value="1"><?= __('🌐 Public (Hub)') ?></option>
                            <option value="0"><?= __('🔒 Private') ?></option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400"><?= __('Short Description') ?></label>
                    <textarea id="edit-description" rows="2" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm shadow-inner"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400"><?= __('Role') ?></label>
                        <select id="edit-role" class="w-full bg-zinc-950 border border-white/10 rounded-xl px-3 py-2.5 focus:outline-none focus:border-emerald-400 text-sm shadow-inner appearance-none cursor-pointer">
                            <option value=""><?= __('Select role') ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['icon'] ?? '✨') ?> <?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                            <option value="Other"><?= __('Other') ?></option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400"><?= __('Domain Tags') ?></label>
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
                        <label class="block text-xs font-medium mb-1.5 text-zinc-400"><?= __('Compatibility') ?></label>
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
                    <label class="block text-xs font-medium mb-1.5 text-zinc-400"><?= __('Modular Files Editor') ?></label>
                    <div class="border border-white/10 rounded-2xl overflow-hidden flex flex-col md:flex-row bg-zinc-950 min-h-[300px]">
                        <div class="w-full md:w-48 bg-zinc-900 border-b md:border-b-0 md:border-r border-white/10 flex flex-col">
                            <div class="p-2 border-b border-white/10 text-[10px] font-bold text-zinc-500 uppercase tracking-wider flex justify-between items-center bg-zinc-950/30">
                                <?= __('Files') ?> <button type="button" onclick="openAddFileModal()" class="text-emerald-400 hover:text-emerald-300 transition"><i class="fas fa-plus"></i></button>
                            </div>
                            <div id="modal-file-list" class="flex md:flex-col overflow-x-auto md:overflow-y-auto overflow-y-hidden p-1 space-x-1 md:space-x-0 md:space-y-1 custom-scrollbar shrink-0 border-b border-white/5 md:border-none"></div>
                        </div>
                        <div class="flex-1 flex flex-col relative min-h-[250px]">
                            <div class="bg-zinc-900 border-b border-white/10 px-3 py-2 text-xs font-mono text-zinc-300 flex justify-between items-center">
                                <span id="modal-current-filename" class="truncate pr-2"><?= __('Loading...') ?></span>
                                <button type="button" id="modal-btn-delete-file" onclick="editModalFileEditor.deleteCurrentFile()" class="text-red-400 hover:text-red-300 hidden transition shrink-0"><i class="fas fa-trash-alt"></i></button>
                            </div>
                            <textarea id="modal-file-editor-textarea" class="flex-1 bg-transparent p-4 focus:outline-none font-mono text-xs text-zinc-300 resize-none custom-scrollbar"></textarea>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-zinc-950 border border-emerald-500/20 rounded-2xl shadow-inner">
                    <h4 class="text-emerald-400 font-bold text-sm mb-4 flex items-center gap-2"><i class="fas fa-gem"></i> <?= __('AgentFi Actions') ?></h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <div class="bg-zinc-900/50 p-4 rounded-xl border border-white/5">
                            <div class="flex justify-between items-start mb-2">
                                <label class="text-white text-sm font-semibold flex items-center gap-1.5"><i class="fas fa-tag text-blue-400"></i> <?= __('List for Sale') ?></label>
                                <button type="button" onclick="agentfiAction('cancel_sale', this)" class="text-[10px] text-red-400 hover:underline px-2 py-0.5 rounded border border-red-500/20 bg-red-500/10 hidden min-w-[90px]" id="btn-cancel-sale"><?= __('Cancel Listing') ?></button>
                            </div>
                            <p class="text-[10px] text-zinc-500 mb-3 leading-tight"><?= __('Sale Desc') ?></p>
                            <div class="flex gap-2">
                                <input type="number" id="agentfi-sale-price" placeholder="<?= __('Price (NEAR)') ?>" step="0.01" min="0" class="w-full bg-zinc-950 border border-white/10 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-blue-400 text-white shadow-inner font-mono">
                                <button type="button" onclick="agentfiAction('list_sale', this)" class="px-3 py-2 bg-blue-500/20 text-blue-400 hover:bg-blue-500 hover:text-zinc-950 font-bold rounded-lg border border-blue-500/30 transition text-xs whitespace-nowrap shadow-sm min-w-[120px] flex items-center justify-center gap-1.5"><?= __('List on Market') ?></button>
                            </div>
                        </div>

                        <div class="bg-zinc-900/50 p-4 rounded-xl border border-white/5">
                            <div class="flex justify-between items-start mb-2">
                                <label class="text-white text-sm font-semibold flex items-center gap-1.5"><i class="fas fa-handshake text-purple-400"></i> <?= __('List for Rent') ?></label>
                                <button type="button" onclick="agentfiAction('cancel_rent', this)" class="text-[10px] text-red-400 hover:underline px-2 py-0.5 rounded border border-red-500/20 bg-red-500/10 hidden min-w-[90px]" id="btn-cancel-rent"><?= __('Cancel Listing') ?></button>
                            </div>
                            <p class="text-[10px] text-zinc-500 mb-3 leading-tight"><?= __('Rent Desc') ?></p>
                            <div class="flex gap-2">
                                <input type="number" id="agentfi-rent-price" placeholder="<?= __('Rent Price (NEAR / 30 Days)') ?>" step="0.01" min="0" class="w-full bg-zinc-950 border border-white/10 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-purple-400 text-white shadow-inner font-mono">
                                <button type="button" onclick="agentfiAction('list_rent', this)" class="px-3 py-2 bg-purple-500/20 text-purple-400 hover:bg-purple-500 hover:text-zinc-950 font-bold rounded-lg border border-purple-500/30 transition text-xs whitespace-nowrap shadow-sm min-w-[120px] flex items-center justify-center gap-1.5"><?= __('List on Market') ?></button>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="p-4 sm:p-5 bg-gradient-to-r from-emerald-900/20 to-teal-900/20 border border-emerald-500/30 rounded-2xl flex items-center justify-between gap-4 shadow-sm">
                    <div>
                        <h3 class="text-white font-bold text-sm flex items-center gap-2"><i class="fas fa-sync-alt text-emerald-400"></i> <?= __('Sync to NEAR') ?></h3>
                        <p class="text-[11px] sm:text-xs text-zinc-400 mt-1"><?= __('Sync Desc') ?></p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" id="sync-toggle" class="sr-only peer">
                        <div class="w-12 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>
            </div>

            <input type="hidden" id="edit-final-payload" name="content">

            <div class="p-4 sm:p-5 border-t border-white/5 bg-zinc-900 shrink-0 flex justify-end gap-3">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 border border-white/10 rounded-xl text-sm font-medium hover:bg-white/5 transition w-full sm:w-auto"><?= __('Cancel') ?></button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg hover:bg-emerald-400 transition w-full sm:w-auto">
                    <span id="save-text"><i class="fas fa-save mr-1"></i> <?= __('Save Changes') ?></span>
                    <span id="loading-spinner" class="hidden animate-spin h-4 w-4 border-2 border-black border-t-transparent rounded-full"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="add-file-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[500] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-md w-full max-h-[calc(100dvh-2rem)] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" id="add-file-content">
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

    async function mintExistingSoul(id) {
        if (!confirm("<?= addslashes(__('Mint Confirm')) ?>")) return;

        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) {
            await window.connectOrBindWallet();
            return;
        }

        try {
            const res = await fetch(`/api/soul/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_minting: true })
            });
            const data = await res.json();
            
            if (data.success) {
                const deposit = nearApi.utils.format.parseNearAmount("0.6");
                const args = {
                    token_id: "soul_" + id,
                    title: data.soul_title,
                    description: data.soul_description || "<?= addslashes(__('No description provided')) ?>",
                    hash: data.hash,
                    reference: data.url
                };
                
                await wallet.account().functionCall({
                    contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                    methodName: "mint_soul",
                    args: args,
                    gas: "30000000000000",
                    attachedDeposit: deposit,
                    walletCallbackUrl: window.location.href
                });
            } else {
                alert(data.error || <?= json_encode(__('Failed to prepare minting.'), JSON_UNESCAPED_UNICODE) ?>);
            }
        } catch(e) {
            alert(<?= json_encode(__('Network error.'), JSON_UNESCAPED_UNICODE) ?>);
        }
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
                let cleaned = rawContent.replace(/\'/g, "'");
                if (cleaned.trim().startsWith('{')) { 
                    this.files = JSON.parse(cleaned); 
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
                btn.className = `w-auto md:w-full text-left px-3 py-2 md:px-2 md:py-1.5 rounded-lg md:rounded text-xs font-mono transition flex items-center md:items-start gap-1.5 shrink-0 ${isActive ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 md:border-transparent' : 'text-zinc-400 hover:bg-white/5 border border-white/10 md:border-transparent'}`;
                
                let icon = 'fa-file-alt';
                const nameUpper = filename.toUpperCase();
                if(nameUpper.includes('SOUL')) icon = 'fa-brain';
                else if(nameUpper.includes('STYLE')) icon = 'fa-palette text-purple-400';
                else if(nameUpper.includes('RULE')) icon = 'fa-shield-alt text-red-400';
                else if(nameUpper.includes('SKILL')) icon = 'fa-tools text-amber-400';
                else if(nameUpper.includes('MEMORY')) icon = 'fa-blue-400';
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
    const editModalFileEditor = new MultiFileEditor();

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
        
        if (editModalFileEditor.files[name] !== undefined) return alert(<?= json_encode(__('File already exists!'), JSON_UNESCAPED_UNICODE) ?>);
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
        document.getElementById('edit-title').value = <?= json_encode(__('Loading...'), JSON_UNESCAPED_UNICODE) ?>;
        document.getElementById('sync-toggle').checked = false; 
        
        document.getElementById('agentfi-sale-price').value = '';
        document.getElementById('agentfi-rent-price').value = '';
        document.getElementById('btn-cancel-sale').classList.add('hidden');
        document.getElementById('btn-cancel-rent').classList.add('hidden');
        
        document.body.style.overflow = 'hidden'; 
        
        const modal = document.getElementById('edit-modal');
        const content = modal.firstElementChild;
        modal.classList.remove('hidden');
        setTimeout(() => { 
            modal.classList.remove('opacity-0'); 
            content.classList.remove('scale-95'); 
            content.classList.add('scale-100'); 
        }, 10);

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

                fetchOnChainData(id);

            } else {
                alert(result.error || <?= json_encode(__('Failed to fetch soul details'), JSON_UNESCAPED_UNICODE) ?>); 
                closeModal();
            }
        } catch(e) { alert(<?= json_encode(__('Network error.'), JSON_UNESCAPED_UNICODE) ?>); closeModal(); }
    }

    async function fetchOnChainData(id) {
        try {
            const rpcPayload = {
                jsonrpc: "2.0", id: "dontcare", method: "query",
                params: {
                    request_type: "call_function", finality: "final",
                    account_id: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                    method_name: "get_soul",
                    args_base64: btoa(JSON.stringify({ token_id: "soul_" + id }))
                }
            };
            const rpcRes = await fetch(window.activeNearRpcUrl || 'https://free.rpc.fastnear.com', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(rpcPayload)
            });
            const rpcData = await rpcRes.json();
            if (rpcData.result && rpcData.result.result) {
                const resString = new TextDecoder().decode(new Uint8Array(rpcData.result.result));
                const tokenInfo = JSON.parse(resString);
                
                if (tokenInfo) {
                    if (tokenInfo.sale_price) {
                        document.getElementById('agentfi-sale-price').value = nearApi.utils.format.formatNearAmount(tokenInfo.sale_price);
                        document.getElementById('btn-cancel-sale').classList.remove('hidden');
                    }
                    if (tokenInfo.rent_price) {
                        document.getElementById('agentfi-rent-price').value = nearApi.utils.format.formatNearAmount(tokenInfo.rent_price);
                        document.getElementById('btn-cancel-rent').classList.remove('hidden');
                    }
                }
            }
        } catch(e) { console.log('RPC Fetch Error for NFT status', e); }
    }

    // 🚨 支援 Button Loading UI + 2s Delay Sync
    async function agentfiAction(actionType, btn) {
        if (!currentEditId) return;
        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) {
            await window.connectOrBindWallet();
            return;
        }

        const args = { token_id: "soul_" + currentEditId };
        let methodName = '';
        
        if (actionType === 'list_sale') {
            const price = document.getElementById('agentfi-sale-price').value;
            if(!price || parseFloat(price) <= 0) {
                args.price = "0";
            } else {
                args.price = nearApi.utils.format.parseNearAmount(price.toString());
            }
            methodName = 'list_for_sale';
        } else if (actionType === 'list_rent') {
            const price = document.getElementById('agentfi-rent-price').value;
            if(!price || parseFloat(price) <= 0) {
                args.price = "0";
            } else {
                args.price = nearApi.utils.format.parseNearAmount(price.toString());
            }
            methodName = 'list_for_rent';
        } else if (actionType === 'cancel_sale') {
            methodName = 'list_for_sale'; args.price = "0"; 
        } else if (actionType === 'cancel_rent') {
            methodName = 'list_for_rent'; args.price = "0";
        }

        // 鎖定按鈕並顯示 Processing UI
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        try {
            await wallet.account().functionCall({
                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                methodName: methodName,
                args: args,
                gas: "30000000000000",
                attachedDeposit: "0",
                walletCallbackUrl: window.location.href
            });
            
            // 🚨 靜默簽署完成，進入 2秒退避等待，顯示 Syncing UI
            btn.innerHTML = '<i class="fas fa-sync fa-spin mr-1"></i> Syncing to DB...';
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            // 強制敲擊後端 API 更新 MySQL 價錢庫
            await fetch(`/api/soul/${currentEditId}`);
            
            window.location.reload();
        } catch(e) { 
            alert(<?= json_encode(__('Blockchain transaction failed or rejected.'), JSON_UNESCAPED_UNICODE) ?>); 
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    async function handleEdit(e) {
        e.preventDefault();
        document.getElementById('edit-final-payload').value = editModalFileEditor.getPayload();

        const btn = e.target.querySelector('button[type="submit"]');
        const text = btn.querySelector('#save-text');
        const spinner = btn.querySelector('#loading-spinner');
        
        const wantSync = document.getElementById('sync-toggle').checked;
        let wallet = null;

        if (wantSync) {
            wallet = await initNearWallet();
            if (!wallet.isSignedIn()) {
                await window.connectOrBindWallet();
                return;
            }
        }

        text.classList.add('hidden'); spinner.classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-not-allowed');

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
                if (wantSync) {
                    // 🚨 更新 Hash 為 0 Deposit 操作，加入 2s 等待與同步
                    text.innerText = "Processing...";
                    text.classList.remove('hidden');
                    spinner.classList.remove('hidden');
                    
                    const args = {
                        token_id: "soul_" + currentEditId,
                        new_hash: data.hash
                    };
                    
                    await wallet.account().functionCall({
                        contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                        methodName: "update_soul_hash",
                        args: args,
                        gas: "30000000000000", 
                        attachedDeposit: "0",
                        walletCallbackUrl: window.location.href
                    });
                    
                    text.innerText = "Syncing to DB...";
                    await new Promise(resolve => setTimeout(resolve, 2000));
                    await fetch(`/api/soul/${currentEditId}`);
                    
                    closeModal(); location.reload(); 
                } else {
                    closeModal(); location.reload(); 
                }
            } else { 
                alert(data.error); 
                text.classList.remove('hidden'); spinner.classList.add('hidden'); 
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        } catch(e) { 
            alert(<?= json_encode(__('Network error.'), JSON_UNESCAPED_UNICODE) ?>); 
            text.classList.remove('hidden'); spinner.classList.add('hidden'); 
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

    function closeModal() { 
        document.body.style.overflow = '';
        
        const modal = document.getElementById('edit-modal');
        const content = modal.firstElementChild;
        modal.classList.add('opacity-0'); 
        content.classList.remove('scale-100'); 
        content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); currentEditId = null; }, 300);
    }
</script>