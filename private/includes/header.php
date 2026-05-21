<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 處理公開頁面 (如首頁/Browse) 的 Remember Me 自動登入
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/../src/Database.php';
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        $tokenParts = explode(':', $_COOKIE['remember_token']);
        if (count($tokenParts) === 2) {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? AND remember_token = ?");
            $stmt->execute([$tokenParts[0], $tokenParts[1]]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
            }
        }
    } catch(Exception $e) {}
}

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
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
                <a href="/my-souls" class="text-sm px-4 py-2 border border-white/10 rounded-2xl hover:bg-white/5 transition flex items-center gap-2" title="My Souls">
                    <i class="fas fa-user-circle text-emerald-400"></i> <span class="hidden sm:inline">My Souls</span>
                </a>
                <a href="/change-password" class="text-sm px-4 py-2 border border-white/10 rounded-2xl hover:bg-white/5 transition flex items-center gap-2" title="Change Password">
                    <i class="fas fa-key text-emerald-400"></i>
                </a>
                <a href="/logout" class="text-sm px-4 py-2 bg-red-500/10 text-red-400 border border-red-500/20 rounded-2xl hover:bg-red-500 hover:text-white transition flex items-center gap-2" title="Log out">
                    <i class="fas fa-sign-out-alt"></i> <span class="hidden md:inline">Log out</span>
                </a>
            <?php else: ?>
                <a href="/login" class="text-sm px-5 py-2 border border-white/30 rounded-2xl hover:bg-white/5 transition">Log in</a>
                <a href="/register" class="text-sm px-5 py-2 bg-emerald-500 text-zinc-950 rounded-2xl font-bold hover:bg-emerald-400 transition shadow-lg">Sign up</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="flex-grow flex flex-col relative z-10">