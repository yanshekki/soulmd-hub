<?php
/**
 * SoulMD Hub - Unified Soul Editor Form
 * Included by upload.php and edit.php
 * 🚀 V6 ULTIMATE: Synced with V16 Dual-Action Engine & Exposed Error Traces
 */

$uStmt = $pdo->prepare("SELECT near_wallet_address, username FROM users WHERE id = ?");
$uStmt->execute([$user_id]);
$uRow = $uStmt->fetch();

$nearWallet = $uRow['near_wallet_address'] ?? null;
$sessionUsername = $uRow['username'] ?? 'anonymous';

$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();
$topDomains = $pdo->query("SELECT name FROM tags_domain ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);
$topCompatibilities = $pdo->query("SELECT name FROM tags_compatibility ORDER BY usage_count DESC, name ASC LIMIT 30")->fetchAll(PDO::FETCH_COLUMN);

if (!$isEditMode) {
    $presetTitle = $_SESSION['preset_title'] ?? '';
    $presetContent = $_SESSION['preset_content'] ?? '';
    $presetRole = $_SESSION['preset_role'] ?? '';
    $presetDomain = '';
    $presetCompat = '';

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
            if (stripos($presetRole, 'Engineer') !== false || stripos($presetRole, 'Coder') !== false) { $presetRole = 'Developer'; }
            elseif (stripos($presetRole, 'Writer') !== false || stripos($presetRole, 'Copywriter') !== false) { $presetRole = 'Writer'; }
            elseif (stripos($presetRole, 'Assistant') !== false) { $presetRole = 'Personal Assistant'; }
            else { $presetRole = 'Other'; }
        }
    }
    unset($_SESSION['preset_title'], $_SESSION['preset_content'], $_SESSION['preset_role']);
} else {
    $presetTitle = $soulData['title'];
    $presetContent = $soulData['content'];
    $presetRole = $soulData['role'];
    $presetDomain = $soulData['domain'];
    $presetCompat = $soulData['compatibility'];
}

$isNftLocked = ($isEditMode && $soulData['is_nft'] == 1 && empty($nearWallet));
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<?php require_once __DIR__ . '/near-wallet-scripts.php'; ?>

