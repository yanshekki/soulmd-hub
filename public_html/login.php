<?php
/**
 * SoulMD Hub - Login Page
 * (Dynamic i18n Internationalization & Pure Native MyNearWallet Redirect Edition)
 * 🚀 Patched: 100% Pure Redirect Mode & Non-Black Emerald Contrast UI
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

loadTranslations('login');

// 如果用戶已經登入，直接跳轉到控制台
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('/my-souls'));
    exit;
}

$pageTitle = __('Log in');
$pageDesc = __('Login Desc');
$hideNavLinks = true; // 登入頁面隱藏導航欄連結保持純淨
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
                <input type="text" id="username" name="username" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner text-white">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400"><?= __('Password') ?></label>
                <input type="password" id="password" name="password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner text-white">
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
            
            <div class="mt-6 pt-6 border-t border-white/10 relative">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-zinc-950 text-zinc-500 text-[10px] px-2 font-bold tracking-widest">WEB3</div>
                
                <button type="button" onclick="handleNearLogin()" id="near-login-btn" class="w-full py-4 bg-gradient-to-r from-emerald-400 to-teal-500 text-zinc-950 font-black text-base rounded-2xl hover:brightness-110 transition flex items-center justify-center gap-3 shadow-[0_0_25px_rgba(52,211,153,0.25)] border-none group transform hover:-translate-y-0.5 duration-200">
                    <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" class="w-5 h-5 opacity-90 group-hover:scale-105 transition shrink-0" alt="NEAR"> 
                    <span id="near-btn-text"><?= __('Connect NEAR Wallet') ?></span>
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
    // 1. 點擊觸發原生 MyNearWallet 授權跳轉
    async function handleNearLogin() {
        const btnText = document.getElementById('near-btn-text');
        const originalText = btnText.innerText;
        btnText.innerText = '<?= addslashes(__('Connecting...')) ?>';

        try {
            const wallet = await initNearWallet();
            if (!wallet.isSignedIn()) {
                // 原生純網頁跳轉模式，100% 避開任何彈窗排版問題
                wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
                setTimeout(() => { btnText.innerText = originalText; }, 1000);
            } else {
                await verifyNearWallet(wallet.getAccountId());
            }
        } catch(e) {
            btnText.innerText = originalText;
        }
    }

    // 2. 監聽從 MyNearWallet 授權成功跳轉回來後的 URL 參數
    window.addEventListener('DOMContentLoaded', async () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('account_id') || urlParams.has('all_keys')) {
            const wallet = await initNearWallet();
            setTimeout(async () => {
                if (wallet.isSignedIn()) {
                    await verifyNearWallet(wallet.getAccountId());
                }
            }, 500);
        }
    });

    // 3. 呼叫後端 API 驗證錢包地址，完成 Web2.5 Session 綁定
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
                window.location.href = '<?= url("/my-souls") ?>';
            } else {
                errorMsg.innerText = data.error || '<?= addslashes(__('Wallet not bound')) ?>';
                errorBox.classList.remove('hidden');
                btnText.innerText = '<?= addslashes(__('Connect NEAR Wallet')) ?>';
                
                // 驗證失敗則強制清空錢包狀態
                const wallet = await initNearWallet();
                wallet.signOut();
                
                // 清理網址列殘留的 account_id 參數，防止循環刷頁
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        } catch (e) {
            errorMsg.innerText = '<?= addslashes(__('Network Error.')) ?>';
            errorBox.classList.remove('hidden');
            btnText.innerText = '<?= addslashes(__('Connect NEAR Wallet')) ?>';
        }
    }

    // 4. 傳統 Web2 密碼登入邏輯
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