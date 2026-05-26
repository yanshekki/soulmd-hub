<?php
/**
 * SoulMD Hub - Creator Workspace & Model Management Dashboard
 * (Dynamic i18n Internationalization & Fully Fluid Responsive Cards Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('my-souls');

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$topDomains = $pdo->query("SELECT name FROM tags_domain ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);
$topCompatibilities = $pdo->query("SELECT name FROM tags_compatibility ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

// 🚨 支援 PHP 伺服器端多語言排序渲染
$sort = $_GET['sort'] ?? 'newest';
$orderSql = "ORDER BY s.created_at DESC";
if ($sort === 'popular') {
    $orderSql = "ORDER BY s.like_count DESC, s.created_at DESC";
} elseif ($sort === 'forks') {
    $orderSql = "ORDER BY s.fork_count DESC, s.created_at DESC";
}

// 💡 關鍵修復：修正為 LEFT JOIN categories c 解決 500 Fatal Error
$stmt = $pdo->prepare("
    SELECT s.*, c.icon as role_icon, c.name as role_name 
    FROM souls s 
    LEFT JOIN categories c ON s.role = c.slug 
    WHERE s.user_id = ? 
    $orderSql
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

$pageTitle = __('My Souls');
$pageDesc = __('Manage and edit your uploaded AI personalities');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-10 border-b border-white/10 pb-6">
        <div>
            <h1 class="text-4xl sm:text-5xl font-bold tracking-tighter"><?= __('My Souls') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-2"><?= __('Manage and edit your uploaded AI personalities') ?></p>
        </div>
        
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-3 w-full lg:w-auto">
            <select onchange="window.location.href='?sort=' + this.value" class="col-span-2 sm:col-span-1 w-full sm:w-auto px-4 py-3 sm:py-2.5 text-sm bg-zinc-900 border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition focus:outline-none focus:border-emerald-400 shadow-inner cursor-pointer appearance-none">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= __('✨ Newest') ?></option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>><?= __('❤️ Like Count') ?></option>
                <option value="forks" <?= $sort === 'forks' ? 'selected' : '' ?>><?= __('🌿 Fork Count') ?></option>
            </select>
            
            <a href="<?= url('/profile/' . rawurlencode($_SESSION['username'] ?? '')) ?>" target="_blank" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-white/10 text-zinc-300 rounded-2xl hover:bg-white/5 transition flex items-center justify-center gap-2 whitespace-nowrap">
                <i class="fas fa-external-link-alt text-[10px] text-zinc-500"></i> <?= __('Profile') ?>
            </a>
            <a href="<?= url('/my-api') ?>" class="col-span-1 px-4 sm:px-5 py-3 sm:py-2.5 text-xs sm:text-sm border border-emerald-500/30 text-emerald-400 rounded-2xl hover:bg-emerald-900/10 transition text-center whitespace-nowrap">
                <?= __('My API Key') ?>
            </a>
            <a href="<?= url('/upload') ?>" class="col-span-2 sm:col-span-1 px-6 py-3 sm:py-2.5 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition flex items-center justify-center gap-2 shadow-lg w-full sm:w-auto">
                <i class="fas fa-plus"></i> <?= __('New Soul') ?>
            </a>
        </div>
    </div>

    <?php if (empty($mySouls)): ?>
        <div class="text-center py-20 sm:py-24 bg-zinc-900/20 border border-white/5 rounded-3xl mx-4 sm:mx-0">
            <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500"><i class="fas fa-folder-open text-3xl"></i></div>
            <h2 class="text-xl sm:text-2xl font-semibold mb-2"><?= __('No souls shared yet') ?></h2>
            <a href="<?= url('/upload') ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg mt-4 w-full sm:w-auto max-w-[200px] mx-auto"><?= __('Upload your first') ?></a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="souls-list">
            <?php foreach ($mySouls as $soul): ?>
                <div class="soul-card bg-zinc-900/60 border border-white/10 rounded-3xl p-5 sm:p-6 hover:border-emerald-400/40 transition-all flex flex-col justify-between backdrop-blur-sm shadow-lg" data-id="<?= $soul['id'] ?>">
                    <div>
                        <div class="flex justify-between items-start gap-3 mb-3">
                            <div>
                                <div class="font-bold text-lg sm:text-xl text-white tracking-tight mb-1 line-clamp-2 leading-tight"><?= htmlspecialchars($soul['title']) ?></div>
                                <div class="text-[10px] sm:text-xs text-zinc-500 flex items-center gap-1.5 flex-wrap">
                                    <span><?= htmlspecialchars($soul['role_icon'] ?? '✨') ?> <?= htmlspecialchars($soul['role_name'] ?? __('Unassigned')) ?></span><span>•</span><span><?= date('M j, Y', strtotime($soul['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="flex gap-2 shrink-0 flex-col items-end">
                                <span class="text-[10px] px-2.5 py-1 rounded-full font-medium border <?= $soul['is_public'] ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-zinc-800 text-zinc-400 border-white/5' ?> shadow-sm">
                                    <i class="fas <?= $soul['is_public'] ? 'fa-globe' : 'fa-lock' ?> mr-1"></i><?= $soul['is_public'] ? __('Public') : __('Private') ?>
                                </span>
                                <span class="text-[9px] px-2 py-0.5 rounded font-medium border <?= $soul['file_type'] === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20' ?> shadow-sm">
                                    <?= $soul['file_type'] === 'full_soul_folder' ? __('Modular') : __('Single .md') ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($soul['description']): ?>
                            <p class="text-xs sm:text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed"><?= htmlspecialchars($soul['description']) ?></p>
                        <?php endif; ?>

                        <div class="flex flex-wrap gap-1.5 mb-6">
                            <?php 
                            $cardDomains = array_filter(array_map('trim', explode(',', $soul['domain'])));
                            foreach (array_slice($cardDomains, 0, 3) as $dTag): ?>
                                <span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded shadow-sm">#<?= htmlspecialchars($dTag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-auto">
                        <div class="flex items-center gap-4 text-xs text-zinc-500">
                            <span title="Forks"><i class="fas fa-code-branch mr-1 text-emerald-500"></i><b class="text-zinc-300"><?= $soul['fork_count'] ?></b></span>
                            <span title="Likes"><i class="fas fa-heart mr-1 text-red-500"></i><b class="text-zinc-300"><?= $soul['like_count'] ?></b></span>
                        </div>
                        
                        <div class="flex flex-wrap items-center gap-2">
                            <button onclick="editSoul(<?= $soul['id'] ?>)" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-200 font-medium rounded-xl border border-white/5 transition flex-1 sm:flex-auto text-center"><?= __('Edit') ?></button>
                            <a href="<?= url('/soul-versions/' . $soul['id']) ?>" class="px-4 py-2.5 sm:py-2 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-400 hover:text-zinc-200 rounded-xl border border-white/5 transition flex items-center justify-center" title="<?= __('Version History') ?>"><i class="fas fa-history"></i></a>
                            <button onclick="deleteSoul(<?= $soul['id'] ?>)" class="px-4 py-2.5 sm:p-2 text-xs text-zinc-500 hover:text-red-400 transition bg-zinc-800 sm:bg-transparent rounded-xl sm:rounded-none border border-white/5 sm:border-none flex items-center justify-center"><i class="far fa-trash-alt sm:text-base"></i></button>
                            <?php $seoUrl = url("/soul/" . rawurlencode($_SESSION['username']) . "/" . $soul['id'] . "/" . makeSlug($soul['role']) . "/" . makeSlug($soul['title'])); ?>
                            <a href="<?= $seoUrl ?>" class="px-5 py-2.5 sm:py-2 text-xs bg-white hover:bg-zinc-200 text-black font-bold rounded-xl transition text-center shadow flex-1 sm:flex-auto"><?= __('View') ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="edit-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-4xl w-full max-h-[92vh] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
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

<div id="add-file-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[60] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-md w-full flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" id="add-file-content">
        <div class="p-5 sm:p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/30">
            <h3 class="text-lg sm:text-xl font-bold tracking-tight text-white"><i class="fas fa-plus-circle text-emerald-400 mr-2"></i><?= __('Add Module File') ?></h3>
            <button type="button" onclick="closeAddFileModal()" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-lg"></i></button>
        </div>
        <div class="p-5 sm:p-6 space-y-6">
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
                    <input type="text" id="custom-filename-input" placeholder="e.g. docs/guide.md" class="flex-1 bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm text-white shadow-inner" onkeydown="if(event.key === 'Enter') { event.preventDefault(); addCustomFile(); }">
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
                let cleaned = rawContent.replace(/\\'/g, "'");
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
    const editModalFileEditor = new MultiFileEditor();

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
            } else {
                alert(result.error || <?= json_encode(__('Failed to fetch soul details'), JSON_UNESCAPED_UNICODE) ?>); 
                closeModal();
            }
        } catch(e) { alert(<?= json_encode(__('Network error.'), JSON_UNESCAPED_UNICODE) ?>); closeModal(); }
    }

    async function handleEdit(e) {
        e.preventDefault();
        document.getElementById('edit-final-payload').value = editModalFileEditor.getPayload();

        const btn = e.target.querySelector('button[type="submit"]');
        const text = btn.querySelector('#save-text');
        const spinner = btn.querySelector('#loading-spinner');
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
                closeModal(); location.reload(); 
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
        const modal = document.getElementById('edit-modal');
        const content = modal.firstElementChild;
        modal.classList.add('opacity-0'); 
        content.classList.remove('scale-100'); 
        content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); currentEditId = null; }, 300);
    }

    async function deleteSoul(id) {
        if (!confirm(<?= json_encode(__('Are you sure you want to permanently delete this AI soul?'), JSON_UNESCAPED_UNICODE) ?>)) return;
        try {
            const res = await fetch(`/api/soul/${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (data.success) { location.reload(); } else { alert(data.error || <?= json_encode(__('Failed to delete'), JSON_UNESCAPED_UNICODE) ?>); }
        } catch(e) { alert(<?= json_encode(__('Network error.'), JSON_UNESCAPED_UNICODE) ?>); }
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>