<?php
session_start();
require_once __DIR__ . '/../includes/seo.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

setSEO('My API Key', 'Manage your API key for SoulMD Hub public API.');

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

$message = '';

// Regenerate API Key
if (isset($_POST['regenerate'])) {
    $newKey = bin2hex(random_bytes(32)); // 64 char key
    $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")
        ->execute([$newKey, $userId]);
    $message = 'API Key 已重新生成！';
}

// Get current API Key
$stmt = $pdo->prepare("SELECT api_key FROM users WHERE id = ?");
$stmt->execute([$userId]);
$apiKey = $stmt->fetch()['api_key'] ?? null;

if (!$apiKey) {
    // Auto generate first time
    $apiKey = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")
        ->execute([$apiKey, $userId]);
}
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-2xl mx-auto px-6 py-12">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold">My API Key</h1>
                <p class="text-zinc-400 mt-1">用於公開 API 存取</p>
            </div>
            <a href="my-souls.php" class="text-sm text-zinc-400 hover:text-white">← 返回 My Souls</a>
        </div>

        <?php if ($message): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-6">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
            <div class="mb-6">
                <div class="text-sm text-zinc-400 mb-2">你嘅 API Key</div>
                <div class="bg-black/50 p-4 rounded-2xl font-mono text-sm break-all">
                    <?= $apiKey ?>
                </div>
            </div>

            <div class="text-xs text-zinc-500 mb-6">
                請妥善保管此金鑰。任何人擁有此金鑰都可以代表你創建 souls。
            </div>

            <form method="POST">
                <button type="submit" name="regenerate" 
                        class="w-full py-4 bg-white text-black font-semibold rounded-2xl hover:bg-zinc-200 transition">
                    重新生成 API Key
                </button>
            </form>
        </div>

        <div class="mt-8 text-sm text-zinc-400">
            <strong>使用方法：</strong><br>
            POST /api/souls 時在 Header 加：<br>
            <code class="bg-zinc-800 px-2 py-1 rounded">Authorization: Bearer <?= $apiKey ?></code>
        </div>
    </div>
</body>
</html>