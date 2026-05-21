<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /my-souls');
    exit;
}

$pageTitle = 'Sign up';
$pageDesc = 'Create your SoulMD Hub account and start sharing AI souls.';
$hideNavLinks = true;
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="flex-grow flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-semibold mb-2">Create your account</h1>
            <p class="text-zinc-400">Start sharing AI souls today</p>
        </div>

        <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-center text-red-200 shadow-lg transition-all">
            <i class="fas fa-exclamation-circle mr-1"></i> <span id="error-msg"></span>
        </div>

        <form id="register-form" class="bg-zinc-900/60 border border-white/10 rounded-3xl p-8 space-y-6 backdrop-blur-sm shadow-2xl">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Username</label>
                <input type="text" id="username" name="username" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Email <span class="text-zinc-500 font-normal">(optional)</span></label>
                <input type="email" id="email" name="email" class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-400">Password</label>
                <input type="password" id="password" name="password" required class="w-full bg-zinc-950 border border-white/10 rounded-2xl px-5 py-3 focus:outline-none focus:border-emerald-400 transition">
            </div>

            <div class="flex items-center text-xs text-zinc-400">
                <input type="checkbox" id="terms" required class="accent-emerald-400 mr-2 w-4 h-4 rounded bg-zinc-900 border-white/20">
                <label for="terms" class="select-none">I agree to the 
                    <button type="button" onclick="openModal('terms-modal')" class="text-emerald-400 hover:text-emerald-300 hover:underline focus:outline-none transition font-medium">Terms</button> and 
                    <button type="button" onclick="openModal('privacy-modal')" class="text-emerald-400 hover:text-emerald-300 hover:underline focus:outline-none transition font-medium">Privacy Policy</button>
                </label>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-4 bg-emerald-500 text-zinc-950 font-bold text-lg rounded-2xl hover:bg-emerald-400 transition flex items-center justify-center gap-3 shadow-lg">
                <span id="submit-text">Create account</span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
            </button>
        </form>

        <div class="text-center mt-8 text-sm text-zinc-400">
            Already have an account? <a href="/login" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition">Log in</a>
        </div>
    </div>
</div>

<div id="terms-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/30">
            <h3 class="text-2xl font-bold tracking-tight text-emerald-400"><i class="fas fa-file-contract mr-2"></i>Terms of Service</h3>
            <button onclick="closeModal('terms-modal')" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto space-y-5 text-sm text-zinc-300 leading-relaxed custom-scrollbar flex-grow">
            <p>Welcome to <strong>SoulMD Hub</strong>. By creating an account, you agree to these terms.</p>
            <h4 class="text-white font-semibold text-base">1. Acceptable Use</h4>
            <p>You agree not to use SoulMD Hub to upload, share, or generate any content that is illegal, harmful, highly offensive, or violates the rights of others.</p>
            <h4 class="text-white font-semibold text-base">2. Content Ownership & License</h4>
            <p>You retain full ownership of the AI souls you upload. However, by setting a soul to "Public", you grant SoulMD Hub and its users a worldwide, royalty-free license to view, use, fork, and modify your public content within the platform.</p>
            <h4 class="text-white font-semibold text-base">3. AI-Generated Content</h4>
            <p>Our platform interacts with AI templates. SoulMD Hub does not guarantee the accuracy, reliability, or safety of the AI outputs generated using the souls found on this platform.</p>
        </div>
        <div class="p-5 border-t border-white/10 bg-zinc-950/50 text-right">
            <button type="button" onclick="closeModal('terms-modal')" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold hover:bg-emerald-400 transition shadow-lg">I Understand</button>
        </div>
    </div>
</div>

<div id="privacy-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
        <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/30">
            <h3 class="text-2xl font-bold tracking-tight text-emerald-400"><i class="fas fa-shield-alt mr-2"></i>Privacy Policy</h3>
            <button onclick="closeModal('privacy-modal')" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto space-y-5 text-sm text-zinc-300 leading-relaxed custom-scrollbar flex-grow">
            <p>Your privacy is important to us. This policy outlines how <strong>SoulMD Hub</strong> collects and uses your data.</p>
            <h4 class="text-white font-semibold text-base">1. Data We Collect</h4>
            <ul class="list-disc pl-5 space-y-1">
                <li><strong>Account Info:</strong> Your chosen username, optional email address, and an encrypted password hash.</li>
                <li><strong>Content Data:</strong> The SOUL.md files, descriptions, tags, and AI personalities you upload.</li>
            </ul>
            <h4 class="text-white font-semibold text-base">2. How We Use Your Data</h4>
            <p>We use your data solely to provide and improve the SoulMD Hub service. Your username will be publicly visible attached to any souls you choose to make "Public".</p>
            <h4 class="text-white font-semibold text-base">3. API Keys & Security</h4>
            <p>Your API key is securely stored. Do not share it publicly. We use industry-standard security measures to protect your account from unauthorized access.</p>
        </div>
        <div class="p-5 border-t border-white/10 bg-zinc-950/50 text-right">
            <button type="button" onclick="closeModal('privacy-modal')" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold hover:bg-emerald-400 transition shadow-lg">I Understand</button>
        </div>
    </div>
</div>

<script>
    // Modal 控制
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        const content = modal.querySelector('div');
        modal.classList.remove('hidden');
        setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); content.classList.add('scale-100'); }, 10);
    }
    function closeModal(modalId) {
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
                // 🚨 完美跳轉優化：註冊成功後使用 encodeURIComponent 進行網址安全編碼，直達佢嘅 Profile 頁面！
                window.location.href = '/profile/' + encodeURIComponent(payload.username);
            } else {
                errorMsg.innerText = data.error || 'Registration failed.';
                errorBox.classList.remove('hidden');
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        } catch(e) {
            errorMsg.innerText = 'Network Error. Please try again.';
            errorBox.classList.remove('hidden');
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-80', 'cursor-not-allowed');
        }
    });
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>