<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 w-full">
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 sm:mb-10">
        <div>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tighter"><?= $isEditMode ? __('Edit Soul') : __('Upload Soul') ?></h1>
            <p class="text-sm sm:text-base text-zinc-400 mt-1"><?= $isEditMode ? __('Edit Subtitle') : __('Upload Subtitle') ?></p>
        </div>
        <a href="<?= url('/my-souls') ?>" class="text-sm text-zinc-400 hover:text-white flex items-center gap-2 border border-white/10 bg-zinc-900/50 px-4 py-2 rounded-full w-fit transition shadow-sm">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= __('Back to My Souls') ?>
        </a>
    </header>

    <div id="success-box" role="alert" class="hidden bg-emerald-900/50 border border-emerald-500 p-5 sm:p-6 rounded-3xl mb-8 text-sm sm:text-lg shadow-lg"></div>
    <div id="error-box" role="alert" class="hidden bg-red-900/50 border border-red-500 p-5 sm:p-6 rounded-3xl mb-8 shadow-lg text-sm sm:text-base"><i class="fas fa-exclamation-circle mr-2" aria-hidden="true"></i> <span id="error-msg"></span></div>

    <section aria-label="AI Model Configuration Form">
        <form id="soul-form" class="space-y-6 sm:space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
                <div class="<?= ($isEditMode && $soulData['is_nft'] == 0) ? 'md:col-span-2' : 'md:col-span-3' ?>">
                    <label for="title" class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Soul Title') ?> <span class="text-red-400">*</span></label>
                    <input type="text" id="title" required value="<?= htmlspecialchars($presetTitle) ?>" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 sm:px-6 py-3 sm:py-4 text-base sm:text-lg focus:outline-none focus:border-emerald-400 shadow-inner" <?= $isNftLocked ? 'disabled' : '' ?>>
                </div>
                
                <?php if ($isEditMode && $soulData['is_nft'] == 0): ?>
                <div>
                    <label for="is_public" class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Visibility') ?></label>
                    <select id="is_public" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 py-3 sm:py-4 text-sm sm:text-base focus:outline-none focus:border-emerald-400 shadow-inner appearance-none cursor-pointer">
                        <option value="1" <?= $soulData['is_public'] ? 'selected' : '' ?>><?= __('  Public (Hub)') ?></option>
                        <option value="0" <?= !$soulData['is_public'] ? 'selected' : '' ?>><?= __('  Private') ?></option>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Short Description') ?></label>
                <textarea id="description" rows="2" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 sm:px-6 py-3 sm:py-4 text-sm sm:text-base focus:outline-none focus:border-emerald-400 shadow-inner" <?= $isNftLocked ? 'disabled' : '' ?>><?= htmlspecialchars($isEditMode ? $soulData['description'] : '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
                <div>
                    <label for="role" class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Role') ?></label>
                    <select id="role" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 py-3 sm:py-4 text-sm sm:text-base focus:outline-none focus:border-emerald-400 shadow-inner appearance-none <?= $isNftLocked ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' ?>" <?= $isNftLocked ? 'disabled' : '' ?>>
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
                    <label for="domain-input" class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Domain Tags') ?></label>
                    <div class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-4 py-2.5 sm:py-3 min-h-[48px] sm:min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition shadow-inner <?= $isNftLocked ? 'cursor-not-allowed opacity-50' : 'cursor-text' ?>" onclick="if(!<?= $isNftLocked ? 'true' : 'false' ?>) document.getElementById('domain-input').focus()">
                        <div id="domain-tags" class="flex flex-wrap gap-1.5 sm:gap-2 empty:hidden"></div>
                        <input type="text" id="domain-input" list="domain-options" class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[80px] text-sm p-0 m-0 text-white" <?= $isNftLocked ? 'disabled' : '' ?>>
                        <input type="hidden" id="domain" value="<?= htmlspecialchars($presetDomain) ?>">
                    </div>
                    <datalist id="domain-options">
                        <?php foreach ($topDomains as $tag): ?><option value="<?= htmlspecialchars($tag) ?>"><?php endforeach; ?>
                    </datalist>
                </div>

                <div>
                    <label for="compatibility-input" class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Compatibility') ?></label>
                    <div class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-4 py-2.5 sm:py-3 min-h-[48px] sm:min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition shadow-inner <?= $isNftLocked ? 'cursor-not-allowed opacity-50' : 'cursor-text' ?>" onclick="if(!<?= $isNftLocked ? 'true' : 'false' ?>) document.getElementById('compatibility-input').focus()">
                        <div id="compatibility-tags" class="flex flex-wrap gap-1.5 sm:gap-2 empty:hidden"></div>
                        <input type="text" id="compatibility-input" list="compatibility-options" class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[80px] text-sm p-0 m-0 text-white" <?= $isNftLocked ? 'disabled' : '' ?>>
                        <input type="hidden" id="compatibility" value="<?= htmlspecialchars($presetCompat) ?>">
                    </div>
                    <datalist id="compatibility-options">
                        <?php foreach ($topCompatibilities as $tag): ?><option value="<?= htmlspecialchars($tag) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <div class="<?= $isNftLocked ? 'opacity-70 pointer-events-none' : '' ?>">
                <label class="block text-sm font-medium mb-3 text-zinc-300"><?= __('Content') ?> <span class="text-red-400">*</span></label>
                
                <div class="flex border-b border-white/20 mb-4 sm:mb-6 overflow-x-auto custom-scrollbar" role="tablist">
                    <button type="button" role="tab" aria-selected="true" aria-controls="tab-visual" onclick="switchUploadTab(0)" class="upload-tab-btn flex-1 px-4 py-3 sm:py-4 text-xs sm:text-sm font-medium border-b-2 border-emerald-400 text-emerald-400 whitespace-nowrap"><i class="fas fa-layer-group mr-1.5 sm:mr-2" aria-hidden="true"></i> <?= __('Visual Editor') ?></button>
                    <button type="button" role="tab" aria-selected="false" aria-controls="tab-raw" onclick="switchUploadTab(1)" class="upload-tab-btn flex-1 px-4 py-3 sm:py-4 text-xs sm:text-sm font-medium text-zinc-400 border-b-2 border-transparent hover:text-white whitespace-nowrap"><i class="fas fa-code mr-1.5 sm:mr-2" aria-hidden="true"></i> <?= __('Raw / Paste') ?></button>
                    <button type="button" role="tab" aria-selected="false" aria-controls="tab-zip" onclick="switchUploadTab(2)" class="upload-tab-btn flex-1 px-4 py-3 sm:py-4 text-xs sm:text-sm font-medium text-zinc-400 border-b-2 border-transparent hover:text-white whitespace-nowrap"><i class="fas fa-file-archive mr-1.5 sm:mr-2" aria-hidden="true"></i> <?= __('Upload File') ?></button>
                </div>

                <div id="tab-visual" role="tabpanel" class="upload-tab-content">
                    <div class="border border-white/10 rounded-2xl overflow-hidden flex flex-col md:flex-row bg-zinc-950/50 shadow-inner min-h-[400px]">
                        <div class="w-full md:w-48 xl:w-56 bg-zinc-900 border-b md:border-b-0 md:border-r border-white/10 flex flex-col">
                            <div class="p-2.5 sm:p-3 border-b border-white/10 text-[10px] sm:text-xs font-bold text-zinc-500 uppercase tracking-wider flex justify-between items-center bg-zinc-950/30">
                                <?= __('Files') ?> <button type="button" aria-label="Add File" onclick="openAddFileModal()" class="text-emerald-400 hover:text-emerald-300 transition"><i class="fas fa-plus" aria-hidden="true"></i></button>
                            </div>
                            <div id="file-list" class="flex md:flex-col overflow-x-auto md:overflow-y-auto overflow-y-hidden p-1.5 sm:p-2 space-x-1.5 md:space-x-0 md:space-y-1 custom-scrollbar shrink-0 border-b border-white/5 md:border-none"></div>
                        </div>
                        <div class="flex-1 flex flex-col relative min-h-[250px]">
                            <div class="bg-zinc-900 border-b border-white/10 px-3 sm:px-4 py-2 text-xs sm:text-sm font-mono text-zinc-300 flex justify-between items-center">
                                <span id="current-filename" class="truncate pr-2">SOUL.md</span>
                                <button type="button" id="btn-delete-file" aria-label="Delete File" onclick="fileEditor.deleteCurrentFile()" class="text-red-400 hover:text-red-300 hidden transition shrink-0"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                            </div>
                            <textarea id="file-editor-textarea" aria-label="File Content Editor" class="flex-1 bg-transparent p-4 focus:outline-none font-mono text-xs sm:text-sm text-zinc-300 resize-none custom-scrollbar" placeholder="<?= __('Start typing...') ?>"></textarea>
                        </div>
                    </div>
                </div>

                <div id="tab-raw" role="tabpanel" class="upload-tab-content hidden">
                    <label for="content-raw" class="sr-only">Raw JSON Content</label>
                    <textarea id="content-raw" rows="10" class="w-full bg-zinc-900 border border-white/20 rounded-2xl sm:rounded-3xl px-5 sm:px-6 py-4 sm:py-5 font-mono text-xs sm:text-sm focus:outline-none focus:border-emerald-400 shadow-inner custom-scrollbar sm:min-h-[300px]" placeholder="<?= __('Raw Placeholder') ?>"></textarea>
                </div>

                <div id="tab-zip" role="tabpanel" class="upload-tab-content hidden">
                    <div onclick="document.getElementById('file-input').click()" class="border-2 border-dashed border-white/30 rounded-2xl sm:rounded-3xl p-8 sm:p-12 text-center hover:border-emerald-400 transition cursor-pointer bg-zinc-900/50">
                        <input type="file" id="file-input" aria-label="Upload Zip or Markdown" accept=".md,.txt,.zip,.json" class="hidden">
                        <i class="fas fa-cloud-upload-alt text-4xl sm:text-5xl mb-4 text-zinc-400" aria-hidden="true"></i>
                        <div class="font-medium text-base sm:text-lg"><?= __('Drag & drop') ?></div>
                        <div class="text-[10px] sm:text-xs text-zinc-400 mt-2"><?= __('Drag & drop subtext') ?></div>
                    </div>
                </div>
            </div>

            <?php if ($isEditMode && $soulData['is_nft'] == 1): ?>
            <div class="mb-6 p-5 sm:p-6 bg-zinc-950 border border-emerald-500/20 rounded-2xl shadow-inner <?= empty($nearWallet) ? 'opacity-50 pointer-events-none' : '' ?>">
                <h3 class="text-emerald-400 font-bold text-sm mb-4 flex items-center gap-2"><i class="fas fa-gem" aria-hidden="true"></i> <?= __('AgentFi Actions') ?></h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <div class="bg-zinc-900/50 p-4 rounded-xl border border-white/5">
                        <div class="flex justify-between items-start mb-2">
                            <label for="agentfi-sale-price" class="text-white text-sm font-semibold flex items-center gap-1.5"><i class="fas fa-tag text-blue-400" aria-hidden="true"></i> <?= __('List for Sale') ?></label>
                            <button type="button" onclick="agentfiAction('cancel_sale', this)" class="text-[10px] text-red-400 hover:underline px-2 py-0.5 rounded border border-red-500/20 bg-red-500/10 hidden" id="btn-cancel-sale"><?= __('Cancel Listing') ?></button>
                        </div>
                        <p class="text-[10px] text-zinc-500 mb-3 leading-tight"><?= __('Sale Desc') ?></p>
                        <div class="flex gap-2">
                            <input type="number" id="agentfi-sale-price" placeholder="<?= __('Price (NEAR)') ?>" step="0.01" min="0" class="w-full bg-zinc-950 border border-white/10 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-blue-400 text-white shadow-inner font-mono">
                            <button type="button" onclick="agentfiAction('list_sale', this)" class="px-3 py-2 bg-blue-500/20 text-blue-400 hover:bg-blue-500 hover:text-zinc-950 font-bold rounded-lg border border-blue-500/30 transition text-xs whitespace-nowrap shadow-sm flex items-center justify-center gap-1 min-w-[120px]"><?= __('List on Market') ?></button>
                        </div>
                    </div>

                    <div class="bg-zinc-900/50 p-4 rounded-xl border border-white/5">
                        <div class="flex justify-between items-start mb-2">
                            <label for="agentfi-rent-price" class="text-white text-sm font-semibold flex items-center gap-1.5"><i class="fas fa-handshake text-purple-400" aria-hidden="true"></i> <?= __('List for Rent') ?></label>
                            <button type="button" onclick="agentfiAction('cancel_rent', this)" class="text-[10px] text-red-400 hover:underline px-2 py-0.5 rounded border border-red-500/20 bg-red-500/10 hidden" id="btn-cancel-rent"><?= __('Cancel Listing') ?></button>
                        </div>
                        <p class="text-[10px] text-zinc-500 mb-3 leading-tight"><?= __('Rent Desc') ?></p>
                        <div class="flex gap-2">
                            <input type="number" id="agentfi-rent-price" placeholder="<?= __('Rent Price (NEAR / 30 Days)') ?>" step="0.01" min="0" class="w-full bg-zinc-950 border border-white/10 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-purple-400 text-white shadow-inner font-mono">
                            <button type="button" onclick="agentfiAction('list_rent', this)" class="px-3 py-2 bg-purple-500/20 text-purple-400 hover:bg-purple-500 hover:text-zinc-950 font-bold rounded-lg border border-purple-500/30 transition text-xs whitespace-nowrap shadow-sm flex items-center justify-center gap-1 min-w-[120px]"><?= __('List on Market') ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="mb-6 p-5 sm:p-6 bg-gradient-to-r <?= ($isEditMode && $soulData['is_nft'] == 1) ? 'from-emerald-900/20 to-teal-900/20 border-emerald-500/30' : 'from-purple-900/20 to-indigo-900/20 border-purple-500/30' ?> border rounded-2xl sm:rounded-3xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full <?= ($isEditMode && $soulData['is_nft'] == 1) ? 'bg-emerald-500' : 'bg-purple-500' ?>"></div>
                <div class="flex-1">
                    <h3 class="text-white font-bold text-sm sm:text-base flex items-center gap-2">
                        <i class="fas <?= ($isEditMode && $soulData['is_nft'] == 1) ? 'fa-sync-alt text-emerald-400' : 'fa-cube text-purple-400' ?>" aria-hidden="true"></i> 
                        <?= ($isEditMode && $soulData['is_nft'] == 1) ? __('Sync to NEAR') : __('Mint to NEAR') ?>
                    </h3>
                    <p class="text-xs sm:text-sm text-zinc-400 mt-1">
                        <?= ($isEditMode && $soulData['is_nft'] == 1) ? __('Sync Desc') : __('Mint Desc') ?>
                    </p>
                    <?php if (!($isEditMode && $soulData['is_nft'] == 1)): ?>
                        <div class="text-[10px] sm:text-xs font-mono font-bold text-purple-400/80 mt-2"><?= __('Platform Fee') ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($nearWallet)): ?>
                        <div class="mt-4 bg-red-500/10 border border-red-500/20 rounded-xl p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-start gap-2 text-red-400 text-xs font-medium">
                                <i class="fas fa-exclamation-triangle mt-0.5 shrink-0" aria-hidden="true"></i>
                                <p><?= ($isEditMode && $soulData['is_nft'] == 1) ? __('NFT Edit Lock Warning') : __('Please connect NEAR wallet first') ?></p>
                            </div>
                            <button type="button" aria-label="Connect Web3 Wallet" onclick="window.connectOrBindWallet()" class="shrink-0 px-4 py-2 bg-red-500 hover:bg-red-400 text-zinc-950 text-xs font-bold rounded-lg transition shadow-md whitespace-nowrap text-center">
                                <i class="fas fa-link" aria-hidden="true"></i> <?= __('Go to Bind Wallet') ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($nearWallet)): ?>
                    <?php if ($isEditMode && $soulData['is_nft'] == 1): ?>
                        <label class="relative inline-flex items-center cursor-not-allowed shrink-0" title="Required for NFT updates">
                            <input type="checkbox" id="mint-toggle" class="sr-only peer" checked disabled>
                            <div class="w-14 h-7 bg-zinc-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500 opacity-70"></div>
                        </label>
                    <?php else: ?>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" id="mint-toggle" class="sr-only peer">
                            <div class="w-14 h-7 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-500"></div>
                        </label>
                    <?php endif; ?>
                <?php else: ?>
                    <label class="relative inline-flex items-center cursor-not-allowed shrink-0 opacity-40">
                        <input type="checkbox" id="mint-toggle" class="sr-only peer" disabled <?= ($isEditMode && $soulData['is_nft'] == 1) ? 'checked' : '' ?>>
                        <div class="w-14 h-7 bg-zinc-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all <?= ($isEditMode && $soulData['is_nft'] == 1) ? 'peer-checked:bg-emerald-500' : 'peer-checked:bg-purple-500' ?>"></div>
                    </label>
                <?php endif; ?>
            </div>

            <?php if ($isNftLocked): ?>
                <button type="button" disabled aria-label="Wallet Required" class="w-full py-4 sm:py-5 bg-zinc-800 text-zinc-500 font-bold text-lg sm:text-xl rounded-2xl sm:rounded-3xl cursor-not-allowed border border-white/5 flex items-center justify-center gap-3 shadow-lg mt-4">
                    <i class="fas fa-lock mr-2" aria-hidden="true"></i> <?= __('Wallet Required to Edit NFT') ?>
                </button>
            <?php else: ?>
                <button type="submit" id="submit-btn" aria-label="<?= $isEditMode ? __('Save Changes') : __('Upload Soul') ?>" class="w-full py-4 sm:py-5 bg-emerald-500 text-zinc-950 font-bold text-lg sm:text-xl rounded-2xl sm:rounded-3xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg hover:scale-[1.01] transform duration-200 mt-4">
                    <span id="submit-text"><i class="fas <?= $isEditMode ? 'fa-save' : 'fa-cloud-upload-alt' ?> mr-2" aria-hidden="true"></i><?= $isEditMode ? __('Save Changes') : __('Upload Soul') ?></span>
                    <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/upload-modals.php'; ?>

