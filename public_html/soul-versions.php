<?php
/**
 * SoulMD Hub - Model Version History Archive
 * (Dynamic i18n Internationalization, Secure Parsing & SPA Async Fetch Edition)
 * 🚀 V5 SEO Optimized: Semantic <main> tag, ARIA labels, and Enhanced Accessibility
 */

require_once __DIR__ . '/../private/src/AppBootstrap.php';

$app = AppBootstrap::forPage([
    'translations' => 'soul-versions',
    'db' => true,
    'seo' => true,
]);
$pdo = $app['pdo'];

$soulId = (int)($_GET['id'] ?? 0);
$userId = (int)($app['user_id'] ?? 0);

if (!$soulId) {
    header('Location: ' . url('/browse'));
    exit;
}

// Public
$stmt = $pdo->prepare("
    SELECT s.*, u.username 
    FROM souls s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.id = ? AND (s.is_public = 1 OR s.user_id = ?)
");
$stmt->execute([$soulId, $userId]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$isOwner = ($soul['user_id'] === $userId);

// PHP SEO
function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

$encodedUsername = rawurlencode($soul['username'] ?? 'anonymous');
$slugRole = makeSlug($soul['role']);
$slugTitle = makeSlug($soul['title']);

$canonicalUrl = url("/soul/{$encodedUsername}/{$soulId}/{$slugRole}/{$slugTitle}");

$pageTitle = __('Version History') . ' - ' . $soul['title'];
$pageDesc = __('Version History Desc');

require_once __DIR__ . '/../private/includes/header.php';
?>

<main class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow">
    <header class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-10 border-b border-white/10 pb-6">
        <div>
            <a href="javascript:history.back()" aria-label="<?= __('Back') ?>" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> <?= __('Back') ?>
            </a>
            <h1 class="text-4xl font-bold tracking-tighter"><?= __('Version History') ?></h1>
            <p class="text-zinc-400 mt-2 flex items-center gap-2">
                <i class="fas fa-file-alt text-emerald-500" aria-hidden="true"></i> <?= htmlspecialchars($soul['title']) ?>
            </p>
        </div>
        <div>
            <a href="<?= $canonicalUrl ?>" aria-label="<?= __('View Current') ?>" class="px-5 py-2.5 bg-white text-zinc-950 rounded-xl font-bold hover:bg-zinc-200 transition shadow-lg flex items-center gap-2">
                <?= __('View Current') ?> <i class="fas fa-external-link-alt text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </header>

    <div id="versions-container" class="min-h-[400px]" aria-live="polite"></div>
    <nav id="pagination-container" aria-label="Version History Pagination" class="mt-16 flex justify-center items-center w-full select-none"></nav>
</main>

<script>
    const soulId = <?= $soulId ?>;
    const isOwner = <?= $isOwner ? 'true' : 'false' ?>;
    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;

    // JS
    const lang_ViewContent = <?= json_encode(__('View Content'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_HideContent = <?= json_encode(__('Hide Content'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Copied = <?= json_encode(__('Copied!'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Copy = <?= json_encode(__('Copy'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_ActiveVersion = <?= json_encode(__('Currently Active Version'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_ParseError = <?= json_encode(__('Parse Error'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_ParseErrorDesc = <?= json_encode(__('Failed to parse JSON folder structure in this version.'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Error = <?= json_encode(__('Error:'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_RawContent = <?= json_encode(__('Raw Content:'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Version = <?= json_encode(__('Version'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Modular = <?= json_encode(__('Modular'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Restore = <?= json_encode(__('Restore'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_NoVersions = <?= json_encode(__('No versions yet'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_NoVersionsDesc = <?= json_encode(__('No versions desc'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Page = <?= json_encode(__('Page'), JSON_UNESCAPED_UNICODE) ?>;
    const lang_Rendering = <?= json_encode(__('Rendering Markdown...'), JSON_UNESCAPED_UNICODE) ?>;

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

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[match]));
    }

    function getFileStyle(filename) {
        const name = filename.toUpperCase();
        if (name.includes('SOUL')) return { icon: 'fa-brain', color: 'text-emerald-400', border: 'border-emerald-400' };
        if (name.includes('STYLE')) return { icon: 'fa-palette', color: 'text-purple-400', border: 'border-purple-400' };
        if (name.includes('RULE')) return { icon: 'fa-shield-alt', color: 'text-red-400', border: 'border-red-400' };
        if (name.includes('SKILL')) return { icon: 'fa-tools', color: 'text-amber-400', border: 'border-amber-400' };
        if (name.includes('MEMORY')) return { icon: 'fa-memory', color: 'text-blue-400', border: 'border-blue-400' };
        if (name.includes('CONTEXT')) return { icon: 'fa-globe', color: 'text-cyan-400', border: 'border-cyan-400' };
        if (name.includes('PROMPT')) return { icon: 'fa-terminal', color: 'text-green-400', border: 'border-green-400' };
        if (name.endsWith('.JSON') || name.includes('ERROR')) return { icon: 'fa-code', color: 'text-yellow-400', border: 'border-yellow-400' };
        return { icon: 'fa-file-alt', color: 'text-zinc-400', border: 'border-zinc-400' };
    }

    function changePage(p) {
        currentPage = p;
        const newUrl = window.location.pathname + '?id=' + soulId + '&page=' + currentPage;
        window.history.replaceState({}, '', newUrl);
        loadVersions();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function renderPagination(current, totalPages) {
        const container = document.getElementById('pagination-container');
        if (totalPages <= 1) { container.innerHTML = ''; return; }
        
        let html = '';
        html += `<div class="flex sm:hidden w-full max-w-sm mx-auto items-center justify-between bg-zinc-900 border border-white/10 rounded-2xl p-2 shadow-lg">`;
        html += `<button onclick="changePage(${current - 1})" aria-label="Previous Page" ${current <= 1 ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-left" aria-hidden="true"></i></button>`;
        html += `<span class="text-xs font-bold text-zinc-400 tracking-widest uppercase">${lang_Page} <span class="text-white text-base">${current}</span> / ${totalPages}</span>`;
        html += `<button onclick="changePage(${current + 1})" aria-label="Next Page" ${current >= totalPages ? 'disabled class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed"' : 'class="px-5 py-3 bg-zinc-800 rounded-xl text-sm font-bold hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-right" aria-hidden="true"></i></button>`;
        html += `</div>`;

        html += `<div class="hidden sm:flex items-center gap-2 bg-zinc-900 border border-white/10 p-2 rounded-2xl shadow-lg">`;
        html += `<button onclick="changePage(${current - 1})" aria-label="Previous Page" ${current <= 1 ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-left text-xs" aria-hidden="true"></i></button>`;

        const windowSize = 2; 
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - windowSize && i <= current + windowSize)) {
                if (i === current) {
                    html += `<button aria-current="page" class="w-10 h-10 flex items-center justify-center rounded-xl bg-emerald-500 text-zinc-950 font-bold shadow-md transform scale-105 transition">${i}</button>`;
                } else {
                    html += `<button aria-label="Page ${i}" onclick="changePage(${i})" class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition font-medium text-sm shadow">${i}</button>`;
                }
            } else if (i === current - windowSize - 1 || i === current + windowSize + 1) {
                html += `<span class="w-10 h-10 flex items-center justify-center text-zinc-500 tracking-widest text-sm" aria-hidden="true">...</span>`;
            }
        }

        html += `<button onclick="changePage(${current + 1})" aria-label="Next Page" ${current >= totalPages ? 'disabled class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 opacity-50 cursor-not-allowed"' : 'class="w-10 h-10 flex items-center justify-center rounded-xl bg-zinc-800 hover:bg-zinc-700 hover:text-emerald-400 transition shadow"'}><i class="fas fa-chevron-right text-xs" aria-hidden="true"></i></button>`;
        html += `</div>`;

        container.innerHTML = html;
    }

    async function loadVersions() {
        const container = document.getElementById('versions-container');
        const pagination = document.getElementById('pagination-container');
        
        container.innerHTML = `<div class="flex justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400" aria-label="Loading"></div></div>`;
        pagination.innerHTML = '';

        try {
            const res = await fetch(`/api/versions?soul_id=${soulId}&page=${currentPage}`);
            const data = await res.json();

            if (data.success && data.data.length > 0) {
                const offset = (currentPage - 1) * 10;
                let html = '<div class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-white/10 before:to-transparent">';
                
                if (currentPage === 1) {
                    html += `
                    <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active mb-12">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-zinc-950 bg-emerald-500 text-zinc-950 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10">
                            <i class="fas fa-check text-xs" aria-hidden="true"></i>
                        </div>
                        <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-2xl text-emerald-400 text-sm font-medium text-center shadow-lg">
                            ${lang_ActiveVersion}
                        </div>
                    </div>`;
                }

                data.data.forEach((version, index) => {
                    const versionNumber = data.total_count - offset - index;
                    const dateObj = new Date(version.edited_at.replace(/-/g, '/'));
                    const dateStr = isNaN(dateObj) ? version.edited_at : dateObj.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                    
                    let contentHtml = '';
                    let isFolder = false;
                    
                    try {
                        let cleaned = version.content.replace(/\\'/g, "'");
                        const parsed = JSON.parse(cleaned);
                        if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
                            isFolder = true;
                            contentHtml += `<div class="flex border-b border-white/10 bg-zinc-950/50 overflow-x-auto custom-scrollbar rounded-t-xl" role="tablist">`;
                            let fileIdx = 0;
                            for (const [fname, fcontent] of Object.entries(parsed)) {
                                const style = getFileStyle(fname);
                                const fcontentStr = typeof fcontent === 'string' ? fcontent : JSON.stringify(fcontent);
                                const isFirst = fileIdx === 0;
                                contentHtml += `
                                    <button role="tab" aria-selected="${isFirst ? 'true' : 'false'}" aria-controls="file-${version.id}-${fileIdx}" onclick="switchFileTab(${version.id}, ${fileIdx}, '${style.border}', '${style.color}')" id="tab-btn-${version.id}-${fileIdx}" class="version-tab-btn-${version.id} px-4 py-3 text-xs font-medium whitespace-nowrap transition border-b-2 ${isFirst ? style.border + ' ' + style.color : 'border-transparent text-zinc-400 hover:text-white hover:bg-white/5'}">
                                        <i class="fas ${style.icon} mr-1.5" aria-hidden="true"></i> ${escapeHTML(fname)}
                                    </button>
                                `;
                                fileIdx++;
                            }
                            contentHtml += `</div><div class="relative bg-zinc-950 rounded-b-xl border-x border-b border-white/5">`;
                            
                            fileIdx = 0;
                            for (const [fname, fcontent] of Object.entries(parsed)) {
                                const fcontentStr = typeof fcontent === 'string' ? fcontent : JSON.stringify(fcontent);
                                contentHtml += `
                                    <div id="file-${version.id}-${fileIdx}" role="tabpanel" aria-labelledby="tab-btn-${version.id}-${fileIdx}" class="version-file-content-${version.id} ${fileIdx === 0 ? 'block' : 'hidden'}">
                                        <div class="sticky top-0 z-10 flex justify-end bg-gradient-to-b from-zinc-950 to-transparent p-3 pointer-events-none">
                                            <button aria-label="${lang_Copy}" onclick="copyVersionText('raw-${version.id}-${fileIdx}', this)" class="pointer-events-auto px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-[10px] font-bold rounded-lg transition border border-white/10 shadow flex items-center gap-1.5"><i class="far fa-copy" aria-hidden="true"></i> ${lang_Copy}</button>
                                        </div>
                                        <textarea id="raw-${version.id}-${fileIdx}" class="hidden" aria-hidden="true">${escapeHTML(fcontentStr)}</textarea>
                                        <div id="render-${version.id}-${fileIdx}" class="prose prose-invert prose-emerald prose-sm max-w-none px-5 pb-6 -mt-6">
                                            <span class="text-zinc-500 animate-pulse">${lang_Rendering}</span>
                                        </div>
                                    </div>
                                `;
                                fileIdx++;
                            }
                            contentHtml += `</div>`;
                        } else {
                            throw new Error("Not a folder object");
                        }
                    } catch(e) {
                        isFolder = false;
                        if (version.content.trim().startsWith('{') && version.content.includes('SOUL.md')) {
                            contentHtml = `
                                <div class="bg-red-500/10 border border-red-500/20 p-4 rounded-xl mb-4 text-red-400 text-xs">
                                    <p class="font-bold flex items-center gap-1.5 mb-1"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> ${lang_ParseError}</p>
                                    <p class="text-red-300/80 mb-2">${lang_ParseErrorDesc}</p>
                                    <p class="font-mono text-[10px] opacity-70">${lang_Error} ${e.message}</p>
                                </div>
                                <div class="text-[10px] text-zinc-500 mb-1 uppercase tracking-widest font-bold">${lang_RawContent}</div>
                                <div class="relative bg-zinc-950 rounded-xl border border-white/5">
                                    <div class="sticky top-0 z-10 flex justify-end bg-gradient-to-b from-zinc-950 to-transparent p-3 pointer-events-none">
                                        <button aria-label="${lang_Copy}" onclick="copyVersionText('raw-${version.id}', this)" class="pointer-events-auto px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-[10px] font-bold rounded-lg transition border border-white/10 shadow flex items-center gap-1.5"><i class="far fa-copy" aria-hidden="true"></i> ${lang_Copy}</button>
                                    </div>
                                    <textarea id="raw-${version.id}" class="hidden" aria-hidden="true">${escapeHTML(version.content)}</textarea>
                                    <pre class="text-[11px] text-zinc-400 font-mono p-4 overflow-x-auto custom-scrollbar -mt-6">${escapeHTML(version.content)}</pre>
                                </div>
                            `;
                        } else {
                            contentHtml = `
                                <div class="relative bg-zinc-950 rounded-xl border border-white/5">
                                    <div class="sticky top-0 z-10 flex justify-end bg-gradient-to-b from-zinc-950 to-transparent p-3 pointer-events-none">
                                        <button aria-label="${lang_Copy}" onclick="copyVersionText('raw-${version.id}', this)" class="pointer-events-auto px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-[10px] font-bold rounded-lg transition border border-white/10 shadow flex items-center gap-1.5"><i class="far fa-copy" aria-hidden="true"></i> ${lang_Copy}</button>
                                    </div>
                                    <textarea id="raw-${version.id}" class="hidden" aria-hidden="true">${escapeHTML(version.content)}</textarea>
                                    <div id="render-${version.id}" class="prose prose-invert prose-emerald prose-sm max-w-none px-5 pb-6 -mt-6">
                                        <span class="text-zinc-500 animate-pulse">${lang_Rendering}</span>
                                    </div>
                                </div>
                            `;
                        }
                    }

                    const restoreBtnHtml = isOwner ? `
                        <button onclick="restoreVersion(${version.id}, this)" aria-label="${lang_Restore}" class="px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-400 hover:text-zinc-950 text-xs font-bold rounded-lg border border-emerald-500/20 transition shadow flex items-center gap-1.5">
                            <i class="fas fa-history" aria-hidden="true"></i> <span class="restore-text">${lang_Restore}</span>
                        </button>
                    ` : '';

                    html += `
                    <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group mb-12">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-zinc-950 bg-zinc-800 text-zinc-500 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10">
                            <i class="fas fa-code-commit text-xs" aria-hidden="true"></i>
                        </div>
                        
                        <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-zinc-900/80 border border-white/10 p-5 rounded-3xl shadow-xl backdrop-blur-sm transition-all hover:border-emerald-500/30">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="px-2 py-0.5 bg-zinc-800 text-zinc-300 rounded text-[10px] font-bold font-mono border border-white/5">v${versionNumber}</span>
                                        ${isFolder ? `<span class="px-2 py-0.5 bg-purple-500/10 text-purple-400 rounded text-[10px] font-medium border border-purple-500/20"><i class="fas fa-folder-open mr-1" aria-hidden="true"></i>${lang_Modular}</span>` : ''}
                                    </div>
                                    <h3 class="text-base sm:text-lg font-bold text-white leading-tight break-words">${escapeHTML(version.title || '<?= addslashes(__('Version')) ?> ' + versionNumber)}</h3>
                                    <div class="text-xs text-zinc-500 mt-1 flex items-center gap-1.5"><i class="far fa-clock" aria-hidden="true"></i> ${dateStr}</div>
                                </div>
                                <div class="shrink-0 flex items-center gap-2">
                                    <button onclick="toggleContent(${version.id}, this)" aria-expanded="false" aria-controls="content-${version.id}" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-medium rounded-lg border border-white/5 transition flex items-center gap-1.5">
                                        <i class="fas fa-eye" aria-hidden="true"></i> <span class="toggle-text">${lang_ViewContent}</span>
                                    </button>
                                    ${restoreBtnHtml}
                                </div>
                            </div>
                            
                            <div id="content-${version.id}" class="hidden mt-4 pt-4 border-t border-white/5 animate-fade-in">
                                ${contentHtml}
                            </div>
                        </div>
                    </div>`;
                });
                
                html += '</div>';
                container.innerHTML = html;
                renderPagination(data.current_page, data.total_pages);

                setTimeout(() => {
                    data.data.forEach(version => {
                        try {
                            let cleaned = version.content.replace(/\\'/g, "'");
                            const parsed = JSON.parse(cleaned);
                            if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
                                let fileIdx = 0;
                                for (const [fname, fcontent] of Object.entries(parsed)) {
                                    const rawEl = document.getElementById(`raw-${version.id}-${fileIdx}`);
                                    const renderEl = document.getElementById(`render-${version.id}-${fileIdx}`);
                                    if (rawEl && renderEl) {
                                        const fcontentStr = typeof fcontent === 'string' ? fcontent : JSON.stringify(fcontent);
                                        renderEl.innerHTML = DOMPurify.sanitize(marked.parse(fcontentStr));
                                    }
                                    fileIdx++;
                                }
                            }
                        } catch(e) {
                            const rawEl = document.getElementById(`raw-${version.id}`);
                            const renderEl = document.getElementById(`render-${version.id}`);
                            if (rawEl && renderEl) {
                                renderEl.innerHTML = DOMPurify.sanitize(marked.parse(rawEl.value));
                            }
                        }
                    });
                }, 100);

            } else {
                container.innerHTML = `
                <div class="text-center py-16 bg-zinc-900/20 border border-dashed border-white/5 rounded-3xl mx-4 sm:mx-0">
                    <div class="w-16 h-16 bg-zinc-900 border border-white/10 rounded-2xl flex items-center justify-center text-zinc-500 mx-auto mb-4"><i class="fas fa-history text-2xl" aria-hidden="true"></i></div>
                    <h3 class="text-xl font-bold text-white mb-2">${lang_NoVersions}</h3>
                    <p class="text-sm text-zinc-400 max-w-md mx-auto leading-relaxed">${lang_NoVersionsDesc}</p>
                </div>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-20 font-medium"><i class="fas fa-wifi mr-2" aria-hidden="true"></i> Network error</div>`;
        }
    }

    function switchFileTab(versionId, fileIdx, activeBorder, activeColor) {
        document.querySelectorAll(`.version-file-content-${versionId}`).forEach(el => {
            el.classList.remove('block'); el.classList.add('hidden');
        });
        document.getElementById(`file-${versionId}-${fileIdx}`).classList.remove('hidden');
        document.getElementById(`file-${versionId}-${fileIdx}`).classList.add('block');
        
        document.querySelectorAll(`.version-tab-btn-${versionId}`).forEach(btn => {
            btn.className = btn.className.replace(/border-[a-z]+-400/g, 'border-transparent');
            btn.className = btn.className.replace(/text-[a-z]+-400/g, 'text-zinc-400');
            btn.classList.add('border-transparent', 'text-zinc-400');
            btn.setAttribute('aria-selected', 'false');
        });
        
        const activeBtn = document.getElementById(`tab-btn-${versionId}-${fileIdx}`);
        activeBtn.classList.remove('border-transparent', 'text-zinc-400');
        activeBtn.classList.add(activeBorder, activeColor);
        activeBtn.setAttribute('aria-selected', 'true');
    }

    function toggleContent(id, btn) {
        const contentDiv = document.getElementById('content-' + id);
        const textSpan = btn.querySelector('.toggle-text');
        const icon = btn.querySelector('i');
        
        if (contentDiv.classList.contains('hidden')) {
            contentDiv.classList.remove('hidden');
            textSpan.innerText = lang_HideContent;
            icon.className = 'fas fa-eye-slash';
            btn.setAttribute('aria-expanded', 'true');
        } else {
            contentDiv.classList.add('hidden');
            textSpan.innerText = lang_ViewContent;
            icon.className = 'fas fa-eye';
            btn.setAttribute('aria-expanded', 'false');
        }
    }

    function copyVersionText(rawId, btn) {
        const text = document.getElementById(rawId).value;
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = `<i class="fas fa-check text-emerald-400" aria-hidden="true"></i> ${lang_Copied}`;
            btn.classList.add('border-emerald-400/50', 'text-white');
            setTimeout(() => { 
                btn.innerHTML = originalHtml; 
                btn.classList.remove('border-emerald-400/50', 'text-white'); 
            }, 2000);
        });
    }

    async function restoreVersion(versionId, btn) {
        if (!confirm(<?= json_encode(__('Restore Confirm'), JSON_UNESCAPED_UNICODE) ?>)) return;
        
        const originalHtml = btn.innerHTML;
        const textSpan = btn.querySelector('.restore-text');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> <span class="restore-text">Processing...</span>';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        try {
            const res = await fetch('/api/versions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ soul_id: soulId, version_id: versionId })
            });
            const data = await res.json();
            
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || lang_Restore + ' failed');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } catch (e) {
            alert(<?= json_encode(__('Network error while restoring.'), JSON_UNESCAPED_UNICODE) ?>);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    window.onload = loadVersions;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>