<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_id = $_SESSION['user_id'];

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // 取得當前使用者嘅資料
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password'])) {
        $error = 'Incorrect current password.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } else {
        // 更新為新密碼
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$hash, $user_id]);
        $success = '✅ Password successfully updated!';
    }
}

$pageTitle = 'Change Password';
$pageDesc = 'Update your SoulMD Hub account password.';
$hideNavLinks = true; // 隱藏多餘導覽列連結
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="flex-grow flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-semibold mb-2">Change Password</h1>
            <p class="text-zinc-400">Keep your account secure</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-center">
                <i class="fas fa-exclamation-circle mr-1"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-8 text-sm text-center text-emerald-100">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <form id="password-form" method="POST" class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 space-y-6 backdrop-blur-sm shadow-2xl">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Current Password</label>
                <input type="password" id="current_password" name="current_password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div class="pt-4 border-t border-white/5">
                <label class="block text-sm font-medium mb-2 text-zinc-400">New Password <span class="text-zinc-500 font-normal">(min 6 chars)</span></label>
                <input type="password" id="new_password" name="new_password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <button type="submit" id="submit-btn" class="w-full py-4 bg-emerald-500 text-zinc-950 font-bold text-lg rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg">
                <span id="submit-text">Update Password</span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
            </button>
        </form>

        <div class="text-center mt-8 text-sm text-zinc-400">
            <a href="/my-souls" class="text-zinc-400 hover:text-white transition flex items-center justify-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to My Souls
            </a>
        </div>
    </div>
</div>

<script>
    const form = document.getElementById('password-form');
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
            document.body.innerHTML = html; // 直接將畫面替換為包含成功/失敗提示的畫面
        } catch (e) {
            alert('Network Error. Please try again.');
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>