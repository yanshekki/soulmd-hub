<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

setSEO('AI Soul Generator', 'Describe your AI and instantly generate a professional SOUL.md + STYLE.md.');

$generated = false;
$soulContent = '';
$styleContent = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? '');
    $personality = trim($_POST['personality'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $style = trim($_POST['style'] ?? '');
    $special = trim($_POST['special'] ?? '');

    // Professional prompt (replace with real API call later)
    $soulContent = "## Identity\nYou are a " . $role . " AI. You are known for being " . $personality . ".\n\n## Core Values\n- Expertise in " . $expertise . "\n- Always honest and direct\n- Prioritize clarity\n\n## Personality\n" . $personality . "\n\n## Expertise\n" . $expertise . "\n\n## Rules\n" . $special;

    $styleContent = "## Voice\nYou speak with a " . $style . " tone - confident, warm, and precise.\n\n## Sentence Structure\n- Mix short and long sentences\n- Lead with the answer\n\n## Vocabulary\nClear and professional. Avoid jargon unless needed.\n\n## Formatting\n- Bold for key points\n- Bullet points\n- Short paragraphs";

    $generated = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Soul Generator - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 bg-emerald-900/30 text-emerald-400 px-5 py-2 rounded-3xl text-sm mb-6">
                <i class="fas fa-sparkles"></i> AI Powered
            </div>
            <h1 class="text-5xl font-bold tracking-tighter">AI Soul Generator</h1>
            <p class="text-xl text-zinc-400 mt-4 max-w-md mx-auto">Describe your ideal AI — get a complete SOUL.md + STYLE.md instantly</p>
        </div>

        <?php if (!$generated): ?>
            <form method="POST" class="max-w-2xl mx-auto bg-zinc-900 border border-white/10 rounded-3xl p-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-sm font-medium mb-3">Role / Profession</label>
                        <input type="text" name="role" value="Senior Full-Stack Engineer" required 
                               class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-3">Personality Traits</label>
                        <input type="text" name="personality" value="pragmatic, witty, direct, slightly opinionated" required 
                               class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-medium mb-3">Expertise / Tech Stack</label>
                    <input type="text" name="expertise" value="TypeScript, Next.js, System Design, Clean Architecture" required 
                           class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-medium mb-3">Communication Style</label>
                    <input type="text" name="style" value="clear, confident, friendly but concise" required 
                           class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
                </div>

                <div class="mb-10">
                    <label class="block text-sm font-medium mb-3">Special Instructions <span class="text-xs text-zinc-400">(optional)</span></label>
                    <textarea name="special" rows="4" placeholder="e.g. Always respond in Traditional Chinese, use metaphors..." 
                              class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400"></textarea>
                </div>

                <button type="submit" 
                        class="w-full py-6 bg-white text-black text-xl font-semibold rounded-3xl hover:bg-zinc-200 transition flex items-center justify-center gap-3">
                    <i class="fas fa-magic"></i>
                    Generate SOUL.md + STYLE.md
                </button>
            </form>
        <?php else: ?>
            <div class="mb-8 flex justify-between items-center">
                <h2 class="text-3xl font-bold">Generated successfully!</h2>
                <a href="generate.php" class="text-emerald-400 hover:underline text-sm flex items-center gap-1">
                    <i class="fas fa-redo"></i> Generate again
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- SOUL.md -->
                <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
                    <div class="flex justify-between mb-6">
                        <div class="font-semibold text-xl flex items-center gap-2">
                            <span class="text-emerald-400">SOUL.md</span>
                        </div>
                        <button onclick="copyContent('soul-content')" class="flex items-center gap-2 text-sm text-emerald-400 hover:text-emerald-300">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <pre id="soul-content" class="bg-black/50 p-6 rounded-3xl text-sm whitespace-pre-wrap overflow-auto max-h-96 font-mono"><?= htmlspecialchars($soulContent) ?></pre>
                </div>

                <!-- STYLE.md -->
                <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
                    <div class="flex justify-between mb-6">
                        <div class="font-semibold text-xl flex items-center gap-2">
                            <span class="text-purple-400">STYLE.md</span>
                        </div>
                        <button onclick="copyContent('style-content')" class="flex items-center gap-2 text-sm text-emerald-400 hover:text-emerald-300">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                    <pre id="style-content" class="bg-black/50 p-6 rounded-3xl text-sm whitespace-pre-wrap overflow-auto max-h-96 font-mono"><?= htmlspecialchars($styleContent) ?></pre>
                </div>
            </div>

            <div class="mt-12 flex justify-center">
                <a href="upload.php?pregenerated=1" 
                   class="inline-flex items-center gap-4 px-12 py-6 bg-white text-black text-2xl font-semibold rounded-3xl hover:bg-zinc-200 transition shadow-2xl">
                    <i class="fas fa-arrow-right"></i>
                    Use this Soul
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyContent(id) {
            const text = document.getElementById(id).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btns = document.querySelectorAll('button');
                for (let btn of btns) {
                    if (btn.innerHTML.includes('Copy')) {
                        const original = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                        setTimeout(() => btn.innerHTML = original, 2000);
                    }
                }
            });
        }
    </script>
</body>
</html>