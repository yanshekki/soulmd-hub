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

$soulId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$soulId) {
    header('Location: /my-souls');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND user_id = ?");
$stmt->execute([$soulId, $userId]);
$soul = $stmt->fetch();

if (!$soul) {
    die('Soul not found or access denied');
}

if (isset($_POST['ajax_restore'])) {
    $versionId = (int)$_POST['version_id'];
    $vStmt = $pdo->prepare("SELECT title, content FROM soul_versions WHERE id = ? AND soul_id = ?");
    $vStmt->execute([$versionId, $soulId]);
    $version = $vStmt->fetch();

    if ($version) {
        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$soulId, $soul['title'], $soul['content']]);

        $fileType = strpos(trim($version['content']), '{') === 0 ? 'full_soul_folder' : 'single_md';

        $pdo->prepare("UPDATE souls SET title = ?, content = ?, file_type = ? WHERE id = ? AND user_id = ?")
            ->execute([$version['title'], $version['content'], $fileType, $soulId, $userId]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Version not found']);
    }
    exit;
}

$versionsStmt = $pdo->prepare("SELECT * FROM soul_versions WHERE soul_id = ? ORDER BY edited_at DESC");
$versionsStmt->execute([$soulId]);
$versions = $versionsStmt->fetchAll();

function getFileStyle($filename) {
    $name = strtoupper($filename);
    if (str_contains($name, 'SOUL')) return ['icon' => 'fa-brain', 'color' => 'text-emerald-400', 'border' => 'border-emerald-400'];
    if (str_contains($name, 'STYLE')) return ['icon' => 'fa-palette', 'color' => 'text-purple-400', 'border' => 'border-purple-400'];
    if (str_contains($name, 'RULE')) return ['icon' => 'fa-shield-alt', 'color' => 'text-red-400', 'border' => 'border-red-400'];
    if (str_contains($name, 'SKILL')) return ['icon' => 'fa-tools', 'color' => 'text-amber-400', 'border' => 'border-amber-400'];
    if (str_contains($name, 'MEMORY')) return ['icon' => 'fa-memory', 'color' => 'text-blue-400', 'border' => 'border-blue-400'];
    if (str_contains($name, 'CONTEXT')) return ['icon' => 'fa-globe', 'color' => 'text-cyan-400', 'border' => 'border-cyan-400'];
    if (str_contains($name, 'PROMPT')) return ['icon' => 'fa-terminal', 'color' => 'text-green-400', 'border' => 'border-green-400'];
    if (str_ends_with($name, '.JSON')) return ['icon' => 'fa-code', 'color' => 'text-yellow-400', 'border' => 'border-yellow-400'];
    return ['icon' => 'fa-file-alt', 'color' => 'text-zinc-400', 'border' => 'border-zinc-400'];
}

