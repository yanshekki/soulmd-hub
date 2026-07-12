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

$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
$chatBaseUrl = url('/chat'); // /chat or /zh/chat

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

    <section id="detail-view" class="hidden max-w-3xl mx-auto" aria-live="polite">
        <button type="button" id="btn-back" class="mb-6 text-sm text-zinc-400 hover:text-emerald-400 transition inline-flex items-center gap-2">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= htmlspecialchars(__('Back to apps')) ?>
        </button>

        <div class="bg-zinc-900/50 border border-white/10 rounded-3xl p-6 sm:p-8 backdrop-blur-sm shadow-2xl">
            <div class="flex items-start gap-4 mb-6">
                <div id="detail-icon" class="w-12 h-12 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-xl shrink-0">
                    <i class="fas fa-puzzle-piece" aria-hidden="true"></i>
                </div>
                <div class="min-w-0">
                    <h2 id="detail-title" class="text-2xl font-bold tracking-tight text-white"></h2>
                    <p id="detail-desc" class="text-sm text-zinc-400 mt-1 leading-relaxed"></p>
                </div>
            </div>

            <div class="mb-8">
                <h3 class="text-sm font-semibold text-zinc-300 mb-3 flex items-center gap-2">
                    <i class="fas fa-robot text-emerald-400" aria-hidden="true"></i>
                    <?= htmlspecialchars(__('Choose AI soul')) ?>
                </h3>
                <div id="soul-picker" class="space-y-3" role="radiogroup" aria-label="<?= htmlspecialchars(__('Choose AI soul')) ?>"></div>
                <p id="soul-picker-error" class="hidden mt-2 text-xs text-red-400"></p>
            </div>

            <form id="app-form" class="space-y-5"></form>

            <p id="form-error" class="hidden mt-4 text-sm text-red-400"></p>

            <button type="submit" form="app-form" id="run-btn"
                class="mt-8 w-full py-4 bg-emerald-500 text-zinc-950 text-base font-bold rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg disabled:opacity-60 disabled:cursor-not-allowed">
                <span id="run-text"><i class="fas fa-comments mr-1" aria-hidden="true"></i> <?= htmlspecialchars(__('Start chat for AI reply')) ?></span>
                <span id="run-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full" aria-hidden="true"></span>
            </button>
        </div>
    </section>
</main>

<script>
(function () {
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    const chatBaseUrl = <?= json_encode($chatBaseUrl, JSON_UNESCAPED_UNICODE) ?>;
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
    };

    let activeCategory = '';
    let searchTimer = null;
    let currentSlug = null;
    let selectedSoulId = null;

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
            const n = app.soul_count || 1;
            const countLabel = i18n.soulsCount.replace(':n', String(n));
            return `
            <button type="button" data-slug="${escapeHTML(app.slug)}"
                class="app-card text-left group bg-zinc-900/60 border border-white/10 hover:border-emerald-400/40 rounded-3xl p-5 transition shadow-lg hover:-translate-y-0.5 duration-200">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:bg-emerald-500/20 transition">
                        <i class="fas ${escapeHTML(icon)}" aria-hidden="true"></i>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        ${badgeHtml(app.badge)}
                        <span class="text-[10px] text-zinc-500">${escapeHTML(countLabel)}</span>
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

    function renderSoulPicker(souls) {
        const box = document.getElementById('soul-picker');
        const err = document.getElementById('soul-picker-error');
        err.classList.add('hidden');
        selectedSoulId = null;

        if (!souls || !souls.length) {
            box.innerHTML = `<p class="text-sm text-zinc-500">${escapeHTML(i18n.failApp)}</p>`;
            return;
        }

        if (souls.length === 1) {
            selectedSoulId = souls[0].id;
        }

        box.innerHTML = souls.map((s, idx) => {
            const checked = (souls.length === 1 || idx === 0) ? 'checked' : '';
            if (checked) selectedSoulId = s.id;
            const desc = (s.description && s.description.trim()) ? s.description : i18n.noDesc;
            const author = s.username
                ? i18n.byAuthor.replace(':name', s.username)
                : '';
            return `
            <label class="soul-option block cursor-pointer rounded-2xl border border-white/10 bg-zinc-950/50 p-4 hover:border-emerald-400/40 transition has-[:checked]:border-emerald-400/60 has-[:checked]:bg-emerald-500/5">
                <div class="flex items-start gap-3">
                    <input type="radio" name="soul_id" value="${s.id}" class="mt-1 accent-emerald-500" ${checked}>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="font-bold text-white text-sm">${escapeHTML(s.title || ('#' + s.id))}</span>
                            ${s.role ? `<span class="text-[10px] px-2 py-0.5 rounded-full bg-white/5 text-zinc-400 border border-white/10">${escapeHTML(i18n.roleLabel)}: ${escapeHTML(s.role)}</span>` : ''}
                        </div>
                        ${author ? `<div class="text-[11px] text-zinc-500 mb-1.5">${escapeHTML(author)}</div>` : ''}
                        <p class="text-xs text-zinc-400 leading-relaxed line-clamp-4">${escapeHTML(desc)}</p>
                    </div>
                </div>
            </label>`;
        }).join('');

        box.querySelectorAll('input[name="soul_id"]').forEach(inp => {
            inp.addEventListener('change', () => {
                selectedSoulId = parseInt(inp.value, 10) || null;
                err.classList.add('hidden');
            });
        });
    }

    async function openApp(slug) {
        currentSlug = slug;
        selectedSoulId = null;
        document.getElementById('catalog-view').classList.add('hidden');
        document.getElementById('detail-view').classList.remove('hidden');
        document.getElementById('form-error').classList.add('hidden');

        const form = document.getElementById('app-form');
        form.innerHTML = `<div class="text-zinc-500 text-sm py-4">${escapeHTML(i18n.loading)}</div>`;
        document.getElementById('soul-picker').innerHTML = `<div class="text-zinc-500 text-sm">${escapeHTML(i18n.loading)}</div>`;

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
            form.innerHTML = (app.fields || []).map(renderField).join('');
            history.replaceState(null, '', '#/' + encodeURIComponent(slug));
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
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    document.getElementById('btn-back').addEventListener('click', showCatalog);

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

        const checked = document.querySelector('input[name="soul_id"]:checked');
        selectedSoulId = checked ? (parseInt(checked.value, 10) || null) : selectedSoulId;
        if (!selectedSoulId) {
            soulErr.textContent = i18n.pickSoul;
            soulErr.classList.remove('hidden');
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

    const hash = (location.hash || '').replace(/^#\/?/, '');
    loadApps().then(() => {
        if (hash) openApp(decodeURIComponent(hash));
    });
})();
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>
