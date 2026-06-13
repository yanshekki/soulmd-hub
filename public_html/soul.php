<?php
/**
 * SoulMD Hub - Public AI Soul Deep Repository View
 * (Dynamic i18n Internationalization, 4-Layer SEO Routing & AgentFi Marketplace Edition)
 * 🚀 V6 ULTIMATE: Synced with V16 Dual-Action Near Scripts & Exposed Error Traces
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';
require_once __DIR__ . '/../private/src/ApiSecurity.php';

session_start();

// Centralized CSRF token (replaces repeated bin2hex block)
$csrfToken = ensureCsrfToken();

loadTranslations('soul');

$db = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'] ?? 0;

if (!$id) {
    header('Location: ' . url('/browse'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, u.username, c.icon as role_icon, c.name as role_name 
    FROM souls s 
    LEFT JOIN users u ON s.user_id = u.id 
    LEFT JOIN categories c ON s.role = c.slug 
    WHERE s.id = ? AND (s.is_public = 1 OR s.is_nft = 1 OR s.user_id = ?)
");
$stmt->execute([$id, $userId]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$isOwner = ($userId > 0 && $userId === $soul['user_id']);

$currentUserWallet = null;
if ($userId > 0) {
    $wStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
    $wStmt->execute([$userId]);
    $currentUserWallet = $wStmt->fetchColumn();
}

$isChainOwner = (!empty($currentUserWallet) && $currentUserWallet === $soul['nft_owner_wallet']);

$canViewContent = ($soul['is_public'] == 1 || $isOwner || $isChainOwner);

if (!$canViewContent) {
    $protectedMsg = "🔒 **" . __('Protected') . "**\n\n" . __('Protected NFT Msg');
    
    if ($soul['file_type'] === 'full_soul_folder') {
        $contentData = json_encode([
            'SOUL.md' => $protectedMsg,
            'STYLE.md' => "🔒 " . __('Protected'),
            'RULES.md' => "🔒 " . __('Protected')
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $contentData = $protectedMsg;
    }
} else {
    $contentData = $soul['content'];
}

function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

$encodedUsername = rawurlencode($soul['username'] ?? 'anonymous');
$slugRole = makeSlug($soul['role']);
$slugTitle = makeSlug($soul['title']);

$canonicalUrl = url("/soul/{$encodedUsername}/{$id}/{$slugRole}/{$slugTitle}");
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($currentUri !== $canonicalUrl && strpos($currentUri, '/api/') === false) {
    header("Location: " . $canonicalUrl, true, 301);
    exit;
}

$hasLiked = false;
if (isset($_SESSION['user_id'])) {
    $likeCheck = $pdo->prepare("SELECT 1 FROM soul_likes WHERE soul_id = ? AND user_id = ?");
    $likeCheck->execute([$id, $_SESSION['user_id']]);
    $hasLiked = (bool)$likeCheck->fetch();
}

$isFolder = $soul['file_type'] === 'full_soul_folder';

if ($isFolder) {
    $cleanedContent = str_replace("\\'", "'", $contentData);
    $files = json_decode($cleanedContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($files) || empty($files)) {
        $errorMsg = json_last_error_msg();
        $files = [
            'ERROR.md' => "## ⚠️ " . __('Parse Error') . "\n" . __('Failed to parse JSON folder structure.') . "\n\n**" . __('Error Details:') . "** `{$errorMsg}`\n\n---\n\n### " . __('Raw Output:') . "\n```json\n" . $contentData . "\n
```"
        ];
    }
} else {
    $files = ['SOUL.md' => $contentData];
}

$avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_ratings FROM soul_ratings WHERE soul_id = ?");
$avgStmt->execute([$id]);
$ratingData = $avgStmt->fetch();
$avgRating = $ratingData['avg_rating'] ?? 0;
$totalRatings = $ratingData['total_ratings'] ?? 0;

$vStmt = $pdo->prepare("SELECT COUNT(*) FROM soul_versions WHERE soul_id = ?");
$vStmt->execute([$id]);
$versionCount = $vStmt->fetchColumn() + 1;

$domains = array_filter(array_map('trim', explode(',', $soul['domain'])));
$compatibilities = array_filter(array_map('trim', explode(',', $soul['compatibility'])));

function getFileStyle($filename) {
    $name = strtoupper($filename);
    if (str_contains($name, 'SOUL')) return ['icon' => 'fa-brain', 'color' => 'text-emerald-400', 'border' => 'border-emerald-400'];
    if (str_contains($name, 'STYLE')) return ['icon' => 'fa-palette', 'color' => 'text-purple-400', 'border' => 'border-purple-400'];
    if (str_contains($name, 'RULE')) return ['icon' => 'fa-shield-alt', 'color' => 'text-red-400', 'border' => 'border-red-400'];
    if (str_contains($name, 'SKILL')) return ['icon' => 'fa-tools', 'color' => 'text-amber-400', 'border' => 'border-amber-400'];
    if (str_contains($name, 'MEMORY')) return ['icon' => 'fa-memory', 'color' => 'text-blue-400', 'border' => 'border-blue-400'];
    if (str_contains($name, 'CONTEXT')) return ['icon' => 'fa-globe', 'color' => 'text-cyan-400', 'border' => 'border-cyan-400'];
    if (str_contains($name, 'PROMPT')) return ['icon' => 'fa-terminal', 'color' => 'text-green-400', 'border' => 'border-green-400'];
    if (str_ends_with($name, '.JSON') || str_contains($name, 'ERROR')) return ['icon' => 'fa-code', 'color' => 'text-yellow-400', 'border' => 'border-yellow-400'];
    return ['icon' => 'fa-file-alt', 'color' => 'text-zinc-400', 'border' => 'border-zinc-400'];
}

// 🚀 SEO Enhancement: Dynamic Title & Truncated Clean Description
$pageTitle = $soul['title'] . ' | ' . ($soul['role_name'] ?: 'AI Persona');
$pageDesc = $soul['description'] ? mb_substr(strip_tags(str_replace(["\r", "\n"], ' ', $soul['description'])), 0, 150) . '...' : __('View this AI soul on SoulMD Hub.');

require_once __DIR__ . '/../private/includes/header.php';
?>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<main class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow flex flex-col">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <a href="<?= $soul['is_nft'] == 1 ? url('/marketplace') : url('/browse') ?>" title="Return to directory" class="inline-flex items-center gap-2 text-sm text-zinc-400 hover:text-emerald-400 transition w-fit border border-white/10 bg-zinc-900/50 px-4 py-2 rounded-full">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $soul['is_nft'] == 1 ? __('Back to Market') : __('Back to Hub') ?>
        </a>
        
        <div class="grid grid-cols-2 sm:flex sm:flex-row gap-3 w-full md:w-auto mt-2 md:mt-0">
            <button onclick="likeSoul()" id="like-btn" aria-label="Like this AI Persona" class="col-span-1 flex items-center justify-center gap-2 px-5 py-2.5 bg-zinc-900 border border-white/10 rounded-xl hover:border-red-500/50 hover:text-red-400 transition shadow-sm">
                <i class="fas fa-heart <?= $hasLiked ? 'text-red-400' : 'text-zinc-500' ?>" aria-hidden="true"></i>
                <span id="like-count" class="font-medium"><?= $soul['like_count'] ?></span>
            </button>
            
            <?php if ($soul['is_nft'] == 0): ?>
            <button onclick="forkSoul()" id="fork-btn" title="Fork to your workspace" class="col-span-1 flex items-center justify-center gap-2 px-5 py-2.5 bg-zinc-900 text-white rounded-xl border border-white/10 font-bold hover:bg-zinc-800 transition shadow-sm">
                <i class="fas fa-code-branch text-emerald-400" aria-hidden="true"></i> <?= __('Fork') ?>
            </button>
            <?php endif; ?>
            
            <?php if ($canViewContent): ?>
                <button onclick="copyMegaPrompt(this)" title="Copy the compiled AI prompt" class="col-span-2 sm:col-span-1 flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-emerald-400 to-cyan-400 text-zinc-950 rounded-xl font-bold hover:opacity-90 transition shadow-lg shadow-emerald-500/20 transform hover:-translate-y-0.5 duration-200">
                    <i class="fas fa-magic" aria-hidden="true"></i> <?= __('Copy Full Prompt') ?>
                </button>
            <?php else: ?>
                <button disabled aria-label="Content Protected" class="col-span-2 sm:col-span-1 flex items-center justify-center gap-2 px-6 py-2.5 bg-zinc-800 text-zinc-500 rounded-xl font-bold cursor-not-allowed border border-white/5 transition shadow-sm">
                    <i class="fas fa-lock" aria-hidden="true"></i> <?= __('Protected') ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="agentfi-market-block" class="hidden mb-6 bg-zinc-950 border border-emerald-500/30 rounded-3xl p-5 flex flex-col md:flex-row items-center justify-between gap-4 shadow-lg animate-fade-in relative overflow-hidden">
        <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
        <div>
            <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-1 flex items-center gap-2">
                <span><i class="fas fa-gem" aria-hidden="true"></i> <?= __('AgentFi Marketplace') ?></span>
                <span class="bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded text-emerald-300 cursor-help" title="<?= __('Floor Desc') ?>">
                    <?= __('Floor Price') ?>: 0.45 NEAR
                </span>
            </div>
            <div class="text-sm text-zinc-300 mt-1">
                <span class="text-zinc-500"><?= __('Current Owner') ?>:</span> <span id="market-owner" class="font-mono text-emerald-300 tracking-tight"></span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 w-full md:w-auto" id="market-actions">
            <button id="btn-buy" onclick="buySoul()" aria-label="Buy AI Ownership" class="hidden flex-1 md:flex-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition shadow-lg text-sm whitespace-nowrap">
                <span id="text-buy"><i class="fas fa-shopping-cart mr-1" aria-hidden="true"></i> <span id="price-buy"></span> NEAR</span>
            </button>
            <button id="btn-rent" onclick="rentSoul()" aria-label="Rent AI for 30 Days" class="hidden flex-1 md:flex-auto px-5 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition shadow-lg text-sm whitespace-nowrap">
                <span id="text-rent"><i class="fas fa-handshake mr-1" aria-hidden="true"></i> <span id="price-rent"></span> NEAR</span>
            </button>
        </div>
    </div>

    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 mb-10 backdrop-blur-sm shadow-xl">
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <?php if ($soul['role_name']): ?>
                <a href="<?= url('/browse?role=' . urlencode($soul['role'])) ?>" title="Explore role: <?= htmlspecialchars($soul['role_name']) ?>" class="px-3 py-1 bg-white/5 border border-white/10 rounded-full text-xs font-medium hover:bg-white/10 transition">
                    <?= htmlspecialchars($soul['role_icon'] ?? '✨') ?> <?= htmlspecialchars($soul['role_name']) ?>
                </a>
            <?php endif; ?>
            <span class="px-3 py-1 text-xs font-medium rounded-full <?= $isFolder ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' ?>">
                <i class="fas <?= $isFolder ? 'fa-folder-open' : 'fa-file-alt' ?>" aria-hidden="true"></i> <?= $isFolder ? __('Modular Folder') : __('Single .md') ?>
            </span>
        </div>

        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight mb-4 leading-tight"><?= htmlspecialchars($soul['title']) ?></h1>
        
        <?php if ($soul['description']): ?>
            <p class="text-base sm:text-lg text-zinc-400 leading-relaxed mb-6 max-w-3xl">
                <?= nl2br(htmlspecialchars($soul['description'])) ?>
            </p>
        <?php endif; ?>

        <div class="bg-blue-900/10 border border-blue-500/20 rounded-3xl p-5 sm:p-6 mb-8 max-w-3xl shadow-inner">
            <h2 class="text-blue-400 text-lg sm:text-xl font-bold mb-3 flex items-center gap-2">
                <i class="fas fa-bolt text-blue-500" aria-hidden="true"></i> <?= __('One-Click Interaction') ?>
            </h2>
            <p class="text-xs sm:text-sm text-zinc-300 mb-6 leading-relaxed">
                <?= __('Instantly interact with this AI soul directly in your browser. Start a live conversation based on the modular instructions provided in this repository. No complex API integrations required.') ?>
            </p>
            
            <a href="<?= url('/chat/' . $id) ?>" target="_blank" title="Start Chat Session" class="flex sm:inline-flex items-center justify-center gap-2 px-8 py-3.5 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-500 transition shadow-lg shadow-blue-500/20 sm:hover:scale-[1.02] transform duration-200 w-full sm:w-auto">
                <i class="fas fa-paper-plane" aria-hidden="true"></i> <?= __('Start Conversation') ?>
            </a>
            
            <div class="mt-5 text-xs text-zinc-400 bg-black/30 p-4 rounded-xl border border-white/5 leading-relaxed">
                <i class="fas fa-shield-alt text-amber-500 mr-1.5" aria-hidden="true"></i> <strong><?= __('Privacy Notice:') ?></strong> <?= __('Each chat session generates a unique, permanent public URL. Anyone possessing this exact URL can view the entire conversation history. Please refrain from sharing personal, private, or sensitive information.') ?>
            </div>
        </div>

        <div class="flex flex-col flex-col md:flex-row md:items-center justify-between gap-6 pt-6 border-t border-white/10">
            <div class="flex flex-wrap items-center gap-4 sm:gap-6 text-sm text-zinc-400">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-400 to-cyan-400 flex items-center justify-center text-zinc-950 font-bold shrink-0 select-none">
                        <?= strtoupper(substr($soul['username'] ?? 'A', 0, 1)) ?>
                    </div>
                    <a href="<?= url('/profile/' . rawurlencode($soul['username'] ?? 'anonymous')) ?>" title="Creator Profile" class="font-medium text-white hover:text-emerald-400 transition truncate max-w-[120px] sm:max-w-none">
                        @<?= htmlspecialchars($soul['username'] ?? 'Anonymous') ?>
                    </a>
                </div>
                <div class="flex items-center gap-2 font-mono text-xs">
                    <i class="far fa-calendar-alt" aria-hidden="true"></i> <?= date('M j, Y', strtotime($soul['created_at'])) ?>
                </div>
                
                <?php if ($soul['is_nft'] == 0): ?>
                <div class="flex items-center gap-2">
                    <i class="fas fa-code-branch text-emerald-400" aria-hidden="true"></i> <?= $soul['fork_count'] ?> <?= __('forks') ?>
                </div>
                <a href="<?= url('/soul-versions/' . $id) ?>" title="Version History" class="flex items-center gap-2 hover:text-emerald-400 transition">
                    <i class="fas fa-history text-emerald-500" aria-hidden="true"></i> <?= $versionCount ?> <?= __('versions') ?>
                </a>
                <?php endif; ?>
                
                <div class="flex items-center gap-2 bg-zinc-950/50 px-3 py-1.5 rounded-lg border border-white/5">
                    <div class="flex text-lg" id="rating-stars" role="radiogroup" aria-label="Rate this persona">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i onclick="rateSoul(<?= $i ?>)" role="radio" aria-checked="<?= $i <= round($avgRating) ? 'true' : 'false' ?>" tabindex="0" class="fas fa-star cursor-pointer hover:scale-110 transition <?= $i <= round($avgRating) ? 'text-amber-400' : 'text-zinc-600' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-xs ml-1 font-mono">
                        <span id="avg-rating" class="text-white font-bold"><?= number_format($avgRating, 1) ?></span> 
                        <span id="total-ratings" class="opacity-50">(<?= $totalRatings ?>)</span>
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-2 lg:items-end">
                <?php if (!empty($domains)): ?>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <?php foreach($domains as $tag): ?>
                            <span class="px-2 py-1 text-[11px] bg-blue-500/10 text-blue-300 border border-blue-500/20 rounded-md">#<?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($compatibilities)): ?>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        <?php foreach($compatibilities as $tag): ?>
                            <span class="px-2 py-1 text-[11px] bg-zinc-800 text-zinc-300 border border-white/10 rounded-md"><i class="fas fa-robot text-xs opacity-50" aria-hidden="true"></i> <?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h2 class="sr-only">AI Agent Architecture Files</h2>

    <div class="bg-zinc-900/40 border border-white/10 rounded-3xl overflow-hidden shadow-xl">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-white/10 bg-zinc-950/50">
            <div class="flex pt-2 px-2 overflow-x-auto custom-scrollbar w-full md:w-auto" role="tablist">
                <?php 
                $i = 0; 
                foreach ($files as $filename => $fileContent): 
                    $i++; 
                    $fStyle = getFileStyle($filename);
                    
                    $displayName = htmlspecialchars($filename);
                    $pathPrefix = '';
                    if (strpos($filename, '/') !== false) {
                        $parts = explode('/', $filename);
                        $nameOnly = array_pop($parts);
                        $pathOnly = implode('/', $parts);
                        $displayName = htmlspecialchars($nameOnly);
                        $pathPrefix = '<div class="text-[9px] opacity-50 -mb-1 truncate max-w-[100px] leading-tight">' . htmlspecialchars($pathOnly) . '/</div>';
                    }
                ?>
                    <button onclick="showFile(<?= $i ?>, '<?= $fStyle['border'] ?>', '<?= $fStyle['color'] ?>')" id="tab-btn-<?= $i ?>" role="tab" aria-selected="<?= $i === 1 ? 'true' : 'false' ?>" aria-controls="file-<?= $i ?>" class="tab-btn px-4 sm:px-5 py-3 text-xs sm:text-sm font-medium whitespace-nowrap transition border-b-2 <?= $i === 1 ? $fStyle['border'] . ' ' . $fStyle['color'] : 'border-transparent text-zinc-400 hover:text-white hover:bg-zinc-900/50' ?> rounded-t-lg" data-border="<?= $fStyle['border'] ?>" data-color="<?= $fStyle['color'] ?>">
                        <div class="flex items-center gap-2 text-left">
                            <i class="fas <?= $fStyle['icon'] ?>" aria-hidden="true"></i>
                            <div class="flex flex-col justify-center min-h-[32px]">
                                <?= $pathPrefix ?>
                                <div class="truncate max-w-[120px] sm:max-w-[150px] leading-tight"><?= $displayName ?></div>
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="flex items-center justify-end gap-2 p-3 md:py-2 md:px-4 bg-zinc-900/30 md:bg-transparent border-t border-white/5 md:border-t-0 shrink-0">
                <?php if ($isFolder && $soul['is_nft'] == 0): ?>
                    <?php if ($canViewContent): ?>
                        <a href="/download/soul/<?= $encodedUsername ?>/<?= $id ?>/<?= $slugRole ?>/<?= $slugTitle ?>.zip" title="Download ZIP Archive" class="px-4 py-2 text-xs font-bold bg-zinc-800 text-white border border-white/10 rounded-lg hover:bg-zinc-700 transition flex items-center gap-2 shadow-sm">
                            <i class="fas fa-file-archive text-amber-400" aria-hidden="true"></i> .zip
                        </a>
                        <button onclick="copyFullFolder(this)" title="Copy Folder JSON" class="px-4 py-2 text-xs font-bold bg-white text-black rounded-lg hover:bg-zinc-200 transition flex items-center gap-2 shadow-sm">
                            <i class="fas fa-copy" aria-hidden="true"></i> <?= __('JSON') ?>
                        </button>
                    <?php else: ?>
                        <button disabled class="px-4 py-2 text-xs font-bold bg-zinc-800 text-zinc-500 border border-white/5 rounded-lg cursor-not-allowed flex items-center gap-2">
                            <i class="fas fa-lock" aria-hidden="true"></i> .zip
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-0">
            <?php 
            $i = 0; 
            foreach ($files as $filename => $fileContent): 
                $i++; 
                $encodedFilename = implode('/', array_map('rawurlencode', explode('/', $filename)));
                $safeContent = is_string($fileContent) ? $fileContent : json_encode($fileContent, JSON_UNESCAPED_UNICODE);
            ?>
                <div id="file-<?= $i ?>" role="tabpanel" aria-labelledby="tab-btn-<?= $i ?>" class="file-tab <?= $i === 1 ? 'block' : 'hidden' ?> relative">
                    <div class="sticky top-0 z-10 flex justify-end bg-gradient-to-b from-zinc-900/90 to-transparent p-4 pointer-events-none gap-2">
                        
                        <?php if ($soul['is_nft'] == 0): ?>
                            <?php if ($canViewContent): ?>
                                <a href="/download/soul/<?= $encodedUsername ?>/<?= $id ?>/<?= $slugRole ?>/<?= $slugTitle ?>/<?= $encodedFilename ?>" target="_blank" title="View Raw File" class="pointer-events-auto flex items-center gap-2 px-3 sm:px-4 py-2 bg-zinc-800/90 hover:bg-zinc-700 text-zinc-200 text-[11px] sm:text-xs font-medium rounded-lg border border-white/10 backdrop-blur transition shadow-lg">
                                    <i class="fas fa-external-link-alt" aria-hidden="true"></i> <span><?= __('Raw') ?></span>
                                </a>
                            <?php endif; ?>
                            
                            <button onclick="copyRaw(<?= $i ?>, this)" title="Copy File Content" class="pointer-events-auto flex items-center gap-2 px-3 sm:px-4 py-2 bg-zinc-800/90 hover:bg-zinc-700 text-zinc-200 text-[11px] sm:text-xs font-medium rounded-lg border border-white/10 backdrop-blur transition shadow-lg">
                                <i class="far fa-copy" aria-hidden="true"></i> <span><?= __('Copy') ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <textarea id="raw-<?= $i ?>" class="hidden"><?= htmlspecialchars($safeContent) ?></textarea>
                    
                    <div id="render-<?= $i ?>" class="prose prose-invert prose-emerald max-w-none px-4 sm:px-8 pb-10 -mt-6">
                        <div class="animate-pulse text-zinc-500"><?= __('Rendering Markdown...') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    const soulDataFiles = <?= json_encode($files, JSON_UNESCAPED_UNICODE) ?>;
    const isFolder = <?= $isFolder ? 'true' : 'false' ?>;
    const soulDbId = <?= $id ?>;

    function getCallbackUrl(actionType) {
        const url = new URL(window.location.origin + window.location.pathname);
        url.searchParams.set('id', soulDbId);
        url.searchParams.set('tx_action', actionType);
        return url.toString();
    }

    async function fetchMarketStatus() {
        try {
            const wrapper = typeof initNearWallet === 'function' ? await initNearWallet() : null;
            const myWallet = wrapper && wrapper.isSignedIn() ? wrapper.getAccountId() : null;
            const rpcRes = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soulDbId });
            
            if (rpcRes.success && rpcRes.data) {
                const tokenInfo = rpcRes.data;
                document.getElementById('agentfi-market-block').classList.remove('hidden');
                document.getElementById('market-owner').innerText = tokenInfo.owner_id;

                const isOwner = myWallet && tokenInfo.owner_id === myWallet;

                let isAlreadyRenting = false;
                if (myWallet && tokenInfo.renters) {
                    const now = Date.now();
                    for (const acc in tokenInfo.renters) {
                        const exp = Number(BigInt(tokenInfo.renters[acc]) / 1000000n);
                        if (exp > now && acc === myWallet) {
                            isAlreadyRenting = true;
                            break;
                        }
                    }
                }

                if (tokenInfo.sale_price && tokenInfo.sale_price !== "0") {
                    const price = window.nearApi.utils.format.formatNearAmount(tokenInfo.sale_price);
                    document.getElementById('price-buy').innerText = `${<?= json_encode(__('Buy Ownership'), JSON_UNESCAPED_UNICODE) ?>} - ${price}`;
                    const btnBuy = document.getElementById('btn-buy');
                    btnBuy.classList.remove('hidden');
                    btnBuy.dataset.price = tokenInfo.sale_price;
                    
                    if (isOwner) {
                        btnBuy.disabled = true;
                        btnBuy.classList.add('opacity-50', 'cursor-not-allowed');
                        btnBuy.classList.remove('hover:bg-blue-600');
                        btnBuy.removeAttribute('onclick');
                    }
                }

                if (tokenInfo.rent_price && tokenInfo.rent_price !== "0") {
                    const price = window.nearApi.utils.format.formatNearAmount(tokenInfo.rent_price);
                    document.getElementById('price-rent').innerText = `${<?= json_encode(__('Rent (30 Days)'), JSON_UNESCAPED_UNICODE) ?>} - ${price}`;
                    const btnRent = document.getElementById('btn-rent');
                    btnRent.classList.remove('hidden');
                    btnRent.dataset.price = tokenInfo.rent_price;
                    
                    if (isOwner || isAlreadyRenting) {
                        btnRent.disabled = true;
                        btnRent.classList.add('opacity-50', 'cursor-not-allowed', 'text-zinc-950/50');
                        btnRent.classList.remove('hover:bg-purple-600');
                        btnRent.removeAttribute('onclick');
                        if (isAlreadyRenting) {
                            btnRent.title = <?= json_encode(__('You are already an active renter')) ?>;
                        }
                    }
                }
            }
        } catch(e) { console.log('Not an NFT or RPC failed'); }
    }

    window.addEventListener('DOMContentLoaded', async () => {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('transactionHashes')) {
            const txAction = urlParams.get('tx_action');
            
            await fetch(`/api/soul/${soulDbId}`);

            if (txAction === 'buy') {
                alert('<?= addslashes(__('Buy success')) ?>');
            } else if (txAction === 'rent') {
                alert('<?= addslashes(__('Rent success')) ?>');
            } else {
                alert('<?= addslashes(__('Transaction Success')) ?>');
            }
            
            const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?id=' + soulDbId;
            window.history.replaceState({path: cleanUrl}, '', cleanUrl);
        }

        const tabs = document.querySelectorAll('.file-tab');
        tabs.forEach((tab, idx) => {
            const i = idx + 1;
            const rawContent = document.getElementById(`raw-${i}`).value;
            const parsedHTML = marked.parse(rawContent);
            document.getElementById(`render-${i}`).innerHTML = DOMPurify.sanitize(parsedHTML);
        });

        fetchMarketStatus();
    });

    // 🚀 FIXED: Synchronized with wrapper.account().functionCall Logic & Exposed Errors
    async function buySoul() {
        const btn = document.getElementById('btn-buy');
        const textSpan = document.getElementById('text-buy');
        const originalText = textSpan.innerHTML;
        
        const wrapper = await initNearWallet();
        if (!wrapper.isSignedIn()) { await window.connectOrBindWallet(); return; }
        
        const price = btn.dataset.price;
        
        textSpan.innerHTML = '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> <span><?= addslashes(__('Processing...')) ?></span>';
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        
        try {
            await wrapper.account().functionCall({ 
                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>", 
                methodName: "buy_soul", 
                args: { token_id: "soul_" + soulDbId }, 
                gas: "30000000000000", 
                attachedDeposit: price.toString(), 
                walletCallbackUrl: getCallbackUrl('buy') 
            });
        } catch (e) {
            console.error("Soul Details Buy Error:", e);
            const errMsg = window.getErrorMessage(e) || '';
            if (errMsg.includes('Transaction not found, but maybe executed') && !errMsg.includes('panick') && !errMsg.includes('ExecutionError') && !errMsg.includes('ActionError')) {
                textSpan.innerHTML = originalText;
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
                return;
            }
            // Recovery: confirm on-chain that buyer now has ownership or access
            try {
                const currentUser = (await initNearWallet()).getAccountId();
                const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soulDbId });
                if (check.success && check.data) {
                    const acc = await window.nearRpcQuery('check_access', { token_id: "soul_" + soulDbId, account_id: currentUser });
                    if ((check.data.owner_id === currentUser) || (acc.success && acc.data)) {
                        textSpan.innerHTML = '<?= addslashes(__('Success!')) ?>';
                        setTimeout(() => location.reload(), 600);
                        return;
                    }
                }
            } catch (_) {}
            alert("<?= addslashes(__('Operation failed')) ?>:\n" + errMsg);
            textSpan.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

    // 🚀 FIXED: Synchronized with wrapper.account().functionCall Logic & Exposed Errors
    async function rentSoul() {
        if (!confirm(<?= json_encode(__('Rent Warning Desc'), JSON_UNESCAPED_UNICODE) ?>)) return;
        const btn = document.getElementById('btn-rent');
        const textSpan = document.getElementById('text-rent');
        const originalText = textSpan.innerHTML;
        
        const wrapper = await initNearWallet();
        if (!wrapper.isSignedIn()) { await window.connectOrBindWallet(); return; }
        
        const currentUser = wrapper.getAccountId();
        try {
            const check = await window.nearRpcQuery('get_soul', { token_id: "soul_" + soulDbId });
            if (check.success && check.data && check.data.renters) {
                const now = Date.now();
                for (const acc in check.data.renters) {
                    const exp = Number(BigInt(check.data.renters[acc]) / 1000000n);
                    if (exp > now && acc === currentUser) {
                        alert(<?= json_encode(__('You are already an active renter')) ?>);
                        textSpan.innerHTML = originalText;
                        btn.disabled = false;
                        btn.classList.remove('opacity-80', 'cursor-not-allowed');
                        return;
                    }
                }
            }
        } catch (_) {}
        
        const price = btn.dataset.price;
        
        textSpan.innerHTML = '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> <span><?= addslashes(__('Processing...')) ?></span>';
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');
        
        try {
            await wrapper.account().functionCall({ 
                contractId: "<?= defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near' ?>", 
                methodName: "rent_soul", 
                args: { token_id: "soul_" + soulDbId }, 
                gas: "30000000000000", 
                attachedDeposit: price.toString(), 
                walletCallbackUrl: getCallbackUrl('rent') 
            });
        } catch (e) {
            console.error("Soul Details Rent Error:", e);
            const errMsg = window.getErrorMessage(e) || '';
            if (errMsg.includes('Transaction not found, but maybe executed') && !errMsg.includes('panick') && !errMsg.includes('ExecutionError') && !errMsg.includes('ActionError')) {
                textSpan.innerHTML = originalText;
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
                return;
            }
            // Recovery: confirm renter now has access via check_access
            try {
                const currentUser = (await initNearWallet()).getAccountId();
                const acc = await window.nearRpcQuery('check_access', { token_id: "soul_" + soulDbId, account_id: currentUser });
                if (acc.success && acc.data) {
                    textSpan.innerHTML = '<?= addslashes(__('Rented!')) ?>';
                    setTimeout(() => location.reload(), 600);
                    return;
                }
            } catch (_) {}
            alert("<?= addslashes(__('Operation failed')) ?>:\n" + errMsg);
            textSpan.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    }

    function copyMegaPrompt(btn) {
        let megaPrompt = '';
        
        if (isFolder) {
            megaPrompt += `<?= addslashes(__('MegaPrompt Intro')) ?>\n\n`;
            
            for (const [filename, content] of Object.entries(soulDataFiles)) {
                if (filename.includes('ERROR.md')) continue;
                
                megaPrompt += `=========================================\n`;
                megaPrompt += `MODULE: ${filename}\n`;
                megaPrompt += `=========================================\n\n`;
                
                let fileStr = typeof content === 'string' ? content : JSON.stringify(content, null, 2);
                megaPrompt += fileStr + `\n\n`;
            }
            
            megaPrompt += `<?= addslashes(__('MegaPrompt Outro')) ?>`;
        } else {
            megaPrompt = Object.values(soulDataFiles)[0];
        }
        
        navigator.clipboard.writeText(megaPrompt).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> <?= addslashes(__('Copied!')) ?>';
            btn.classList.add('bg-white', 'text-black');
            btn.classList.remove('bg-gradient-to-r', 'from-emerald-400', 'to-cyan-400', 'text-zinc-950');
            
            alert(`<?= addslashes(__('MegaPrompt Success')) ?>`);
            
            setTimeout(() => { 
                btn.innerHTML = originalHtml; 
                btn.classList.remove('bg-white', 'text-black');
                btn.classList.add('bg-gradient-to-r', 'from-emerald-400', 'to-cyan-400', 'text-zinc-950');
            }, 3000);
        });
    }

    marked.setOptions({ 
        breaks: true, 
        gfm: true, 
        highlight: function(code, lang) { 
            if (lang && hljs.getLanguage(lang)) {
                try { return hljs.highlight(code, { language: lang }).value; } catch (e) {}
            }
            return hljs.highlightAuto(code).value; 
        } 
    });

    function showFile(n, activeBorder, activeColor) {
        document.querySelectorAll('.file-tab').forEach(el => { el.classList.remove('block'); el.classList.add('hidden'); });
        document.getElementById('file-' + n).classList.remove('hidden');
        document.getElementById('file-' + n).classList.add('block');
        
        document.querySelectorAll('.tab-btn').forEach((btn) => {
            btn.className = btn.className.replace(/border-[a-z]+-400/g, 'border-transparent');
            btn.className = btn.className.replace(/text-[a-z]+-400/g, 'text-zinc-400');
            btn.classList.add('border-transparent', 'text-zinc-400');
            btn.setAttribute('aria-selected', 'false');
        });
        
        const activeBtn = document.getElementById('tab-btn-' + n);
        activeBtn.classList.remove('border-transparent', 'text-zinc-400');
        activeBtn.classList.add(activeBorder, activeColor);
        activeBtn.setAttribute('aria-selected', 'true');
    }

    function copyRaw(id, btn) {
        const text = document.getElementById('raw-' + id).value;
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-emerald-400" aria-hidden="true"></i> <?= addslashes(__('Copied!')) ?>';
            btn.classList.add('border-emerald-400/50', 'text-white');
            setTimeout(() => { btn.innerHTML = originalHtml; btn.classList.remove('border-emerald-400/50', 'text-white'); }, 2000);
        });
    }

    function copyFullFolder(btn) {
        <?php if($isFolder): ?>
            const jsonStr = <?= json_encode($cleanedContent ?? $contentData) ?>;
            navigator.clipboard.writeText(jsonStr).then(() => { 
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check text-emerald-600" aria-hidden="true"></i> <?= addslashes(__('Copied!')) ?>';
                setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
            });
        <?php endif; ?>
    }

    async function rateSoul(stars) {
        const btns = document.querySelectorAll('#rating-stars i');
        btns.forEach(btn => btn.style.pointerEvents = 'none');
        try {
            const res = await fetch('/api/rate', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' }, 
                body: JSON.stringify({ soul_id: <?= $id ?>, rating: stars }) 
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('avg-rating').innerText = parseFloat(data.avg_rating).toFixed(1);
                document.getElementById('total-ratings').innerText = `(${data.total_ratings})`;
                
                const roundedAvg = Math.round(data.avg_rating);
                btns.forEach((btn, idx) => {
                    if (idx + 1 <= roundedAvg) {
                        btn.classList.remove('text-zinc-600');
                        btn.classList.add('text-amber-400');
                        btn.setAttribute('aria-checked', 'true');
                    } else {
                        btn.classList.remove('text-amber-400');
                        btn.classList.add('text-zinc-600');
                        btn.setAttribute('aria-checked', 'false');
                    }
                });
            } else {
                if (data.error && data.error.includes('Login')) {
                    window.location.href = '<?= url('/login') ?>';
                } else {
                    alert(data.error || `<?= addslashes(__('Rating failed')) ?>`);
                }
            }
        } catch (e) { 
            alert(`<?= addslashes(__('Network error')) ?>`); 
        } finally { 
            btns.forEach(btn => btn.style.pointerEvents = 'auto'); 
        }
    }

    async function likeSoul() {
        const btn = document.getElementById('like-btn');
        const icon = btn.querySelector('i');
        const countSpan = document.getElementById('like-count');
        btn.style.pointerEvents = 'none';
        
        const originalClassName = icon.className;
        icon.className = 'fas fa-spinner fa-spin text-zinc-400';
        try {
            const res = await fetch('/api/like', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' }, 
                body: JSON.stringify({ soul_id: <?= $id ?> }) 
            });
            const data = await res.json();
            if (data.success) {
                let currentCount = parseInt(countSpan.innerText);
                if (data.liked) {
                    countSpan.innerText = currentCount + 1;
                    icon.className = 'fas fa-heart text-red-400 animate-bounce';
                    setTimeout(() => icon.classList.remove('animate-bounce'), 1000);
                } else {
                    countSpan.innerText = Math.max(currentCount - 1, 0);
                    icon.className = 'fas fa-heart text-zinc-500';
                }
            } else {
                if (data.error && data.error.includes('Login')) {
                    window.location.href = '<?= url('/login') ?>'; 
                } else {
                    alert(data.error || `<?= addslashes(__('Operation failed')) ?>`);
                    icon.className = originalClassName;
                }
            }
        } catch (e) { 
            alert(`<?= addslashes(__('Network error')) ?>`); 
            icon.className = originalClassName;
        } finally { 
            btn.style.pointerEvents = 'auto'; 
        }
    }

    async function forkSoul() {
        const btn = document.getElementById('fork-btn');
        const originalHtml = btn.innerHTML;
        btn.style.pointerEvents = 'none';
        btn.innerHTML = `<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> <?= addslashes(__('Forking...')) ?>`;
        try {
            const res = await fetch('/api/fork', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>' }, body: JSON.stringify({ soul_id: <?= $id ?> }) });
            const data = await res.json();
            if (data.success && data.new_soul_id) window.location.href = data.url;
            else {
                if(data.error === 'Login required') window.location.href = '<?= url('/login') ?>'; else alert(data.error || `<?= addslashes(__('Fork failed')) ?>`);
                btn.innerHTML = originalHtml;
            }
        } catch (e) { alert(`<?= addslashes(__('Network error')) ?>`); btn.innerHTML = originalHtml; } finally { btn.style.pointerEvents = 'auto'; }
    }
</script>
<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>