<?php
/**
 * SoulMD Hub - Grand Unified Settings Hub
 * (Account, Web3, Platform API, Encrypted BYOK Engine - 100% i18n Edition)
 * 🚀 Patched: 100% Structural integrity retained + i18n + Cryptographic Signature Payload
 */
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ' . url('/login')); exit; }

if (empty($_SESSION['chat_csrf_token'])) {
    $_SESSION['chat_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['chat_csrf_token'];

// 🌍 載入專屬語言包
loadTranslations('my-setting');

$userId = $_SESSION['user_id'];
$db = Database::getInstance();
$pdo = $db->getConnection();

$stmt = $pdo->prepare("SELECT username, email, api_key, near_wallet_address FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$pageTitle = __('SEO Title');
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex flex-col md:flex-row gap-8 flex-grow">
    
    <div class="w-full md:w-64 shrink-0 flex flex-row md:flex-col gap-2 overflow-x-auto md:overflow-visible pb-4 md:pb-0">
        <button onclick="switchTab('account')" id="btn-account" class="tab-btn active shrink-0 text-left px-5 py-3 rounded-xl font-bold text-sm transition bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"><i class="fas fa-shield-alt w-5"></i> <?= __('Account Security') ?></button>
        <button onclick="switchTab('web3')" id="btn-web3" class="tab-btn shrink-0 text-left px-5 py-3 rounded-xl font-bold text-sm transition text-zinc-400 hover:bg-white/5"><i class="fas fa-wallet w-5"></i> <?= __('Web3 Wallet') ?></button>
        <button onclick="switchTab('api')" id="btn-api" class="tab-btn shrink-0 text-left px-5 py-3 rounded-xl font-bold text-sm transition text-zinc-400 hover:bg-white/5"><i class="fas fa-key w-5"></i> <?= __('Developer API') ?></button>
        <button onclick="switchTab('byok')" id="btn-byok" class="tab-btn shrink-0 text-left px-5 py-3 rounded-xl font-bold text-sm transition text-zinc-400 hover:bg-white/5"><i class="fas fa-brain w-5"></i> <?= __('Custom AI Engine (BYOK)') ?></button>
    </div>

    <div class="flex-1 bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 backdrop-blur-sm shadow-2xl relative overflow-hidden min-h-[600px]">
        
        <div id="tab-account" class="tab-content block animate-fade-in">
            <h2 class="text-2xl font-bold mb-6 text-white border-b border-white/10 pb-4"><?= __('Account Security') ?></h2>
            <div class="mb-6">
                <label class="block text-xs text-zinc-500 uppercase tracking-widest font-bold mb-2"><?= __('Username') ?></label>
                <div class="bg-zinc-950 px-4 py-3 rounded-xl border border-white/5 text-zinc-300 font-mono"><?= htmlspecialchars($user['username']) ?></div>
            </div>
            <form id="pwd-form" class="space-y-4">
                <div>
                    <label class="block text-xs text-zinc-500 uppercase tracking-widest font-bold mb-2"><?= __('Current Password') ?></label>
                    <input type="password" id="old-pwd" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-400 focus:outline-none text-white shadow-inner">
                </div>
                <div>
                    <label class="block text-xs text-zinc-500 uppercase tracking-widest font-bold mb-2"><?= __('New Password') ?></label>
                    <input type="password" id="new-pwd" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-400 focus:outline-none text-white shadow-inner">
                </div>
                <div>
                    <label class="block text-xs text-zinc-500 uppercase tracking-widest font-bold mb-2"><?= __('Confirm New Password') ?></label>
                    <input type="password" id="confirm-pwd" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-400 focus:outline-none text-white shadow-inner">
                </div>
                <button type="submit" id="pwd-submit-btn" class="mt-4 px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-xl transition shadow flex items-center justify-center gap-2 min-w-[150px]">
                    <span id="pwd-submit-text"><?= __('Update Password') ?></span>
                    <span id="pwd-submit-loading" class="hidden animate-spin h-4 w-4 border-2 border-zinc-950 border-t-transparent rounded-full"></span>
                </button>
            </form>
        </div>

        <div id="tab-web3" class="tab-content hidden animate-fade-in">
            <h2 class="text-2xl font-bold mb-6 text-white border-b border-white/10 pb-4 flex items-center gap-3"><i class="fas fa-wallet text-blue-400"></i> <?= __('Web3 Wallet Binding') ?></h2>
            <p class="text-sm text-zinc-400 mb-6"><?= __('Wallet Binding Desc') ?></p>
            <?php if ($user['near_wallet_address']): ?>
                <div class="bg-zinc-950 border border-emerald-500/30 p-5 rounded-2xl flex items-center gap-4 shadow-inner">
                    <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" class="w-8 h-8 opacity-90">
                    <div>
                        <div class="text-xs text-emerald-500 font-bold uppercase tracking-widest mb-1"><?= __('Bound Permanently') ?></div>
                        <code class="text-lg text-white font-mono"><?= htmlspecialchars($user['near_wallet_address']) ?></code>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-blue-900/10 border border-blue-500/30 p-4 rounded-2xl mb-4 text-[11px] sm:text-xs text-blue-300 leading-relaxed flex items-start gap-2 shadow-inner">
                    <i class="fas fa-exclamation-triangle text-blue-400 mt-0.5 shrink-0"></i>
                    <div>
                        <strong class="text-blue-400 font-bold uppercase tracking-wide block mb-1"><?= __('Important Warning:') ?></strong> <?= __('Wallet one-time warning') ?>
                    </div>
                </div>
                
                <button type="button" onclick="bindNearWallet()" id="bind-wallet-btn" class="w-full md:w-auto px-8 py-4 bg-gradient-to-r from-emerald-400 to-teal-500 text-zinc-950 font-black text-base rounded-2xl hover:brightness-110 transition flex items-center justify-center gap-3 shadow-[0_0_25px_rgba(52,211,153,0.25)] border-none group transform hover:-translate-y-0.5 duration-200 relative overflow-hidden">
                    <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" id="bind-wallet-icon" class="w-5 h-5 opacity-90 group-hover:scale-105 transition shrink-0" alt="NEAR"> 
                    <span id="bind-wallet-text"><?= __('Connect & Bind Wallet') ?></span>
                </button>
            <?php endif; ?>
        </div>

        <div id="tab-api" class="tab-content hidden animate-fade-in">
            <h2 class="text-2xl font-bold mb-6 text-white border-b border-white/10 pb-4 flex items-center gap-3"><i class="fas fa-key text-amber-400"></i> <?= __('Platform API Key') ?></h2>
            <p class="text-sm text-zinc-400 mb-6"><?= __('API Key Desc') ?></p>
            <div class="bg-zinc-950 border border-white/10 p-4 rounded-xl flex items-center justify-between gap-3 mb-6 shadow-inner">
                <code id="key-display" class="text-base text-amber-400 font-mono truncate select-all"><?= htmlspecialchars($user['api_key']) ?></code>
            </div>
            <button type="button" id="roll-btn" onclick="rollApiKey()" class="px-6 py-3 bg-zinc-800 hover:bg-amber-500/20 text-white font-bold rounded-xl transition border border-white/5 hover:border-red-500/30 flex items-center justify-center gap-2 min-w-[170px]">
                <span id="roll-text"><i class="fas fa-redo mr-2"></i> <?= __('Regenerate Key') ?></span>
                <span id="roll-loading" class="hidden animate-spin h-4 w-4 border-2 border-current border-t-transparent rounded-full"></span>
            </button>
        </div>

        <div id="tab-byok" class="tab-content hidden animate-fade-in relative">
            <div class="absolute top-0 right-0 px-3 py-1 bg-purple-500/20 text-purple-400 text-[10px] font-bold rounded-bl-xl uppercase tracking-widest border-b border-l border-purple-500/30"><?= __('Unlimited Chat Unlock') ?></div>
            
            <div class="flex items-center justify-between border-b border-white/10 pb-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3"><i class="fas fa-brain text-purple-400"></i> <?= __('BYOK Title') ?></h2>
                    <p class="text-sm text-zinc-400 mt-2"><?= __('BYOK Desc') ?></p>
                </div>
                <label class="flex items-center cursor-pointer relative">
                    <input type="checkbox" id="use_byok" class="sr-only">
                    <div class="toggle-bg block w-14 h-8 bg-zinc-700 rounded-full border border-white/10 transition-colors"></div>
                    <div class="toggle-dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform transform"></div>
                </label>
            </div>

            <div id="byok-settings-panel" class="space-y-6 opacity-50 pointer-events-none transition-opacity duration-300">
                <div class="bg-zinc-950/50 border border-white/5 rounded-2xl p-5 shadow-inner">
                    <h3 class="text-emerald-400 font-bold mb-4 uppercase tracking-widest text-xs flex items-center"><i class="fas fa-comment-alt mr-2"></i><?= __('Text LLM') ?></h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold"><?= __('Provider Preset') ?></label>
                            <select id="text_provider" onchange="autoFillProvider('text')" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-purple-400 transition">
                                <option value="openai"><?= __('OpenAI (Recommended)') ?></option>
                                <option value="deepseek">DeepSeek</option>
                                <option value="together"><?= __('Together AI (Open Source)') ?></option>
                                <option value="groq"><?= __('Groq (Ultra Fast)') ?></option>
                                <option value="openrouter"><?= __('OpenRouter (Claude/Gemini)') ?></option>
                                <option value="custom"><?= __('Custom (OpenAI Compatible)') ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold"><?= __('API URL') ?></label>
                            <input type="text" id="text_api_url" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold"><?= __('Model Name') ?></label>
                            <input type="text" id="text_model" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono transition">
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold"><?= __('Your API Key') ?></label>
                            <input type="password" id="text_api_key" placeholder="sk-..." class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono placeholder-zinc-700 transition">
                        </div>
                    </div>
                </div>

                <div class="bg-zinc-950/50 border border-white/5 rounded-2xl p-5 shadow-inner">
                    <h3 class="text-blue-400 font-bold mb-4 uppercase tracking-widest text-xs flex items-center"><i class="fas fa-eye mr-2"></i><?= __('Vision LLM') ?> <span class="text-zinc-500 ml-2 font-normal text-[10px]"><?= __('Optional Fallback') ?></span></h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold"><?= __('Provider Preset') ?></label>
                            <select id="vision_provider" onchange="autoFillProvider('vision')" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-purple-400 transition">
                                <option value="openai"><?= __('OpenAI (Recommended)') ?></option>
                                <option value="together">Together AI (Llama 3.2 Vision)</option>
                                <option value="openrouter">OpenRouter</option>
                                <option value="custom"><?= __('Custom (OpenAI Compatible)') ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold"><?= __('API URL') ?></label>
                            <input type="text" id="vision_api_url" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold"><?= __('Model Name') ?></label>
                            <input type="text" id="vision_model" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono transition">
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold"><?= __('Your API Key') ?></label>
                            <input type="password" id="vision_api_key" placeholder="sk-..." class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono placeholder-zinc-700 transition">
                        </div>
                    </div>
                </div>

                <div class="bg-zinc-950/50 border border-amber-500/20 rounded-2xl p-5 shadow-inner relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-amber-500"></div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-amber-400 font-bold uppercase tracking-widest text-xs flex items-center"><i class="fas fa-compress-arrows-alt mr-2"></i><?= __('Memory Compression') ?></h3>
                        <span class="text-xl font-black text-white bg-zinc-800 px-3 py-1 rounded-lg font-mono border border-white/5 shadow" id="compress_val_display">10</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-zinc-400 mb-5 leading-relaxed"><?= __('Memory Desc') ?></p>
                    <input type="range" id="memory_compress_threshold" min="4" max="50" step="2" class="w-full accent-amber-400 h-2 bg-zinc-800 rounded-lg appearance-none cursor-pointer outline-none" oninput="document.getElementById('compress_val_display').innerText = this.value">
                </div>

                <button onclick="saveLLMSettings()" id="save-llm-btn" class="w-full py-4 bg-purple-600 hover:bg-purple-500 text-white font-bold text-lg rounded-2xl transition shadow-lg shadow-purple-500/20 flex items-center justify-center gap-2 transform hover:-translate-y-0.5 border-none">
                    <i class="fas fa-save"></i> <?= __('Save Custom Engine Settings') ?>
                </button>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<script>
    const serverCsrfToken = "<?= $csrfToken ?>"; 

    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('bg-emerald-500/20', 'text-emerald-400', 'border', 'border-emerald-500/30');
            el.classList.add('text-zinc-400', 'hover:bg-white/5');
        });
        
        document.getElementById('tab-' + tabId).classList.remove('hidden');
        const activeBtn = document.getElementById('btn-' + tabId);
        if (activeBtn) {
            activeBtn.classList.remove('text-zinc-400', 'hover:bg-white/5');
            activeBtn.classList.add('bg-emerald-500/20', 'text-emerald-400', 'border', 'border-emerald-500/30');
        }
    }

    const PRESETS = {
        'openai': { textUrl: 'https://api.openai.com/v1/chat/completions', textModel: 'gpt-4o', visUrl: 'https://api.openai.com/v1/chat/completions', visModel: 'gpt-4o' },
        'deepseek': { textUrl: 'https://api.deepseek.com/chat/completions', textModel: 'deepseek-chat', visUrl: '', visModel: '' },
        'together': { textUrl: 'https://api.together.xyz/v1/chat/completions', textModel: 'meta-llama/Llama-3.3-70B-Instruct-Turbo', visUrl: 'https://api.together.xyz/v1/chat/completions', visModel: 'meta-llama/Llama-3.2-90B-Vision-Instruct-Turbo' },
        'groq': { textUrl: 'https://api.groq.com/openai/v1/chat/completions', textModel: 'llama-3.3-70b-versatile', visUrl: '', visModel: '' },
        'openrouter': { textUrl: 'https://openrouter.ai/api/v1/chat/completions', textModel: 'anthropic/claude-3.5-sonnet', visUrl: 'https://openrouter.ai/api/v1/chat/completions', visModel: 'anthropic/claude-3.5-sonnet' }
    };

    function autoFillProvider(type) {
        const val = document.getElementById(type + '_provider').value;
        if(val === 'custom') return;
        const conf = PRESETS[val];
        if(conf) {
            if(type === 'text') {
                document.getElementById('text_api_url').value = conf.textUrl || '';
                document.getElementById('text_model').value = conf.textModel || '';
            } else {
                document.getElementById('vision_api_url').value = conf.visUrl || '';
                document.getElementById('vision_model').value = conf.visModel || '';
            }
        }
    }

    const useByokToggle = document.getElementById('use_byok');
    const byokPanel = document.getElementById('byok-settings-panel');

    useByokToggle.addEventListener('change', function() {
        const bg = this.nextElementSibling;
        const dot = bg.nextElementSibling;
        if(this.checked) {
            bg.classList.replace('bg-zinc-700', 'bg-purple-500');
            dot.classList.add('translate-x-6');
            byokPanel.classList.remove('opacity-50', 'pointer-events-none');
        } else {
            bg.classList.replace('bg-purple-500', 'bg-zinc-700');
            dot.classList.remove('translate-x-6');
            byokPanel.classList.add('opacity-50', 'pointer-events-none');
        }
    });

    window.addEventListener('DOMContentLoaded', async () => {
        // 🚀 核心升級：喺最頂端優先攔截 URL 參數，完美支援大一統分流
        const urlParams = new URLSearchParams(window.location.search);
        const hasWalletCallback = urlParams.has('account_id') || urlParams.has('all_keys');
        const forcedTab = urlParams.get('tab');

        if (hasWalletCallback || forcedTab === 'web3') {
            switchTab('web3'); 
        }

        try {
            const res = await fetch('/api/settings');
            const resData = await res.json();
            if(resData.success) {
                const d = resData.data;
                document.getElementById('memory_compress_threshold').value = d.memory_compress_threshold;
                document.getElementById('compress_val_display').innerText = d.memory_compress_threshold;
                
                ['text', 'vision'].forEach(type => {
                    document.getElementById(type+'_provider').value = d[type+'_provider'] || 'openai';
                    document.getElementById(type+'_api_url').value = d[type+'_api_url'] || '';
                    document.getElementById(type+'_model').value = d[type+'_model'] || '';
                    document.getElementById(type+'_api_key').value = d[type+'_api_key'] || ''; 
                });

                if(d.use_byok == 1) {
                    useByokToggle.checked = true;
                    useByokToggle.dispatchEvent(new Event('change'));
                }
            }
        } catch(e) {}

        <?php if (!$user['near_wallet_address']): ?>
            if (hasWalletCallback) {
                const wallet = await initNearWallet();
                setTimeout(async () => {
                    if (wallet.isSignedIn()) {
                        await executeWalletBind(wallet.getAccountId());
                    }
                }, 500);
            }
        <?php endif; ?>
        
        // 🚨 修正：自動背景登入驗證時，處理錢包不匹配的語言包
        try {
            const wallet = await initNearWallet();
            const isPhpLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
            const phpWalletAddress = "<?= $nearWallet ?? '' ?>";

            if (wallet.isSignedIn() && isPhpLoggedIn) {
                const currentWeb3Wallet = wallet.getAccountId();
                if (phpWalletAddress && currentWeb3Wallet !== phpWalletAddress) {
                    console.warn("Web2 and Web3 Wallet mismatch. Forcing Web3 sync.");
                    wallet.signOut();
                    alert("<?= addslashes(__('Wallet mismatch alert')) ?>");
                    window.location.reload();
                }
            }
        } catch (e) {
            console.error("Auto-sync engine error:", e);
        }
    });

    async function saveLLMSettings() {
        const btn = document.getElementById('save-llm-btn');
        btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> <?= addslashes(__('Saving...')) ?>';
        btn.disabled = true; // 🚨 鎖定自訂引擎儲存
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        
        const payload = {
            use_byok: document.getElementById('use_byok').checked ? 1 : 0,
            memory_compress_threshold: document.getElementById('memory_compress_threshold').value,
            text_provider: document.getElementById('text_provider').value,
            text_model: document.getElementById('text_model').value,
            text_api_url: document.getElementById('text_api_url').value,
            text_api_key: document.getElementById('text_api_key').value,
            vision_provider: document.getElementById('vision_provider').value,
            vision_model: document.getElementById('vision_model').value,
            vision_api_url: document.getElementById('vision_api_url').value,
            vision_api_key: document.getElementById('vision_api_key').value
        };

        try {
            const res = await fetch('/api/settings', { 
                method: 'POST', 
                headers: { 'Content-Type':'application/json', 'X-CSRF-Token': serverCsrfToken }, 
                body: JSON.stringify(payload) 
            });
            const data = await res.json();
            if(data.success) {
                btn.innerHTML = '<i class="fas fa-check mr-2"></i> <?= addslashes(__('Settings Saved')) ?>';
                btn.classList.replace('bg-purple-600', 'bg-emerald-500');
                setTimeout(() => { 
                    btn.innerHTML = '<i class="fas fa-save mr-2"></i> <?= addslashes(__('Save Custom Engine Settings')) ?>'; 
                    btn.classList.replace('bg-emerald-500', 'bg-purple-600'); 
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }, 2000);
            } else {
                alert('<?= addslashes(__('Save Failed')) ?>' + data.error);
                btn.innerHTML = '<i class="fas fa-save mr-2"></i> <?= addslashes(__('Save Custom Engine Settings')) ?>';
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } catch(e) { 
            alert('<?= addslashes(__('Network Error')) ?>'); 
            btn.innerHTML = '<i class="fas fa-save mr-2"></i> <?= addslashes(__('Save Custom Engine Settings')) ?>';
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    document.getElementById('pwd-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('pwd-submit-btn');
        const text = document.getElementById('pwd-submit-text');
        const loading = document.getElementById('pwd-submit-loading');

        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.disabled = true; // 🚨 鎖定密碼修改
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        const oldp = document.getElementById('old-pwd').value;
        const newp = document.getElementById('new-pwd').value;
        const confirmp = document.getElementById('confirm-pwd').value;
        
        try {
            const res = await fetch('/api/change-password', { 
                method: 'POST', 
                headers: { 'Content-Type':'application/json', 'X-CSRF-Token': serverCsrfToken }, 
                body: JSON.stringify({current_password:oldp, new_password:newp, confirm_password:confirmp}) 
            });
            const data = await res.json();
            
            if(data.success) { 
                alert('<?= addslashes(__('Password updated successfully!')) ?>'); 
                document.getElementById('pwd-form').reset(); 
            } else { alert(data.error); }
        } catch(e) {
            alert('<?= addslashes(__('Network Error')) ?>');
        } finally {
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    });

    async function bindNearWallet() {
        const btn = document.getElementById('bind-wallet-btn');
        const text = document.getElementById('bind-wallet-text');
        const icon = document.getElementById('bind-wallet-icon');
        const originalText = text.innerHTML;

        text.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> <?= addslashes(__('Connecting to RPC...')) ?>';
        btn.disabled = true; // 🚨 鎖定 Web3 錢包綁定
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        if(icon) icon.classList.add('hidden');

        try {
            const wallet = await initNearWallet();
            if (!wallet.isSignedIn()) {
                wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
            } else {
                await executeWalletBind(wallet.getAccountId());
            }
        } catch(e) {
            text.innerHTML = originalText;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            if(icon) icon.classList.remove('hidden');
        }
    }

    async function executeWalletBind(accountId) {
        const text = document.getElementById('bind-wallet-text');
        const btn = document.getElementById('bind-wallet-btn');
        if(text) text.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> <?= addslashes(__('Binding Address...')) ?>';
        if(btn) {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }
        
        try {
            // 🚀 核心安全升級：產生防偽密碼學簽章 Payload (Ed25519) 送給後端
            const authPayload = await window.generateNearAuthPayload(accountId);
            authPayload.action = 'bind';

            const res = await fetch('/api/bind-wallet', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': serverCsrfToken },
                body: JSON.stringify(authPayload)
            });
            const data = await res.json();
            if (data.success) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
                window.location.reload();
            } else {
                alert('<?= addslashes(__('Bind Failed')) ?>' + (data.error || ''));
                const wallet = await initNearWallet();
                wallet.signOut(); 
                if(text) text.innerText = '<?= addslashes(__('Connect & Bind Wallet')) ?>';
                if(btn) {
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch(e) {
            alert('<?= addslashes(__('Network Error')) ?>');
            if(text) text.innerText = '<?= addslashes(__('Connect & Bind Wallet')) ?>';
            if(btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    function rollApiKey() {
        if(!confirm('<?= addslashes(__('Key Regen Confirm')) ?>')) return;
        
        const btn = document.getElementById('roll-btn');
        const text = document.getElementById('roll-text');
        const loading = document.getElementById('roll-loading');

        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.disabled = true; // 🚨 鎖定金鑰重置
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        fetch('/api/regenerate-key', { 
            method: 'POST',
            headers: { 'X-CSRF-Token': serverCsrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) { 
                document.getElementById('key-display').innerText = data.new_api_key; 
                alert('<?= addslashes(__('Key generated successfully!')) ?>'); 
            } else {
                alert(data.error || 'Operation failed');
            }
        })
        .catch(e => {
            alert('<?= addslashes(__('Network Error')) ?>');
        })
        .finally(() => {
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        });
    }

    <?php if ($isAdmin): ?>
    async function triggerAutoBuyback() {
        const amount = document.getElementById('buyback-amount').value;
        if (!amount || amount <= 0) return alert('<?= addslashes(__('Please enter a valid NEAR amount.')) ?>');
        
        let confirmText = `<?= addslashes(__('Buyback Confirm')) ?>`.replace(':amount', amount);
        if (!confirm(confirmText)) return;

        const wallet = await initNearWallet();
        if (!wallet.isSignedIn()) {
            alert("<?= addslashes(__('Please connect NEAR wallet first')) ?>");
            wallet.requestSignIn({ contractId: "<?= NEAR_CONTRACT_ID; ?>" });
            return;
        }

        const btn = document.getElementById('buyback-btn');
        const text = document.getElementById('buyback-text');
        const originalHtml = text.innerHTML;
        
        // 🚨 修正：Admin Buyback 加入語言包
        text.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> <?= addslashes(__('Processing...')) ?>';
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');

        try {
            const amountInYocto = nearApi.utils.format.parseNearAmount(amount.toString());
            await wallet.account().functionCall({
                contractId: "<?= NEAR_CONTRACT_ID; ?>",
                methodName: "auto_buyback_and_burn",
                args: { amount_in_near: amountInYocto },
                gas: "100000000000000",
                attachedDeposit: "0", 
                walletCallbackUrl: window.location.href
            });
            
            text.innerHTML = '<i class="fas fa-sync fa-spin mr-2"></i> <?= addslashes(__('Syncing...')) ?>';
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            alert("<?= addslashes(__('Buyback initiated successfully!')) ?>");
            window.location.reload();
        } catch(e) {
            let errorText = `<?= addslashes(__('Buyback Failed')) ?>`.replace(':contract', '<?= NEAR_CONTRACT_ID; ?>');
            alert(errorText);
            
            text.innerHTML = originalHtml;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>