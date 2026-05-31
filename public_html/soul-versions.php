<?php
/**
 * SoulMD Hub - Model Version History Archive
 * (Dynamic i18n Internationalization, Secure Parsing & SPA Async Fetch Edition)
 * 🚀 Patched: Added Button Loading UI for Restore Action
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
loadTranslations('soul-versions');

$db = Database::getInstance();
$pdo = $db->getConnection();

$soulId = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'] ?? 0;

if (!$soulId) {
    header('Location: ' . url('/browse'));
    exit;
}

// 🚨 權限檢查：允許查看 Public 靈魂的歷史紀錄
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
$canonicalUrl = url("/soul/{$encodedUsername}/{$soulId}/{$slugRole}/{$slugTitle}");

$pageTitle = __('Version History') . ' - ' . $soul['title'];
$pageDesc = __('Version History Desc');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-8 flex-grow">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-10 border-b border-white/10 pb-6">
        <div>
            <a href="javascript:history.back()" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                <i class="fas fa-arrow-left"></i> <?= __('Back') ?>
            </a>
            <h1 class="text-4xl font-bold tracking-tighter"><?= __('Version History') ?></h1>
            <p class="text-zinc-400 mt-2 flex items-center gap-2">
                <i class="fas fa-file-alt text-emerald-500"></i> <?= htmlspecialchars($soul['title']) ?>
            </p>
        </div>
        <div>
            <a href="<?= $canonicalUrl ?>" class="px-5 py-2.5 bg-white text-zinc-950 rounded-xl font-bold hover:bg-zinc-200 transition shadow-lg flex items-center gap-2">
                <?= __('View Current') ?> <i class="fas fa-external-link-alt text-xs"></i>
            </a>
        </div>
    </div>

    <div id="versions-container" class="min-h-[400px]"></div>
    <div id="pagination-container" class="mt-16 flex justify-center items-center w-full select-none"></div>
</div>

<script>
    const soulId = <?= $soulId ?>;
    const isOwner = <?= $isOwner ? 'true' : 'false' ?>;
    let currentPage = parseInt(new URLSearchParams(window.location.search).get('page')) || 1;

    // 🌍 動態注入多語言 JS 變數
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

    async function loadVersions() {
        const container = document.getElementById('versions-container');
        const pagination = document.getElementById('pagination-container');
        
        container.innerHTML = `<div class="flex justify-center py-20"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div></div>`;
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
                            <i class="fas fa-check text-xs"></i>
                        </div>
                        <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-emerald-500/10 border border-emerald-500/20 p-4 rounded-2xl text-emerald-400 text-sm font-medium text-center shadow-lg">
                            ${lang_ActiveVersion}
                        </div>
                    </div>`;
                }

                data.data.forEach((version, index) => {
                    const versionNumber = data.total_count - offset - index;
                    const dateObj = new Date(version.edited_at.replace(/-/g, '/'));
                    const datePart = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const timePart = String(dateObj.getHours()).padStart(2, '0') + ':' + String(dateObj.getMinutes()).padStart(2, '0');
                    const dateStr = `${datePart} • ${timePart}`;

                    const isFolder = version.content && version.content.trim().startsWith('{');
                    let files = {};
                    
                    if (isFolder) {
                        try {
                            files = JSON.parse(version.content.replace(/\\'/g, "'"));
                            if (typeof files !== 'object' || files === null || Object.keys(files).length === 0) throw new Error("Invalid JSON");
                        } catch (e) {
                            files = { 'ERROR.md': `## ⚠️ ${lang_ParseError}\n${lang_ParseErrorDesc}\n\n**${lang_Error}** \`${e.message}\`\n\n---\n\n### ${lang_RawContent}\n\`\`\`json\n${version.content}\n\`\`\`` };
                        }
                    } else {
                        files = { 'SOUL.md': version.content };
                    }

                    html += `
                    <div class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-zinc-950 bg-zinc-800 text-zinc-400 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10">
                            <span class="text-xs font-bold font-mono">${versionNumber}</span>
                        </div>
                        
                        <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-zinc-900/60 border border-white/10 rounded-3xl p-6 backdrop-blur-sm hover:border-white/20 transition-colors shadow-xl">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="text-xs text-emerald-400 font-medium mb-1 tracking-wider uppercase">${lang_Version} ${versionNumber}</div>
                                    <div class="font-bold text-lg mb-1 leading-tight text-white">${escapeHTML(version.title)}</div>
                                    <div class="text-xs text-zinc-500 flex items-center gap-1.5">
                                        <i class="far fa-clock"></i> ${dateStr}
                                    </div>
                                </div>
                                ${isFolder ? `<span class="text-[10px] px-2 py-0.5 rounded font-medium border bg-purple-500/10 text-purple-400 border-purple-500/20 shrink-0">${lang_Modular}</span>` : ''}
                            </div>

                            <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-white/5">
                                <button onclick="toggleContent(${version.id})" id="btn-toggle-${version.id}" class="flex-1 px-4 py-2 bg-zinc-800 text-zinc-300 text-xs font-medium rounded-xl hover:bg-zinc-700 transition flex items-center justify-center gap-2 border border-white/5 shadow-sm">
                                    <i class="fas fa-eye" id="icon-${version.id}"></i> <span>${lang_ViewContent}</span>
                                </button>
                                
                                ${isOwner ? `
                                <button onclick="restoreVersion(${version.id}, ${soulId}, this)" class="flex-1 px-4 py-2 bg-emerald-500/10 text-emerald-400 text-xs font-bold rounded-xl hover:bg-emerald-500 hover:text-zinc-950 transition flex items-center justify-center gap-2 border border-emerald-500/20 shadow-sm min-w-[100px]">
                                    <i class="fas fa-undo"></i> ${lang_Restore}
                                </button>` : ''}
                            </div>

                            <div id="content-${version.id}" class="hidden mt-4 pt-4 border-t border-white/5">
                    `;

                    if (Object.keys(files).length > 1) {
                        html += `<div class="flex overflow-x-auto border-b border-white/10 mb-4 pb-2 custom-scrollbar gap-2">`;
                        let fIdx = 0;
                        for (const [fname, fcontent] of Object.entries(files)) {
                            fIdx++;
                            const fStyle = getFileStyle(fname);
                            let displayName = escapeHTML(fname);
                            let pathPrefix = '';
                            if (fname.includes('/')) {
                                const parts = fname.split('/');
                                displayName = escapeHTML(parts.pop());
                                const pathOnly = escapeHTML(parts.join('/'));
                                pathPrefix = `<div class="text-[9px] opacity-50 -mb-1 truncate max-w-[80px] leading-tight">${pathOnly}/</div>`;
                            }
                            const isActive = fIdx === 1 ? `${fStyle.border} ${fStyle.color}` : 'border-transparent text-zinc-400 hover:text-white hover:bg-zinc-800';

                            html += `<button onclick="showVersionFile(${version.id}, ${fIdx}, '${fStyle.border}', '${fStyle.color}')" id="tab-btn-v${version.id}-${fIdx}" class="tab-btn-v${version.id} px-3 py-1.5 text-[11px] font-medium whitespace-nowrap transition border-b-2 rounded-t-lg bg-zinc-950/50 ${isActive}">
                                <div class="flex items-center gap-1.5 text-left">
                                    <i class="fas ${fStyle.icon}"></i>
                                    <div class="flex flex-col justify-center min-h-[24px]">
                                        ${pathPrefix}
                                        <div class="truncate max-w-[100px] leading-tight">${displayName}</div>
                                    </div>
                                </div>
                            </button>`;
                        }
                        html += `</div>`;
                    }

                    html += `<div class="bg-zinc-950 border border-white/5 p-5 rounded-2xl relative shadow-inner">`;
                    let fIdx = 0;
                    for (const [fname, fcontent] of Object.entries(files)) {
                        fIdx++;
                        const safeContent = typeof fcontent === 'string' ? fcontent : JSON.stringify(fcontent, null, 2);
                        const isHidden = fIdx === 1 ? 'block' : 'hidden';

                        html += `
                        <div id="file-v${version.id}-${fIdx}" class="file-tab-v${version.id} ${isHidden}">
                            <div class="flex justify-between items-center mb-4 border-b border-white/5 pb-2">
                                <span class="text-xs font-mono text-zinc-500">${escapeHTML(fname)}</span>
                                <button onclick="copyRaw(${version.id}, ${fIdx}, this)" class="text-[10px] bg-zinc-800 text-zinc-300 px-3 py-1.5 rounded-md border border-white/10 hover:bg-zinc-700 transition shadow">
                                    <i class="far fa-copy mr-1"></i> ${lang_Copy}
                                </button>
                            </div>
                            <textarea id="raw-v${version.id}-${fIdx}" class="raw-v${version.id} hidden" data-idx="${fIdx}">${escapeHTML(safeContent)}</textarea>
                            <div id="render-v${version.id}-${fIdx}" class="prose prose-invert prose-emerald max-w-none prose-sm overflow-y-auto max-h-[350px] custom-scrollbar pr-2 text-zinc-300 leading-relaxed">
                                <div class="animate-pulse text-zinc-500 flex items-center gap-2"><i class="fas fa-spinner fa-spin"></i> ${lang_Rendering}</div>
                            </div>
                        </div>`;
                    }
                    
                    html += `</div></div></div></div>`; // Close inner divs
                });

                html += `</div>`;
                container.innerHTML = html;
                renderPagination(data.current_page, data.total_pages);
            } else {
                container.innerHTML = `
                <div class="text-center py-24 bg-zinc-900/20 border border-white/5 rounded-3xl shadow-inner">
                    <div class="mx-auto w-20 h-20 flex items-center justify-center bg-zinc-900 border border-white/10 rounded-2xl mb-6 text-zinc-500">
                        <i class="fas fa-history text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-semibold mb-2">${lang_NoVersions}</h2>
                    <p class="text-zinc-400 text-sm max-w-sm mx-auto">${lang_NoVersionsDesc}</p>
                </div>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="text-red-400 text-center py-12"><i class="fas fa-wifi mr-2"></i> Network Error</div>`;
        }
    }

    // 🚨 支援 Button Loading UI 鎖定
    async function restoreVersion(versionId, targetSoulId, btn) {
        if (!confirm(<?= json_encode(__('Restore Confirm'), JSON_UNESCAPED_UNICODE) ?>)) return;

        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        try {
            const res = await fetch('/api/versions', { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ version_id: versionId, soul_id: targetSoulId })
            });
            const data = await res.json();
            
            if (data.success) {
                window.location.href = '<?= url("/my-souls") ?>'; 
            } else {
                if (data.error && data.error.includes('Login')) { 
                    window.location.href = '<?= url("/login") ?>'; 
                } else { 
                    alert(data.error || <?= json_encode(__('Restore failed'), JSON_UNESCAPED_UNICODE) ?>); 
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch(e) {
            alert(<?= json_encode(__('Network error while restoring.'), JSON_UNESCAPED_UNICODE) ?>);
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
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
            btnSpan.innerText = lang_HideContent;

            const textareas = document.querySelectorAll(`.raw-v${versionId}`);
            textareas.forEach(ta => {
                const idx = ta.dataset.idx;
                const renderDiv = document.getElementById(`render-v${versionId}-${idx}`);
                if (renderDiv.innerHTML.includes('fa-spinner')) {
                    const parsedHTML = marked.parse(ta.value);
                    renderDiv.innerHTML = DOMPurify.sanitize(parsedHTML);
                }
            });
        } else {
            contentDiv.classList.add('hidden');
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            btnSpan.innerText = lang_ViewContent;
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
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i> ' + lang_Copied;
            btn.classList.add('border-emerald-400/50', 'text-white');
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.classList.remove('border-emerald-400/50', 'text-white');
            }, 2000);
        });
    }

    window.onload = loadVersions;
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>