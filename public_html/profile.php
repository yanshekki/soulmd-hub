<?php
/**
 * SoulMD Hub - Public Creator Profile Portfolio
 * (Dynamic i18n Internationalization & Robust Mobile-First Grid Edition - Fixed)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('profile');

$db = Database::getInstance();
$pdo = $db->getConnection();

$usernameParam = $_GET['username'] ?? '';

// 1. 撈取目標用戶基本資料與全局統計數據
$userStmt = $pdo->prepare("SELECT id, username, created_at FROM users WHERE username = ?");
$userStmt->execute([$usernameParam]);
$profileUser = $userStmt->fetch();

if (!$profileUser) {
    http_response_code(404);
    $pageTitle = __('User Not Found');
    $pageDesc = __('User Not Found Desc');
    require_once __DIR__ . '/../private/includes/header.php';
    ?>
    <div class="max-w-md w-full mx-auto px-4 py-24 text-center animate-fade-in flex-grow flex flex-col justify-center">
        <div class="w-20 h-20 bg-zinc-900 border border-white/10 rounded-2xl flex items-center justify-center mx-auto mb-6 text-zinc-500"><i class="fas fa-user-slash text-3xl"></i></div>
        <h1 class="text-3xl font-bold mb-2 text-white"><?= __('User Not Found') ?></h1>
        <p class="text-sm text-zinc-400 mb-8"><?= __('User Not Found Desc') ?></p>
        <a href="<?= url('/browse') ?>" class="px-6 py-3 bg-emerald-500 text-zinc-950 font-bold rounded-2xl hover:bg-emerald-400 transition shadow-lg w-fit mx-auto"><?= __('Back to Hub') ?></a>
    </div>
    <?php
    require_once __DIR__ . '/../private/includes/footer.php';
    exit;
}

$profileUserId = (int)$profileUser['id'];
$safeUsername = htmlspecialchars($profileUser['username']);

// 彙整該創作者獲得的讚好與分叉總數
$statsStmt = $pdo->prepare("SELECT COUNT(*) as total_souls, SUM(like_count) as total_likes, SUM(fork_count) as total_forks FROM souls WHERE user_id = ? AND is_public = 1");
$statsStmt->execute([$profileUserId]);
$stats = $statsStmt->fetch();

$totalSouls = (int)($stats['total_souls'] ?? 0);
$totalLikes = (int)($stats['total_likes'] ?? 0);
$totalForks = (int)($stats['total_forks'] ?? 0);

// 🌍 雙語 SEO 動態注入
$pageTitle = __('SEO Title', ['username' => $safeUsername]);
$pageDesc = __('SEO Desc', ['username' => $safeUsername]);
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow flex flex-col">
    
    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 mb-10 backdrop-blur-sm shadow-xl flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500 to-cyan-400"></div>
        <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-5 text-center sm:text-left">
            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-tr from-emerald-400 to-cyan-400 flex items-center justify-center text-zinc-950 font-black text-2xl sm:text-3xl shadow-lg shadow-emerald-500/10 select-none">
                <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">@<?= $safeUsername ?></h1>
                <p class="text-zinc-400 text-xs sm:text-sm mt-1 flex items-center gap-1.5 justify-center sm:justify-start">
                    <i class="far fa-calendar-alt text-zinc-500"></i> <?= date('M Y', strtotime($profileUser['created_at'])) ?>
                </p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 sm:gap-6 text-center border-t md:border-t-0 border-white/5 pt-5 md:pt-0 w-full md:w-auto">
            <div class="px-2 sm:px-4">
                <div class="text-xl sm:text-2xl font-black text-white font-mono"><?= number_format($totalSouls) ?></div>
                <div class="text-[9px] sm:text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1"><?= __('Total Shared') ?></div>
            </div>
            <div class="px-2 sm:px-4 border-x border-white/5">
                <div class="text-xl sm:text-2xl font-black text-emerald-400 font-mono"><?= number_format($totalForks) ?></div>
                <div class="text-[9px] sm:text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1"><?= __('Forks Received') ?></div>
            </div>
            <div class="px-2 sm:px-4">
                <div class="text-xl sm:text-2xl font-black text-red-400 font-mono"><?= number_format($totalLikes) ?></div>
                <div class="text-[9px] sm:text-[10px] text-zinc-500 font-bold uppercase tracking-widest mt-1"><?= __('Likes Received') ?></div>
            </div>
        </div>
    </div>

    <div class="flex-grow flex flex-col">
        <h2 class="text-xl font-bold mb-6 flex items-center gap-2 text-white">
            <i class="fas fa-layer-group text-zinc-500"></i> <?= __('AI Souls Portfolio') ?>
        </h2>

        <div id="portfolio-container" class="min-h-[300px] flex-grow flex flex-col">
            <div class="flex justify-center py-20 flex-grow items-center" id="portfolio-loading">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div>
            </div>
        </div>
        
        <div id="portfolio-pagination" class="mt-10 flex justify-center items-center w-full select-none"></div>
    </div>
</div>

<script>
    let currentPage = 1;
    const profileUserId = <?= $profileUserId ?>;
    const safeUsername = <?= json_encode($safeUsername, JSON_UNESCAPED_UNICODE) ?>;

    // 💡 安全修復：使用 json_encode 防止任何語法報錯斷行
    const lang_Modular = <?= json_encode(__('Modular'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_SingleMd = <?= json_encode(__('Single .md'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Public = <?= json_encode(__('Public'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Unassigned = <?= json_encode(__('Unassigned'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_ViewRepo = <?= json_encode(__('View Repository'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_NoSouls = <?= json_encode(__('No public souls found'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_EmptyDesc = <?= json_encode(__('Empty Desc'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_BackHub = <?= json_encode(__('Back to Hub'), JSON_UNESCAPED_UNICODE) ?>;
    const url_hub = <?= json_encode(url('/browse'), JSON_UNESCAPED_UNICODE) ?>;
    const url_prefix = <?= json_encode(url('/soul/'), JSON_UNESCAPED_UNICODE) ?>;

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }
    
    function makeSlug(str) {
        if (!str) return 'unassigned';
        let slug = str.toLowerCase();
        slug = slug.replace(/[\s_:\/?#\[\]@!$&'()*+,;=<>\\|]+/g, '-');
        slug = slug.replace(/^-+|-+$/g, '');
        return encodeURIComponent(slug);
    }

    function changePage(page) {
        currentPage = page;
        loadPortfolio();
        window.scrollTo({ top: 300, behavior: 'smooth' });
    }

    // 💡 嚴重錯誤修復：完美恢復雙端（手機+桌面）響應式分頁器
    function renderPagination(current, totalPages) {
        const container = document.getElementById('portfolio-pagination');
        if (totalPages <= 1) { 
            container.innerHTML = ''; 
            return; 
        }

        let html = '';
        
        // 📱 手機版 UI (sm:hidden)
        html += `<div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">`;
        if (current > 1) {
            html += `<button onclick="changePage(${current - 1})" class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-left"></i></button>`;
        } else {
            html += `<button disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"><i class="fas fa-chevron-left"></i></button>`;
        }
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase">PAGE <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        if (current < totalPages) {
            html += `<button onclick="changePage(${current + 1})" class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-right"></i></button>`;
        } else {
            html += `<button disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"><i class="fas fa-chevron-right"></i></button>`;
        }
        html += `</div>`;

        // 💻 桌面版 UI (hidden sm:flex)
        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        if (current > 1) {
            html += `<button onclick="changePage(${current - 1})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-left text-xs"></i></button>`;
        } else {
            html += `<button disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"><i class="fas fa-chevron-left text-xs"></i></button>`;
        }

        const windowSize = 2; 
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - windowSize && i <= current + windowSize)) {
                if (i === current) {
                    html += `<button class="w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-500 text-zinc-950 font-bold shadow-md transform scale-105 transition">${i}</button>`;
                } else {
                    html += `<button onclick="changePage(${i})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition font-medium text-sm shadow">${i}</button>`;
                }
            } else if (i === current - windowSize - 1 || i === current + windowSize + 1) {
                html += `<span class="w-10 h-10 flex items-center justify-center text-zinc-500 tracking-widest text-sm">...</span>`;
            }
        }

        if (current < totalPages) {
            html += `<button onclick="changePage(${current + 1})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"><i class="fas fa-chevron-right text-xs"></i></button>`;
        } else {
            html += `<button disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"><i class="fas fa-chevron-right text-xs"></i></button>`;
        }
        html += `</div>`;

        container.innerHTML = html;
    }

    async function loadPortfolio() {
        const container = document.getElementById('portfolio-container');
        const pagination = document.getElementById('portfolio-pagination');
        
        container.innerHTML = `<div class="flex justify-center py-20 flex-grow items-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;
        pagination.innerHTML = '';

        try {
            const res = await fetch(`/api/souls?user_id=${profileUserId}&page=${currentPage}&limit=9&sort=newest`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
                data.data.forEach(soul => {
                    const tags = soul.domain ? soul.domain.split(',').map(t => t.trim()).filter(Boolean).slice(0, 3) : [];
                    let tagsHtml = '';
                    tags.forEach(t => { tagsHtml += `<span class="text-[10px] bg-white/5 text-zinc-300 border border-white/5 px-2 py-0.5 rounded shadow-sm">#${escapeHTML(t)}</span>`; });

                    const seoUrl = `${url_prefix}${encodeURIComponent(safeUsername)}/${soul.id}/${makeSlug(soul.role)}/${makeSlug(soul.title)}`;
                    const typeLabel = soul.file_type === 'full_soul_folder' ? lang_Modular : lang_SingleMd;
                    const roleLabel = soul.role ? escapeHTML(soul.role) : lang_Unassigned;

                    html += `
                        <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-5 sm:p-6 hover:border-emerald-400/40 transition-all shadow-lg flex flex-col justify-between backdrop-blur-sm group">
                            <div>
                                <div class="flex justify-between items-start gap-3 mb-3">
                                    <div class="font-bold text-lg text-white group-hover:text-emerald-400 transition line-clamp-2 leading-tight">${escapeHTML(soul.title)}</div>
                                    <span class="text-[9px] px-2 py-0.5 rounded font-medium border shrink-0 shadow-sm ${soul.file_type === 'full_soul_folder' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20'}">${typeLabel}</span>
                                </div>
                                ${soul.description ? `<p class="text-xs sm:text-sm text-zinc-400 line-clamp-2 mb-4 leading-relaxed">${escapeHTML(soul.description)}</p>` : ''}
                                <div class="flex flex-wrap gap-1.5 mb-5">${tagsHtml}</div>
                            </div>
                            <div class="pt-4 border-t border-white/5 flex flex-col gap-4 mt-auto">
                                <div class="flex items-center justify-between text-xs text-zinc-500">
                                    <span class="truncate pr-2"><i class="fas fa-robot mr-1 text-zinc-600"></i> ${roleLabel}</span>
                                    <div class="flex items-center gap-3 shrink-0 font-mono">
                                        <span title="Forks"><i class="fas fa-code-branch text-emerald-500 mr-1"></i><b>${soul.fork_count}</b></span>
                                        <span title="Likes"><i class="fas fa-heart text-red-500 mr-1"></i><b>${soul.like_count}</b></span>
                                    </div>
                                </div>
                                <a href="${seoUrl}" class="w-full py-2.5 bg-zinc-800 hover:bg-emerald-500 hover:text-zinc-950 font-bold text-xs text-white rounded-xl text-center border border-white/5 transition shadow-inner">
                                    ${lang_ViewRepo} <i class="fas fa-arrow-right text-[10px] ml-0.5"></i>
                                </a>
                            </div>
                        </div>
                    `;
                });
                html += `</div>`;
                container.innerHTML = html;
                renderPagination(data.current_page, data.total_pages);
            } else {
                container.innerHTML = `
                    <div class="text-center py-20 bg-zinc-900/20 border border-white/5 rounded-3xl flex-grow flex flex-col justify-center items-center">
                        <div class="text-5xl mb-4 opacity-40">📁</div>
                        <p class="text-xl font-bold mb-1 text-zinc-300">${lang_NoSouls}</p>
                        <p class="text-sm text-zinc-500 max-w-xs mx-auto mb-6">${lang_EmptyDesc}</p>
                        <a href="${url_hub}" class="px-5 py-2.5 bg-zinc-800 hover:bg-zinc-700 text-white font-medium rounded-xl text-sm transition shadow border border-white/10">${lang_BackHub}</a>
                    </div>
                `;
            }
        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-20 font-medium flex-grow flex items-center justify-center"><i class="fas fa-wifi mr-2"></i> Error loading profile data.</div>`;
        }
    }

    window.onload = loadPortfolio;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>