$pageTitle = 'Version History - ' . $soul['title'];
$pageDesc = 'View and restore previous versions of your soul.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-10 border-b border-white/10 pb-6">
        <div>
            <a href="/my-souls" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                <i class="fas fa-arrow-left"></i> Back to My Souls
            </a>
            <h1 class="text-4xl font-bold tracking-tighter">Version History</h1>
            <p class="text-zinc-400 mt-2 flex items-center gap-2">
                <i class="fas fa-file-alt text-emerald-500"></i> <?= htmlspecialchars($soul['title']) ?>
            </p>
        </div>
        <div>
            <a href="/soul/<?= $soulId ?>" class="px-5 py-2.5 bg-white text-zinc-950 rounded-xl font-bold hover:bg-zinc-200 transition shadow-lg flex items-center gap-2">
                View Current <i class="fas fa-external-link-alt text-xs"></i>
            </a>
        </div>
    </div>

    <?php if (empty($versions)): ?>
        <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
            <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500">
                <i class="fas fa-history text-3xl"></i>
            </div>
            <h2 class="text-2xl font-semibold mb-2">No versions yet</h2>
            <p class="text-zinc-400 text-sm max-w-sm mx-auto">Every time you edit and save this soul, the previous version will be automatically backed up here.</p>
        </div>
    <?php else: ?>
        <div class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-white/10 before:to-transparent">
            
            <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active mb-12">
                <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-zinc-950 bg-emerald-500 text-zinc-950 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10">
                    <i class="fas fa-check text-xs"></i>
                </div>
                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-2xl text-emerald-400 text-sm font-medium text-center shadow-lg">
                    Currently Active Version
                </div>
            </div>

            <?php foreach ($versions as $index => $version): 
                $versionNumber = count($versions) - $index;
                $isFolder = strpos(trim($version['content']), '{') === 0;
                $files = $isFolder ? (json_decode($version['content'], true) ?: []) : ['SOUL.md' => $version['content']];
                if (empty($files)) $files = ['SOUL.md' => $version['content']];
            ?>
                <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-zinc-950 bg-zinc-800 text-zinc-400 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10">
                        <span class="text-xs font-bold font-mono"><?= $versionNumber ?></span>
                    </div>
                    
                    <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-zinc-900/60 border border-white/10 rounded-3xl p-6 backdrop-blur-sm hover:border-white/20 transition-colors shadow-xl">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <div class="text-xs text-emerald-400 font-medium mb-1 tracking-wider uppercase">Version <?= $versionNumber ?></div>
                                <div class="font-bold text-lg mb-1 leading-tight text-white"><?= htmlspecialchars($version['title']) ?></div>
                                <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                    <i class="far fa-clock"></i> <?= date('M j, Y • H:i', strtotime($version['edited_at'])) ?>
                                </div>
                            </div>
                            <?php if ($isFolder): ?>
                                <span class="text-[10px] px-2 py-0.5 rounded font-medium border bg-purple-500/10 text-purple-400 border-purple-500/20 shrink-0">Modular</span>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-white/5">
                            <button onclick="toggleContent(<?= $version['id'] ?>)" id="btn-toggle-<?= $version['id'] ?>" class="flex-1 px-4 py-2 bg-zinc-800 text-zinc-300 text-xs font-medium rounded-xl hover:bg-zinc-700 transition flex items-center justify-center gap-2 border border-white/5 shadow-sm">
                                <i class="fas fa-eye" id="icon-<?= $version['id'] ?>"></i> <span>View Content</span>
                            </button>
                            <button onclick="restoreVersion(<?= $version['id'] ?>)" class="flex-1 px-4 py-2 bg-emerald-500/10 text-emerald-400 text-xs font-bold rounded-xl hover:bg-emerald-500 hover:text-zinc-950 transition flex items-center justify-center gap-2 border border-emerald-500/20 shadow-sm">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </div>

                        <div id="content-<?= $version['id'] ?>" class="hidden mt-4 pt-4 border-t border-white/5">
                            <?php if (count($files) > 1): ?>
                                <div class="flex overflow-x-auto border-b border-white/10 mb-4 pb-2 custom-scrollbar gap-2">
                                    <?php 
                                    $fIdx = 0; 
                                    foreach($files as $fname => $fcontent): 
                                        $fIdx++; 
                                        $fStyle = getFileStyle($fname); 
                                        
                                        $displayName = htmlspecialchars($fname);
                                        $pathPrefix = '';
                                        if (strpos($fname, '/') !== false) {
                                            $parts = explode('/', $fname);
                                            $nameOnly = array_pop($parts);
                                            $pathOnly = implode('/', $parts);
                                            $displayName = htmlspecialchars($nameOnly);
                                            $pathPrefix = '<div class="text-[9px] opacity-50 -mb-1 truncate max-w-[80px] leading-tight">' . htmlspecialchars($pathOnly) . '/</div>';
                                        }
                                    ?>
                                        <button onclick="showVersionFile(<?= $version['id'] ?>, <?= $fIdx ?>, '<?= $fStyle['border'] ?>', '<?= $fStyle['color'] ?>')" id="tab-btn-v<?= $version['id'] ?>-<?= $fIdx ?>" class="tab-btn-v<?= $version['id'] ?> px-3 py-1.5 text-[11px] font-medium whitespace-nowrap transition border-b-2 rounded-t-lg bg-zinc-950/50 <?= $fIdx === 1 ? $fStyle['border'] . ' ' . $fStyle['color'] : 'border-transparent text-zinc-400 hover:text-white hover:bg-zinc-800' ?>">
                                            <div class="flex items-center gap-1.5 text-left">
                                                <i class="fas <?= $fStyle['icon'] ?>"></i>
                                                <div class="flex flex-col justify-center min-h-[24px]">
                                                    <?= $pathPrefix ?>
                                                    <div class="truncate max-w-[100px] leading-tight"><?= $displayName ?></div>
                                                </div>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="bg-zinc-950 border border-white/5 p-5 rounded-2xl relative shadow-inner">
                                <?php $fIdx = 0; foreach($files as $fname => $fcontent): $fIdx++; ?>
                                    <div id="file-v<?= $version['id'] ?>-<?= $fIdx ?>" class="file-tab-v<?= $version['id'] ?> <?= $fIdx === 1 ? 'block' : 'hidden' ?>">
                                        <div class="flex justify-between items-center mb-4 border-b border-white/5 pb-2">
                                            <span class="text-xs font-mono text-zinc-500"><?= htmlspecialchars($fname) ?></span>
                                            <button onclick="copyRaw(<?= $version['id'] ?>, <?= $fIdx ?>, this)" class="text-[10px] bg-zinc-800 text-zinc-300 px-3 py-1.5 rounded-md border border-white/10 hover:bg-zinc-700 transition shadow">
                                                <i class="far fa-copy mr-1"></i> Copy
                                            </button>
                                        </div>
                                        <textarea id="raw-v<?= $version['id'] ?>-<?= $fIdx ?>" class="raw-v<?= $version['id'] ?> hidden" data-idx="<?= $fIdx ?>"><?= htmlspecialchars($fcontent) ?></textarea>
                                        
                                        <div id="render-v<?= $version['id'] ?>-<?= $fIdx ?>" class="prose prose-invert prose-emerald max-w-none prose-sm overflow-y-auto max-h-[350px] custom-scrollbar pr-2 text-zinc-300 leading-relaxed">
                                            <div class="animate-pulse text-zinc-500 flex items-center gap-2"><i class="fas fa-spinner fa-spin"></i> Rendering Markdown...</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    marked.setOptions({
        breaks: true,
        gfm: true,
        highlight: function(code, lang) {
            const language = hljs.getLanguage(lang) ? lang : 'plaintext';
            return hljs.highlightAuto(code).value;
        }
    });

    async function restoreVersion(versionId) {
        if (!confirm('Are you sure you want to restore this version?\n\nThe currently active version will be automatically backed up as a new history record.')) return;

        const formData = new FormData();
        formData.append('ajax_restore', '1');
        formData.append('version_id', versionId);

        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.href = '/my-souls'; 
            } else {
                alert(data.error || 'Restore failed');
            }
        } catch(e) {
            alert('Network error while restoring.');
        }
    }

    function toggleContent(versionId) {
        const contentDiv = document.getElementById('content-' + versionId);
        const icon = document.getElementById('icon-' + versionId);
        const btnSpan = document.querySelector(`#btn-toggle-${versionId} span`);
        
        if (contentDiv.classList.contains('hidden')) {
            contentDiv.classList.remove('hidden');
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            btnSpan.innerText = 'Hide Content';

            const textareas = document.querySelectorAll(`.raw-v${versionId}`);
            textareas.forEach(ta => {
                const idx = ta.dataset.idx;
                const renderDiv = document.getElementById(`render-v${versionId}-${idx}`);
                if (renderDiv.innerHTML.includes('Rendering Markdown...')) {
                    renderDiv.innerHTML = marked.parse(ta.value);
                }
            });
        } else {
            contentDiv.classList.add('hidden');
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            btnSpan.innerText = 'View Content';
        }
    }

    function showVersionFile(versionId, fileIdx, activeBorder, activeColor) {
        document.querySelectorAll(`.file-tab-v${versionId}`).forEach(el => {
            el.classList.remove('block');
            el.classList.add('hidden');
        });
        document.getElementById(`file-v${versionId}-${fileIdx}`).classList.remove('hidden');
        document.getElementById(`file-v${versionId}-${fileIdx}`).classList.add('block');
        
        document.querySelectorAll(`.tab-btn-v${versionId}`).forEach((btn) => {
            btn.className = btn.className.replace(/border-[a-z]+-400/g, 'border-transparent');
            btn.className = btn.className.replace(/text-[a-z]+-400/g, 'text-zinc-400');
            btn.classList.add('border-transparent', 'text-zinc-400');
        });
        
        const activeBtn = document.getElementById(`tab-btn-v${versionId}-${fileIdx}`);
        activeBtn.classList.remove('border-transparent', 'text-zinc-400');
        activeBtn.classList.add(activeBorder, activeColor);
    }

    function copyRaw(versionId, fileIdx, btn) {
        const text = document.getElementById(`raw-v${versionId}-${fileIdx}`).value;
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i> Copied!';
            btn.classList.add('border-emerald-400/50', 'text-white');
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('border-emerald-400/50', 'text-white');
            }, 2000);
        });
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>