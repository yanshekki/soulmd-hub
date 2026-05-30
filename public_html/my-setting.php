<?php
/**
 * SoulMD Hub - Grand Unified Settings Hub
 * (Account, Web3, Platform API, Encrypted BYOK Engine)
 */
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /login'); exit; }

$userId = $_SESSION['user_id'];
$db = Database::getInstance();
$pdo = $db->getConnection();

// 獲取基本資訊
$stmt = $pdo->prepare("SELECT username, email, api_key, near_wallet_address FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$pageTitle = 'My Settings - SoulMD Hub';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-8 flex flex-col md:flex-row gap-8 flex-grow">
    
    <div class="w-full md:w-64 shrink-0 flex flex-row md:flex-col gap-2 overflow-x-auto md:overflow-visible pb-4 md:pb-0">
        <button onclick="switchTab('account')" id="btn-account" class="tab-btn active shrink-0 text-left px-5 py-3 rounded-xl font-bold text-sm transition bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"><i class="fas fa-shield-alt w-5"></i> 帳戶安全</button>
        <button onclick="switchTab('web3')" id="btn-web3" class="tab-btn shrink-0 text-left px-5 py-3 rounded-xl font-bold text-sm transition text-zinc-400 hover:bg-white/5"><i class="fas fa-wallet w-5"></i> Web3 錢包</button>
        <button onclick="switchTab('api')" id="btn-api" class="tab-btn shrink-0 text-left px-5 py-3 rounded-xl font-bold text-sm transition text-zinc-400 hover:bg-white/5"><i class="fas fa-key w-5"></i> 開發者 API</button>
        <button onclick="switchTab('byok')" id="btn-byok" class="tab-btn shrink-0 text-left px-5 py-3 rounded-xl font-bold text-sm transition text-zinc-400 hover:bg-white/5"><i class="fas fa-brain w-5"></i> 自訂 AI 引擎 (BYOK)</button>
    </div>

    <div class="flex-1 bg-zinc-900/60 border border-white/10 rounded-3xl p-6 sm:p-8 backdrop-blur-sm shadow-2xl relative overflow-hidden min-h-[600px]">
        
        <div id="tab-account" class="tab-content block animate-fade-in">
            <h2 class="text-2xl font-bold mb-6 text-white border-b border-white/10 pb-4">帳戶安全</h2>
            <div class="mb-6">
                <label class="block text-xs text-zinc-500 uppercase tracking-widest font-bold mb-2">使用者名稱</label>
                <div class="bg-zinc-950 px-4 py-3 rounded-xl border border-white/5 text-zinc-300 font-mono"><?= htmlspecialchars($user['username']) ?></div>
            </div>
            <form id="pwd-form" class="space-y-4">
                <div>
                    <label class="block text-xs text-zinc-500 uppercase tracking-widest font-bold mb-2">舊密碼</label>
                    <input type="password" id="old-pwd" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-400 focus:outline-none text-white shadow-inner">
                </div>
                <div>
                    <label class="block text-xs text-zinc-500 uppercase tracking-widest font-bold mb-2">新密碼</label>
                    <input type="password" id="new-pwd" required class="w-full bg-zinc-950 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-emerald-400 focus:outline-none text-white shadow-inner">
                </div>
                <button type="submit" class="mt-4 px-6 py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 font-bold rounded-xl transition shadow">更新密碼</button>
            </form>
        </div>

        <div id="tab-web3" class="tab-content hidden animate-fade-in">
            <h2 class="text-2xl font-bold mb-6 text-white border-b border-white/10 pb-4 flex items-center gap-3"><i class="fas fa-wallet text-blue-400"></i> Web3 錢包綁定</h2>
            <p class="text-sm text-zinc-400 mb-6">綁定錢包後解鎖 AgentFi 功能，包含購買、租借 AI 模型。每個帳號僅可綁定一次，且無法修改。</p>
            <?php if ($user['near_wallet_address']): ?>
                <div class="bg-zinc-950 border border-emerald-500/30 p-5 rounded-2xl flex items-center gap-4 shadow-inner">
                    <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" class="w-8 h-8 opacity-90">
                    <div>
                        <div class="text-xs text-emerald-500 font-bold uppercase tracking-widest mb-1">已永久綁定</div>
                        <code class="text-lg text-white font-mono"><?= htmlspecialchars($user['near_wallet_address']) ?></code>
                    </div>
                </div>
            <?php else: ?>
                <button onclick="bindNearWallet()" id="bind-wallet-btn" class="w-full md:w-auto px-8 py-4 bg-gradient-to-r from-emerald-400 to-teal-500 text-zinc-950 font-black rounded-2xl transition hover:brightness-110 shadow-[0_0_25px_rgba(52,211,153,0.25)] flex items-center justify-center gap-3">
                    <img src="https://cryptologos.cc/logos/near-protocol-near-logo.svg?v=033" id="bind-wallet-icon" class="w-5 h-5 opacity-90"> 
                    <span id="bind-wallet-text">連接並綁定 NEAR 錢包</span>
                </button>
            <?php endif; ?>
        </div>

        <div id="tab-api" class="tab-content hidden animate-fade-in">
            <h2 class="text-2xl font-bold mb-6 text-white border-b border-white/10 pb-4 flex items-center gap-3"><i class="fas fa-key text-amber-400"></i> 平台 API 金鑰</h2>
            <p class="text-sm text-zinc-400 mb-6">此金鑰用於呼叫 SoulMD 平台的 <code>/api/chat.php</code> 端點 (受平台計畫額度限制)。</p>
            <div class="bg-zinc-950 border border-white/10 p-4 rounded-xl flex items-center justify-between gap-3 mb-6 shadow-inner">
                <code id="key-display" class="text-base text-amber-400 font-mono truncate select-all"><?= htmlspecialchars($user['api_key']) ?></code>
            </div>
            <button onclick="rollApiKey()" class="px-6 py-3 bg-zinc-800 hover:bg-amber-500/20 text-white font-bold rounded-xl transition border border-white/5 hover:border-amber-500/30"><i class="fas fa-redo mr-2"></i> 重新生成金鑰</button>
        </div>

        <div id="tab-byok" class="tab-content hidden animate-fade-in relative">
            <div class="absolute top-0 right-0 px-3 py-1 bg-purple-500/20 text-purple-400 text-[10px] font-bold rounded-bl-xl uppercase tracking-widest border-b border-l border-purple-500/30">無限暢聊解鎖</div>
            
            <div class="flex items-center justify-between border-b border-white/10 pb-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3"><i class="fas fa-brain text-purple-400"></i> 自訂 AI 引擎 (BYOK)</h2>
                    <p class="text-sm text-zinc-400 mt-2">啟動後，平台將「左手交右手」代發請求至您專屬的 API。不扣除任何平台次數配額！金鑰均經 AES-256 加密存儲。</p>
                </div>
                <label class="flex items-center cursor-pointer relative">
                    <input type="checkbox" id="use_byok" class="sr-only">
                    <div class="toggle-bg block w-14 h-8 bg-zinc-700 rounded-full border border-white/10 transition-colors"></div>
                    <div class="toggle-dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition-transform transform"></div>
                </label>
            </div>

            <div id="byok-settings-panel" class="space-y-6 opacity-50 pointer-events-none transition-opacity duration-300">
                
                <div class="bg-zinc-950/50 border border-white/5 rounded-2xl p-5 shadow-inner">
                    <h3 class="text-emerald-400 font-bold mb-4 uppercase tracking-widest text-xs flex items-center"><i class="fas fa-comment-alt mr-2"></i>文字推理模型 (Text LLM)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold">主流平台預設配置</label>
                            <select id="text_provider" onchange="autoFillProvider('text')" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-purple-400 transition">
                                <option value="openai">OpenAI (推薦)</option>
                                <option value="deepseek">DeepSeek</option>
                                <option value="together">Together AI (開源模型)</option>
                                <option value="groq">Groq (極速推理)</option>
                                <option value="openrouter">OpenRouter (Claude/Gemini聚合)</option>
                                <option value="custom">自訂 (Custom - OpenAI 相容)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold">API URL (OpenAI 相容端點)</label>
                            <input type="text" id="text_api_url" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold">模型名稱 (Model Name)</label>
                            <input type="text" id="text_model" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono transition">
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold">您的 API 金鑰 (API Key)</label>
                            <input type="password" id="text_api_key" placeholder="sk-..." class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono placeholder-zinc-700 transition">
                        </div>
                    </div>
                </div>

                <div class="bg-zinc-950/50 border border-white/5 rounded-2xl p-5 shadow-inner">
                    <h3 class="text-blue-400 font-bold mb-4 uppercase tracking-widest text-xs flex items-center"><i class="fas fa-eye mr-2"></i>圖像分析模型 (Vision LLM) <span class="text-zinc-500 ml-2 font-normal text-[10px]">(選填：Fallback 用)</span></h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold">主流平台預設配置</label>
                            <select id="vision_provider" onchange="autoFillProvider('vision')" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-purple-400 transition">
                                <option value="openai">OpenAI (推薦)</option>
                                <option value="together">Together AI (Llama 3.2 Vision)</option>
                                <option value="openrouter">OpenRouter</option>
                                <option value="custom">自訂 (Custom)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold">API URL</label>
                            <input type="text" id="vision_api_url" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold">模型名稱 (Model Name)</label>
                            <input type="text" id="vision_model" class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono transition">
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1.5 font-bold">您的 API 金鑰 (API Key)</label>
                            <input type="password" id="vision_api_key" placeholder="sk-..." class="w-full bg-zinc-900 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-zinc-300 focus:outline-none focus:border-purple-400 font-mono placeholder-zinc-700 transition">
                        </div>
                    </div>
                </div>

                <div class="bg-zinc-950/50 border border-amber-500/20 rounded-2xl p-5 shadow-inner relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-amber-500"></div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-amber-400 font-bold uppercase tracking-widest text-xs flex items-center"><i class="fas fa-compress-arrows-alt mr-2"></i>上下文記憶體壓縮頻率 (Memory Compression)</h3>
                        <span class="text-xl font-black text-white bg-zinc-800 px-3 py-1 rounded-lg font-mono border border-white/5 shadow" id="compress_val_display">10</span>
                    </div>
                    <p class="text-[11px] sm:text-xs text-zinc-400 mb-5 leading-relaxed">為了防止 Context 過長燒乾您的 API 額度，系統會在對話達到特定輪數時，呼叫您的金鑰將舊紀錄壓縮成摘要。建議設定為 10-20 輪，節省 Token 花費。</p>
                    <input type="range" id="memory_compress_threshold" min="4" max="50" step="2" class="w-full accent-amber-400 h-2 bg-zinc-800 rounded-lg appearance-none cursor-pointer outline-none" oninput="document.getElementById('compress_val_display').innerText = this.value">
                </div>

                <button onclick="saveLLMSettings()" id="save-llm-btn" class="w-full py-4 bg-purple-600 hover:bg-purple-500 text-white font-bold text-lg rounded-2xl transition shadow-lg shadow-purple-500/20 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                    <i class="fas fa-save"></i> 儲存自訂引擎設定
                </button>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../private/includes/near-wallet-scripts.php'; ?>

<script>
    // --- UI Tabs 邏輯 ---
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('bg-emerald-500/20', 'text-emerald-400', 'border', 'border-emerald-500/30');
            el.classList.add('text-zinc-400', 'hover:bg-white/5');
        });
        
        document.getElementById('tab-' + tabId).classList.remove('hidden');
        const activeBtn = document.getElementById('btn-' + tabId);
        activeBtn.classList.remove('text-zinc-400', 'hover:bg-white/5');
        activeBtn.classList.add('bg-emerald-500/20', 'text-emerald-400', 'border', 'border-emerald-500/30');
    }

    // --- LLM Providers 預設數據 ---
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

    // --- 載入與儲存 BYOK 設定 ---
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
    });

    async function saveLLMSettings() {
        const btn = document.getElementById('save-llm-btn');
        btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> 儲存中...';
        
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
            const res = await fetch('/api/settings', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            const data = await res.json();
            if(data.success) {
                btn.innerHTML = '<i class="fas fa-check mr-2"></i> 設定已儲存';
                btn.classList.replace('bg-purple-600', 'bg-emerald-500');
                btn.classList.replace('shadow-purple-500/20', 'shadow-emerald-500/20');
                setTimeout(() => { 
                    btn.innerHTML = '<i class="fas fa-save mr-2"></i> 儲存自訂引擎設定'; 
                    btn.classList.replace('bg-emerald-500', 'bg-purple-600'); 
                    btn.classList.replace('shadow-emerald-500/20', 'shadow-purple-500/20'); 
                }, 2000);
            } else {
                alert('儲存失敗: ' + data.error);
                btn.innerHTML = '<i class="fas fa-save mr-2"></i> 儲存自訂引擎設定';
            }
        } catch(e) { 
            alert('網絡錯誤！'); 
            btn.innerHTML = '<i class="fas fa-save mr-2"></i> 儲存自訂引擎設定';
        }
    }

    // --- 其他舊有功能移植 ---
    document.getElementById('pwd-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const originalText = btn.innerText;
        btn.innerText = '更新中...';
        btn.disabled = true;

        const oldp = document.getElementById('old-pwd').value;
        const newp = document.getElementById('new-pwd').value;
        const res = await fetch('/api/change-password', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({old_password:oldp, new_password:newp}) });
        const data = await res.json();
        
        btn.disabled = false;
        btn.innerText = originalText;

        if(data.success) { 
            alert('密碼更新成功！'); 
            document.getElementById('pwd-form').reset(); 
        } else { 
            alert('錯誤: ' + data.error); 
        }
    });

    async function bindNearWallet() {
        const btn = document.getElementById('bind-wallet-btn');
        const text = document.getElementById('bind-wallet-text');
        const icon = document.getElementById('bind-wallet-icon');
        const originalText = text.innerHTML;

        text.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> 等緊 RPC 連接...';
        btn.classList.add('opacity-50', 'pointer-events-none');
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
            btn.classList.remove('opacity-50', 'pointer-events-none');
            if(icon) icon.classList.remove('hidden');
        }
    }

    async function executeWalletBind(accountId) {
        const text = document.getElementById('bind-wallet-text');
        if(text) text.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> 正在綁定地址...';
        
        try {
            const res = await fetch('/api/bind-wallet', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'bind', wallet: accountId })
            });
            const data = await res.json();
            if (data.success) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: cleanUrl}, '', cleanUrl);
                window.location.reload();
            } else {
                alert('綁定失敗: ' + (data.error || '未知的錯誤'));
                const wallet = await initNearWallet();
                wallet.signOut();
                if(text) text.innerText = '連接並綁定 NEAR 錢包';
                const btn = document.getElementById('bind-wallet-btn');
                if(btn) btn.classList.remove('opacity-50', 'pointer-events-none');
            }
        } catch(e) {
            alert('網絡錯誤！');
            if(text) text.innerText = '連接並綁定 NEAR 錢包';
            const btn = document.getElementById('bind-wallet-btn');
            if(btn) btn.classList.remove('opacity-50', 'pointer-events-none');
        }
    }

    async function rollApiKey() {
        if(!confirm('確定要重新生成平台 API 金鑰嗎？舊金鑰將立即失效！')) return;
        const res = await fetch('/api/regenerate-key', { method: 'POST' });
        const data = await res.json();
        if(data.success) { document.getElementById('key-display').innerText = data.new_api_key; alert('生成成功！'); }
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>