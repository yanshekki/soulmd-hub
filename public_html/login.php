<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

setSEO('Log in', 'Sign in to your SoulMD Hub account.');

session_start();

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
<body class="bg-zinc-950 text-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="flex items-center justify-center gap-2 mb-4">
                <div class="text-4xl font-bold tracking-tighter">SoulMD</div>
                <div class="text-emerald-400 text-xs font-medium px-3 py-1 bg-emerald-900/30 rounded-full">HUB</div>
            </div>
            <h1 class="text-3xl font-semibold">Welcome back</h1>
            <p class="text-zinc-400 mt-2">Sign in to manage your souls</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-500 p-4 rounded-3xl mb-8 text-sm text-center">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form id="login-form" class="bg-zinc-900 border border-white/10 rounded-3xl p-8 space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Username</label>
                <input type="text" id="username" name="username" required class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Password</label>
                <input type="password" id="password" name="password" required class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="accent-emerald-400"> Remember me
                </label>
                <a href="#" class="text-emerald-400 hover:underline">Forgot password?</a>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-5 bg-white text-black font-semibold text-lg rounded-3xl hover:bg-zinc-200 transition flex items-center justify-center gap-3">
                <span id="submit-text">Log in</span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-black border-t-transparent rounded-full"></span>
            </button>
        </form>

        <div class="text-center mt-8 text-sm text-zinc-400">
            Don't have an account? <a href="register" class="text-emerald-400 hover:underline font-medium">Sign up</a>
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

            const formData = new FormData(form);
            const res = await fetch('login', { method: 'POST', body: formData });
            const html = await res.text();

            if (html.includes('Location: my-souls')) {
                window.location.href = 'my-souls';
            } else {
                document.body.innerHTML = html;
            }
        });
    </script>
</body>
</html>