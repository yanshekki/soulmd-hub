<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

setSEO('Sign up', 'Create your SoulMD Hub account and start sharing AI souls.');

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            header('Location: my-souls.php');
            exit;
        } catch (Exception $e) {
            $error = 'Username already taken';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - SoulMD Hub</title>
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
            <h1 class="text-3xl font-semibold">Create your account</h1>
            <p class="text-zinc-400 mt-2">Start sharing AI souls today</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-500 p-4 rounded-3xl mb-8 text-sm text-center">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form id="register-form" class="bg-zinc-900 border border-white/10 rounded-3xl p-8 space-y-6">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Username</label>
                <input type="text" id="username" name="username" required class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Email (optional)</label>
                <input type="email" id="email" name="email" class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Password</label>
                <input type="password" id="password" name="password" required class="w-full bg-zinc-800 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
            </div>

            <div class="flex items-center text-xs text-zinc-400">
                <input type="checkbox" id="terms" class="accent-emerald-400 mr-2">
                I agree to the <a href="#" class="text-emerald-400 hover:underline">Terms</a> and <a href="#" class="text-emerald-400 hover:underline">Privacy Policy</a>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-5 bg-white text-black font-semibold text-lg rounded-3xl hover:bg-zinc-200 transition flex items-center justify-center gap-3">
                <span id="submit-text">Create account</span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-black border-t-transparent rounded-full"></span>
            </button>
        </form>

        <div class="text-center mt-8 text-sm text-zinc-400">
            Already have an account? <a href="login.php" class="text-emerald-400 hover:underline font-medium">Log in</a>
        </div>
    </div>

    <script>
        const form = document.getElementById('register-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            const text = document.getElementById('submit-text');
            const loading = document.getElementById('submit-loading');

            text.classList.add('hidden');
            loading.classList.remove('hidden');

            const formData = new FormData(form);
            const res = await fetch('register.php', { method: 'POST', body: formData });
            const html = await res.text();

            if (html.includes('Location: my-souls.php')) {
                window.location.href = 'my-souls.php';
            } else {
                document.body.innerHTML = html;
            }
        });
    </script>
</body>
</html>