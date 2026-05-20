<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

// Dynamic SEO
$seoTitle = 'Browse AI Souls';
$seoDesc = 'Discover and explore thousands of AI agent souls shared by the community.';
if (!empty($_GET['q'])) {
    $seoTitle = 'Search: ' . htmlspecialchars($_GET['q']);
    $seoDesc = 'Search results for "' . htmlspecialchars($_GET['q']) . '" on SoulMD Hub.';
}
setSEO($seoTitle, $seoDesc);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seoTitle) ?> - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        <!-- Navbar -->
        <nav class="flex justify-between items-center mb-10">
            <a href="/" class="flex items-center gap-2 text-3xl font-bold tracking-tighter">SoulMD</a>
            <div class="hidden md:flex items-center gap-8 text-sm">
                <a href="browse" class="font-medium text-emerald-400">Browse</a>
                <a href="generate" class="hover:text-emerald-400 transition">AI Generator</a>
                <a href="upload" class="hover:text-emerald-400 transition">Upload</a>
                <a href="my-souls" class="hover:text-emerald-400 transition">My Souls</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="login" class="text-sm px-6 py-2 border border-white/30 rounded-3xl hover:bg-white/5 transition">Log in</a>
                <a href="register" class="text-sm px-6 py-2 bg-white text-black rounded-3xl font-semibold hover:bg-zinc-200 transition">Sign up</a>
            </div>
        </nav>

        <h1 class="text-5xl font-bold tracking-tighter mb-2">Browse Souls</h1>
        <p class="text-zinc-400 mb-10">Discover and explore AI personalities shared by the community.</p>

        <!-- Filters + Search -->
        <div class="flex flex-col lg:flex-row gap-4 mb-10">
            <div class="flex-1 relative">
                <input id="search-input" type="text" placeholder="Search souls..." 
                       class="w-full bg-zinc-900 border border-white/10 rounded-3xl px-6 py-4 text-lg focus:outline-none focus:border-emerald-400 pl-12">
                <i class="fas fa-search absolute left-6 top-1/2 -translate-y-1/2 text-zinc-400"></i>
            </div>

            <div class="flex gap-3">
                <!-- Role filter -->
                <select id="role-filter" class="bg-zinc-900 border border-white/10 rounded-3xl px-6 py-4 text-sm focus:outline-none focus:border-emerald-400">
                    <option value="">All Roles</option>
                    <option value="Developer">Developer</option>
                    <option value="Writer">Writer</option>
                    <option value="Business Analyst">Business Analyst</option>
                    <option value="Researcher">Researcher</option>
                    <option value="Creative">Creative</option>
                    <option value="Personal Assistant">Personal Assistant</option>
                </select>

                <!-- File type filter -->
                <select id="type-filter" class="bg-zinc-900 border border-white/10 rounded-3xl px-6 py-4 text-sm focus:outline-none focus:border-emerald-400">
                    <option value="">All Types</option>
                    <option value="single_md">Single .md</option>
                    <option value="full_soul_folder">Full Soul Folder</option>
                </select>

                <!-- Clear button -->
                <button onclick="clearFilters()" 
                        class="px-6 py-4 border border-white/20 rounded-3xl hover:bg-white/5 transition text-sm flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    Clear
                </button>
            </div>
        </div>

        <!-- Results -->
        <div id="results-container" class="min-h-[400px]">
            <!-- AJAX content loaded here -->
        </div>
    </div>

    <script>
        let timeout = null;

        async function loadSouls() {
            const container = document.getElementById('results-container');
            container.innerHTML = `
                <div class="flex justify-center py-20">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-400"></div>
                </div>
            `;

            const q = document.getElementById('search-input').value.trim();
            const role = document.getElementById('role-filter').value;
            const type = document.getElementById('type-filter').value;

            const params = new URLSearchParams();
            if (q) params.append('q', q);
            if (role) params.append('role', role);
            if (type) params.append('file_type', type);

            try {
                const res = await fetch(`api/souls.php?${params.toString()}`);
                const data = await res.json();

                if (data.success && data.data.length > 0) {
                    let html = `<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">`;
                    data.data.forEach(soul => {
                        html += `
                            <a href="soul.php?id=${soul.id}" class="group bg-zinc-900 border border-white/10 rounded-3xl p-6 hover:border-emerald-400/50 transition-all">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="font-semibold text-xl group-hover:text-emerald-400 transition">${soul.title}</div>
                                    <div class="text-xs px-3 py-1 rounded-full ${soul.file_type === 'full_soul_folder' ? 'bg-purple-900 text-purple-400' : 'bg-emerald-900 text-emerald-400'}">
                                        ${soul.file_type === 'full_soul_folder' ? 'Folder' : '.md'}
                                    </div>
                                </div>
                                ${soul.description ? `<p class="text-sm text-zinc-400 line-clamp-3 mb-6">${soul.description}</p>` : ''}
                                <div class="flex items-center justify-between text-xs text-zinc-500">
                                    <div>${soul.role || '—'}</div>
                                    <div class="flex items-center gap-3">
                                        <span>${soul.fork_count} forks</span>
                                        <span>${soul.like_count} likes</span>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    html += `</div>`;
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `
                        <div class="text-center py-20 text-zinc-400">
                            <div class="text-6xl mb-6">🔎</div>
                            <p class="text-xl">No souls found</p>
                            <p class="text-sm mt-2">Try different keywords or filters</p>
                        </div>
                    `;
                }
            } catch (e) {
                container.innerHTML = `<div class="text-red-400 text-center py-20">Error loading souls</div>`;
            }
        }

        function clearFilters() {
            document.getElementById('search-input').value = '';
            document.getElementById('role-filter').value = '';
            document.getElementById('type-filter').value = '';
            loadSouls();
        }

        // Real-time search
        document.getElementById('search-input').addEventListener('input', () => {
            clearTimeout(timeout);
            timeout = setTimeout(loadSouls, 300);
        });

        // Filter change
        document.getElementById('role-filter').addEventListener('change', loadSouls);
        document.getElementById('type-filter').addEventListener('change', loadSouls);

        // Initial load
        window.onload = loadSouls;
    </script>
</body>
</html>