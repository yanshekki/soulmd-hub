<?php
/**
 * SoulMD Hub - Mini Apps Hub
 * Pick a tool → fill form → choose a mapped soul → open chat with prefilled message.
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/src/ApiSecurity.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
loadTranslations('apps');
$csrfToken = ensureCsrfToken();

// SEO path: /apps/{slug} (no hash)
$initialAppSlug = trim((string)($_GET['slug'] ?? ''));
if ($initialAppSlug !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/', $initialAppSlug)) {
    $initialAppSlug = '';
}

$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
$chatBaseUrl = url('/chat'); // /chat or /zh/chat
$appsBaseUrl = rtrim(url('/apps'), '/'); // /apps or /zh/apps

require_once __DIR__ . '/../private/includes/header.php';
?>

<main class="max-w-6xl w-full mx-auto px-4 sm:px-6 pb-20 pt-8 flex-grow">
    <header class="text-center mb-10">
        <div class="inline-flex items-center gap-2 bg-emerald-900/20 text-emerald-400 px-4 py-1.5 rounded-full text-xs font-medium mb-5 border border-emerald-500/20">
            <i class="fas fa-puzzle-piece" aria-hidden="true"></i> <?= htmlspecialchars(__('Apps Badge')) ?>
        </div>
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tighter mb-3">
            <span class="gradient-text"><?= htmlspecialchars(__('Apps Title')) ?></span>
        </h1>
        <p class="text-base sm:text-lg text-zinc-400 max-w-2xl mx-auto leading-relaxed"><?= htmlspecialchars(__('Apps Subtitle')) ?></p>
    </header>

    <section id="catalog-view" aria-label="<?= htmlspecialchars(__('Apps Title')) ?>">
        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500 text-sm" aria-hidden="true"></i>
                <input type="search" id="apps-search" autocomplete="off"
                    placeholder="<?= htmlspecialchars(__('Search apps')) ?>"
                    class="w-full bg-zinc-950 border border-white/10 rounded-2xl pl-11 pr-4 py-3 text-sm focus:outline-none focus:border-emerald-400 transition shadow-inner">
            </div>
            <div class="flex flex-wrap gap-2" id="category-filters" role="tablist">
                <button type="button" data-cat="" class="cat-btn active px-4 py-2 rounded-full text-sm border border-emerald-400/40 bg-emerald-500/15 text-emerald-300 font-medium transition"><?= htmlspecialchars(__('All categories')) ?></button>
                <button type="button" data-cat="destiny" class="cat-btn px-4 py-2 rounded-full text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_destiny')) ?></button>
                <button type="button" data-cat="career" class="cat-btn px-4 py-2 rounded-full text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_career')) ?></button>
                <button type="button" data-cat="legal" class="cat-btn px-4 py-2 rounded-full text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_legal')) ?></button>
                <button type="button" data-cat="health" class="cat-btn px-4 py-2 rounded-full text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_health')) ?></button>
                <button type="button" data-cat="life" class="cat-btn px-4 py-2 rounded-full text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_life')) ?></button>
                <button type="button" data-cat="emotion" class="cat-btn px-4 py-2 rounded-full text-sm border border-white/10 text-zinc-400 hover:border-emerald-400/40 hover:text-emerald-300 transition"><?= htmlspecialchars(__('cat_emotion')) ?></button>
            </div>
        </div>

        <div id="apps-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 min-h-[120px]">
            <div class="col-span-full flex items-center justify-center py-16 text-zinc-500 text-sm gap-2">
                <span class="animate-spin h-4 w-4 border-2 border-zinc-500 border-t-transparent rounded-full"></span>
                <?= htmlspecialchars(__('Loading apps')) ?>
            </div>
        </div>
    </section>

    <section id="detail-view" class="hidden max-w-6xl mx-auto w-full" aria-live="polite">
        <button type="button" id="btn-back" class="mb-5 text-sm text-zinc-400 hover:text-emerald-400 transition inline-flex items-center gap-2">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= htmlspecialchars(__('Back to apps')) ?>
        </button>

        <!-- App hero -->
        <div class="flex items-start gap-3 sm:gap-4 mb-6 px-1">
            <div id="detail-icon" class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg sm:text-xl shrink-0">
                <i class="fas fa-puzzle-piece" aria-hidden="true"></i>
            </div>
            <div class="min-w-0">
                <h2 id="detail-title" class="text-xl sm:text-2xl font-bold tracking-tight text-white"></h2>
                <p id="detail-desc" class="text-sm text-zinc-400 mt-1 leading-relaxed max-w-2xl"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-6 items-start">
            <!-- Step 1: soul picker (compact + scrollable) -->
            <aside class="lg:col-span-5 bg-zinc-900/60 border border-white/10 rounded-3xl overflow-hidden shadow-xl flex flex-col max-h-[min(70vh,640px)] lg:max-h-[calc(100dvh-12rem)] lg:sticky lg:top-24">
                <div class="shrink-0 p-4 border-b border-white/5 bg-zinc-950/40">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="text-sm font-semibold text-zinc-200 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-500/15 text-emerald-400 text-[11px] font-black flex items-center justify-center">1</span>
                            <?= htmlspecialchars(__('Choose AI soul')) ?>
                        </h3>
                        <span id="soul-count-badge" class="text-[11px] font-mono text-zinc-500 tabular-nums"></span>
                    </div>
                    <div class="relative">
                        <i class="fas fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-xs" aria-hidden="true"></i>
                        <input type="search" id="soul-filter" autocomplete="off"
                            placeholder="<?= htmlspecialchars(__('Filter personas…')) ?>"
                            class="w-full bg-zinc-950 border border-white/10 rounded-xl pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:border-emerald-400/60 transition placeholder-zinc-600">
                    </div>
                </div>

                <div id="soul-picker" role="listbox" aria-label="<?= htmlspecialchars(__('Choose AI soul')) ?>"
                    class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1.5 min-h-[180px]"></div>

                <!-- Selected preview (richer card) -->
                <div id="soul-selected-bar" class="hidden shrink-0 border-t border-emerald-500/25 bg-gradient-to-b from-emerald-500/10 to-zinc-950/80 p-4 max-h-[42%] overflow-y-auto custom-scrollbar">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="text-[10px] uppercase tracking-wider text-emerald-400 font-bold"><?= htmlspecialchars(__('Selected')) ?></div>
                        <div id="soul-selected-stats" class="flex items-center gap-2 text-[11px] text-zinc-400"></div>
                    </div>
                    <div id="soul-selected-title" class="text-sm sm:text-base font-bold text-white leading-snug"></div>
                    <div id="soul-selected-meta" class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px] text-zinc-400"></div>
                    <div id="soul-selected-tags" class="mt-2 flex flex-wrap gap-1.5"></div>
                    <p id="soul-selected-desc" class="text-xs text-zinc-300 mt-2.5 leading-relaxed whitespace-pre-wrap"></p>
                    <a id="soul-selected-link" href="#" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 mt-3 text-[11px] font-semibold text-emerald-400 hover:text-emerald-300 transition">
                        <i class="fas fa-external-link-alt text-[10px]" aria-hidden="true"></i>
                        <?= htmlspecialchars(__('View soul page')) ?>
                    </a>
                </div>
                <p id="soul-picker-error" class="hidden shrink-0 px-4 py-2 text-xs text-red-400 border-t border-red-500/20"></p>
            </aside>

            <!-- Step 2: form -->
            <div class="lg:col-span-7 bg-zinc-900/60 border border-white/10 rounded-3xl p-5 sm:p-7 shadow-xl">
                <h3 class="text-sm font-semibold text-zinc-200 flex items-center gap-2 mb-5">
                    <span class="w-6 h-6 rounded-lg bg-emerald-500/15 text-emerald-400 text-[11px] font-black flex items-center justify-center">2</span>
                    <?= htmlspecialchars(__('Fill in details')) ?>
                </h3>

                <div id="app-disclaimer" class="hidden mb-4 text-xs text-amber-200/90 bg-amber-500/10 border border-amber-500/25 rounded-xl px-3 py-2.5 leading-relaxed"></div>

                <form id="app-form" class="space-y-4"></form>

                <p id="form-error" class="hidden mt-4 text-sm text-red-400"></p>

                <button type="submit" form="app-form" id="run-btn"
                    class="mt-6 w-full py-3.5 sm:py-4 bg-emerald-500 text-zinc-950 text-sm sm:text-base font-bold rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg shadow-emerald-500/10 disabled:opacity-60 disabled:cursor-not-allowed sticky bottom-4">
                    <span id="run-text"><i class="fas fa-comments mr-1" aria-hidden="true"></i> <?= htmlspecialchars(__('Start chat for AI reply')) ?></span>
                    <span id="run-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    const chatBaseUrl = <?= json_encode($chatBaseUrl, JSON_UNESCAPED_UNICODE) ?>;
    const appsBaseUrl = <?= json_encode($appsBaseUrl, JSON_UNESCAPED_UNICODE) ?>;
    const initialSlug = <?= json_encode($initialAppSlug, JSON_UNESCAPED_UNICODE) ?>;
    const i18n = {
        loading: <?= json_encode(__('Loading apps'), JSON_UNESCAPED_UNICODE) ?>,
        empty: <?= json_encode(__('No apps found'), JSON_UNESCAPED_UNICODE) ?>,
        failList: <?= json_encode(__('Failed to load apps'), JSON_UNESCAPED_UNICODE) ?>,
        failApp: <?= json_encode(__('Failed to load app'), JSON_UNESCAPED_UNICODE) ?>,
        network: <?= json_encode(__('Network error'), JSON_UNESCAPED_UNICODE) ?>,
        hot: <?= json_encode(__('Hot'), JSON_UNESCAPED_UNICODE) ?>,
        popular: <?= json_encode(__('Popular'), JSON_UNESCAPED_UNICODE) ?>,
        pickSoul: <?= json_encode(__('Please select an AI soul'), JSON_UNESCAPED_UNICODE) ?>,
        byAuthor: <?= json_encode(__('By :name'), JSON_UNESCAPED_UNICODE) ?>,
        roleLabel: <?= json_encode(__('Role'), JSON_UNESCAPED_UNICODE) ?>,
        noDesc: <?= json_encode(__('No description provided.'), JSON_UNESCAPED_UNICODE) ?>,
        soulsCount: <?= json_encode(__(':n AI options'), JSON_UNESCAPED_UNICODE) ?>,
        noSoulsFound: <?= json_encode(__('No matching souls for this theme'), JSON_UNESCAPED_UNICODE) ?>,
        noFilterMatch: <?= json_encode(__('No personas match your filter'), JSON_UNESCAPED_UNICODE) ?>,
        filterPh: <?= json_encode(__('Filter personas…'), JSON_UNESCAPED_UNICODE) ?>,
        likes: <?= json_encode(__('Likes'), JSON_UNESCAPED_UNICODE) ?>,
        forks: <?= json_encode(__('Forks'), JSON_UNESCAPED_UNICODE) ?>,
        modular: <?= json_encode(__('Modular'), JSON_UNESCAPED_UNICODE) ?>,
        singleFile: <?= json_encode(__('Single file'), JSON_UNESCAPED_UNICODE) ?>,
        viewSoul: <?= json_encode(__('View soul page'), JSON_UNESCAPED_UNICODE) ?>,
    };

    let activeCategory = '';
    let searchTimer = null;
    let currentSlug = null;
    let selectedSoulId = null;
    /** @type {Array<{id:number,title:string,description:string,role:string,username:string}>} */
    let currentSouls = [];

    function escapeHTML(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function randomSessionToken() {
        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);
        return Array.from(bytes, b => b.toString(16).padStart(2, '0')).join('');
    }

    async function loadApps() {
        const grid = document.getElementById('apps-grid');
        const q = document.getElementById('apps-search').value.trim();
        const params = new URLSearchParams();
        if (activeCategory) params.set('category', activeCategory);
        if (q) params.set('q', q);
        try {
            const res = await fetch('/api/apps' + (params.toString() ? '?' + params.toString() : ''));
            const data = await res.json();
            if (!data.success) throw new Error(data.error || i18n.failList);
            renderCards(data.data || []);
        } catch (e) {
            grid.innerHTML = `<div class="col-span-full text-center py-12 text-red-400 text-sm">${escapeHTML(e.message || i18n.failList)}</div>`;
        }
    }

    function badgeHtml(badge) {
        if (!badge) return '';
        const label = badge === 'hot' ? i18n.hot : (badge === 'popular' ? i18n.popular : badge);
        return `<span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-300 border border-amber-400/30 font-bold">${escapeHTML(label)}</span>`;
    }

    function renderCards(apps) {
        const grid = document.getElementById('apps-grid');
        if (!apps.length) {
            grid.innerHTML = `<div class="col-span-full text-center py-12 text-zinc-500 text-sm">${escapeHTML(i18n.empty)}</div>`;
            return;
        }
        grid.innerHTML = apps.map(app => {
            const icon = (app.icon || 'fa-puzzle-piece').replace(/[^a-z0-9-]/gi, '');
            return `
            <button type="button" data-slug="${escapeHTML(app.slug)}"
                class="app-card text-left group bg-zinc-900/60 border border-white/10 hover:border-emerald-400/40 rounded-3xl p-5 transition shadow-lg hover:-translate-y-0.5 duration-200">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500/20 transition">
                        <i class="fas ${escapeHTML(icon)}" aria-hidden="true"></i>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        ${badgeHtml(app.badge)}
                    </div>
                </div>
                <h3 class="text-lg font-bold text-white mb-1.5 group-hover:text-emerald-300 transition">${escapeHTML(app.title)}</h3>
                <p class="text-sm text-zinc-400 leading-relaxed line-clamp-3">${escapeHTML(app.description)}</p>
            </button>`;
        }).join('');

        grid.querySelectorAll('.app-card').forEach(btn => {
            btn.addEventListener('click', () => openApp(btn.getAttribute('data-slug')));
        });
    }

    function initialFromTitle(title) {
        const t = (title || '?').trim();
        return escapeHTML(t.charAt(0) || '?');
    }

    function domainTagsHtml(soul, limit) {
        const tags = Array.isArray(soul.domains) ? soul.domains : [];
        if (!tags.length) return '';
        return tags.slice(0, limit || 3).map(t =>
            `<span class="inline-flex px-1.5 py-0.5 rounded-md bg-white/5 border border-white/10 text-[10px] text-zinc-400">${escapeHTML(t)}</span>`
        ).join('');
    }

    function updateSelectedBar() {
        const bar = document.getElementById('soul-selected-bar');
        const titleEl = document.getElementById('soul-selected-title');
        const metaEl = document.getElementById('soul-selected-meta');
        const tagsEl = document.getElementById('soul-selected-tags');
        const statsEl = document.getElementById('soul-selected-stats');
        const descEl = document.getElementById('soul-selected-desc');
        const linkEl = document.getElementById('soul-selected-link');
        const soul = currentSouls.find(s => s.id === selectedSoulId);
        if (!soul) {
            bar.classList.add('hidden');
            return;
        }
        titleEl.textContent = soul.title || ('#' + soul.id);

        const roleLabel = soul.role_name || soul.role || '';
        const metaBits = [];
        if (soul.username) metaBits.push('<i class="fas fa-user text-[9px] opacity-70"></i> @' + escapeHTML(soul.username));
        if (roleLabel) metaBits.push('<i class="fas fa-tag text-[9px] opacity-70"></i> ' + escapeHTML(roleLabel));
        if (soul.file_type === 'full_soul_folder') metaBits.push(escapeHTML(i18n.modular));
        else if (soul.file_type) metaBits.push(escapeHTML(i18n.singleFile));
        metaEl.innerHTML = metaBits.length
            ? metaBits.map(b => `<span class="inline-flex items-center gap-1">${b}</span>`).join('<span class="text-zinc-600">·</span>')
            : '';

        tagsEl.innerHTML = domainTagsHtml(soul, 6);

        const likes = Number(soul.like_count || 0);
        const forks = Number(soul.fork_count || 0);
        statsEl.innerHTML = `
            <span title="${escapeHTML(i18n.likes)}"><i class="fas fa-heart text-rose-400/80"></i> ${likes}</span>
            <span title="${escapeHTML(i18n.forks)}"><i class="fas fa-code-branch text-sky-400/80"></i> ${forks}</span>`;

        const desc = (soul.description && soul.description.trim()) ? soul.description.trim() : i18n.noDesc;
        descEl.textContent = desc;

        const uname = encodeURIComponent(soul.username || 'anonymous');
        const roleSlug = encodeURIComponent((soul.role || 'other').toString().toLowerCase().replace(/\s+/g, '-'));
        const titleSlug = encodeURIComponent((soul.title || 'soul').toString().toLowerCase().replace(/\s+/g, '-').slice(0, 80));
        linkEl.href = `<?= url('/soul/') ?>${uname}/${soul.id}/${roleSlug}/${titleSlug}`;
        linkEl.classList.remove('hidden');
        bar.classList.remove('hidden');
    }

    function renderSoulPicker(souls) {
        currentSouls = Array.isArray(souls) ? souls.slice() : [];
        const filterEl = document.getElementById('soul-filter');
        if (filterEl) filterEl.value = '';
        selectedSoulId = currentSouls.length ? currentSouls[0].id : null;
        paintSoulList();
        updateSelectedBar();
    }

    function paintSoulList() {
        const box = document.getElementById('soul-picker');
        const err = document.getElementById('soul-picker-error');
        const badge = document.getElementById('soul-count-badge');
        err.classList.add('hidden');

        const filter = (document.getElementById('soul-filter')?.value || '').trim().toLowerCase();
        let list = currentSouls;
        if (filter) {
            list = currentSouls.filter(s => {
                const domains = Array.isArray(s.domains) ? s.domains.join(' ') : (s.domain || '');
                const hay = [s.title, s.username, s.role, s.role_name, s.description, domains].join(' ').toLowerCase();
                return hay.includes(filter);
            });
        }

        badge.textContent = i18n.soulsCount.replace(':n', String(list.length));

        if (!currentSouls.length) {
            box.innerHTML = `<div class="px-3 py-8 text-center text-sm text-amber-300/90">${escapeHTML(i18n.noSoulsFound)}</div>`;
            return;
        }
        if (!list.length) {
            box.innerHTML = `<div class="px-3 py-8 text-center text-sm text-zinc-500">${escapeHTML(i18n.noFilterMatch)}</div>`;
            return;
        }

        if (!list.some(s => s.id === selectedSoulId)) {
            selectedSoulId = list[0].id;
        }

        box.innerHTML = list.map(s => {
            const active = s.id === selectedSoulId;
            const author = s.username ? '@' + s.username : '';
            const role = s.role_name || s.role || '';
            const likes = Number(s.like_count || 0);
            const desc = (s.description && s.description.trim()) ? s.description.trim() : '';
            const tags = domainTagsHtml(s, 2);
            return `
            <button type="button" role="option" aria-selected="${active ? 'true' : 'false'}" data-soul-id="${s.id}"
                class="soul-row w-full text-left rounded-xl px-3 py-2.5 transition border ${
                    active
                        ? 'bg-emerald-500/10 border-emerald-400/40 ring-1 ring-emerald-400/20'
                        : 'bg-zinc-950/30 border-white/5 hover:bg-white/[0.04] hover:border-white/10'
                }">
                <div class="flex items-start gap-3">
                    <span class="w-9 h-9 rounded-xl shrink-0 flex items-center justify-center text-sm font-black mt-0.5 ${
                        active ? 'bg-emerald-500 text-zinc-950' : 'bg-zinc-800 text-zinc-300 border border-white/10'
                    }">${initialFromTitle(s.title)}</span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="text-sm font-semibold text-white leading-snug line-clamp-2">${escapeHTML(s.title || ('#' + s.id))}</span>
                            ${active ? '<i class="fas fa-check text-emerald-400 text-xs shrink-0 mt-1" aria-hidden="true"></i>' : ''}
                        </span>
                        <span class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-zinc-500">
                            ${author ? `<span class="truncate max-w-[9rem]">${escapeHTML(author)}</span>` : ''}
                            ${role ? `<span class="px-1.5 py-0.5 rounded bg-white/5 border border-white/10 text-zinc-400">${escapeHTML(role)}</span>` : ''}
                            ${likes > 0 ? `<span class="text-zinc-500"><i class="fas fa-heart text-rose-400/70 text-[9px]"></i> ${likes}</span>` : ''}
                        </span>
                        ${desc ? `<span class="mt-1.5 block text-[11px] text-zinc-400 leading-relaxed ${active ? 'line-clamp-3' : 'line-clamp-1'}">${escapeHTML(desc)}</span>` : ''}
                        ${tags ? `<span class="mt-1.5 flex flex-wrap gap-1">${tags}</span>` : ''}
                    </span>
                </div>
            </button>`;
        }).join('');

        box.querySelectorAll('.soul-row').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedSoulId = parseInt(btn.getAttribute('data-soul-id'), 10) || null;
                paintSoulList();
                updateSelectedBar();
                err.classList.add('hidden');
                // Keep selected row in view + scroll selected panel into focus on mobile
                btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
        });
        updateSelectedBar();
    }

    async function openApp(slug) {
        currentSlug = slug;
        selectedSoulId = null;
        document.getElementById('catalog-view').classList.add('hidden');
        document.getElementById('detail-view').classList.remove('hidden');
        document.getElementById('form-error').classList.add('hidden');

        const form = document.getElementById('app-form');
        form.innerHTML = `<div class="text-zinc-500 text-sm py-4">${escapeHTML(i18n.loading)}</div>`;
        document.getElementById('soul-picker').innerHTML = `<div class="px-3 py-8 text-center text-sm text-zinc-500">${escapeHTML(i18n.loading)}</div>`;
        document.getElementById('soul-selected-bar').classList.add('hidden');
        document.getElementById('soul-count-badge').textContent = '';

        try {
            const res = await fetch('/api/apps?slug=' + encodeURIComponent(slug));
            const data = await res.json();
            if (!data.success || !data.data) throw new Error(data.error || i18n.failApp);
            const app = data.data;
            document.getElementById('detail-title').textContent = app.title;
            document.getElementById('detail-desc').textContent = app.description;
            const icon = (app.icon || 'fa-puzzle-piece').replace(/[^a-z0-9-]/gi, '');
            document.getElementById('detail-icon').innerHTML = `<i class="fas ${escapeHTML(icon)}" aria-hidden="true"></i>`;
            renderSoulPicker(app.souls || []);
            const disc = document.getElementById('app-disclaimer');
            if (app.disclaimer) {
                disc.textContent = app.disclaimer;
                disc.classList.remove('hidden');
            } else {
                disc.textContent = '';
                disc.classList.add('hidden');
            }
            form.innerHTML = (app.fields || []).map(renderField).join('');
            const cleanPath = appsBaseUrl + '/' + encodeURIComponent(slug);
            const cur = window.location.pathname.replace(/\/+$/, '');
            const target = cleanPath.replace(/\/+$/, '');
            if (cur !== target) {
                history.pushState({ appSlug: slug }, '', cleanPath);
            } else {
                history.replaceState({ appSlug: slug }, '', cleanPath);
            }
            if (app.title) {
                document.title = app.title + ' · ' + <?= json_encode(__('Apps Title'), JSON_UNESCAPED_UNICODE) ?>;
            }
        } catch (e) {
            form.innerHTML = `<div class="text-red-400 text-sm py-4">${escapeHTML(e.message || i18n.failApp)}</div>`;
        }
    }

    function renderField(f) {
        const id = 'field_' + f.name;
        const req = f.required ? 'required' : '';
        const max = f.maxlength ? `maxlength="${parseInt(f.maxlength, 10)}"` : '';
        const ph = f.placeholder ? `placeholder="${escapeHTML(f.placeholder)}"` : '';
        const label = escapeHTML(f.label || f.name);
        let control = '';
        if (f.type === 'textarea') {
            control = `<textarea id="${id}" name="${escapeHTML(f.name)}" rows="3" ${req} ${max} ${ph}
                class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-400 transition shadow-inner"></textarea>`;
        } else if (f.type === 'select') {
            const opts = (f.options || []).map(o =>
                `<option value="${escapeHTML(o.value)}">${escapeHTML(o.label)}</option>`
            ).join('');
            control = `<select id="${id}" name="${escapeHTML(f.name)}" ${req}
                class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-400 transition shadow-inner appearance-none">${opts}</select>`;
        } else {
            control = `<input type="text" id="${id}" name="${escapeHTML(f.name)}" ${req} ${max} ${ph}
                class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-400 transition shadow-inner">`;
        }
        return `<div>
            <label for="${id}" class="block text-sm font-medium mb-2 text-zinc-300">${label}${f.required ? ' <span class="text-emerald-500">*</span>' : ''}</label>
            ${control}
        </div>`;
    }

    function showCatalog() {
        currentSlug = null;
        selectedSoulId = null;
        document.getElementById('detail-view').classList.add('hidden');
        document.getElementById('catalog-view').classList.remove('hidden');
        document.title = <?= json_encode(__('SEO Title'), JSON_UNESCAPED_UNICODE) ?>;
        const cleanPath = appsBaseUrl;
        if (window.location.pathname.replace(/\/$/, '') !== cleanPath.replace(/\/$/, '')) {
            history.pushState({ appSlug: null }, '', cleanPath);
        }
    }

    document.getElementById('btn-back').addEventListener('click', showCatalog);

    document.getElementById('soul-filter').addEventListener('input', () => {
        paintSoulList();
    });

    document.querySelectorAll('.cat-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.cat-btn').forEach(b => {
                b.classList.remove('active', 'border-emerald-400/40', 'bg-emerald-500/15', 'text-emerald-300', 'font-medium');
                b.classList.add('border-white/10', 'text-zinc-400');
            });
            btn.classList.add('active', 'border-emerald-400/40', 'bg-emerald-500/15', 'text-emerald-300', 'font-medium');
            btn.classList.remove('border-white/10', 'text-zinc-400');
            activeCategory = btn.getAttribute('data-cat') || '';
            loadApps();
        });
    });

    document.getElementById('apps-search').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadApps, 250);
    });

    document.getElementById('app-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!currentSlug) return;

        const formErr = document.getElementById('form-error');
        const soulErr = document.getElementById('soul-picker-error');
        formErr.classList.add('hidden');
        soulErr.classList.add('hidden');

        if (!selectedSoulId) {
            soulErr.textContent = i18n.pickSoul;
            soulErr.classList.remove('hidden');
            document.getElementById('soul-picker')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        const fields = {};
        new FormData(e.target).forEach((v, k) => { fields[k] = String(v).trim(); });

        const runBtn = document.getElementById('run-btn');
        const runText = document.getElementById('run-text');
        const runLoading = document.getElementById('run-loading');
        runBtn.disabled = true;
        runText.classList.add('hidden');
        runLoading.classList.remove('hidden');

        try {
            const res = await fetch('/api/apps', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({
                    slug: currentSlug,
                    soul_id: selectedSoulId,
                    fields,
                }),
            });
            const data = await res.json();
            if (!data.success) {
                formErr.textContent = data.error || i18n.failApp;
                formErr.classList.remove('hidden');
                return;
            }

            const sessionToken = randomSessionToken();
            try {
                sessionStorage.setItem('soulmd_app_prefill', JSON.stringify({
                    soulId: data.soul_id,
                    content: data.content,
                    slug: data.slug,
                    ts: Date.now(),
                }));
            } catch (err) { /* private mode */ }

            // chatBaseUrl is already lang-aware (/chat or /zh/chat)
            window.location.href = chatBaseUrl.replace(/\/$/, '') + '/' + data.soul_id + '/' + sessionToken;
        } catch (err) {
            formErr.textContent = err.message || i18n.network;
            formErr.classList.remove('hidden');
        } finally {
            runBtn.disabled = false;
            runText.classList.remove('hidden');
            runLoading.classList.add('hidden');
        }
    });

    function slugFromPath() {
        // Support /apps/{slug} and /zh/apps/{slug}
        const path = window.location.pathname.replace(/\/+$/, '');
        const base = appsBaseUrl.replace(/\/+$/, '');
        if (path === base) return '';
        if (path.indexOf(base + '/') === 0) {
            const rest = path.slice(base.length + 1);
            const seg = rest.split('/').filter(Boolean)[0] || '';
            return /^[a-zA-Z0-9_-]+$/.test(seg) ? seg : '';
        }
        return '';
    }

    window.addEventListener('popstate', () => {
        const slug = slugFromPath();
        if (slug) openApp(slug);
        else showCatalog();
    });

    // Legacy hash URLs: /apps#/feng-shui → /apps/feng-shui
    const legacyHash = (location.hash || '').replace(/^#\/?/, '');
    if (legacyHash && /^[a-zA-Z0-9_-]+$/.test(legacyHash) && !slugFromPath() && !initialSlug) {
        history.replaceState(null, '', appsBaseUrl + '/' + encodeURIComponent(legacyHash));
    }

    const bootSlug = initialSlug || slugFromPath() || ((location.hash || '').replace(/^#\/?/, '') || '');
    loadApps().then(() => {
        if (bootSlug && /^[a-zA-Z0-9_-]+$/.test(bootSlug)) {
            openApp(bootSlug);
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>
