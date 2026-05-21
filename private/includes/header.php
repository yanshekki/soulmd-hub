<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
    // SEO 動態注入
    if (isset($pageTitle)) {
        setSEO($pageTitle, $pageDesc ?? '');
    } else {
        setSEO('SoulMD Hub', '');
    }
    ?>
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    
    <style>
        .gradient-text { background: linear-gradient(to right, #34d399, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.3); }
        .tag-input-field:focus { outline: none !important; box-shadow: none !important; }
        ::-webkit-calendar-picker-indicator { filter: invert(1); cursor: pointer; }
    </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen flex flex-col relative">

    <nav class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 flex justify-between items-center <?= isset($navAbsolute) && $navAbsolute ? 'absolute top-0 left-0 right-0 z-50' : 'mb-4' ?>">
        <a href="/" class="flex items-center gap-2 text-2xl font-bold tracking-tighter hover:text-emerald-400 transition w-fit">
            SoulMD <span class="text-emerald-400 text-[10px] px-2 py-1 bg-emerald-900/30 rounded-full">HUB</span>
        </a>
        
        <?php if (!isset($hideNavLinks) || !$hideNavLinks): ?>
        <div class="hidden md:flex items-center gap-8 text-sm font-medium">
            <a href="/browse" class="text-zinc-400 hover:text-emerald-400 transition">Browse</a>
            <a href="/generate" class="text-zinc-400 hover:text-emerald-400 transition">AI Generator</a>
            <a href="/upload" class="text-zinc-400 hover:text-emerald-400 transition">Upload</a>
        </div>
        <?php endif; ?>

        <div class="flex items-center gap-3 shrink-0">
            <?php if ($isLoggedIn): ?>
                <a href="/my-souls" class="text-sm px-4 py-2 border border-white/10 rounded-2xl hover:bg-white/5 transition flex items-center gap-2">
                    <i class="fas fa-user-circle text-emerald-400"></i> <span class="hidden sm:inline">My Souls</span>
                </a>
                <a href="/logout" class="text-sm px-4 py-2 bg-red-500/10 text-red-400 border border-red-500/20 rounded-2xl hover:bg-red-500 hover:text-white transition flex items-center gap-2">
                    <i class="fas fa-sign-out-alt"></i> <span class="hidden sm:inline">Log out</span>
                </a>
            <?php else: ?>
                <a href="/login" class="text-sm px-5 py-2 border border-white/30 rounded-2xl hover:bg-white/5 transition">Log in</a>
                <a href="/register" class="text-sm px-5 py-2 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg">Sign up</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="flex-grow flex flex-col relative z-10">