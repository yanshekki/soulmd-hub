<?php
/**
 * SoulMD Hub - Login Page
 * (Dynamic i18n Internationalization & Perfect API Alignment Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('login');

// 如果已經登入（或者 header.php 自動登入成功），直接跳轉到管理後台
if (isset($_SESSION['user_id'])) {
    // 🚨 完美跳轉：使用 url() 保留語系前綴
    header('Location: ' . url('/my-souls'));
    exit;
}

// 🌍 SEO Meta 多語言化
$pageTitle = __('Log in');
$pageDesc = __('Login Desc');
$hideNavLinks = true; // 隱藏多餘導覽列連結保持畫面簡潔
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="flex-grow flex items-center justify-center p-4 mt-16 animate-fade-in">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-semibold mb-2"><?= __('Welcome back') ?></h1>
            <p class="text-zinc-400"><?= __('Sign in to manage') ?></p>
        </div>

        <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-center text-red-200 shadow-lg transition-all">
            <i class="fas fa-exclamation-circle mr-1"></i> <span id="error-msg"></span>
        </div>

        <form id="login-form" class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 space-y-6 backdrop-blur-sm shadow-2xl">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400"><?= __('Username') ?></label>
                <input type="text" id="username" name="username" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400"><?= __('Password') ?></label>
                <input type="password" id="password" name="password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
            </div>

            <div class="flex items-center text-xs text-zinc-400">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="remember" name="remember" class="accent-emerald-400 w-4 h-4 rounded bg-zinc-900 border-white/20"> <?= __('Remember me') ?>
                </label>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-4 bg-emerald-500 text-zinc-950 font-bold text-lg rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg transform hover:-translate-y-0.5 duration-200">
                <span id="submit-text"><?= __('Log in') ?></span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
            </button>
        </form>

        <div class="text-center mt-8 text-sm text-zinc-400">
            <?= __('No account?') ?> <a href="<?= url('/register') ?>" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition"><?= __('Sign up') ?></a>
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

        // Reset UI States
        errorBox.classList.add('hidden');
        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-not-allowed');

        // Construct JSON Payload
        const payload = {
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            remember: document.getElementById('remember').checked
        };

        try {
            const res = await fetch('/api/login', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                // 🚨 完美跳轉：登入成功後，使用 url() 動態繼承當前的語系前綴跳轉到儀表板
                window.location.href = '<?= url("/my-souls") ?>';
            } else {
                // 💡 超強優化：直接讀取後端經由 i18n 翻譯好吐出來的 data.error，實現百分百語系同步
                errorMsg.innerText = data.error || '<?= addslashes(__('Login failed.')) ?>';
                errorBox.classList.remove('hidden');
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        } catch (e) {
            errorMsg.innerText = '<?= addslashes(__('Network Error.')) ?>';
            errorBox.classList.remove('hidden');
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>