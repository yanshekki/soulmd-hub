<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

session_start();
$db = Database::getInstance();
$pdo = $db->getConnection();

// ==========================================
// 1. Remember Me 自動登入攔截 (處理從保護頁面跳轉過來的情況)
// ==========================================
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $tokenParts = explode(':', $_COOKIE['remember_token']);
    if (count($tokenParts) === 2) {
        try {
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? AND remember_token = ?");
            $stmt->execute([$tokenParts[0], $tokenParts[1]]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: /my-souls');
                exit;
            }
        } catch (PDOException $e) {
            // 忽略欄位未建立的錯誤
        }
    }
}

// 如果已經登入，直接跳轉到管理後台
if (isset($_SESSION['user_id'])) {
    header('Location: /my-souls');
    exit;
}

// ==========================================
// 2. AJAX 登入處理 (回傳 JSON 以完美顯示 Error)
// ==========================================
if (isset($_POST['ajax_login'])) {
    header('Content-Type: application/json');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // 如果有勾選 Remember me，產生 Token 並存入 Cookie (30日)
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            try {
                $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token, $user['id']]);
                setcookie('remember_token', $user['id'] . ':' . $token, time() + 86400 * 30, '/');
            } catch(PDOException $e) {}
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Incorrect username or password. Please try again.']);
    }
    exit;
}

require_once __DIR__ . '/../private/includes/seo.php';
$pageTitle = 'Log in';
$pageDesc = 'Sign in to your SoulMD Hub account.';
$hideNavLinks = true; // 隱藏多餘導覽列連結保持畫面簡潔
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="flex-grow flex items-center justify-center p-4 mt-16">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-semibold mb-2">Welcome back</h1>
            <p class="text-zinc-400">Sign in to manage your AI agent souls</p>
        </div>

        <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-center text-red-200 shadow-lg transition-all">
            <i class="fas fa-exclamation-circle mr-1"></i> <span id="error-msg"></span>
        </div>

        <form id="login-form" class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 space-y-6 backdrop-blur-sm shadow-2xl">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Username</label>
                <input type="text" id="username" name="username" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Password</label>
                <input type="password" id="password" name="password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div class="flex items-center text-xs text-zinc-400">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="remember" name="remember" class="accent-emerald-400 w-4 h-4 rounded bg-zinc-900 border-white/20"> Remember me
                </label>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-4 bg-emerald-500 text-zinc-950 font-bold text-lg rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg">
                <span id="submit-text">Log in</span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
            </button>
        </form>

        <div class="text-center mt-8 text-sm text-zinc-400">
            Don't have an account? <a href="/register" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition">Sign up</a>
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
        const errorBox = document.getElementById('error-box');
        const errorMsg = document.getElementById('error-msg');

        // 隱藏舊的錯誤訊息，切換按鈕狀態
        errorBox.classList.add('hidden');
        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-not-allowed');

        const formData = new FormData(form);
        formData.append('ajax_login', '1');

        try {
            const res = await fetch(window.location.href, { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                window.location.href = '/my-souls';
            } else {
                // 完美顯示錯誤
                errorMsg.innerText = data.error;
                errorBox.classList.remove('hidden');
                
                // 還原按鈕狀態
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        } catch (e) {
            errorMsg.innerText = 'Network Error. Please try again.';
            errorBox.classList.remove('hidden');
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>