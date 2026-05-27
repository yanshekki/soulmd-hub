<?php
/**
 * SoulMD Hub - Upload & Publish Dashboard
 * (Decoupled Modals & Dynamic i18n Internationalization Edition)
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
loadTranslations('upload');

$db = Database::getInstance();
$pdo = $db->getConnection();

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

$pageTitle = __('Upload Soul');
$pageDesc = __('Upload Subtitle');
require_once __DIR__ . '/../private/includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 w-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 sm:mb-10">
        <div>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tighter"><?= __('Upload Soul') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-1"><?= __('Upload Subtitle') ?></p>
        </div>
        <a href="<?= url('/my-souls') ?>" class="text-sm text-zinc-400 hover:text-white flex items-center gap-2 border border-white/10 bg-zinc-900/50 px-4 py-2 rounded-full w-fit transition shadow-sm">
            <i class="fas fa-arrow-left"></i> <?= __('Back to My Souls') ?>
        </a>
    </div>

    <div id="success-box" class="hidden bg-emerald-900/50 border border-emerald-500 p-5 sm:p-6 rounded-3xl mb-8 text-sm sm:text-lg shadow-lg"></div>
    <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-5 sm:p-6 rounded-3xl mb-8 shadow-lg text-sm sm:text-base"><i class="fas fa-exclamation-circle mr-2"></i><span id="error-msg"></span></div>

    <form id="upload-form" class="space-y-6 sm:space-y-8">
        <div>
            <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Soul Title') ?> <span class="text-red-400">*</span></label>
            <input type="text" id="title" name="title" required value="<?= htmlspecialchars($presetTitle) ?>" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 sm:px-6 py-3 sm:py-4 text-base sm:text-lg focus:outline-none focus:border-emerald-400 shadow-inner">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Short Description') ?></label>
            <textarea id="description" name="description" rows="2" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 sm:px-6 py-3 sm:py-4 text-sm sm:text-base focus:outline-none focus:border-emerald-400 shadow-inner"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Role') ?></label>
                <select id="role" name="role" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 py-3 sm:py-4 text-sm sm:text-base focus:outline-none focus:border-emerald-400 shadow-inner appearance-none cursor-pointer">
                    <option value=""><?= __('Select role') ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $presetRole === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['icon'] ?? '✨') ?> <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="Other" <?= $presetRole === 'Other' ? 'selected' : '' ?>><?= __('Other') ?></option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Domain Tags') ?></label>
                <div class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-4 py-2.5 sm:py-3 min-h-[48px] sm:min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition cursor-text shadow-inner" onclick="document.getElementById('domain-input').focus()">
                    <div id="domain-tags" class="flex flex-wrap gap-1.5 sm:gap-2 empty:hidden"></div>
                    <input type="text" id="domain-input" list="domain-options" placeholder="<?= __('Domain Placeholder') ?>" class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[80px] sm:min-w-[100px] text-sm p-0 m-0 text-white">
                    <input type="hidden" id="domain" name="domain" value="">
                </div>
                <datalist id="domain-options">
                    <?php foreach ($topDomains as $tag): ?>
                        <option value="<?= htmlspecialchars($tag) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Compatibility') ?></label>
                <div class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-4 py-2.5 sm:py-3 min-h-[48px] sm:min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition cursor-text shadow-inner" onclick="document.getElementById('compatibility-input').focus()">
                    <div id="compatibility-tags" class="flex flex-wrap gap-1.5 sm:gap-2 empty:hidden"></div>
                    <input type="text" id="compatibility-input" list="compatibility-options" placeholder="<?= __('Compatibility Placeholder') ?>" class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[80px] sm:min-w-[100px] text-sm p-0 m-0 text-white">
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
            <label class="block text-sm font-medium mb-3 text-zinc-300"><?= __('Content') ?> <span class="text-red-400">*</span></label>
            
            <div class="flex border-b border-white/20 mb-4 sm:mb-6 overflow-x-auto custom-scrollbar">
                <button type="button" onclick="switchUploadTab(0)" class="upload-tab-btn flex-1 px-4 py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 border-emerald-400 text-emerald-400 whitespace-nowrap"><i class="fas fa-layer-group mr-1.5 sm:mr-2"></i> <?= __('Visual Editor') ?></button>
                <button type="button" onclick="switchUploadTab(1)" class="upload-tab-btn flex-1 px-4 py-3 sm:py-4 text-xs sm:text-sm font-medium text-zinc-400 border-b-2 border-transparent hover:text-white whitespace-nowrap"><i class="fas fa-code mr-1.5 sm:mr-2"></i> <?= __('Raw / Paste') ?></button>
                <button type="button" onclick="switchUploadTab(2)" class="upload-tab-btn flex-1 px-4 py-3 sm:py-4 text-xs sm:text-sm font-medium text-zinc-400 border-b-2 border-transparent hover:text-white whitespace-nowrap"><i class="fas fa-file-archive mr-1.5 sm:mr-2"></i> <?= __('Upload File') ?></button>
            </div>

            <div id="tab-visual" class="upload-tab-content">
                <div class="border border-white/10 rounded-2xl overflow-hidden flex flex-col md:flex-row bg-zinc-950/50 shadow-inner min-h-[400px]">
                    <div class="w-full md:w-48 xl:w-56 bg-zinc-900 border-b md:border-b-0 md:border-r border-white/10 flex flex-col">
                        <div class="p-2.5 sm:p-3 border-b border-white/10 text-[10px] sm:text-xs font-bold text-zinc-500 uppercase tracking-wider flex justify-between items-center bg-zinc-950/30">
                            <?= __('Files') ?> <button type="button" onclick="openAddFileModal()" class="text-emerald-400 hover:text-emerald-300 transition"><i class="fas fa-plus"></i></button>
                        </div>
                        <div id="file-list" class="flex md:flex-col overflow-x-auto md:overflow-y-auto overflow-y-hidden p-1.5 sm:p-2 space-x-1.5 md:space-x-0 md:space-y-1 custom-scrollbar shrink-0 border-b border-white/5 md:border-none"></div>
                    </div>
                    <div class="flex-1 flex flex-col relative min-h-[250px]">
                        <div class="bg-zinc-900 border-b border-white/10 px-3 sm:px-4 py-2 text-xs sm:text-sm font-mono text-zinc-300 flex justify-between items-center">
                            <span id="current-filename" class="truncate pr-2">SOUL.md</span>
                            <button type="button" id="btn-delete-file" onclick="fileEditor.deleteCurrentFile()" class="text-red-400 hover:text-red-300 hidden transition shrink-0"><i class="fas fa-trash-alt"></i></button>
                        </div>
                        <textarea id="file-editor-textarea" class="flex-1 bg-transparent p-4 focus:outline-none font-mono text-xs sm:text-sm text-zinc-300 resize-none custom-scrollbar" placeholder="<?= __('Start typing...') ?>"></textarea>
                    </div>
                </div>
            </div>

            <div id="tab-raw" class="upload-tab-content hidden">
                <textarea id="content-raw" rows="10" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 sm:px-6 py-4 sm:py-5 font-mono text-xs sm:text-sm focus:outline-none focus:border-emerald-400 shadow-inner custom-scrollbar sm:min-h-[300px]" placeholder="<?= __('Raw Placeholder') ?>"><?= htmlspecialchars($presetContent) ?></textarea>
            </div>

            <div id="tab-zip" class="upload-tab-content hidden">
                <div onclick="document.getElementById('file-input').click()" class="border-2 border-dashed border-white/30 rounded-2xl sm:rounded-3xl p-8 sm:p-12 text-center hover:border-emerald-400 transition cursor-pointer bg-zinc-900/50">
                    <input type="file" id="file-input" accept=".md,.txt,.zip,.json" class="hidden">
                    <i class="fas fa-cloud-upload-alt text-4xl sm:text-5xl mb-4 text-zinc-400"></i>
                    <div class="font-medium text-base sm:text-lg"><?= __('Drag & drop') ?></div>
                    <div class="text-[10px] sm:text-xs text-zinc-400 mt-2"><?= __('Drag & drop subtext') ?></div>
                </div>
            </div>
        </div>

        <button type="submit" id="submit-btn" class="w-full py-4 sm:py-5 bg-emerald-500 text-zinc-950 font-bold text-lg sm:text-xl rounded-2xl sm:rounded-3xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg hover:scale-[1.01] transform duration-200 mt-4">
            <span id="submit-text"><i class="fas fa-cloud-upload-alt mr-2"></i><?= __('Upload Soul') ?></span>
            <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
        </button>
    </form>
</div>

<?php 
// 🚨 動態載入已分拆嘅 Modal 獨立組件
require_once __DIR__ . '/../private/includes/upload-modals.php'; 
?>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>