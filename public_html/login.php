<?php
/**
 * SoulMD Hub - Login Page
 * (Dynamic i18n Internationalization & Web2.5 Wallet Login Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('login');

// 如果已經登入（或者 header.php 自動登入成功），直接跳轉到管理後台
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('/my-souls'));
    exit;
}

// 🌍 SEO Meta 多語言化
$pageTitle = __('Log in');
$pageDesc = __('Login Desc');
$hideNavLinks = true; // 隱藏多餘導覽列連結保持畫面簡潔
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="flex-grow flex items-center justify-center p-4 mt-10 sm:mt-16 animate-fade-in">
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
            
            <div class="mt-6 pt-6 border-t border-white/10">
                <button type="button" onclick="handleNearLogin()" id="near-login-btn" class="w-full py-4 bg-zinc-950 border border-emerald-500/30 text-emerald-400 font-bold text-base rounded-2xl hover:bg-emerald-900/30 transition flex items-center justify-center gap-3 shadow-lg">
                    <i class="fas fa-wallet text-xl"></i> <span id="near-btn-text"><?= __('Login Web3') ?? __('Login with NEAR Wallet') ?></span>
                </button>
            </div>
        </form>

        <div class="text-center mt-8 text-sm text-zinc-400">
            <?= __('No account?') ?> <a href="<?= url('/register') ?>" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition"><?= __('Sign up') ?></a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<script>
    // 🚀 Web3 錢包登入邏輯
    async function handleNearLogin() {
        const btnText = document.getElementById('near-btn-text');
        const originalText = btnText.innerText;
        btnText.innerText = '<?= addslashes(__('Connecting...')) ?>';

        try {
            const wallet = await initNearWallet();
            if (!wallet.isSignedIn()) {
                // 導向錢包授權頁面
                wallet.requestSignIn({ contractId: "soulmd-hub.near" });
            } else {
                // 已授權，直接驗證後端
                await verifyNearWallet(wallet.getAccountId());
            }
        } catch(e) {
            btnText.innerText = originalText;
        }
    }

    // 偵測從錢包授權後返回
    window.addEventListener('DOMContentLoaded', async () => {
        const urlParams = new URLSearchParams(window.location.search);
        // 如果網址有 account_id，代表剛從錢包授權成功跳轉回來
        if (urlParams.has('account_id') || urlParams.has('all_keys')) {
            const wallet = await initNearWallet();
            if (wallet.isSignedIn()) {
                await verifyNearWallet(wallet.getAccountId());
            }
        }
    });

    // 呼叫後端 API 驗證錢包並登入
    async function verifyNearWallet(accountId) {
        const errorBox = document.getElementById('error-box');
        const errorMsg = document.getElementById('error-msg');
        const btnText = document.getElementById('near-btn-text');
        btnText.innerText = '<?= addslashes(__('Connecting...')) ?>';

        try {
            const res = await fetch('/api/wallet-login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ account_id: accountId })
            });
            const data = await res.json();
            if (data.success) {
                // 登入成功，跳去 My Souls 儀表板
                window.location.href = '<?= url("/my-souls") ?>';
            } else {
                // 如果後端找不到綁定，提示用戶並自動登出 Wallet 讓佢可以再試
                errorMsg.innerText = data.error || '<?= addslashes(__('Wallet not bound')) ?>';
                errorBox.classList.remove('hidden');
                btnText.innerText = '<?= addslashes(__('Login Web3')) ?? addslashes(__('Login with NEAR Wallet')) ?>';
                const wallet = await initNearWallet();
                wallet.signOut();
            }
        } catch (e) {
            errorMsg.innerText = '<?= addslashes(__('Network Error.')) ?>';
            errorBox.classList.remove('hidden');
            btnText.innerText = '<?= addslashes(__('Login Web3')) ?? addslashes(__('Login with NEAR Wallet')) ?>';
        }
    }

    // 原有 Web2 表單登入邏輯
    const form = document.getElementById('login-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('submit-btn');
        const text = document.getElementById('submit-text');
        const loading = document.getElementById('submit-loading');
        const errorBox = document.getElementById('error-box');
        const errorMsg = document.getElementById('error-msg');

        errorBox.classList.add('hidden');
        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-not-allowed');

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
                window.location.href = '<?= url("/my-souls") ?>';
            } else {
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