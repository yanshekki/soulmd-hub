<?php
/**
 * SoulMD Hub - Mini Apps Hub
 * Catalog of form-driven LLM tools (name advisor, feng shui, wedding dates, etc.)
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

    <!-- Catalog view -->
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

    <!-- Detail / form / result -->
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

            <form id="app-form" class="space-y-5"></form>

            <button type="submit" form="app-form" id="run-btn"
                class="mt-8 w-full py-4 bg-emerald-500 text-zinc-950 text-base font-bold rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg disabled:opacity-60 disabled:cursor-not-allowed">
                <span id="run-text"><i class="fas fa-bolt mr-1" aria-hidden="true"></i> <?= htmlspecialchars(__('Run app')) ?></span>
                <span id="run-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full" aria-hidden="true"></span>
            </button>

            <div id="result-panel" class="hidden mt-8 pt-6 border-t border-white/10">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-bold text-white"><?= htmlspecialchars(__('Result')) ?></h3>
                    <button type="button" id="btn-rerun" class="text-xs text-emerald-400 hover:text-emerald-300 transition"><?= htmlspecialchars(__('Try again')) ?></button>
                </div>
                <div id="result-body" class="prose prose-invert prose-sm prose-emerald max-w-none bg-zinc-950/60 border border-white/5 rounded-2xl p-5 text-zinc-200 leading-relaxed"></div>
                <div id="truncation-notice" class="hidden mt-4"></div>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    const csrfToken = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;
    const upgradeUrl = <?= json_encode(url('/upgrade'), JSON_UNESCAPED_UNICODE) ?>;
    const i18n = {
        loading: <?= json_encode(__('Loading apps'), JSON_UNESCAPED_UNICODE) ?>,
        empty: <?= json_encode(__('No apps found'), JSON_UNESCAPED_UNICODE) ?>,
        failList: <?= json_encode(__('Failed to load apps'), JSON_UNESCAPED_UNICODE) ?>,
        failApp: <?= json_encode(__('Failed to load app'), JSON_UNESCAPED_UNICODE) ?>,
        network: <?= json_encode(__('Network error'), JSON_UNESCAPED_UNICODE) ?>,
        emptyReply: <?= json_encode(__('Empty result'), JSON_UNESCAPED_UNICODE) ?>,
        running: <?= json_encode(__('Running…'), JSON_UNESCAPED_UNICODE) ?>,
        run: <?= json_encode(__('Run app'), JSON_UNESCAPED_UNICODE) ?>,
        hot: <?= json_encode(__('Hot'), JSON_UNESCAPED_UNICODE) ?>,
        popular: <?= json_encode(__('Popular'), JSON_UNESCAPED_UNICODE) ?>,
        vipHint: <?= json_encode(__('Suggested VIP'), JSON_UNESCAPED_UNICODE) ?>,
        truncNotice: <?= json_encode(__('Reply truncated notice'), JSON_UNESCAPED_UNICODE) ?>,
        truncCta: <?= json_encode(__('Reply truncated upgrade CTA'), JSON_UNESCAPED_UNICODE) ?>,
        truncPlain: <?= json_encode(__('Reply truncated plain'), JSON_UNESCAPED_UNICODE) ?>,
    };

    let activeCategory = '';
    let searchTimer = null;
    let currentSlug = null;

    if (typeof marked !== 'undefined') {
        if (typeof marked.use === 'function') marked.use({ breaks: true, gfm: true });
        else if (typeof marked.setOptions === 'function') { try { marked.setOptions({ breaks: true, gfm: true }); } catch (e) {} }
    }

    function escapeHTML(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function parseMarkdown(text) {
        try {
            if (typeof marked !== 'undefined' && marked.parse) return marked.parse(text || '');
        } catch (e) {}
        return escapeHTML(text || '').replace(/\n/g, '<br>');
    }

    function sanitizeHtml(html) {
        if (typeof DOMPurify !== 'undefined') return DOMPurify.sanitize(html);
        return html;
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

    async function openApp(slug) {
        currentSlug = slug;
        document.getElementById('catalog-view').classList.add('hidden');
        document.getElementById('detail-view').classList.remove('hidden');
        document.getElementById('result-panel').classList.add('hidden');
        document.getElementById('result-body').innerHTML = '';
        document.getElementById('truncation-notice').classList.add('hidden');
        document.getElementById('truncation-notice').innerHTML = '';

        const form = document.getElementById('app-form');
        form.innerHTML = `<div class="text-zinc-500 text-sm py-4">${escapeHTML(i18n.loading)}</div>`;

        try {
            const res = await fetch('/api/apps?slug=' + encodeURIComponent(slug));
            const data = await res.json();
            if (!data.success || !data.data) throw new Error(data.error || i18n.failApp);
            const app = data.data;
            document.getElementById('detail-title').textContent = app.title;
            document.getElementById('detail-desc').textContent = app.description;
            const icon = (app.icon || 'fa-puzzle-piece').replace(/[^a-z0-9-]/gi, '');
            document.getElementById('detail-icon').innerHTML = `<i class="fas ${escapeHTML(icon)}" aria-hidden="true"></i>`;
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
        document.getElementById('detail-view').classList.add('hidden');
        document.getElementById('catalog-view').classList.remove('hidden');
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    document.getElementById('btn-back').addEventListener('click', showCatalog);
    document.getElementById('btn-rerun').addEventListener('click', () => {
        document.getElementById('result-panel').classList.add('hidden');
        window.scrollTo({ top: document.getElementById('app-form').offsetTop - 80, behavior: 'smooth' });
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

        const form = e.target;
        const fields = {};
        new FormData(form).forEach((v, k) => { fields[k] = String(v).trim(); });

        const runBtn = document.getElementById('run-btn');
        const runText = document.getElementById('run-text');
        const runLoading = document.getElementById('run-loading');
        runBtn.disabled = true;
        runText.classList.add('hidden');
        runLoading.classList.remove('hidden');

        const resultPanel = document.getElementById('result-panel');
        const resultBody = document.getElementById('result-body');
        const truncBox = document.getElementById('truncation-notice');
        resultPanel.classList.add('hidden');
        truncBox.classList.add('hidden');
        truncBox.innerHTML = '';

        try {
            const res = await fetch('/api/apps', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ slug: currentSlug, fields }),
            });
            const raw = await res.text();
            let data;
            try { data = JSON.parse(raw); } catch (err) {
                throw new Error(i18n.network);
            }

            if (!data.success) {
                resultBody.innerHTML = `<span class="text-amber-400"><i class="fas fa-exclamation-circle"></i> ${escapeHTML(data.error || i18n.failApp)}</span>`;
                resultPanel.classList.remove('hidden');
                if (data.needs_upgrade) {
                    truncBox.innerHTML = `<a href="${upgradeUrl}" class="inline-flex items-center gap-2 mt-2 px-4 py-2 rounded-xl bg-amber-500/15 border border-amber-400/40 text-amber-200 text-sm font-semibold hover:bg-amber-500/25 transition"><i class="fas fa-crown"></i> ${escapeHTML(i18n.truncCta)}</a>`;
                    truncBox.classList.remove('hidden');
                }
            } else {
                const reply = data.reply || i18n.emptyReply;
                resultBody.innerHTML = sanitizeHtml(parseMarkdown(reply));
                if (data.truncated) {
                    if (data.needs_upgrade) {
                        truncBox.innerHTML = `
                            <div class="pt-3 border-t border-amber-500/25 text-amber-300/95 text-xs leading-relaxed">
                                <div class="flex items-start gap-2"><i class="fas fa-cut mt-0.5"></i><span>${escapeHTML(i18n.truncNotice)}</span></div>
                                <a href="${upgradeUrl}" class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 border border-amber-400/40 text-amber-200 font-semibold transition"><i class="fas fa-crown"></i> ${escapeHTML(i18n.truncCta)}</a>
                            </div>`;
                    } else {
                        truncBox.innerHTML = `<div class="pt-3 border-t border-amber-500/25 text-amber-300/95 text-xs flex items-start gap-2"><i class="fas fa-cut mt-0.5"></i><span>${escapeHTML(i18n.truncPlain)}</span></div>`;
                    }
                    truncBox.classList.remove('hidden');
                }
                resultPanel.classList.remove('hidden');
                setTimeout(() => resultPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
            }
        } catch (err) {
            resultBody.innerHTML = `<span class="text-red-400"><i class="fas fa-wifi"></i> ${escapeHTML(err.message || i18n.network)}</span>`;
            resultPanel.classList.remove('hidden');
        } finally {
            runBtn.disabled = false;
            runText.classList.remove('hidden');
            runLoading.classList.add('hidden');
        }
    });

    // Deep link #/slug
    const hash = (location.hash || '').replace(/^#\/?/, '');
    loadApps().then(() => {
        if (hash) openApp(decodeURIComponent(hash));
    });
})();
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>
