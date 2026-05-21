<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

$db = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: /browse');
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, u.username, c.icon as role_icon, c.name as role_name 
    FROM souls s 
    LEFT JOIN users u ON s.user_id = u.id 
    LEFT JOIN categories c ON s.role = c.slug 
    WHERE s.id = ? AND s.is_public = 1
");
$stmt->execute([$id]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// 🚨 PHP 端 SEO 友善助手
function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

$encodedUsername = rawurlencode($soul['username'] ?? 'anonymous');
$slugRole = makeSlug($soul['role']);
$slugTitle = makeSlug($soul['title']);

// 🚨 完美 SEO 301 跳轉機制：若果 URL 係舊版短網址，自動跳轉去完整 SEO Path
$canonicalUrl = "/soul/{$encodedUsername}/{$id}/{$slugRole}/{$slugTitle}";
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
$contentData = $soul['content'];

if ($isFolder) {
    $files = json_decode($contentData, true) ?: [];
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
    if (str_ends_with($name, '.JSON')) return ['icon' => 'fa-code', 'color' => 'text-yellow-400', 'border' => 'border-yellow-400'];
    return ['icon' => 'fa-file-alt', 'color' => 'text-zinc-400', 'border' => 'border-zinc-400'];
}

$pageTitle = $soul['title'];
$pageDesc = $soul['description'] ?: 'View this AI soul on SoulMD Hub.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <a href="/browse" class="inline-flex items-center gap-2 text-sm text-zinc-400 hover:text-emerald-400 transition w-fit border border-white/10 bg-zinc-900/50 px-4 py-2 rounded-full">
            <i class="fas fa-arrow-left"></i> Back to Hub
        </a>
        <div class="flex items-center gap-3">
            <button onclick="likeSoul()" id="like-btn" class="flex items-center gap-2 px-5 py-2.5 bg-zinc-900 border border-white/10 rounded-xl hover:border-red-500/50 hover:text-red-400 transition shadow-sm">
                <i class="fas fa-heart <?= $hasLiked ? 'text-red-400' : 'text-zinc-500' ?>"></i>
                <span id="like-count" class="font-medium"><?= $soul['like_count'] ?></span>
            </button>
            <button onclick="forkSoul()" id="fork-btn" class="flex items-center gap-2 px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold hover:bg-emerald-400 transition shadow-lg hover:shadow-emerald-500/20 transform hover:-translate-y-0.5 duration-200">
                <i class="fas fa-code-branch"></i> Fork Soul
            </button>
        </div>
    </div>

    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 mb-10 backdrop-blur-sm">
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <?php if ($soul['role_name']): ?>
                <a href="/browse?role=<?= urlencode($soul['role']) ?>" class="px-3 py-1 bg-white/5 border border-white/10 rounded-full text-xs font-medium hover:bg-white/10 transition">
                    <?= htmlspecialchars($soul['role_icon'] ?? '✨') ?> <?= htmlspecialchars($soul['role_name']) ?>
                </a>
            <?php endif; ?>
            <span class="px-3 py-1 text-xs font-medium rounded-full <?= $isFolder ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' ?>">
                <i class="fas <?= $isFolder ? 'fa-folder-open' : 'fa-file-alt' ?>"></i> <?= $isFolder ? 'Modular Folder' : 'Single .md' ?>
            </span>
        </div>

        <h1 class="text-4xl md:text-5xl font-bold tracking-tight mb-4"><?= htmlspecialchars($soul['title']) ?></h1>
        
        <?php if ($soul['description']): ?>
            <p class="text-lg text-zinc-400 leading-relaxed mb-8 max-w-3xl">
                <?= nl2br(htmlspecialchars($soul['description'])) ?>
            </p>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pt-6 border-t border-white/10">
            <div class="flex flex-wrap items-center gap-6 text-sm text-zinc-400">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-400 to-cyan-400 flex items-center justify-center text-zinc-950 font-bold">
                        <?= strtoupper(substr($soul['username'] ?? 'A', 0, 1)) ?>
                    </div>
                    <a href="/profile/<?= rawurlencode($soul['username'] ?? 'anonymous') ?>" class="font-medium text-white hover:text-emerald-400 transition">
                        @<?= htmlspecialchars($soul['username'] ?? 'Anonymous') ?>
                    </a>
                </div>
                <div class="flex items-center gap-2">
                    <i class="far fa-calendar-alt"></i> <?= date('M j, Y', strtotime($soul['created_at'])) ?>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-code-branch text-emerald-400"></i> <?= $soul['fork_count'] ?> forks
                </div>
                <a href="/soul-versions/<?= $id ?>" class="flex items-center gap-2 hover:text-emerald-400 transition">
                    <i class="fas fa-history text-emerald-500"></i> <?= $versionCount ?> versions
                </a>
                
                <div class="flex items-center gap-2 bg-zinc-950/50 px-3 py-1.5 rounded-lg border border-white/5">
                    <div class="flex text-lg" id="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i onclick="rateSoul(<?= $i ?>)" class="fas fa-star cursor-pointer hover:scale-110 transition <?= $i <= round($avgRating) ? 'text-amber-400' : 'text-zinc-600' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-xs ml-1">
                        <span id="avg-rating" class="text-white font-bold"><?= number_format($avgRating, 1) ?></span> 
                        <span id="total-ratings" class="opacity-50">(<?= $totalRatings ?>)</span>
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-2 lg:items-end">
                <?php if (!empty($domains)): ?>
                    <div class="flex flex-wrap gap-2 justify-end">
                        <?php foreach($domains as $tag): ?>
                            <span class="px-2 py-1 text-[11px] bg-blue-500/10 text-blue-300 border border-blue-500/20 rounded-md">#<?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($compatibilities)): ?>
                    <div class="flex flex-wrap gap-2 justify-end">
                        <?php foreach($compatibilities as $tag): ?>
                            <span class="px-2 py-1 text-[11px] bg-zinc-800 text-zinc-300 border border-white/10 rounded-md"><i class="fas fa-robot text-xs opacity-50"></i> <?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="bg-zinc-900/40 border border-white/10 rounded-3xl overflow-hidden shadow-xl">
        <div class="flex items-center justify-between border-b border-white/10 bg-zinc-950/50 px-2 overflow-x-auto custom-scrollbar">
            <div class="flex pt-2">
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
                    <button onclick="showFile(<?= $i ?>, '<?= $fStyle['border'] ?>', '<?= $fStyle['color'] ?>')" id="tab-btn-<?= $i ?>" class="tab-btn px-5 py-3 text-sm font-medium whitespace-nowrap transition border-b-2 <?= $i === 1 ? $fStyle['border'] . ' ' . $fStyle['color'] : 'border-transparent text-zinc-400 hover:text-white hover:bg-zinc-900/50' ?> rounded-t-lg" data-border="<?= $fStyle['border'] ?>" data-color="<?= $fStyle['color'] ?>">
                        <div class="flex items-center gap-2 text-left">
                            <i class="fas <?= $fStyle['icon'] ?>"></i>
                            <div class="flex flex-col justify-center min-h-[32px]">
                                <?= $pathPrefix ?>
                                <div class="truncate max-w-[150px] leading-tight"><?= $displayName ?></div>
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="flex items-center gap-2 my-2 ml-4 shrink-0">
                <?php if ($isFolder): ?>
                    <a href="/download/soul/<?= $encodedUsername ?>/<?= $id ?>/<?= $slugRole ?>/<?= $slugTitle ?>.zip" class="px-4 py-2 text-xs font-bold bg-zinc-800 text-white border border-white/10 rounded-lg hover:bg-zinc-700 transition flex items-center gap-2 shadow-sm">
                        <i class="fas fa-file-archive text-amber-400"></i> Download .zip
                    </a>
                    <button onclick="copyFullFolder()" class="px-4 py-2 text-xs font-bold bg-white text-black rounded-lg hover:bg-zinc-200 transition flex items-center gap-2 shadow-sm">
                        <i class="fas fa-copy"></i> Copy JSON
                    </button>
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
                <div id="file-<?= $i ?>" class="file-tab <?= $i === 1 ? 'block' : 'hidden' ?> relative">
                    <div class="sticky top-0 z-10 flex justify-end bg-gradient-to-b from-zinc-900/90 to-transparent p-4 pointer-events-none gap-2">
                        
                        <a href="/download/soul/<?= $encodedUsername ?>/<?= $id ?>/<?= $slugRole ?>/<?= $slugTitle ?>/<?= $encodedFilename ?>" target="_blank" class="pointer-events-auto flex items-center gap-2 px-4 py-2 bg-zinc-800/90 hover:bg-zinc-700 text-zinc-200 text-xs font-medium rounded-lg border border-white/10 backdrop-blur transition shadow-lg">
                            <i class="fas fa-external-link-alt"></i> Raw
                        </a>
                        
                        <button onclick="copyRaw(<?= $i ?>, this)" class="pointer-events-auto flex items-center gap-2 px-4 py-2 bg-zinc-800/90 hover:bg-zinc-700 text-zinc-200 text-xs font-medium rounded-lg border border-white/10 backdrop-blur transition shadow-lg">
                            <i class="far fa-copy"></i> Copy
                        </button>
                    </div>
                    
                    <textarea id="raw-<?= $i ?>" class="hidden"><?= htmlspecialchars($safeContent) ?></textarea>
                    
                    <div id="render-<?= $i ?>" class="prose prose-invert prose-emerald max-w-none px-8 pb-10 -mt-6">
                        <div class="animate-pulse text-zinc-500">Rendering Markdown...</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
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

    window.addEventListener('DOMContentLoaded', () => {
        const tabs = document.querySelectorAll('.file-tab');
        tabs.forEach((tab, idx) => {
            const i = idx + 1;
            const rawContent = document.getElementById(`raw-${i}`).value;
            const parsedHTML = marked.parse(rawContent);
            document.getElementById(`render-${i}`).innerHTML = DOMPurify.sanitize(parsedHTML);
        });
    });

    function showFile(n, activeBorder, activeColor) {
        document.querySelectorAll('.file-tab').forEach(el => { el.classList.remove('block'); el.classList.add('hidden'); });
        document.getElementById('file-' + n).classList.remove('hidden');
        document.getElementById('file-' + n).classList.add('block');
        
        document.querySelectorAll('.tab-btn').forEach((btn) => {
            btn.className = btn.className.replace(/border-[a-z]+-400/g, 'border-transparent');
            btn.className = btn.className.replace(/text-[a-z]+-400/g, 'text-zinc-400');
            btn.classList.add('border-transparent', 'text-zinc-400');
        });
        
        const activeBtn = document.getElementById('tab-btn-' + n);
        activeBtn.classList.remove('border-transparent', 'text-zinc-400');
        activeBtn.classList.add(activeBorder, activeColor);
    }

    function copyRaw(id, btn) {
        const text = document.getElementById('raw-' + id).value;
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i> Copied!';
            btn.classList.add('border-emerald-400/50', 'text-white');
            setTimeout(() => { btn.innerHTML = originalHtml; btn.classList.remove('border-emerald-400/50', 'text-white'); }, 2000);
        });
    }

    function copyFullFolder() {
        <?php if($isFolder): ?>
            const jsonStr = <?= json_encode($contentData) ?>;
            navigator.clipboard.writeText(jsonStr).then(() => { alert('✅ Copied as Full Folder JSON!'); });
        <?php endif; ?>
    }

    async function rateSoul(stars) {
        const btns = document.querySelectorAll('#rating-stars i');
        btns.forEach(btn => btn.style.pointerEvents = 'none');
        try {
            const res = await fetch('/api/rate', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
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
                    } else {
                        btn.classList.remove('text-amber-400');
                        btn.classList.add('text-zinc-600');
                    }
                });
            } else {
                if (data.error && data.error.includes('Login')) {
                    window.location.href = '/login';
                } else {
                    alert(data.error || 'Rating failed');
                }
            }
        } catch (e) { 
            alert('Network error'); 
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
                headers: { 'Content-Type': 'application/json' }, 
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
                    window.location.href = '/login'; 
                } else {
                    alert(data.error || 'Operation failed');
                    icon.className = originalClassName;
                }
            }
        } catch (e) { 
            alert('Network error'); 
            icon.className = originalClassName;
        } finally { 
            btn.style.pointerEvents = 'auto'; 
        }
    }

    async function forkSoul() {
        const btn = document.getElementById('fork-btn');
        const originalHtml = btn.innerHTML;
        btn.style.pointerEvents = 'none';
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Forking...`;
        try {
            const res = await fetch('/api/fork', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ soul_id: <?= $id ?> }) });
            const data = await res.json();
            if (data.success && data.new_soul_id) window.location.href = data.url;
            else {
                if(data.error === 'Login required') window.location.href = '/login'; else alert(data.error || 'Fork failed');
                btn.innerHTML = originalHtml;
            }
        } catch (e) { alert('Network error'); btn.innerHTML = originalHtml; } finally { btn.style.pointerEvents = 'auto'; }
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>