<?php
/**
 * SoulMD Hub - Modular AI Generator
 * (Dynamic i18n Internationalization Edition with Localized Prompt Templates)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('generate');

// 🌍 SEO Meta 多語言化
$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-5xl w-full mx-auto px-4 sm:px-6 pb-16 pt-8">
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 bg-emerald-900/20 text-emerald-400 px-4 py-1.5 rounded-full text-xs font-medium mb-6 border border-emerald-500/20">
            <i class="fas fa-layer-group"></i> <?= __('Modular AI Generator') ?>
        </div>
        <h1 class="text-5xl font-bold tracking-tighter mb-4"><?= __('Design your') ?> <span class="gradient-text"><?= __('Modular AI') ?></span></h1>
        <p class="text-lg text-zinc-400 max-w-xl mx-auto"><?= __('Generator Subtitle') ?></p>
    </div>

    <div id="form-section">
        <div class="max-w-3xl mx-auto mb-8 flex flex-wrap justify-center gap-3">
            <span class="text-sm text-zinc-500 py-2"><?= __('Quick Presets:') ?></span>
            <button type="button" onclick="fillTemplate('dev')" class="px-4 py-2 rounded-full bg-zinc-900 border border-white/10 text-sm hover:border-emerald-400/50 hover:text-emerald-400 transition"><?= __('Expert Coder') ?></button>
            <button type="button" onclick="fillTemplate('writer')" class="px-4 py-2 rounded-full bg-zinc-900 border border-white/10 text-sm hover:border-emerald-400/50 hover:text-emerald-400 transition"><?= __('Copywriter') ?></button>
            <button type="button" onclick="fillTemplate('assistant')" class="px-4 py-2 rounded-full bg-zinc-900 border border-white/10 text-sm hover:border-emerald-400/50 hover:text-emerald-400 transition"><?= __('Executive Assistant') ?></button>
        </div>

        <form id="generate-form" class="max-w-3xl mx-auto bg-zinc-900/50 border border-white/10 rounded-3xl p-8 backdrop-blur-sm shadow-2xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Role / Profession') ?></label>
                    <input type="text" id="input-role" required placeholder="<?= __('Role PH') ?>" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Personality Traits') ?></label>
                    <input type="text" id="input-personality" required placeholder="<?= __('Personality PH') ?>" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Expertise / Tech Stack') ?></label>
                <input type="text" id="input-expertise" required placeholder="<?= __('Expertise PH') ?>" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2 text-zinc-300"><?= __('Communication Style') ?></label>
                <input type="text" id="input-style" required placeholder="<?= __('Style PH') ?>" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium mb-2 text-zinc-300 flex justify-between">
                    <?= __('Hard Rules') ?> <span class="text-xs text-zinc-500 font-normal"><?= __('Optional') ?></span>
                </label>
                <textarea id="input-special" rows="3" placeholder="<?= __('Rules PH') ?>" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner"></textarea>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-4 bg-emerald-500 text-zinc-950 text-lg font-bold rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg transform hover:-translate-y-0.5 duration-200">
                <span id="submit-text"><i class="fas fa-bolt mr-1"></i> <?= __('Generate Modular Agent') ?></span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
            </button>
        </form>
    </div>

    <div id="result-section" class="hidden animate-fade-in">
        <div class="max-w-3xl mx-auto mb-6 flex justify-between items-end border-b border-white/10 pb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2"><?= __('Modular Folder Generated! 📁') ?></h2>
                <p class="text-zinc-400 text-sm"><?= __('Result Subtitle') ?></p>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="resetForm()" class="px-5 py-2.5 bg-zinc-800 border border-white/10 rounded-xl text-sm font-medium hover:bg-zinc-700 transition flex items-center gap-2 shadow">
                    <i class="fas fa-redo text-xs"></i> <?= __('New') ?>
                </button>
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-zinc-900 border border-white/10 rounded-3xl p-6 flex flex-col mb-8 shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <div class="font-bold text-lg flex items-center gap-2">
                    <i class="fas fa-folder-open text-purple-400"></i> <?= __('JSON Output') ?>
                </div>
            </div>
            <pre id="folder-content" class="bg-zinc-950 border border-white/5 p-5 rounded-2xl text-[13px] whitespace-pre-wrap overflow-y-auto max-h-[450px] font-mono text-zinc-300 leading-relaxed shadow-inner"></pre>
        </div>

        <div class="flex flex-col items-center justify-center pt-4">
            <a href="<?= url('/upload') ?>" class="px-12 py-4 bg-emerald-500 text-zinc-950 text-xl font-bold rounded-2xl hover:bg-emerald-400 transition flex items-center gap-3 shadow-lg hover:scale-105 transform duration-200">
                <?= __('Go to Upload') ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<script>
    // 🌍 將 Quick Presets 內容動態轉為多語言變數
    const templates = {
        dev: { 
            role: '<?= addslashes(__('js_dev_role')) ?>', 
            personality: '<?= addslashes(__('js_dev_personality')) ?>', 
            expertise: '<?= addslashes(__('js_dev_expertise')) ?>', 
            style: '<?= addslashes(__('js_dev_style')) ?>', 
            special: '<?= addslashes(__('js_dev_special')) ?>' 
        },
        writer: { 
            role: '<?= addslashes(__('js_writer_role')) ?>', 
            personality: '<?= addslashes(__('js_writer_personality')) ?>', 
            expertise: '<?= addslashes(__('js_writer_expertise')) ?>', 
            style: '<?= addslashes(__('js_writer_style')) ?>', 
            special: '<?= addslashes(__('js_writer_special')) ?>' 
        },
        assistant: { 
            role: '<?= addslashes(__('js_assistant_role')) ?>', 
            personality: '<?= addslashes(__('js_assistant_personality')) ?>', 
            expertise: '<?= addslashes(__('js_assistant_expertise')) ?>', 
            style: '<?= addslashes(__('js_assistant_style')) ?>', 
            special: '<?= addslashes(__('js_assistant_special')) ?>' 
        }
    };

    function fillTemplate(type) {
        const t = templates[type];
        if(!t) return;
        document.getElementById('input-role').value = t.role;
        document.getElementById('input-personality').value = t.personality;
        document.getElementById('input-expertise').value = t.expertise;
        document.getElementById('input-style').value = t.style;
        document.getElementById('input-special').value = t.special;
        const form = document.getElementById('generate-form');
        form.classList.add('ring-2', 'ring-emerald-400', 'scale-[1.01]', 'transition-all');
        setTimeout(() => form.classList.remove('ring-2', 'ring-emerald-400', 'scale-[1.01]'), 300);
    }

    function resetForm() {
        document.getElementById('result-section').classList.add('hidden');
        document.getElementById('form-section').classList.remove('hidden');
    }

    const genForm = document.getElementById('generate-form');
    genForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('submit-btn');
        const text = document.getElementById('submit-text');
        const loading = document.getElementById('submit-loading');
        
        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-not-allowed');

        const role = document.getElementById('input-role').value.trim();
        const personality = document.getElementById('input-personality').value.trim();
        const expertise = document.getElementById('input-expertise').value.trim();
        const style = document.getElementById('input-style').value.trim();
        const special = document.getElementById('input-special').value.trim();

        // 🌍 組合多語言的 Prompt 模板 (JS replace)
        let soulContent = `<?= addslashes(__('Prompt Identity')) ?>`
                            .replace(':role', role)
                            .replace(':personality', personality)
                            .replace(':expertise', expertise);
                            
        let styleContent = `<?= addslashes(__('Prompt Voice')) ?>`
                            .replace(':style', style);
                            
        let specialBlock = special ? '- ' + special + '\n' : '';
        let rulesContent = `<?= addslashes(__('Prompt Rules')) ?>`
                            .replace(':special', specialBlock);

        const filesObj = {
            'SOUL.md': soulContent,
            'STYLE.md': styleContent,
            'RULES.md': rulesContent
        };
        const folderJson = JSON.stringify(filesObj, null, 2);

        try {
            await fetch('/api/save-preset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: role, role: role, content: folderJson })
            });
            
            document.getElementById('form-section').classList.add('hidden');
            document.getElementById('result-section').classList.remove('hidden');
            document.getElementById('folder-content').textContent = folderJson; 
        } catch(err) {
            alert('<?= addslashes(__('Error generating preset.')) ?>');
        } finally {
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>