<script>
    const isEditMode = <?= $isEditMode ? 'true' : 'false' ?>;
    const soulId = <?= $soulId ?? 0 ?>;
    const isNft = <?= ($soulData['is_nft'] ?? 0) == 1 ? 'true' : 'false' ?>;
    const initialContent = <?= json_encode($presetContent ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const serverCsrfToken = "<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES) ?>";  // provided by includer (edit.php / upload.php) for session CSRF protection on form submit

    window.addEventListener('DOMContentLoaded', async () => {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('errorMessage') || urlParams.has('errorCode')) {
            const raw = (urlParams.get('errorMessage') || urlParams.get('errorCode') || '');
            let nice = raw;
            try { nice = window.getErrorMessage(raw.trim().startsWith('{') ? JSON.parse(decodeURIComponent(raw)) : decodeURIComponent(raw)); } catch(_) { nice = window.getErrorMessage(raw) || raw; }

            // On callback error, still verify on-chain (the tx often landed despite the selector "validation" complaint)
            let recovered = false;
            const checkId = soulId;
            if (checkId) {
                try {
                    const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + checkId });
                    if (check && check.success && check.data && check.data.owner_id) {
                        recovered = true;
                        // Clean URL and show non-blocking success instead of scary alert
                        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + (soulId ? '?id=' + soulId : '');
                        window.history.replaceState({path: cleanUrl}, '', cleanUrl);
                        const successBox = document.getElementById('success-box');
                        if (successBox) {
                            successBox.innerHTML = '✅ Mint succeeded on-chain (verified via RPC). Syncing...';
                            successBox.classList.remove('hidden');
                            setTimeout(() => window.location.reload(), 1200);
                        } else {
                            window.location.reload();
                        }
                    }
                } catch(_) {}
            }
            if (!recovered) {
                alert("<?= addslashes(__('Blockchain transaction failed or rejected.')) ?>\n" + nice);
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + (soulId ? '?id=' + soulId : '');
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        } else if (urlParams.has('transactionHashes')) {
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + (soulId ? '?id=' + soulId : '');
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        }

        if (isEditMode && initialContent) {
            document.getElementById('content-raw').value = initialContent;
            fileEditor.loadData(initialContent);
        }

        if (isEditMode && isNft && "<?= $nearWallet ?>") {
            fetchOnChainData();
        }
    });

    async function fetchOnChainData() {
        try {
            const rpcRes = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soulId });
            
            if (rpcRes.success && rpcRes.data) {
                const tokenInfo = rpcRes.data;
                if (tokenInfo.sale_price && tokenInfo.sale_price !== "0") {
                    document.getElementById('agentfi-sale-price').value = nearApi.utils.format.formatNearAmount(tokenInfo.sale_price);
                    document.getElementById('btn-cancel-sale').classList.remove('hidden');
                }
                if (tokenInfo.rent_price && tokenInfo.rent_price !== "0") {
                    document.getElementById('agentfi-rent-price').value = nearApi.utils.format.formatNearAmount(tokenInfo.rent_price);
                    document.getElementById('btn-cancel-rent').classList.remove('hidden');
                }
            }
        } catch(e) {}
    }

    // 🚀 FIXED: Using the restored functionCall wrapper directly & Detailed Error Exposure
    async function agentfiAction(actionType, btn) {
        if (!isEditMode) return;
        const wrapper = await initNearWallet();
        if (!wrapper.isSignedIn()) {
            await window.connectOrBindWallet();
            return;
        }

        const args = { token_id: "soul_" + soulId };
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

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Processing...';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        try {
            await wrapper.account().functionCall({
                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                methodName: methodName, 
                args: args, 
                gas: "30000000000000", 
                attachedDeposit: "0", 
                walletCallbackUrl: window.location.href
            });
            
            btn.innerHTML = '<i class="fas fa-sync fa-spin mr-1" aria-hidden="true"></i> Syncing to DB...';
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            await fetch(`/api/soul/${soulId}`);
            
            window.location.reload();
        } catch(e) {
            console.error("AgentFi Action Error:", e);

            // Same resilient pattern as main mint flow:
            // wallet-selector often throws "Request validation error" / internal validation even when the
            // list_for_sale / list_for_rent tx actually succeeded on-chain (callbackUrl + INCLUDED case).
            // Verify with live get_soul. If the price state now matches what we tried to set → treat as success.
            let onChainStateMatches = false;
            try {
                const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soulId });
                if (check && check.success && check.data) {
                    const t = check.data;
                    if (actionType.includes('sale')) {
                        const wanted = (args.price === "0" || args.price === 0) ? null : args.price;
                        const now = t.sale_price;
                        onChainStateMatches = (wanted == null)
                            ? (!now || now === "0" || now === null)
                            : (now === wanted);
                    } else if (actionType.includes('rent')) {
                        const wanted = (args.price === "0" || args.price === 0) ? null : args.price;
                        const now = t.rent_price;
                        onChainStateMatches = (wanted == null)
                            ? (!now || now === "0" || now === null)
                            : (now === wanted);
                    }
                }
            } catch (_) {}

            if (onChainStateMatches) {
                // On-chain already reflects the listing/cancel we wanted. Do the normal success cleanup.
                btn.innerHTML = '<i class="fas fa-sync fa-spin mr-1" aria-hidden="true"></i> Syncing to DB...';
                await new Promise(resolve => setTimeout(resolve, 2000));
                await fetch(`/api/soul/${soulId}`);
                window.location.reload();
                return;
            }

            // Only show failure if on-chain does NOT match what we attempted.
            alert("<?= addslashes(__('Blockchain transaction failed or rejected.')) ?>\n" + window.getErrorMessage(e));
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    const form = document.getElementById('soul-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('submit-btn');
            const text = document.getElementById('submit-text');
            const loading = document.getElementById('submit-loading');
            const errorBox = document.getElementById('error-box');
            const errorMsg = document.getElementById('error-msg');
            const mintToggle = document.getElementById('mint-toggle');
            
            const wantMintOrSync = (isEditMode && isNft && "<?= $nearWallet ?>") ? true : (mintToggle ? mintToggle.checked : false);

            let wrapper = null;
            if (wantMintOrSync) {
                wrapper = await initNearWallet();
                if (!wrapper.isSignedIn()) {
                    await window.connectOrBindWallet();
                    return;
                }
            }

            errorBox.classList.add('hidden');

            let finalContent = '';
            if (activeMainTab === 0) finalContent = fileEditor.getPayload();
            else if (activeMainTab === 1) finalContent = document.getElementById('content-raw').value;
            else finalContent = uploadedContentStr;

            if (!finalContent || finalContent.trim() === '') {
                errorMsg.innerText = "<?= addslashes(__('Content empty')) ?>";
                errorBox.classList.remove('hidden'); window.scrollTo({ top: 0, behavior: 'smooth' }); return;
            }

            text.classList.add('hidden'); loading.classList.remove('hidden'); btn.classList.add('opacity-80', 'cursor-not-allowed');

            // 每次 submit 都 live on-chain check（用 nearRpcQuery），確保只有真正 on-chain 存在嘅 NFT 先用 update_soul_hash
            // 解決「第一次 mint 失敗後，DB is_nft=1 但 on-chain 無，永遠鎖死喺 update 模式」問題
            let onChainNftExists = false;
            if (isEditMode && "<?= $nearWallet ?>") {
                try {
                    const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soulId });
                    onChainNftExists = check.success && check.data && check.data.owner_id;
                } catch (e) {
                    console.warn('Live on-chain NFT check failed, will treat as not-minted for retry:', e);
                    onChainNftExists = false;
                }
            }

            const payload = {
                title: document.getElementById('title').value,
                description: document.getElementById('description').value,
                role: document.getElementById('role').value,
                domain: document.getElementById('domain').value,
                compatibility: document.getElementById('compatibility').value,
                content: finalContent
            };

            if (isEditMode) {
                const pubNode = document.getElementById('is_public');
                payload.is_public = pubNode ? parseInt(pubNode.value) : <?= $soulData['is_public'] ?? 0 ?>;
                // 只有當我們決定要做 on-chain mint（而唔係 update）先 set is_minting，讓 backend 正確標記
                if (wantMintOrSync && !onChainNftExists) payload.is_minting = 1;
            } else {
                payload.is_minting = wantMintOrSync ? 1 : 0;
            }

            const method = isEditMode ? 'PUT' : 'POST';
            const endpoint = isEditMode ? `/api/soul/${soulId}` : '/api/souls';

            try {
                const headers = { 'Content-Type': 'application/json' };
                if (serverCsrfToken) headers['X-CSRF-Token'] = serverCsrfToken;
                const res = await fetch(endpoint, { method: method, headers: headers, body: JSON.stringify(payload) });
                const data = await res.json();

                if (data.success) {
                    const targetUrl = isEditMode ? window.location.href : data.url.replace("<?= BASE_URL ?>", "<?= url('') ?>");
                    
                    if (wantMintOrSync) {
                        // 用 live check 嘅結果決定用 update_soul_hash 定 mint_soul（每次都 check on-chain 係唔係真係 NFT）
                        if (isEditMode && onChainNftExists) {
                            text.innerText = "Processing...";
                            text.classList.remove('hidden'); loading.classList.remove('hidden');
                            
                            // update hash for existing on-chain NFT
                            await wrapper.account().functionCall({
                                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                                methodName: "update_soul_hash",
                                args: { token_id: "soul_" + soulId, new_hash: data.hash },
                                gas: "30000000000000", 
                                attachedDeposit: "0", 
                                walletCallbackUrl: targetUrl
                            });

                            text.innerText = "Syncing to DB...";
                            await new Promise(resolve => setTimeout(resolve, 2000));
                            await fetch(`/api/soul/${soulId}`);
                            
                            window.location.href = "<?= url('/my-souls') ?>";
                        } else {
                            text.innerText = "<?= addslashes(__('Redirecting to Wallet...')) ?>";
                            text.classList.remove('hidden'); loading.classList.add('hidden');
                            
                            const deposit = nearApi.utils.format.parseNearAmount("0.6");
                            const newId = isEditMode ? soulId : data.id;
                            const refUrl = "<?= url('/soul/') ?>" + "<?= rawurlencode($sessionUsername) ?>/" + newId;
                            
                            // mint new NFT on-chain (retry path if previous failed or DB stale)
                            await wrapper.account().functionCall({
                                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>",
                                methodName: "mint_soul",
                                args: { token_id: "soul_" + newId, title: payload.title, description: payload.description || "<?= addslashes(__('No description provided')) ?>", hash: data.hash, reference: refUrl },
                                gas: "30000000000000", 
                                attachedDeposit: deposit, 
                                walletCallbackUrl: targetUrl
                            });
                        }
                    } else {
                        window.location.href = isEditMode ? "<?= url('/my-souls') ?>" : targetUrl;
                    }
                } else {
                    errorMsg.innerText = data.error || "<?= addslashes(__('Failed to save soul.')) ?>";
                    errorBox.classList.remove('hidden'); window.scrollTo({ top: 0, behavior: 'smooth' });
                    text.classList.remove('hidden'); loading.classList.add('hidden'); btn.classList.remove('opacity-80', 'cursor-not-allowed');
                }
            } catch(err) {
                console.error("Form Submit Error:", err);

                // Even if wallet throws "Request validation error" or similar (common with callbackUrl + selector),
                // the tx may have succeeded (as proven by explorer receipt + logs). Always double-check on-chain.
                const newIdForCheck = isEditMode ? soulId : (data && data.id ? data.id : null);
                if (newIdForCheck) {
                    try {
                        // Use the same live RPC the form already trusts for mint-vs-update decision
                        const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + newIdForCheck });
                        if (check && check.success && check.data && check.data.owner_id) {
                            // ✅ On-chain success! Hide error, sync DB, go to my-souls.
                            errorBox.classList.add('hidden');
                            if (text) text.innerText = "Mint succeeded on blockchain (verified)!";
                            if (loading) loading.classList.remove('hidden');
                            try { await fetch(`/api/soul/${newIdForCheck}`); } catch(_) {}
                            setTimeout(() => {
                                window.location.href = "<?= url('/my-souls') ?>";
                            }, 900);
                            return;
                        }
                    } catch (verifyErr) {
                        console.warn('Post-tx on-chain verify failed:', verifyErr);
                    }
                }

                // Only show the scary banner if on-chain check also didn't find the token.
                errorMsg.innerText = "<?= addslashes(__('Blockchain transaction failed or rejected.')) ?>\n" + (window.getErrorMessage ? window.getErrorMessage(err) : (err.message || ''));
                errorBox.classList.remove('hidden'); window.scrollTo({ top: 0, behavior: 'smooth' });
                if (text) text.classList.remove('hidden');
                if (loading) loading.classList.add('hidden');
                if (btn) btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        });
    }
</script>