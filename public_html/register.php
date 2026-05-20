<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 3) {
        $error = 'Username 至少 3 個字元';
    } elseif (strlen($password) < 6) {
        $error = '密碼至少 6 個字元';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            header('Location: my-souls.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Username 已存在';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>註冊 - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-zinc-900 border border-white/10 rounded-3xl p-10">
        <h1 class="text-3xl font-bold text-center mb-8">建立帳號</h1>

        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-500 p-3 rounded-2xl mb-6 text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm mb-2">Username</label>
                <input type="text" name="username" required class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
            </div>
            <div>
                <label class="block text-sm mb-2">Email（可選）</label>
                <input type="email" name="email" class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
            </div>
            <div>
                <label class="block text-sm mb-2">密碼</label>
                <input type="password" name="password" required class="w-full bg-zinc-800 border border-white/20 rounded-2xl px-5 py-3">
            </div>

            <button type="submit" class="w-full py-4 bg-white text-black font-semibold rounded-2xl mt-4">
                註冊
            </button>
        </form>

        <div class="text-center mt-6 text-sm text-zinc-400">
            已有帳號？ <a href="login.php" class="text-emerald-400 hover:underline">登入</a>
        </div>
    </div>
</body>
</html>