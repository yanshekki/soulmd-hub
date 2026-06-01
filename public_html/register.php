<?php
/**
 * SoulMD Hub - Registration Gateway
 * (Dynamic i18n Multi-Language, Secure Nonce Modals & Perfect Mobile Modal Edition)
 * 🚀 Patched: Added Strict Button Loading UI & Disable Lock to prevent double submissions
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('/my-souls'));
    exit;
}

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('register');

// 🌍 SEO Meta 多語言化
$pageTitle = __('Sign up');
$pageDesc = __('Register Desc');
$hideNavLinks = true;
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="flex-grow flex items-center justify-center p-4 animate-fade-in">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-semibold mb-2"><?= __('Create your account') ?></h1>
            <p class="text-zinc-400"><?= __('Start sharing AI souls today') ?></p>
        </div>

        <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-center text-red-200 shadow-lg transition-all">
            <i class="fas fa-exclamation-circle mr-1"></i> <span id="error-msg"></span>
        </div>

        <form id="register-form" class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 space-y-6 backdrop-blur-sm shadow-2xl">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400"><?= __('Username') ?></label>
                <input type="text" id="username" name="username" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400"><?= __('Email') ?> <span class="text-zinc-500 font-normal">(<?= __('optional') ?>)</span></label>
                <input type="email" id="email" name="email" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400"><?= __('Password') ?></label>
                <input type="password" id="password" name="password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition shadow-inner">
            </div>

            <div class="flex items-center text-xs text-zinc-400 select-none">
                <input type="checkbox" id="terms" required class="accent-emerald-400 mr-2 w-4 h-4 rounded bg-zinc-900 border-white/20 cursor-pointer">
                <label for="terms" class="cursor-pointer"><?= __('I agree to the') ?> 
                    <button type="button" onclick="openModal('terms-modal')" class="text-emerald-400 hover:text-emerald-300 hover:underline focus:outline-none transition font-medium"><?= __('Terms') ?></button> <?= __('and') ?> 
                    <button type="button" onclick="openModal('privacy-modal')" class="text-emerald-400 hover:text-emerald-300 hover:underline focus:outline-none transition font-medium"><?= __('Privacy Policy') ?></button>
                </label>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-4 bg-emerald-500 text-zinc-950 font-bold text-lg rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg transform hover:-translate-y-0.5 duration-200">
                <span id="submit-text"><?= __('Create account') ?></span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
            </button>
        </form>

        <div class="text-center mt-8 text-sm text-zinc-400">
            <?= __('Already have an account?') ?> <a href="<?= url('/login') ?>" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition"><?= __('Log in') ?></a>
        </div>
    </div>
</div>

<div id="terms-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[500] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-2xl w-full max-h-[calc(100dvh-2rem)] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/30 shrink-0">
            <h3 class="text-2xl font-bold tracking-tight text-emerald-400"><i class="fas fa-file-contract mr-2"></i><?= __('Terms of Service') ?></h3>
            <button onclick="closeModal('terms-modal')" class="text-zinc-400 hover:text-white transition focus:outline-none"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto space-y-5 text-sm text-zinc-300 leading-relaxed custom-scrollbar flex-grow">
            <p><?= __('Terms Intro') ?></p>
            <h4 class="text-white font-semibold text-base"><?= __('Terms H1') ?></h4>
            <p><?= __('Terms P1') ?></p>
            <h4 class="text-white font-semibold text-base"><?= __('Terms H2') ?></h4>
            <p><?= __('Terms P2') ?></p>
            <h4 class="text-white font-semibold text-base"><?= __('Terms H3') ?></h4>
            <p><?= __('Terms P3') ?></p>
        </div>
        <div class="p-5 border-t border-white/10 bg-zinc-950/50 text-right shrink-0">
            <button type="button" onclick="closeModal('terms-modal')" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold hover:bg-emerald-400 transition shadow-lg"><?= __('I Understand') ?></button>
        </div>
    </div>
</div>

<div id="privacy-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-[500] p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-2xl w-full max-h-[calc(100dvh-2rem)] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/30 shrink-0">
            <h3 class="text-2xl font-bold tracking-tight text-emerald-400"><i class="fas fa-shield-alt mr-2"></i><?= __('Privacy Policy') ?></h3>
            <button onclick="closeModal('privacy-modal')" class="text-zinc-400 hover:text-white transition focus:outline-none"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto space-y-5 text-sm text-zinc-300 leading-relaxed custom-scrollbar flex-grow">
            <p><?= __('Register Desc') ?></p>
            <h4 class="text-white font-semibold text-base"><?= __('Privacy H1') ?></h4>
            <ul class="list-disc pl-5 space-y-2">
                <li><?= __('Privacy L1') ?></li>
                <li><?= __('Privacy L2') ?></li>
            </ul>
            <h4 class="text-white font-semibold text-base"><?= __('Privacy H2') ?></h4>
            <p><?= __('Privacy P2') ?></p>
            <h4 class="text-white font-semibold text-base"><?= __('Privacy H3') ?></h4>
            <p><?= __('Privacy P3') ?></p>
        </div>
        <div class="p-5 border-t border-white/10 bg-zinc-950/50 text-right shrink-0">
            <button type="button" onclick="closeModal('privacy-modal')" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold hover:bg-emerald-400 transition shadow-lg"><?= __('I Understand') ?></button>
        </div>
    </div>
</div>

<script>
    // 🚨 Modal 鎖定背景滾動修復
    function openModal(modalId) {
        document.body.style.overflow = 'hidden'; 
        const modal = document.getElementById(modalId);
        const content = modal.querySelector('div');
        modal.classList.remove('hidden');
        setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); content.classList.add('scale-100'); }, 10);
    }
    
    function closeModal(modalId) {
        document.body.style.overflow = ''; 
        const modal = document.getElementById(modalId);
        const content = modal.querySelector('div');
        modal.classList.add('opacity-0'); content.classList.remove('scale-100'); content.classList.add('scale-95');
        setTimeout(() => { modal.classList.add('hidden'); }, 300);
    }

    const form = document.getElementById('register-form');
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
        
        // 🚨 鎖定註冊按鈕
        btn.disabled = true;
        btn.classList.add('opacity-80', 'cursor-not-allowed');

        const payload = {
            username: document.getElementById('username').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value
        };

        try {
            const res = await fetch('/api/register', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                window.location.href = '<?= url("/profile") ?>/' + encodeURIComponent(payload.username);
            } else {
                errorMsg.innerText = data.error || '<?= addslashes(__('Registration failed.')) ?>';
                errorBox.classList.remove('hidden');
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                // 🚨 錯誤時解除鎖定
                btn.disabled = false;
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        } catch(e) {
            errorMsg.innerText = '<?= addslashes(__('Network Error.')) ?>';
            errorBox.classList.remove('hidden');
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            // 🚨 錯誤時解除鎖定
            btn.disabled = false;
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>