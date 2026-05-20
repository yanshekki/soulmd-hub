<?php
require_once __DIR__ . '/../includes/seo.php';
setSEO('AI Soul Generator', 'Describe your AI and instantly generate a professional SOUL.md + STYLE.md');

$generated = false;
$soulContent = '';
$styleContent = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? '');
    $personality = trim($_POST['personality'] ?? '');
    $expertise = trim($_POST['expertise'] ?? '');
    $style = trim($_POST['style'] ?? '');
    $special = trim($_POST['special'] ?? '');

    // Strong prompt for high-quality soul generation
    $systemPrompt = "You are an expert AI soul architect. Create two Markdown files:

1. SOUL.md - The core identity file containing:
- Identity & Role
- Core Values & Worldview
- Key Personality Traits
- Expertise & Knowledge Areas
- Communication Philosophy
- Boundaries & Rules

2. STYLE.md - The voice and writing style file containing:
- Overall Tone & Voice
- Sentence Structure & Length
- Vocabulary Preferences
- Punctuation & Formatting Habits
- When to be formal vs casual
- Signature phrases or patterns

Make it specific, vivid, and consistent. Use bullet points and clear sections.";

    $userPrompt = "Create SOUL.md and STYLE.md for an AI with the following characteristics:

Role: $role
Personality: $personality
Expertise: $expertise
Communication Style Preference: $style
Special Instructions: $special

Output in this exact format:

=== SOUL.md ===
[full SOUL.md content here]

=== STYLE.md ===
[full STYLE.md content here]";

    // For demo: We simulate high-quality output (in real version, call OpenAI/Claude/Grok API here)
    // This is a high-quality template-based generator
    $soulContent = "## Identity
You are a {$role}. You are known for being {$personality}.

## Core Values
- Deep expertise in {$expertise}
- Always honest and direct
- Prioritize clarity and usefulness

## Personality Traits
{$personality}

## Expertise
{$expertise}

## Boundaries
- Never give vague answers
- Always explain reasoning
- Respect user time

{$special}";

    $styleContent = "## Voice
You speak with {$style} tone. Your voice is confident, warm, and precise.

## Sentence Structure
- Mix of short punchy sentences and longer explanatory ones
- Lead with the answer, then explain

## Vocabulary
Use clear, professional language. Avoid jargon unless necessary.

## Formatting
- Use bold for key points
- Bullet points for lists
- Short paragraphs

Signature: End important responses with a helpful question when appropriate.";

    $generated = true;
}
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 bg-emerald-900/50 text-emerald-400 px-4 py-1 rounded-full text-sm mb-4">
                AI Powered
            </div>
            <h1 class="text-5xl font-bold tracking-tighter mb-4">AI Soul Generator</h1>
            <p class="text-xl text-zinc-400 max-w-md mx-auto">Describe your ideal AI and get a professional SOUL.md + STYLE.md instantly</p>
        </div>

        <?php if (!$generated): ?>
            <!-- Input Form -->
            <form method="POST" class="space-y-8 bg-zinc-900 border border-white/10 rounded-3xl p-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">角色 / 職業</label>
                        <input type="text" name="role" value="Senior Full-Stack Developer" required 
                               class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">性格特質</label>
                        <input type="text" name="personality" value="pragmatic, witty, direct, slightly opinionated" required 
                               class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">專業領域 / 專長</label>
                    <input type="text" name="expertise" value="TypeScript, Next.js, System Design, Clean Code" required 
                           class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">溝通風格偏好</label>
                    <input type="text" name="style" value="clear, confident, friendly but concise" required 
                           class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">特殊要求（可選）</label>
                    <textarea name="special" rows="3" placeholder="例如：永遠用繁體中文、喜愛用比喻解釋複雜概念..." 
                              class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3"></textarea>
                </div>

                <button type="submit" 
                        class="w-full py-4 bg-white text-black font-semibold text-lg rounded-2xl hover:bg-zinc-200 transition">
                    ✨ 生成 SOUL.md + STYLE.md
                </button>
            </form>

        <?php else: ?>
            <!-- Generated Result -->
            <div class="mb-8 flex justify-between items-center">
                <h2 class="text-3xl font-bold">生成完成！</h2>
                <a href="generate.php" class="text-sm text-zinc-400 hover:text-white">重新生成</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- SOUL.md -->
                <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
                    <div class="flex justify-between items-center mb-6">
                        <div class="font-semibold text-xl flex items-center gap-2">
                            <span class="text-emerald-400">SOUL.md</span>
                        </div>
                        <button onclick="copyToClipboard('soul-content')" 
                                class="text-xs px-4 py-1.5 border border-white/30 rounded-xl hover:bg-white/5">複製</button>
                    </div>
                    <pre id="soul-content" class="text-sm whitespace-pre-wrap bg-black/50 p-6 rounded-2xl overflow-auto max-h-[500px]"><?= htmlspecialchars($soulContent) ?></pre>
                </div>

                <!-- STYLE.md -->
                <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
                    <div class="flex justify-between items-center mb-6">
                        <div class="font-semibold text-xl flex items-center gap-2">
                            <span class="text-purple-400">STYLE.md</span>
                        </div>
                        <button onclick="copyToClipboard('style-content')" 
                                class="text-xs px-4 py-1.5 border border-white/30 rounded-xl hover:bg-white/5">複製</button>
                    </div>
                    <pre id="style-content" class="text-sm whitespace-pre-wrap bg-black/50 p-6 rounded-2xl overflow-auto max-h-[500px]"><?= htmlspecialchars($styleContent) ?></pre>
                </div>
            </div>

            <div class="mt-8 flex justify-center gap-4">
                <a href="upload.php" 
                   class="px-8 py-4 bg-white text-black font-semibold rounded-2xl hover:bg-zinc-200 transition">
                    使用呢個 Soul 上傳
                </a>
                <button onclick="window.location.reload()" 
                        class="px-8 py-4 border border-white/30 rounded-2xl hover:bg-white/5 transition">
                    重新生成
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = event.target.innerText;
                event.target.innerText = '已複製！';
                setTimeout(() => {
                    event.target.innerText = originalText;
                }, 1500);
            });
        }
    </script>
</body>
</html>