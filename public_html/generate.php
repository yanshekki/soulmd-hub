<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

$generated = false;
$folderJson = '';
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? '');
    $personality = trim($_POST['personality'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $style = trim($_POST['style'] ?? '');
    $special = trim($_POST['special'] ?? '');

    // 1. 核心大腦
    $soulContent = "## 🤖 Identity\n";
    $soulContent .= "You are an expert **{$role}**. You are known for being {$personality}.\n\n";
    $soulContent .= "## 🎯 Core Objectives\n";
    $soulContent .= "- Provide top-tier assistance leveraging your deep expertise in **{$expertise}**.\n";
    $soulContent .= "- Deliver solutions that are accurate, actionable, and highly insightful.\n";

    // 2. 語氣與風格
    $styleContent = "## 🗣️ Voice & Tone\n";
    $styleContent .= "- Speak with a {$style} tone.\n";
    $styleContent .= "- Use bold text for key concepts and code blocks for technical details.\n";
    $styleContent .= "- Lead with a direct answer, followed by structured elaboration.\n";

    // 3. 嚴格規則
    $rulesContent = "## 🚧 Boundaries & Hard Rules\n";
    if ($special) {
        $rulesContent .= "- {$special}\n";
    }
    $rulesContent .= "- Maintain character and role consistency at all times.\n";
    $rulesContent .= "- Never fabricate facts or guess answers if information is missing.\n";
    $rulesContent .= "- Avoid passive voice and unnecessary fluff.\n";

    // 打包成 Modular Folder
    $filesObj = [
        'SOUL.md' => $soulContent,
        'STYLE.md' => $styleContent,
        'RULES.md' => $rulesContent
    ];
    $folderJson = json_encode($filesObj, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $_SESSION['preset_title'] = $role;
    $_SESSION['preset_content'] = $folderJson;
    $_SESSION['preset_role'] = $role;

    $generated = true;
}

$pageTitle = 'AI Soul Generator';
$pageDesc = 'Describe your AI and instantly generate a modular Modular Folder.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-5xl w-full mx-auto px-4 sm:px-6 pb-16 pt-8">
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 bg-emerald-900/20 text-emerald-400 px-4 py-1.5 rounded-full text-xs font-medium mb-6 border border-emerald-500/20">
            <i class="fas fa-layer-group"></i> Modular AI Generator
        </div>
        <h1 class="text-5xl font-bold tracking-tighter mb-4">Design your <span class="gradient-text">Modular AI</span></h1>
        <p class="text-lg text-zinc-400 max-w-xl mx-auto">Instantly generate a complete agent architecture containing <code>SOUL.md</code>, <code>STYLE.md</code>, and <code>RULES.md</code>.</p>
    </div>

    <?php if (!$generated): ?>
        <div class="max-w-3xl mx-auto mb-8 flex flex-wrap justify-center gap-3">
            <span class="text-sm text-zinc-500 py-2">Quick Presets:</span>
            <button type="button" onclick="fillTemplate('dev')" class="px-4 py-2 rounded-full bg-zinc-900 border border-white/10 text-sm hover:border-emerald-400/50 hover:text-emerald-400 transition">💻 Expert Coder</button>
            <button type="button" onclick="fillTemplate('writer')" class="px-4 py-2 rounded-full bg-zinc-900 border border-white/10 text-sm hover:border-emerald-400/50 hover:text-emerald-400 transition">✍️ Copywriter</button>
            <button type="button" onclick="fillTemplate('assistant')" class="px-4 py-2 rounded-full bg-zinc-900 border border-white/10 text-sm hover:border-emerald-400/50 hover:text-emerald-400 transition">🤖 Executive Assistant</button>
        </div>

        <form id="generate-form" method="POST" class="max-w-3xl mx-auto bg-zinc-900/50 border border-white/10 rounded-3xl p-8 backdrop-blur-sm shadow-2xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">Role / Profession</label>
                    <input type="text" id="input-role" name="role" required placeholder="e.g. Senior Data Scientist" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">Personality Traits</label>
                    <input type="text" id="input-personality" name="personality" required placeholder="e.g. pragmatic, direct, witty" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2 text-zinc-300">Expertise / Tech Stack</label>
                <input type="text" id="input-expertise" name="expertise" required placeholder="e.g. Python, Machine Learning, Data Viz" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2 text-zinc-300">Communication Style</label>
                <input type="text" id="input-style" name="style" required placeholder="e.g. clear, confident, highly technical" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium mb-2 text-zinc-300 flex justify-between">
                    Hard Rules <span class="text-xs text-zinc-500 font-normal">Optional</span>
                </label>
                <textarea id="input-special" name="special" rows="3" placeholder="e.g. Always output code in blocks, do not explain basics..." class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition"></textarea>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-4 bg-emerald-500 text-zinc-950 text-lg font-bold rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3">
                <span id="submit-text"><i class="fas fa-bolt mr-1"></i> Generate Modular Agent</span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
            </button>
        </form>

    <?php else: ?>
        <div class="max-w-3xl mx-auto mb-6 flex justify-between items-end border-b border-white/10 pb-6">
            <div>
                <h2 class="text-3xl font-bold mb-2">Modular Folder Generated! 📁</h2>
                <p class="text-zinc-400 text-sm">We compiled your inputs into a multi-file JSON. Click 'Go to Upload' to publish.</p>
            </div>
            <div class="flex gap-3">
                <a href="/generate" class="px-5 py-2.5 border border-white/20 rounded-xl text-sm font-medium hover:bg-white/5 transition flex items-center gap-2">
                    <i class="fas fa-redo text-xs"></i> New
                </a>
            </div>
        </div>

        <div class="max-w-3xl mx-auto bg-zinc-900 border border-white/10 rounded-3xl p-6 flex flex-col mb-8">
            <div class="flex justify-between items-center mb-4">
                <div class="font-bold text-lg flex items-center gap-2">
                    <i class="fas fa-folder-open text-purple-400"></i> JSON Output
                </div>
            </div>
            <pre id="folder-content" class="bg-zinc-950 border border-white/5 p-5 rounded-2xl text-[13px] whitespace-pre-wrap overflow-y-auto max-h-[450px] font-mono text-zinc-300 leading-relaxed"><?= htmlspecialchars($folderJson) ?></pre>
        </div>

        <div class="flex flex-col items-center justify-center pt-4">
            <a href="/upload" class="px-12 py-4 bg-emerald-500 text-zinc-950 text-xl font-bold rounded-2xl hover:bg-emerald-400 transition flex items-center gap-3 shadow-lg hover:scale-105 transform duration-200">
                Go to Upload <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    const templates = {
        dev: { role: 'Senior Full-Stack Engineer', personality: 'pragmatic, logical, direct, slightly witty', expertise: 'TypeScript, Next.js, System Architecture, Clean Code', style: 'concise, code-heavy, professional', special: 'Always provide robust code examples and briefly explain the "why" behind the approach.' },
        writer: { role: 'Expert Copywriter & Editor', personality: 'creative, empathetic, persuasive, articulate', expertise: 'SEO, Marketing Strategies, Storytelling, Audience Engagement', style: 'engaging, warm, highly readable', special: 'Use markdown headers creatively. Highlight key copywriting points in bold.' },
        assistant: { role: 'Top-tier Executive Assistant', personality: 'highly organized, polite, efficient, detail-oriented', expertise: 'Task prioritization, summarizing long texts, scheduling logic', style: 'structured, clear, action-oriented', special: 'Always end your responses with a brief bulleted list of "Next Actions" or summaries.' }
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

    const genForm = document.getElementById('generate-form');
    if (genForm) {
        genForm.addEventListener('submit', function() {
            document.getElementById('submit-text').classList.add('hidden');
            document.getElementById('submit-loading').classList.remove('hidden');
            document.getElementById('submit-btn').classList.add('opacity-80', 'cursor-not-allowed');
        });
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>