<?php
session_start();
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: my-souls.php');
        exit;
    } else {
        $error = 'Username 或密碼錯誤';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>登入 - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-3xl p-10">
        <h1 class="text-3xl font-bold text-center mb-8">登入</h1>

        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-500 p-3 rounded-2xl mb-6 text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm mb-2">Username</label>
                <input type="text" name="username" required class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
            </div>
            <div>
                <label class="block text-sm mb-2">密碼</label>
                <input type="password" name="password" required class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
            </div>

            <button type="submit" class="w-full py-4 bg-white text-black font-semibold rounded-2xl mt-4">
                登入
            </button>
        </form>

        <div class="text-center mt-6 text-sm text-zinc-400">
            仲未有帳號？ <a href="register.php" class="text-emerald-400 hover:underline">註冊</a>
        </div>
    </div>
</body>
</html>