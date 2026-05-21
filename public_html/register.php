<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

setSEO('Sign up', 'Create your SoulMD Hub account and start sharing AI souls.');

session_start();

// 如果已經登入，直接跳轉到管理後台
if (isset($_SESSION['user_id'])) {
    header('Location: my-souls');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    if (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            header('Location: my-souls');
            exit;
        } catch (Exception $e) {
            $error = 'Username already taken';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 漂亮的自訂滾動條供 Modal 使用 */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.05); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2); border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.3); }
    </style>
</head>
<body class="bg-zinc-950 text-white min-h-screen flex flex-col">
    <nav class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 flex justify-between items-center absolute top-0 left-0 right-0">
        <a href="/" class="flex items-center gap-2 text-2xl font-bold tracking-tighter hover:text-emerald-400 transition w-fit">
            SoulMD <span class="text-emerald-400 text-[10px] px-2 py-1 bg-emerald-900/30 rounded-full">HUB</span>
        </a>
    </nav>

    <div class="flex-grow flex items-center justify-center p-4 mt-16">
        <div class="w-full max-w-md">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-semibold mb-2">Create your account</h1>
                <p class="text-zinc-400">Start sharing AI souls today</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-900/50 border border-red-500 p-4 rounded-3xl mb-8 text-sm text-center">
                    <?= $error ?>
                </div>
            <?php endif; ?>

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
                Already have an account? <a href="login" class="text-emerald-400 hover:text-emerald-300 hover:underline font-medium transition">Log in</a>
            </div>
        </div>
    </div>

    <div id="terms-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" id="terms-content">
            <div class="p-6 border-b border-white/10 flex justify-between items-center bg-zinc-950/30">
                <h3 class="text-2xl font-bold tracking-tight text-emerald-400"><i class="fas fa-file-contract mr-2"></i>Terms of Service</h3>
                <button onclick="closeModal('terms-modal')" class="text-zinc-400 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-6 overflow-y-auto space-y-5 text-sm text-zinc-300 leading-relaxed custom-scrollbar flex-grow">
                <p>Welcome to <strong>SoulMD Hub</strong>. By creating an account, you agree to these terms.</p>
                <h4 class="text-white font-semibold text-base">1. Acceptable Use</h4>
                <p>You agree not to use SoulMD Hub to upload, share, or generate any content that is illegal, harmful, highly offensive, or violates the rights of others. We reserve the right to remove any public souls that violate these principles without prior notice.</p>
                <h4 class="text-white font-semibold text-base">2. Content Ownership & License</h4>
                <p>You retain full ownership of the AI souls (prompts, markdown files) you upload. However, by setting a soul to "Public", you grant SoulMD Hub and its users a worldwide, royalty-free license to view, use, fork, and modify your public content within the platform.</p>
                <h4 class="text-white font-semibold text-base">3. AI-Generated Content</h4>
                <p>Our platform interacts with AI templates and provides prompts. SoulMD Hub does not guarantee the accuracy, reliability, or safety of the AI outputs generated using the souls found on this platform. Use them at your own discretion.</p>
                <h4 class="text-white font-semibold text-base">4. Account Termination</h4>
                <p>We reserve the right to suspend or terminate your account at our sole discretion if we suspect a violation of these Terms of Service or any malicious behavior (e.g., API abuse).</p>
            </div>
            <div class="p-5 border-t border-white/10 bg-zinc-950/50 text-right">
                <button type="button" onclick="closeModal('terms-modal')" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold hover:bg-emerald-400 transition shadow-lg">I Understand</button>
            </div>
        </div>
    </div>

    <div id="privacy-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div class="bg-zinc-900 border border-white/10 rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300" id="privacy-content">
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
                    <li><strong>Usage Data:</strong> Like counts, fork counts, and ratings you provide to other souls.</li>
                </ul>
                <h4 class="text-white font-semibold text-base">2. How We Use Your Data</h4>
                <p>We use your data solely to provide and improve the SoulMD Hub service. Your username will be publicly visible attached to any souls you choose to make "Public". Your email (if provided) is only used for account recovery and essential notifications.</p>
                <h4 class="text-white font-semibold text-base">3. API Keys & Security</h4>
                <p>Your API key is securely stored. Do not share it publicly. We use industry-standard security measures (such as PDO prepared statements and password hashing) to protect your account from unauthorized access.</p>
                <h4 class="text-white font-semibold text-base">4. Cookies & Sessions</h4>
                <p>SoulMD Hub uses essential session cookies to keep you logged in. We do not use third-party tracking cookies for targeted advertising.</p>
            </div>
            <div class="p-5 border-t border-white/10 bg-zinc-950/50 text-right">
                <button type="button" onclick="closeModal('privacy-modal')" class="px-6 py-2.5 bg-emerald-500 text-zinc-950 rounded-xl font-bold hover:bg-emerald-400 transition shadow-lg">I Understand</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = modal.querySelector('div');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            const content = modal.querySelector('div');
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        document.getElementById('terms-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('terms-modal');
        });
        document.getElementById('privacy-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal('privacy-modal');
        });

        const form = document.getElementById('register-form');
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

                if (html.includes('Location: my-souls')) {
                    window.location.href = 'my-souls';
                } else {
                    document.body.innerHTML = html;
                }
            } catch(e) {
                alert('Network Error. Please try again.');
                text.classList.remove('hidden');
                loading.classList.add('hidden');
                btn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        });
    </script>
</body>
</html>