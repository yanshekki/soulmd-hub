<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

setSEO('My API Key', 'Manage your API key for SoulMD Hub public API.');

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];

$message = '';

if (isset($_POST['regenerate'])) {
    $newKey = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$newKey, $userId]);
    $message = '✅ API Key regenerated successfully!';
}

$stmt = $pdo->prepare("SELECT api_key FROM users WHERE id = ?");
$stmt->execute([$userId]);
$apiKey = $stmt->fetch()['api_key'] ?? null;

if (!$apiKey) {
    $apiKey = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$apiKey, $userId]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My API Key - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-12">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold tracking-tighter">My API Key</h1>
                <p class="text-zinc-400 mt-1">For programmatic access to SoulMD Hub</p>
            </div>
            <a href="my-souls" class="text-sm text-zinc-400 hover:text-white flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> My Souls
            </a>
        </div>

        <?php if ($message): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 p-6 rounded-3xl mb-8 text-lg">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="bg-zinc-900 border border-white/10 rounded-3xl p-8">
            <div class="mb-6">
                <div class="flex justify-between items-center text-sm text-zinc-400 mb-3">
                    <span>Your API Key</span>
                    <button onclick="copyKey()" class="flex items-center gap-1 text-emerald-400 hover:text-emerald-300">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <div id="key-display" class="bg-black/60 font-mono text-sm p-6 rounded-3xl break-all select-all">
                    <?= htmlspecialchars($apiKey) ?>
                </div>
            </div>

            <div class="text-xs text-zinc-400 mb-8">
                Keep this key secret. Anyone with it can create souls on your behalf.
            </div>

            <form method="POST" class="flex justify-center">
                <button type="submit" name="regenerate" class="px-10 py-4 border border-white/30 text-sm font-medium rounded-3xl hover:bg-white/5 transition flex items-center gap-2">
                    <i class="fas fa-redo"></i> Regenerate Key
                </button>
            </form>
        </div>

        <div class="mt-12 text-sm text-zinc-400">
            <strong>How to use:</strong><br>
            Add to your requests:<br>
            <code class="block bg-zinc-900 p-4 rounded-3xl mt-3 font-mono text-xs">Authorization: Bearer <?= htmlspecialchars($apiKey) ?></code>
        </div>
    </div>

    <script>
        function copyKey() {
            const key = document.getElementById('key-display').innerText;
            navigator.clipboard.writeText(key).then(() => {
                const original = event.target.innerHTML;
                event.target.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => event.target.innerHTML = original, 2000);
            });
        }
    </script>
</body>
</html>