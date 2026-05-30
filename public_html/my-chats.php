<?php
/**
 * SoulMD Hub - My Chats Page
 * (V5: 100% SPA Async Fetch API, Dual-Track Pagination Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
loadTranslations('my-chats');

$isLoggedIn = isset($_SESSION['user_id']);

$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow flex flex-col">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-10 border-b border-white/10 pb-6">
        <div>
            <h1 class="text-4xl font-bold tracking-tighter"><?= __('My Chats') ?></h1>
            <p class="text-zinc-400 mt-1"><?= __('My Chats Subtitle') ?></p>
        </div>
        <div>
            <a href="<?= url('/browse') ?>" class="px-6 py-3 bg-white text-zinc-950 rounded-2xl font-bold hover:bg-zinc-200 transition flex items-center gap-2 shadow-lg">
                <i class="fas fa-search"></i> <?= __('Discover Souls') ?>
            </a>
        </div>
    </div>

    <?php if ($isLoggedIn): ?>
        <h2 class="text-xl font-bold mb-5 flex items-center gap-2 text-white">
            <i class="fas fa-user-circle text-emerald-400"></i> <?= __('My Personal Sessions') ?>
        </h2>
        
        <div id="personal-container" class="min-h-[250px] mb-6"></div>
        <div id="personal-pagination" class="mb-12 flex justify-center items-center w-full"></div>
    <?php endif; ?>

    <div id="visited-section" class="hidden flex-grow flex-col">
        <h2 class="text-xl font-bold mb-5 flex items-center gap-2 text-zinc-300">
            <i class="fas fa-history text-zinc-500"></i> <?= __('Recently Viewed') ?>
        </h2>
        <div id="guest-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
    </div>

    <?php if (!$isLoggedIn): ?>
        <div id="guest-loading" class="flex-grow flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-emerald-400"></div>
        </div>

        <div id="guest-empty" class="hidden text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl flex-grow">
            <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500"><i class="fas fa-user-secret text-3xl"></i></div>
            <h2 class="text-2xl font-semibold mb-2"><?= __('Guest History Empty') ?></h2>
            <p class="text-zinc-400 text-sm mb-6 max-w-md mx-auto"><?= __('Guest Empty Desc') ?></p>
            <div class="flex items-center justify-center gap-4">
                <a href="<?= url('/register') ?>" class="px-6 py-3 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg"><?= __('Create Account') ?></a>
                <a href="<?= url('/browse') ?>" class="px-6 py-3 bg-zinc-800 text-white rounded-2xl font-bold hover:bg-zinc-700 transition border border-white/5"><?= __('Explore Souls') ?></a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;

    // 🌍 JavaScript 動態語言變數
    const lang_GuestUser = "<?= addslashes(__('Guest User')) ?>";
    const lang_OwnedBy = "<?= addslashes(__('Owned by')) ?>";
    const lang_ViewSession = "<?= addslashes(__('View Session')) ?>";
    const lang_Unassigned = "<?= addslashes(__('Unassigned')) ?>";
    const lang_Recent = "<?= addslashes(__('Recent')) ?>";
    const lang_ConnError = "<?= addslashes(__('Connection Error')) ?>";
    const lang_ConnErrorDesc = "<?= addslashes(__('Connection Error Desc')) ?>";
    const lang_Page = "<?= addslashes(__('Page')) ?>";
    const lang_Private = "<?= addslashes(__('Private')) ?>";
    const lang_Public = "<?= addslashes(__('Public')) ?>";
    const lang_PrivateTooltip = "<?= addslashes(__('Private Tooltip')) ?>";
    const lang_PublicTooltip = "<?= addslashes(__('Public Tooltip')) ?>";
    const lang_ContinueChat = "<?= addslashes(__('Continue Chat')) ?>";
    
    const url_chat_prefix = "<?= url('/chat/') ?>";
    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }

    // 🚀 分頁器渲染
    function changePage(p) {
        currentPage = p;
        const newUrl = window.location.pathname + '?page=' + currentPage;
        window.history.replaceState({}, '', newUrl);
        loadPersonalChats();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function renderPagination(current, totalPages) {
        const container = document.getElementById('personal-pagination');
        if (totalPages <= 1) { container.innerHTML = ''; return; }

        let html = '';
        html += `<div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">`;
        html += `<button onclick="changePage(${current - 1})" ${current <= 1 ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-left"></i></button>`;
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase">${lang_Page} <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        html += `<button onclick="changePage(${current + 1})" ${current >= totalPages ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-right"></i></button>`;
        html += `</div>`;

        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        html += `<button onclick="changePage(${current - 1})" ${current <= 1 ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-left text-xs"></i></button>`;

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

        html += `<button onclick="changePage(${current + 1})" ${current >= totalPages ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-right text-xs"></i></button>`;
        html += `</div>`;

        container.innerHTML = html;
    }

    // 🚀 非同步載入個人對話
    async function loadPersonalChats() {
        if (!isLoggedIn) return;
        const container = document.getElementById('personal-container');
        const pagination = document.getElementById('personal-pagination');
        
        container.innerHTML = `<div class="flex justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;
        pagination.innerHTML = '';

        try {
            const res = await fetch(`/api/my-chats?page=${currentPage}`);
            const data = await res.json();
            
            if (data.success && data.data.length > 0) {
                let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
                data.data.forEach(chat => {
                    const dateObj = new Date(chat.created_at.replace(/-/g, '/'));
                    const dateStr = isNaN(dateObj) ? lang_Recent : dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    const roleLabel = chat.role ? escapeHTML(chat.role) : lang_Unassigned;
                    
                    const privacyBadge = chat.is_private == 1
                        ? `<span class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wider border bg-emerald-500/10 text-emerald-400 border-emerald-500/20" title="${lang_PrivateTooltip}"><i class="fas fa-lock mr-1"></i>${lang_Private}</span>`
                        : `<span class="text-[10px] px-2 py-1 rounded-md font-bold uppercase tracking-wider border bg-zinc-800 text-zinc-400 border-white/5" title="${lang_PublicTooltip}"><i class="fas fa-globe mr-1"></i>${lang_Public}</span>`;

                    html += `
                        <div class="bg-zinc-900/60 border border-emerald-500/20 rounded-3xl p-6 hover:border-emerald-400/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-xl hover:-translate-y-1 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-500/50 to-transparent"></div>
                            <div>
                                <div class="flex justify-between items-start gap-4 mb-4">
                                    <div>
                                        <div class="font-bold text-xl text-white tracking-tight mb-1 line-clamp-1" title="${escapeHTML(chat.title)}">${escapeHTML(chat.title)}</div>
                                        <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                            <span>${escapeHTML(chat.role_icon || '✨')} ${roleLabel}</span>
                                            <span>•</span>
                                            <span>${dateStr}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">${privacyBadge}</div>
                                </div>
                                <div class="text-[10px] font-mono text-emerald-500/70 mb-6 bg-black/20 p-2 rounded-lg border border-emerald-500/10 truncate">
                                    <i class="fas fa-link mr-1"></i> ${escapeHTML(chat.session_token)}
                                </div>
                            </div>
                            <div class="pt-4 border-t border-white/5 mt-auto">
                                <a href="${url_chat_prefix}${chat.soul_id}/${chat.session_token}" class="w-full py-3 bg-zinc-800 hover:bg-emerald-500 hover:text-zinc-950 text-white font-bold rounded-xl flex items-center justify-center gap-2 transition shadow-lg">
                                    ${lang_ContinueChat} <i class="fas fa-arrow-right text-xs"></i>
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
                    <div class="text-center py-16 bg-zinc-900/20 border border-white/5 rounded-3xl">
                        <div class="mx-auto w-16 h-16 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-4 text-zinc-500"><i class="fas fa-comments text-2xl"></i></div>
                        <p class="text-zinc-400 text-sm mb-4"><?= addslashes(__('No personal chats')) ?></p>
                        <a href="<?= url('/browse') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-zinc-800 text-white rounded-xl font-bold hover:bg-zinc-700 transition shadow"><?= addslashes(__('Start a Chat')) ?></a>
                    </div>
                `;
            }
        } catch(e) {
            container.innerHTML = `<div class="text-red-400 text-center py-12"><i class="fas fa-wifi mr-2"></i> ${lang_ConnError}</div>`;
        }
    }

    function getGuestTokens() {
        const tokens = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith('soulmd_agreement_')) {
                const parts = key.split('_');
                if (parts.length >= 4) {
                    const token = parts.slice(3).join('_');
                    if (token && !tokens.includes(token)) tokens.push(token);
                }
            }
        }
        return tokens;
    }

    async function loadGuestChats() {
        const tokens = getGuestTokens();
        const loading = document.getElementById('guest-loading');
        const empty = document.getElementById('guest-empty');
        const grid = document.getElementById('guest-grid');
        const section = document.getElementById('visited-section');

        if (tokens.length === 0) {
            if (loading) loading.classList.add('hidden');
            if (empty && !isLoggedIn) empty.classList.remove('hidden');
            return;
        }

        try {
            const res = await fetch('/api/my-chats', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tokens: tokens })
            });
            const result = await res.json();

            if (loading) loading.classList.add('hidden');

            if (result.success && result.data.length > 0) {
                section.classList.remove('hidden');
                section.classList.add('flex');
                let html = '';
                
                result.data.forEach(chat => {
                    const safeDateString = (chat.created_at || '').replace(/-/g, '/');
                    const dateObj = new Date(safeDateString);
                    const dateStr = isNaN(dateObj) ? lang_Recent : dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    
                    const ownerText = chat.owner_username ? `@${escapeHTML(chat.owner_username)}` : lang_GuestUser;
                    const roleText = chat.role ? escapeHTML(chat.role) : lang_Unassigned;
                    
                    html += `
                        <div class="bg-zinc-900/40 border border-dashed border-white/10 rounded-3xl p-6 hover:border-emerald-500/50 transition-all flex flex-col justify-between backdrop-blur-sm shadow-xl hover:-translate-y-1">
                            <div>
                                <div class="flex justify-between items-start gap-4 mb-4">
                                    <div>
                                        <div class="font-bold text-lg text-zinc-300 tracking-tight mb-1 line-clamp-1" title="${escapeHTML(chat.title)}">${escapeHTML(chat.title)}</div>
                                        <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                            <span>${roleText}</span>
                                            <span>•</span>
                                            <span>${dateStr}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="text-[10px] px-2 py-1 rounded-md font-medium border bg-zinc-800/80 text-zinc-400 border-white/5" title="${lang_OwnedBy}"><i class="fas fa-user mr-1"></i>${ownerText}</span>
                                    </div>
                                </div>
                                <div class="text-[10px] font-mono text-zinc-500 mb-6 bg-black/20 p-2 rounded-lg border border-white/5 truncate">
                                    <i class="fas fa-link text-emerald-500/30 mr-1"></i> ${escapeHTML(chat.session_token)}
                                </div>
                            </div>
                            <div class="pt-4 border-t border-white/5 mt-auto">
                                <a href="${url_chat_prefix}${chat.soul_id}/${chat.session_token}" class="w-full py-2.5 bg-zinc-800/50 hover:bg-emerald-500 hover:text-zinc-950 text-zinc-300 font-bold rounded-xl flex items-center justify-center gap-2 transition shadow-sm border border-white/5">
                                    ${lang_ViewSession} <i class="fas fa-external-link-alt text-[10px]"></i>
                                </a>
                            </div>
                        </div>
                    `;
                });
                grid.innerHTML = html;
            } else {
                if (empty && !isLoggedIn) empty.classList.remove('hidden');
            }
        } catch (e) {
            if (loading) loading.classList.add('hidden');
            if (empty && !isLoggedIn) {
                empty.classList.remove('hidden');
                empty.querySelector('h2').innerText = lang_ConnError;
                empty.querySelector('p').innerText = lang_ConnErrorDesc;
            }
        }
    }

    window.onload = function() {
        if (isLoggedIn) loadPersonalChats();
        loadGuestChats();
    };
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>