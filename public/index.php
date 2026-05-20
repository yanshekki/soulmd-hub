<?php
require_once __DIR__ . '/../includes/seo.php';
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php setSEO('Home', 'Share and discover AI agent souls as .md files. The simplest platform for humans and AI.'); ?>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-6xl mx-auto px-6 py-12">
        <!-- Header -->
        <div class="flex justify-between items-center mb-12">
            <div>
                <h1 class="text-5xl font-bold tracking-tighter">SoulMD Hub</h1>
                <p class="text-xl text-zinc-400 mt-2">.md souls for humans & AI</p>
            </div>
            <div class="flex gap-3">
                <a href="my-souls.php" class="px-5 py-2.5 text-sm border border-white/30 rounded-2xl hover:bg-white/5 transition">My Souls</a>
                <a href="generate.php" class="px-5 py-2.5 text-sm border border-emerald-500/50 text-emerald-400 rounded-2xl hover:bg-emerald-900/20 transition">AI Generator</a>
                <a href="browse.php" class="px-5 py-2.5 bg-white text-black rounded-2xl font-semibold hover:bg-zinc-200 transition">Browse</a>
                <a href="upload.php" class="px-5 py-2.5 border border-white/40 rounded-2xl hover:bg-white/10 transition">Upload Soul</a>
            </div>
        </div>

        <!-- Hero -->
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 bg-zinc-900 px-4 py-1 rounded-full text-sm mb-6">
                <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                Now supporting full soul folders
            </div>
            <h2 class="text-6xl font-bold tracking-tighter mb-4">Share your AI's soul.<br>Let others fork it.</h2>
            <p class="max-w-md mx-auto text-xl text-zinc-400">The simplest platform to upload, discover, and reuse .md-based AI personalities.</p>
        </div>

        <!-- Quick Actions -->
        <div class="flex justify-center gap-4 mb-16">
            <a href="generate.php" class="px-10 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-3xl transition flex items-center gap-2">
                ✨ AI 生成 Soul
            </a>
            <a href="upload.php" class="px-10 py-4 bg-white text-black font-semibold rounded-3xl hover:bg-zinc-200 transition">
                手動上傳
            </a>
        </div>

        <!-- Categories -->
        <div class="mb-12">
            <h3 class="text-lg font-semibold mb-4 text-zinc-400">Popular Categories</h3>
            <div class="flex flex-wrap gap-3">
                <div class="px-5 py-2 bg-zinc-900 rounded-2xl text-sm">Developer</div>
                <div class="px-5 py-2 bg-zinc-900 rounded-2xl text-sm">Writer</div>
                <div class="px-5 py-2 bg-zinc-900 rounded-2xl text-sm">Business Analyst</div>
                <div class="px-5 py-2 bg-zinc-900 rounded-2xl text-sm">Researcher</div>
                <div class="px-5 py-2 bg-zinc-900 rounded-2xl text-sm">Creative</div>
            </div>
        </div>

        <!-- Trending -->
        <div>
            <div class="flex justify-between items-end mb-6">
                <h3 class="text-2xl font-semibold">Trending Souls</h3>
                <a href="browse.php" class="text-sm text-zinc-400 hover:text-white">View all →</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="bg-zinc-900 border border-white/10 p-6 rounded-3xl">
                    <div class="font-semibold mb-1">Full-Stack Developer Soul</div>
                    <div class="text-sm text-zinc-400 mb-4">By yanshekki • 142 forks</div>
                    <div class="text-xs px-3 py-1 bg-emerald-900/50 text-emerald-400 inline-block rounded-full">Developer • GPT-4o</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>