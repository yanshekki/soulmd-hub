<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

$db = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: browse.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ? AND is_public = 1");
$stmt->execute([$id]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    die('Soul not found');
}

setSEO($soul['title'], $soul['description'] ?: 'View this AI soul on SoulMD Hub.');

$isFolder = $soul['file_type'] === 'full_soul_folder';
$contentData = $soul['content'];

if ($isFolder) {
    $files = json_decode($contentData, true) ?: [];
} else {
    $files = ['SOUL.md' => $contentData];
}

// Average rating
$avgStmt = $pdo->prepare("SELECT AVG(rating) as avg FROM soul_ratings WHERE soul_id = ?");
$avgStmt->execute([$id]);
$avgRating = $avgStmt->fetch()['avg'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($soul['title']) ?> - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .markdown-content { line-height: 1.75; }
        .markdown-content h1, .markdown-content h2, .markdown-content h3 { margin-top: 1.5em; font-weight: 600; }
        .markdown-content pre { background: #111827; padding: 1.25rem; border-radius: 1rem; overflow-x: auto; font-size: 0.875rem; }
        .markdown-content code { font-family: ui-monospace, monospace; }
        .star { color: #fbbf24; cursor: pointer; transition: transform 0.1s; }
        .star:hover { transform: scale(1.3); }
        .star.active { color: #fbbf24; }
    </style>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
        <!-- Back + Actions -->
        <div class="flex items-center justify-between mb-8">
            <a href="browse.php" class="flex items-center gap-2 text-sm text-zinc-400 hover:text-white transition">
                <i class="fas fa-arrow-left"></i> Back to Browse
            </a>
            <div class="flex items-center gap-3">
                <!-- Like Button -->
                <button onclick="likeSoul()" id="like-btn"
                        class="flex items-center gap-2 px-5 py-2 border border-white/30 rounded-3xl hover:bg-white/5 transition">
                    <i class="fas fa-heart text-red-400"></i>
                    <span id="like-count"><?= $soul['like_count'] ?></span>
                </button>
                
                <!-- Fork Button -->
                <button onclick="forkSoul()" id="fork-btn"
                        class="flex items-center gap-2 px-5 py-2 bg-white text-black rounded-3xl font-semibold hover:bg-zinc-200 transition">
                    <i class="fas fa-copy"></i> Fork
                </button>
            </div>
        </div>

        <!-- Title + Metadata -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-3">
                <h1 class="text-4xl font-bold"><?= htmlspecialchars($soul['title']) ?></h1>
                <span class="px-4 py-1 text-xs font-medium rounded-3xl <?= $isFolder ? 'bg-purple-900 text-purple-300' : 'bg-emerald-900 text-emerald-300' ?>">
                    <?= $isFolder ? 'Full Soul Folder' : 'Single .md' ?>
                </span>
            </div>
            <div class="flex items-center gap-6 text-sm text-zinc-400">
                <div><?= date('F j, Y', strtotime($soul['created_at'])) ?></div>
                <div><?= $soul['fork_count'] ?> forks</div>
                <div class="flex items-center gap-1">
                    <span id="avg-rating"><?= number_format($avgRating, 1) ?></span>
                    <span class="text-amber-400">★</span>
                </div>
            </div>
        </div>

        <!-- Rating -->
        <div class="mb-10 flex items-center gap-3">
            <span class="text-sm text-zinc-400">Rate this soul</span>
            <div class="flex text-3xl" id="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button onclick="rateSoul(<?= $i ?>)" class="star transition hover:scale-125 <?= $i <= round($avgRating) ? 'text-amber-400' : 'text-zinc-600' ?>">
                        ★
                    </button>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Description -->
        <?php if ($soul['description']): ?>
            <div class="text-lg text-zinc-300 mb-12 leading-relaxed">
                <?= nl2br(htmlspecialchars($soul['description'])) ?>
            </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
            <?php if ($isFolder && count($files) > 1): ?>
                <div class="flex border-b border-white/20 mb-8 overflow-x-auto">
                    <?php $i = 0; foreach ($files as $filename => $fileContent): $i++; ?>
                        <button onclick="showFile(<?= $i ?>)" 
                                class="tab-btn px-8 py-4 text-sm font-medium whitespace-nowrap <?= $i === 1 ? 'border-b-2 border-white text-white' : 'text-zinc-400' ?>">
                            <?= htmlspecialchars($filename) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php $i = 0; foreach ($files as $filename => $fileContent): $i++; ?>
                    <div id="file-<?= $i ?>" class="file-tab <?= $i === 1 ? '' : 'hidden' ?>">
                        <pre class="markdown-content whitespace-pre-wrap text-sm leading-relaxed p-6 bg-black/50 rounded-2xl"><?= htmlspecialchars($fileContent) ?></pre>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <pre class="markdown-content whitespace-pre-wrap text-sm leading-relaxed p-6 bg-black/50 rounded-2xl"><?= htmlspecialchars($contentData) ?></pre>
            <?php endif; ?>
        </div>

        <div class="mt-12 text-center text-xs text-zinc-500">
            This soul is public. Fork it to make your own copy.
        </div>
    </div>

    <script>
        let currentRating = <?= round($avgRating) ?>;

        // Rate Soul (1-5 stars)
        async function rateSoul(stars) {
            const btns = document.querySelectorAll('#rating-stars button');
            btns.forEach((btn, i) => btn.style.pointerEvents = 'none');

            try {
                const res = await fetch('api/rate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ soul_id: <?= $id ?>, rating: stars })
                });
                const data = await res.json();

                if (data.success) {
                    currentRating = stars;
                    // Update stars
                    btns.forEach((btn, i) => {
                        btn.classList.toggle('text-amber-400', i + 1 <= stars);
                        btn.classList.toggle('text-zinc-600', i + 1 > stars);
                    });
                    // Update average (simple refresh for now)
                    location.reload();
                } else {
                    alert(data.error || 'Rating failed');
                }
            } catch (e) {
                alert('Network error');
            } finally {
                btns.forEach((btn, i) => btn.style.pointerEvents = 'auto');
            }
        }

        // Like Soul
        async function likeSoul() {
            const btn = document.getElementById('like-btn');
            btn.style.pointerEvents = 'none';
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span id="like-count"><?= $soul['like_count'] + 1 ?></span>`;

            try {
                const res = await fetch('api/like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ soul_id: <?= $id ?> })
                });
                const data = await res.json();

                if (data.success) {
                    // Update count without reload
                    document.getElementById('like-count').innerText = <?= $soul['like_count'] + 1 ?>;
                    btn.innerHTML = `<i class="fas fa-heart text-red-400"></i> <span id="like-count"><?= $soul['like_count'] + 1 ?></span>`;
                } else {
                    alert(data.error || 'Like failed');
                    location.reload();
                }
            } catch (e) {
                alert('Network error');
                location.reload();
            } finally {
                btn.style.pointerEvents = 'auto';
            }
        }

        // Fork Soul
        async function forkSoul() {
            const btn = document.getElementById('fork-btn');
            const originalText = btn.innerHTML;
            btn.style.pointerEvents = 'none';
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Forking...`;

            try {
                const res = await fetch('api/fork.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ soul_id: <?= $id ?> })
                });
                const data = await res.json();

                if (data.success && data.new_soul_id) {
                    window.location.href = `soul.php?id=${data.new_soul_id}`;
                } else {
                    alert(data.error || 'Fork failed');
                    btn.innerHTML = originalText;
                    btn.style.pointerEvents = 'auto';
                }
            } catch (e) {
                alert('Network error');
                btn.innerHTML = originalText;
                btn.style.pointerEvents = 'auto';
            }
        }

        // File tab switching
        function showFile(n) {
            document.querySelectorAll('.file-tab').forEach(el => el.classList.add('hidden'));
            document.getElementById('file-' + n).classList.remove('hidden');
            
            document.querySelectorAll('.tab-btn').forEach((btn, i) => {
                btn.classList.toggle('border-b-2', i + 1 === n);
                btn.classList.toggle('border-white', i + 1 === n);
                btn.classList.toggle('text-white', i + 1 === n);
                btn.classList.toggle('text-zinc-400', i + 1 !== n);
            });
        }

        // Init
        window.onload = function() {
            // Highlight current rating
            const btns = document.querySelectorAll('#rating-stars button');
            btns.forEach((btn, i) => {
                if (i + 1 <= currentRating) {
                    btn.classList.add('text-amber-400');
                    btn.classList.remove('text-zinc-600');
                }
            });
        };
    </script>
</body>
</html>