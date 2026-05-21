<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

setSEO('Log in', 'Sign in to your SoulMD Hub account.');

session_start();

// 如果已經登入，直接跳轉到管理後台
if (isset($_SESSION['user_id'])) {
    header('Location: my-souls');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: my-souls');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white min-h-screen flex flex-col">
    <nav class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 flex justify-between items-center absolute top-0 left-0 right-0">
        <a href="/" class="flex items-center gap-2 text-2xl font-bold tracking-tighter hover:text-emerald-400 transition w-fit">
            SoulMD <span class="text-emerald-400 text-[10px] px-2 py-1 bg-emerald-900/30 rounded-full">HUB</span>
        </a>
    </nav>

    <div class="flex-grow flex items-center justify-center p-4 mt-16">
        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-semibold mb-2">Welcome back</h1>
                <p class="text-zinc-400">Sign in to manage your AI agent souls</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-900/50 border border-red-500 p-4 rounded-3xl mb-8 text-sm text-center">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form id="login-form" class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 space-y-6 backdrop-blur-sm shadow-2xl">
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-400">Username</label>
                    <input type="text" id="username" name="username" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-400">Password</label>
                    <input type="password" id="password" name="password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
                </div>

                <div class="flex items-center justify-between text-xs text-zinc-400">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" class="accent-emerald-400 w-4 h-4 rounded bg-zinc-900 border-white/20"> Remember me
                    </label>
                    <a href="#" class="text-emerald-400 hover:text-emerald-300 hover:underline transition">Forgot password?</a>
                </div>

                <button type="submit" id="submit-btn" class="w-full py-4 bg-emerald-500 text-zinc-950 font-bold text-lg rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg">
                    <span id="submit-text">Log in</span>
                    <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
                </button>
            </form>

            <div class="text-center mt-8 text-sm text-zinc-400">
                Don't have an account? <a href="register" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition">Sign up</a>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('login-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            const text = document.getElementById('submit-text');
            const loading = document.getElementById('submit-loading');

            text.classList.add('hidden');
            loading.classList.remove('hidden');
            btn.classList.add('opacity-80', 'cursor-not-allowed');

            const formData = new FormData(form);
            try {
                const res = await fetch(window.location.href, { method: 'POST', body: formData });
                const html = await res.text();

                if (html.includes('Location: my-souls')) {
                    window.location.href = 'my-souls';
                } else {
                    document.body.innerHTML = html;
                }
            } catch (e) {
                alert('Network Error. Please try again.');
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        });
    </script>
</body>
</html>