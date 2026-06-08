<?php
/**
 * SoulMD Hub - Login Page
 * (Dynamic i18n Internationalization & Pure Native MyNearWallet Redirect Edition)
 * 🚀 V5 SEO Optimized: Semantic <main> tag, a11y Form Labels, and ARIA Live Regions
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

loadTranslations('login');

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('/my-souls'));
    exit;
}

// 🚀 SEO Enhancement: Use dedicated SEO keys
$pageTitle = __('SEO Title');
$pageDesc = __('SEO Desc');
$hideNavLinks = true;

require_once __DIR__ . '/../private/includes/header.php';
?>

<main class="flex-grow flex items-center justify-center p-4 mt-10 sm:mt-16 animate-fade-in" aria-labelledby="login-heading">
    <div class="w-full max-w-md">
        <header class="text-center mb-10">
            <h1 id="login-heading" class="text-3xl font-semibold mb-2 text-white"><?= __('Welcome back') ?></h1>
            <p class="text-zinc-400"><?= __('Sign in to manage') ?></p>
        </header>

        <div id="error-box" role="alert" aria-live="assertive" class="hidden bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-center text-red-200 shadow-lg transition-all">
            <i class="fas fa-exclamation-circle mr-1" aria-hidden="true"></i> <span id="error-msg"></span>
        </div>

        <section aria-label="Login Form">
            <form id="login-form" class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 space-y-6 backdrop-blur-sm shadow-2xl">
                <div>
                    <label for="username" class="block text-sm font-medium mb-2 text-zinc-400"><?= __('Username') ?></label>
                    <input type="text" id="username" name="username" required autocomplete="username" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner text-white">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-2 text-zinc-400"><?= __('Password') ?></label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner text-white">
                </div>

                <div class="flex items-center text-xs text-zinc-400 select-none">
                    <label for="remember" class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" id="remember" name="remember" class="accent-emerald-400 w-4 h-4 rounded bg-zinc-900 border-white/20"> <?= __('Remember me') ?>
                    </label>
                </div>

                <button type="submit" id="submit-btn" aria-label="<?= __('Log in') ?>" class="w-full py-4 bg-emerald-500 text-zinc-950 font-bold text-lg rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg transform hover:-translate-y-0.5 duration-200">
                    <span id="submit-text"><?= __('Log in') ?></span>
                    <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full" aria-hidden="true"></span>
                </button>
                
                <div class="mt-6 pt-6 border-t border-white/10 relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-zinc-950 text-zinc-500 text-[10px] px-2 font-bold tracking-widest" aria-hidden="true">WEB3</div>
                    
                    <button type="button" onclick="handleNearLogin()" id="near-login-btn" aria-label="<?= __('Connect NEAR Wallet') ?>" class="w-full py-4 bg-gradient-to-r from-emerald-400 to-teal-500 text-zinc-950 font-black text-base rounded-2xl hover:brightness-110 transition flex items-center justify-center gap-3 shadow-[0_0_25px_rgba(52,211,153,0.25)] border-none group transform hover:-translate-y-0.5 duration-200">
                        <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" id="near-btn-icon" class="w-5 h-5 opacity-90 group-hover:scale-105 transition shrink-0" alt="NEAR Protocol"> 
                        <span id="near-btn-text"><?= __('Connect NEAR Wallet') ?></span>
                    </button>
                </div>
            </form>
        </section>

        <footer class="text-center mt-8 text-sm text-zinc-400">
            <?= __('No account?') ?> <a href="<?= url('/register') ?>" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition"><?= __('Sign up') ?></a>
        </footer>
    </div>
</main>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<script>
    async function handleNearLogin() {
        const btn = document.getElementById('near-login-btn');
        const btnText = document.getElementById('near-btn-text');
        const btnIcon = document.getElementById('near-btn-icon');
        const originalText = btnText.innerHTML;

        btnText.innerHTML = '<i class="fas fa-spinner animate-spin mr-1" aria-hidden="true"></i> <?= addslashes(__('Connecting to RPC...')) ?>';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        if(btnIcon) btnIcon.classList.add('hidden');

        try {
            const wallet = await initNearWallet();
            if (!wallet.isSignedIn()) {
                wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
            } else {
                await verifyNearWallet(wallet.getAccountId());
            }
        } catch(e) {
            btnText.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            if(btnIcon) btnIcon.classList.remove('hidden');
        }
    }

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

    async function verifyNearWallet(accountId) {
        const errorBox = document.getElementById('error-box');
        const errorMsg = document.getElementById('error-msg');
        const btn = document.getElementById('near-login-btn');
        const btnText = document.getElementById('near-btn-text');
        const btnIcon = document.getElementById('near-btn-icon');
        
        if(btn) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            if(btnIcon) btnIcon.classList.add('hidden');
        }
        if(btnText) btnText.innerHTML = '<i class="fas fa-spinner animate-spin mr-1" aria-hidden="true"></i> <?= addslashes(__('Verifying Session...')) ?>';

        try {
            // 🚀 核心密碼學驗證
            const authPayload = await window.generateNearAuthPayload(accountId);

            const res = await fetch('/api/wallet-login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(authPayload)
            });
            const data = await res.json();
            if (data.success) {
                window.location.href = '<?= url("/my-souls") ?>';
            } else {
                errorMsg.innerText = data.error || '<?= addslashes(__('Wallet not bound')) ?>';
                errorBox.classList.remove('hidden');
                if(btnText) btnText.innerText = '<?= addslashes(__('Connect NEAR Wallet')) ?>';
                if(btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    if(btnIcon) btnIcon.classList.remove('hidden');
                }
                
                const wallet = await initNearWallet();
                wallet.signOut();
                
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
            }
        } catch (e) {
            errorMsg.innerText = '<?= addslashes(__('Network Error.')) ?>';
            errorBox.classList.remove('hidden');
            if(btnText) btnText.innerText = '<?= addslashes(__('Connect NEAR Wallet')) ?>';
            if(btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                if(btnIcon) btnIcon.classList.remove('hidden');
            }
        }
    }

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
        
        btn.disabled = true;
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
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        } catch (e) {
            errorMsg.innerText = '<?= addslashes(__('Network Error.')) ?>';
            errorBox.classList.remove('hidden');
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>