<?php
/**
 * SoulMD Hub - Change Password Security Panel
 * (Dynamic i18n Internationalization & Robust Security Crypt Routing Edition)
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

// 🌍 載入此頁面的專屬獨立多語言詞典
loadTranslations('change-password');

// 🌍 SEO Meta 多語言化
$pageTitle = __('Change Password');
$pageDesc = __('Security Desc');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-xl w-full mx-auto px-4 sm:px-6 py-12 flex-grow flex flex-col justify-center animate-fade-in">
    <div class="mb-8 flex justify-between items-end border-b border-white/10 pb-5">
        <div>
            <h1 class="text-3xl font-bold tracking-tighter text-white"><?= __('Account Security') ?></h1>
            <p class="text-xs text-zinc-400 mt-1.5"><?= __('Update Subtitle') ?></p>
        </div>
        <a href="<?= url('/my-souls') ?>" class="text-xs text-zinc-400 hover:text-white flex items-center gap-1.5 border border-white/10 bg-zinc-900/50 px-3.5 py-1.5 rounded-full transition shadow-sm shrink-0">
            <i class="fas fa-arrow-left text-[10px]"></i> <?= __('Back to Dashboard') ?>
        </a>
    </div>

    <div id="status-box" class="hidden border p-4 rounded-2xl mb-6 text-sm text-center shadow-lg transition-all"></div>

    <form id="password-form" class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 space-y-5 backdrop-blur-sm shadow-2xl">
        <div>
            <label class="block text-xs font-medium mb-2 text-zinc-400 uppercase tracking-wider"><?= __('Current Password') ?></label>
            <input type="password" id="current-password" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm font-mono tracking-widest text-white shadow-inner">
        </div>

        <div class="border-t border-white/5 pt-4">
            <label class="block text-xs font-medium mb-2 text-zinc-400 uppercase tracking-wider"><?= __('New Password') ?></label>
            <input type="password" id="new-password" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm font-mono tracking-widest text-white shadow-inner">
        </div>

        <div>
            <label class="block text-xs font-medium mb-2 text-zinc-400 uppercase tracking-wider"><?= __('Confirm New Password') ?></label>
            <input type="password" id="confirm-password" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-400 text-sm font-mono tracking-widest text-white shadow-inner">
        </div>

        <button type="submit" id="submit-btn" class="w-full py-3.5 bg-emerald-500 text-zinc-950 font-bold text-sm rounded-xl hover:bg-emerald-400 transition flex items-center justify-center gap-2 shadow-lg transform hover:-translate-y-0.5 duration-200 mt-2">
            <span id="submit-text"><i class="fas fa-shield-keyhole mr-1"></i> <?= __('Save New Credentials') ?></span>
            <span id="submit-loading" class="hidden animate-spin h-4 w-4 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
        </button>
    </form>
</div>

<script>
    const passForm = document.getElementById('password-form');
    passForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('submit-btn');
        const text = document.getElementById('submit-text');
        const loading = document.getElementById('submit-loading');
        const statusBox = document.getElementById('status-box');

        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        // Reset Box UI States
        statusBox.className = "hidden border p-4 rounded-2xl mb-6 text-sm text-center shadow-lg transition-all";
        statusBox.innerHTML = '';

        // 1. 前端嚴格密碼一致性校驗
        if (newPassword !== confirmPassword) {
            statusBox.classList.add('bg-red-900/50', 'text-red-200', 'border-red-500', 'block');
            statusBox.innerText = "<?= addslashes(__('Passwords do not match!')) ?>";
            return;
        }

        // Trigger Loading Spinner
        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-80', 'cursor-not-allowed');

        const payload = {
            current_password: currentPassword,
            new_password: newPassword
        };

        try {
            // Hit the security API backend (API ignores lang routing via regex)
            const res = await fetch('/api/change-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                statusBox.classList.add('bg-emerald-900/50', 'text-emerald-400', 'border-emerald-500', 'block');
                statusBox.innerHTML = `<i class="fas fa-check-circle mr-1"></i> <?= addslashes(__('Password updated successfully!')) ?>`;
                passForm.reset();
                
                // 🚨 變更成功後，自動導向回多語言首頁前綴的工作區大廳
                setTimeout(() => { window.location.href = '<?= url("/my-souls") ?>'; }, 2000);
            } else {
                statusBox.classList.add('bg-red-900/50', 'text-red-200', 'border-red-500', 'block');
                statusBox.innerText = data.error || "<?= addslashes(__('Failed to update.')) ?>";
                
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        } catch (err) {
            statusBox.classList.add('bg-red-900/50', 'text-red-200', 'border-red-500', 'block');
            statusBox.innerText = "<?= addslashes(__('Network Error.')) ?>";
            
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>