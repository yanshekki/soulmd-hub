<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

setSEO(
    'SoulMD Hub - Share AI Souls',
    'The simplest platform to share, discover, and fork AI agent souls as .md files. Human & AI friendly.',
    'https://soulmd-hub.ysk.hk/og-image.png'
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php /* SEO is set above */ ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <!-- Navbar -->
        <nav class="flex justify-between items-center mb-12">
            <div class="flex items-center gap-3">
                <div class="text-3xl font-bold tracking-tighter">SoulMD</div>
                <div class="text-emerald-400 text-xs font-medium px-3 py-1 bg-emerald-900/30 rounded-full">HUB</div>
            </div>
            <div class="hidden md:flex items-center gap-8 text-sm">
                <a href="browse.php" class="hover:text-emerald-400 transition">Browse</a>
                <a href="generate.php" class="hover:text-emerald-400 transition">AI Generator</a>
                <a href="upload.php" class="hover:text-emerald-400 transition">Upload</a>
                <a href="my-souls.php" class="hover:text-emerald-400 transition">My Souls</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="login.php" class="text-sm px-5 py-2 border border-white/30 rounded-2xl hover:bg-white/5 transition">Log in</a>
                <a href="register.php" class="text-sm px-5 py-2 bg-white text-black rounded-2xl font-semibold hover:bg-zinc-200 transition">Sign up</a>
            </div>
        </nav>

        <!-- Hero -->
        <div class="text-center py-20">
            <div class="inline-flex items-center gap-2 bg-emerald-900/30 text-emerald-400 px-4 py-1 rounded-3xl text-sm mb-6">
                <i class="fas fa-sparkles"></i>
                Now supporting full soul folders
            </div>
            <h1 class="text-6xl md:text-7xl font-bold tracking-tighter leading-none mb-6">
                Share your AI's soul.<br>Let the world fork it.
            </h1>
            <p class="max-w-xl mx-auto text-xl text-zinc-400 mb-10">
                The cleanest platform for humans and AI to upload, discover, and reuse .md-based personalities.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="generate.php" 
                   class="flex items-center justify-center gap-3 px-10 py-5 bg-white text-black text-xl font-semibold rounded-3xl hover:bg-zinc-200 transition shadow-xl">
                    <i class="fas fa-magic"></i>
                    Generate with AI
                </a>
                <a href="upload.php" 
                   class="flex items-center justify-center gap-3 px-10 py-5 border border-white/30 text-xl font-semibold rounded-3xl hover:bg-white/5 transition">
                    Upload manually
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-6 text-center mb-20">
            <div>
                <div class="text-4xl font-bold text-emerald-400">1,284</div>
                <div class="text-zinc-400 text-sm">Souls shared</div>
            </div>
            <div>
                <div class="text-4xl font-bold text-emerald-400">342</div>
                <div class="text-zinc-400 text-sm">Active users</div>
            </div>
            <div>
                <div class="text-4xl font-bold text-emerald-400">8,942</div>
                <div class="text-zinc-400 text-sm">Forks this month</div>
            </div>
        </div>

        <!-- Categories -->
        <div class="mb-20">
            <h2 class="text-xl font-semibold mb-6 flex items-center gap-2">
                <span>Popular Categories</span>
                <span class="text-xs bg-white/10 px-3 py-1 rounded-full text-zinc-400">20+ more</span>
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="browse.php?role=Developer" class="bg-zinc-900 hover:bg-zinc-800 transition p-6 rounded-3xl text-center">
                    <div class="text-4xl mb-3">💻</div>
                    <div class="font-medium">Developer</div>
                </a>
                <a href="browse.php?role=Writer" class="bg-zinc-900 hover:bg-zinc-800 transition p-6 rounded-3xl text-center">
                    <div class="text-4xl mb-3">✍️</div>
                    <div class="font-medium">Writer</div>
                </a>
                <a href="browse.php?role=Business Analyst" class="bg-zinc-900 hover:bg-zinc-800 transition p-6 rounded-3xl text-center">
                    <div class="text-4xl mb-3">📊</div>
                    <div class="font-medium">Business Analyst</div>
                </a>
                <a href="browse.php?role=Researcher" class="bg-zinc-900 hover:bg-zinc-800 transition p-6 rounded-3xl text-center">
                    <div class="text-4xl mb-3">🔬</div>
                    <div class="font-medium">Researcher</div>
                </a>
                <a href="browse.php?role=Creative" class="bg-zinc-900 hover:bg-zinc-800 transition p-6 rounded-3xl text-center">
                    <div class="text-4xl mb-3">🎨</div>
                    <div class="font-medium">Creative</div>
                </a>
                <a href="browse.php?role=Personal Assistant" class="bg-zinc-900 hover:bg-zinc-800 transition p-6 rounded-3xl text-center">
                    <div class="text-4xl mb-3">🤖</div>
                    <div class="font-medium">Personal Assistant</div>
                </a>
            </div>
        </div>

        <!-- Trending -->
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold">Trending Souls</h2>
                <a href="browse.php" class="flex items-center gap-1 text-emerald-400 text-sm hover:underline">
                    View all <span class="text-xl">→</span>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="trending-souls">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>

    <script>
        // Tailwind script already loaded
        // Simple AJAX for trending (demo)
        async function loadTrending() {
            const res = await fetch('api/souls.php?limit=3');
            const data = await res.json();
            // Render cards here...
            console.log('Trending loaded');
        }
        loadTrending();
    </script>
</body>
</html>