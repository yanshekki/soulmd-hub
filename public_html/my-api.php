<?php
// 如果沒有宣告 $isPublicApiPage，則預設為 false (私人管理模式)
$isPublicApiPage = $isPublicApiPage ?? false;

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

$apiKey = 'YOUR_API_KEY';
$isPremiumActive = false;
$userTier = 'free';
$isExpired = false;

// 如果是私人模式，才進行登入驗證及撈取/生成 API Key 與 訂閱狀態
if (!$isPublicApiPage) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $userId = $_SESSION['user_id'];

    // 獲取 API Key 與 訂閱狀態
    $stmt = $pdo->prepare("SELECT api_key, tier, vip_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userRow = $stmt->fetch();
    
    if ($userRow) {
        $apiKey = $userRow['api_key'];
        $userTier = $userRow['tier'];
        $expiry = $userRow['vip_expires_at'] ? strtotime($userRow['vip_expires_at']) : 0;
        
        if ($userTier !== 'free') {
            if ($expiry > time()) {
                $isPremiumActive = true;
            } else {
                $isExpired = true; // 曾經付費但已過期
            }
        }
    }

    if (!$apiKey) {
        $apiKey = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$apiKey, $userId]);
    }
}

$baseUrl = defined('BASE_URL') ? BASE_URL : ("https://" . $_SERVER['HTTP_HOST']);

$pageTitle = $isPublicApiPage ? 'Public API Reference' : 'Developer API';
$pageDesc = 'Manage your API key and read integration docs for SoulMD Hub.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-8">
        <div>
            <?php if ($isPublicApiPage): ?>
                <a href="/browse" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                    <i class="fas fa-arrow-left"></i> Back to Hub
                </a>
            <?php else: ?>
                <a href="/my-souls" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                    <i class="fas fa-arrow-left"></i> Back to My Souls
                </a>
            <?php endif; ?>
            
            <h1 class="text-4xl font-bold tracking-tighter"><?= $isPublicApiPage ? 'Public API Reference' : 'Developer API' ?></h1>
            <p class="text-zinc-400 mt-2">Integrate SoulMD Hub programmatically. 100% API-Driven Architecture.</p>
        </div>
    </div>

    <?php if (!$isPublicApiPage): ?>
        <?php if (!$isPremiumActive): ?>
            <div class="bg-gradient-to-r from-red-900/40 to-amber-900/40 border border-red-500/50 p-6 rounded-3xl mb-8 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-red-500"></div>
                <div class="flex items-start gap-4 z-10">
                    <div class="text-red-400 text-3xl mt-1"><i class="fas fa-lock"></i></div>
                    <div>
                        <h3 class="text-xl font-bold text-white mb-1">
                            <?= $isExpired ? 'Your Premium Subscription has Expired!' : 'Headless Chat API is Locked (Free Tier)' ?>
                        </h3>
                        <p class="text-sm text-zinc-300 leading-relaxed">
                            <?= $isExpired 
                                ? "Your VIP/PRO access has lapsed. Direct headless access to the <code>/api/chat</code> endpoint has been restricted. Please renew your pass to restore full API integration capabilities." 
                                : "Direct headless access to the core Chat Engine (<code>/api/chat</code>) is exclusively reserved for VIP and PRO members. Upgrade now to build automated agents." ?>
                        </p>
                    </div>
                </div>
                <div class="shrink-0 z-10 w-full md:w-auto">
                    <a href="/upgrade" class="w-full md:w-auto px-6 py-3 bg-red-500 hover:bg-red-400 text-zinc-950 font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-red-500/20">
                        <?= $isExpired ? '<i class="fas fa-sync-alt"></i> Renew Subscription' : '<i class="fas fa-crown"></i> Upgrade to Unlock' ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div id="success-box" class="hidden bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-8 text-sm text-emerald-100 shadow-lg flex items-center gap-2 transition-all">
            <i class="fas fa-check-circle"></i> <span id="success-msg"></span>
        </div>
        <div id="error-box" class="hidden bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-8 text-sm text-red-200 shadow-lg flex items-center gap-2 transition-all">
            <i class="fas fa-exclamation-circle"></i> <span id="error-msg"></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <?php if (!$isPublicApiPage): ?>
        <div class="xl:col-span-4 space-y-6">
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 backdrop-blur-sm shadow-xl relative overflow-hidden sticky top-6">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                <h3 class="text-lg font-bold mb-1">Your Secret Key</h3>
                <p class="text-xs text-zinc-400 mb-6 leading-relaxed">This key grants full access to create, edit, and interact with souls on your behalf. Keep it secure.</p>
                
                <div class="bg-zinc-950 border border-white/10 p-4 rounded-2xl flex items-center justify-between gap-3 mb-6">
                    <code id="key-display" class="text-sm text-emerald-400 font-mono truncate select-all"><?= htmlspecialchars($apiKey) ?></code>
                    <button onclick="copyKey(this)" class="text-zinc-400 hover:text-white transition shrink-0 bg-white/5 hover:bg-white/10 w-8 h-8 rounded-lg flex items-center justify-center">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>

                <button type="button" id="roll-btn" onclick="rollApiKey()" class="mb-3 w-full py-3 bg-zinc-800 hover:bg-red-500/20 text-zinc-300 hover:text-red-400 border border-white/5 hover:border-red-500/30 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                    <span id="roll-text"><i class="fas fa-redo text-xs"></i> Roll API Key</span>
                    <span id="roll-loading" class="hidden animate-spin h-4 w-4 border-2 border-current border-t-transparent rounded-full"></span>
                </button>

                <button onclick="downloadPostmanCollection()" class="w-full py-3 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/10">
                    <i class="fas fa-file-download"></i> Download Postman Collection
                </button>

                <div class="mt-8 pt-6 border-t border-white/10">
                    <h3 class="text-emerald-400 font-bold mb-2 flex items-center gap-2"><i class="fas fa-shield-alt"></i> Authentication</h3>
                    <p class="text-xs text-zinc-300 leading-relaxed mb-4">Pass your API key via the HTTP <code>Authorization</code> header for endpoints that require it.</p>
                    <div class="bg-zinc-950 border border-white/10 p-3 rounded-xl text-xs font-mono text-zinc-400 overflow-x-auto whitespace-nowrap">
                        Authorization: Bearer <span class="text-emerald-300">YOUR_API_KEY</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="<?= $isPublicApiPage ? 'xl:col-span-12 max-w-5xl mx-auto w-full' : 'xl:col-span-8' ?> space-y-8">
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 md:p-8 backdrop-blur-sm shadow-xl">
                
                <?php if ($isPublicApiPage): ?>
                    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-8 border-b border-white/10 pb-4">
                        <h2 class="text-2xl font-bold">API Reference</h2>
                        <button onclick="downloadPostmanCollection()" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/10">
                            <i class="fas fa-file-download"></i> Download Postman Collection
                        </button>
                    </div>
                <?php else: ?>
                    <h2 class="text-2xl font-bold mb-8 border-b border-white/10 pb-4">API Reference</h2>
                <?php endif; ?>

                <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-10"><i class="fas fa-user-shield mr-2"></i> Authentication & Account</h3>
                
                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/register</code>
                    </div>
                    <p class="text-sm text-zinc-400">Register a new user and generate an API key. Enforces secure alpha-numeric URL constraints.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "username": "developer101",
  "password": "securepassword123",
  "email": "dev@example.com"
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Account created successfully",
  "api_key": "7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c..."
}</pre>
                        </details>
                    </div>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/login</code>
                    </div>
                    <p class="text-sm text-zinc-400">Authenticate user. Returns API Key and sets a secure 30-day web session if requested.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "username": "developer101",
  "password": "securepassword123",
  "remember": true
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Login successful",
  "api_key": "7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c..."
}</pre>
                        </details>
                    </div>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/change-password</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Change the current logged-in user's password securely.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "current_password": "securepassword123",
  "new_password": "brandnewpassword999",
  "confirm_password": "brandnewpassword999"
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Password successfully updated!"
}</pre>
                        </details>
                    </div>
                </div>

                <h3 class="text-xl font-bold text-amber-400 mb-6 mt-12"><i class="fas fa-comments mr-2"></i> Interaction & Chat Engine</h3>

                <div class="mb-10 border-l-2 border-amber-500 pl-6 space-y-3 relative">
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/chat</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded border border-red-500/20">Auth Required</span>
                        <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded border border-amber-500/20"><i class="fas fa-crown mr-1"></i>VIP / PRO Only</span>
                    </div>
                    <p class="text-sm text-zinc-400">Headless API access to retrieve conversation history. Strict permission controls prevent accessing private sessions.</p>
                    <p class="text-xs text-zinc-500 font-mono">Query params: ?soul_id=1&session_token=random_token_here</p>
                    <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                        <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "messages": [
    {
      "role": "user",
      "content": "Hello! How can you help me today?"
    },
    {
      "role": "assistant",
      "content": "I am an expert assistant. I can help you with coding and reasoning tasks."
    }
  ]
}</pre>
                    </details>
                </div>

                <div class="mb-10 border-l-2 border-amber-500 pl-6 space-y-3 relative">
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/chat</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded border border-red-500/20">Auth Required</span>
                        <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded border border-amber-500/20"><i class="fas fa-crown mr-1"></i>VIP / PRO Only</span>
                    </div>
                    <p class="text-sm text-zinc-400">Headless API access to interact with the core routing engine. Send messages, optionally attach base64 images (Vision AI), and receive responses. Free tier requests to this endpoint will be strictly rejected with a 403 Forbidden status.</p>
                    <p class="text-xs text-amber-400 font-semibold bg-amber-500/10 border border-amber-500/20 p-2.5 rounded-xl"><i class="fas fa-exclamation-triangle"></i> <strong>Subscription Policy:</strong> Direct API integration requires an active VIP or PRO license. If your subscription period expires, your integration will be automatically disabled until a renewal is processed.</p>
                    
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "action": "chat",
  "soul_id": 1,
  "session_token": "unique_session_id_123",
  "content": "Can you analyze this architecture diagram?",
  "image": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...", // Optional Base64 Image
  "is_private": false
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "reply": "Based on the provided architecture diagram, here is the technical breakdown..."
}</pre>
                        </details>
                    </div>
                </div>

                <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-12"><i class="fas fa-brain mr-2"></i> Core Souls Hub</h3>

                <div class="mb-10 border-l-2 border-purple-500 pl-6 space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/categories</code>
                    </div>
                    <p class="text-sm text-zinc-400">Fetch the complete white-list of roles/categories including their corresponding slug names and emoji icons.</p>
                    <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                        <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "count": 2,
  "data": [
    { "id": 1, "name": "Developer", "slug": "Developer", "icon": "💻" },
    { "id": 2, "name": "Writer", "slug": "Writer", "icon": "✍️" }
  ]
}</pre>
                    </details>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/souls</code>
                    </div>
                    <p class="text-sm text-zinc-400">List, search and filter public souls. Optimized with strict DB select limits.</p>
                    <p class="text-xs text-zinc-500 font-mono">Query params: ?limit=20&offset=0&q=ai&sort=popular&role=Developer</p>
                    <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                        <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "count": 1,
  "data": [
    {
      "id": 1,
      "title": "Expert Translator",
      "description": "Translates documents contextually",
      "role": "Translator",
      "domain": "Education",
      "compatibility": "Claude 3.5 Sonnet",
      "file_type": "single_md",
      "like_count": 12,
      "fork_count": 3,
      "created_at": "2026-05-21 12:00:00"
    }
  ]
}</pre>
                    </details>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/soul/{id}</code>
                    </div>
                    <p class="text-sm text-zinc-400">Retrieve raw architecture files, tags, and stats of a single public or owned soul.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample (file_type: single_md)</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 5,
    "title": "Expert Translator",
    "description": "Translates documents contextually",
    "content": "## Identity\nYou are an expert translator...",
    "file_type": "single_md",
    "role": "Translator",
    "domain": "Education",
    "compatibility": "Claude 3.5 Sonnet",
    "is_public": 1,
    "like_count": 12,
    "fork_count": 3,
    "created_at": "2026-05-21 12:00:00"
  }
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-purple-400 cursor-pointer select-none font-medium hover:underline">View Response Sample (file_type: full_soul_folder)</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "data": {
    "id": 2,
    "user_id": 5,
    "title": "Advanced Dev Architecture",
    "description": "Full-stack code assistant package layout",
    "content": "{\n  \"SOUL.md\": \"## Identity\\nYou are a senior developer...\",\n  \"STYLE.md\": \"## Voice\\nConcise, code-heavy...\",\n  \"RULES.md\": \"## Hard Rules\\nNever write legacy code...\"\n}",
    "file_type": "full_soul_folder",
    "role": "Developer",
    "domain": "Coding & Dev",
    "compatibility": "GPT-4o",
    "is_public": 1,
    "like_count": 88,
    "fork_count": 15,
    "created_at": "2026-05-21 14:22:10"
  }
}</pre>
                        </details>
                    </div>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/souls</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Publish a brand new AI agent. Automatically detects single .md prompt or full Modular configuration folders.</p>
                    <p class="text-xs text-red-400 font-semibold bg-red-500/10 border border-red-500/20 p-2.5 rounded-xl"><i class="fas fa-exclamation-triangle"></i> CRITICAL CONSTRAINT: The <code>role</code> field inside the request body MUST strictly use one of the <code>slug</code> values provided by the <code>/api/categories</code> API. Invalid roles will be forcefully fallbacked to 'Other'.</p>
                    
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "title": "Expert Translator",
  "description": "Translates documents contextually",
  "content": "## Identity\nYou are an expert translator...",
  "role": "Translator",
  "domain": "Education",
  "compatibility": "Claude 3.5 Sonnet"
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Soul created successfully",
  "id": 42,
  "url": "<?= $baseUrl ?>/soul/42"
}</pre>
                        </details>
                    </div>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 font-mono text-[10px] font-bold rounded border border-amber-500/30">PUT</span>
                        <code class="text-base font-bold text-white">/api/soul/{id}</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Update an existing soul module layout. Automatically creates an incremental version timeline backup record.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "title": "Expert Translator v2",
  "description": "Updated translation engine",
  "content": "## Identity\nYou are...",
  "role": "Translator",
  "domain": "Education",
  "compatibility": "Claude 3.5 Sonnet",
  "is_public": 1
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Soul updated successfully"
}</pre>
                        </details>
                    </div>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-red-500/20 text-red-400 font-mono text-[10px] font-bold rounded border border-red-500/30">DELETE</span>
                        <code class="text-base font-bold text-white">/api/soul/{id}</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Permanently delete a soul architecture configuration and gracefully updates relational metadata tracking statistics.</p>
                    <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                        <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Soul deleted successfully"
}</pre>
                    </details>
                </div>

                <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-12"><i class="fas fa-code-branch mr-2"></i> Profiles & Social Interactions</h3>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/profile</code>
                    </div>
                    <p class="text-sm text-zinc-400">Fetch public indicators (aggregated likes, forks, total models) and public soul array mapping for any developer.</p>
                    <p class="text-xs text-zinc-500 font-mono">Query params: ?username=developer101</p>
                    <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                        <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "user": {
    "username": "developer101",
    "joined_at": "2026-05-20 10:00:00"
  },
  "stats": {
    "total_souls": 5,
    "total_likes": 24,
    "total_forks": 8
  },
  "souls": [
    {
      "id": 1,
      "title": "Expert Translator",
      "description": "Translates documents...",
      "role": "Translator",
      "domain": "Education",
      "compatibility": "Claude 3.5 Sonnet",
      "file_type": "single_md",
      "like_count": 12,
      "fork_count": 3,
      "created_at": "2026-05-21 12:00:00"
    }
  ]
}</pre>
                    </details>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/versions</code>
                    </div>
                    <p class="text-sm text-zinc-400">Retrieve full historical rollback archive versions of a soul. Protected by strict IDOR multi-tenant permission validation check.</p>
                    <p class="text-xs text-zinc-500 font-mono">Query params: ?soul_id={id}</p>
                    <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                        <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "count": 1,
  "data": [
    {
      "id": 12,
      "soul_id": 1,
      "title": "Expert Translator v1",
      "content": "## Identity\nYou are...",
      "edited_at": "2026-05-21 15:30:00"
    }
  ]
}</pre>
                    </details>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/versions</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Instantly restore active state content layout to a historical milestone setup version identifier point.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "soul_id": 1,
  "version_id": 5
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Version restored successfully"
}</pre>
                        </details>
                    </div>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/fork</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Clone a public agent model directly into your workspace account as an independent project fork tree line node.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "soul_id": 1
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "new_soul_id": 43,
  "url": "<?= $baseUrl ?>/soul/43",
  "message": "Soul forked successfully!"
}</pre>
                        </details>
                    </div>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/like</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Toggle like/unlike state. Enforces atomic uniqueness index mapping constraints. Returns boolean state indicating if currently liked.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "soul_id": 1
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "liked": true,
  "message": "Soul liked successfully"
}</pre>
                        </details>
                    </div>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/rate</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Rate between 1 to 5 stars. Submitting again overrides previous row entry record. Returns updated global live averages for instant interface refresh.</p>
                    <div class="pt-1 flex flex-col gap-2">
                        <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "soul_id": 1,
  "rating": 5
}</pre>
                        </details>
                        <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                            <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Rating submitted successfully",
  "avg_rating": 4.5,
  "total_ratings": 18
}</pre>
                        </details>
                    </div>
                </div>

                <?php if (!$isPublicApiPage): ?>
                <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-12"><i class="fas fa-tools mr-2"></i> Internal Web Utilities</h3>
                <p class="text-sm text-zinc-500 mb-6 bg-amber-500/10 border border-amber-500/20 p-3 rounded-xl text-amber-200"><i class="fas fa-exclamation-triangle"></i> Note: The following endpoints rely on browser Session Cookies and cannot be authenticated via API Keys. They are excluded from the Postman Collection.</p>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/logout</code>
                        <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded ml-2 border border-amber-500/20">Session Cookie Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Clear session and remember me tokens for the current browser session.</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/regenerate-key</code>
                        <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded ml-2 border border-amber-500/20">Session Cookie Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Invalidate the current API key and issue a new 32-byte hex key securely to the logged-in user.</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/save-preset</code>
                        <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded ml-2 border border-amber-500/20">Session Cookie Required</span>
                    </div>
                    <p class="text-sm text-zinc-400">Internal endpoint to temporarily save generated AI layouts into current user's session cache memory.</p>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
    <?php if (!$isPublicApiPage): ?>
    function copyKey(btn) {
        const key = document.getElementById('key-display').innerText;
        navigator.clipboard.writeText(key).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i>';
            setTimeout(() => btn.innerHTML = original, 2000);
        });
    }

    async function rollApiKey() {
        if (!confirm('Are you sure you want to roll your API Key? All applications using the old key will lose access immediately.')) return;
        
        const btn = document.getElementById('roll-btn');
        const text = document.getElementById('roll-text');
        const loading = document.getElementById('roll-loading');
        const successBox = document.getElementById('success-box');
        const errorBox = document.getElementById('error-box');

        text.classList.add('hidden');
        loading.classList.remove('hidden');
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        successBox.classList.add('hidden');
        errorBox.classList.add('hidden');

        try {
            const res = await fetch('/api/regenerate-key', { method: 'POST' });
            const data = await res.json();

            if (data.success) {
                document.getElementById('key-display').innerText = data.new_api_key;
                document.getElementById('success-msg').innerText = data.message;
                successBox.classList.remove('hidden');
            } else {
                document.getElementById('error-msg').innerText = data.error || 'Operation failed';
                errorBox.classList.remove('hidden');
            }
        } catch(e) {
            document.getElementById('error-msg').innerText = 'Network error. Please try again.';
            errorBox.classList.remove('hidden');
        } finally {
            text.classList.remove('hidden');
            loading.classList.add('hidden');
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
    <?php endif; ?>

    function downloadPostmanCollection() {
        const keyDisplay = document.getElementById('key-display');
        const currentApiKey = keyDisplay ? keyDisplay.innerText : 'YOUR_API_KEY';
        
        const collection = {
            "info": {
                "name": "SoulMD Hub Public API",
                "_postman_id": "soulmd_hub_collection_" + Date.now(),
                "description": "Official API Collection for SoulMD Hub - Modular AI Agent SaaS Ecosystem.",
                "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
            },
            "item": [
                {
                    "name": "Authentication & Account",
                    "item": [
                        {
                            "name": "Register User",
                            "request": {
                                "method": "POST",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"username": "developer101", "password": "securepassword123", "email": "dev@example.com"}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/register", "host": ["{{baseUrl}}"], "path": ["api", "register"] }
                            },
                            "response": [{
                                "name": "Registration Success",
                                "status": "Created",
                                "code": 201,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Account created successfully", "api_key": "7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c..."}, null, 2)
                            }]
                        },
                        {
                            "name": "Login User",
                            "request": {
                                "method": "POST",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"username": "developer101", "password": "securepassword123", "remember": true}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/login", "host": ["{{baseUrl}}"], "path": ["api", "login"] }
                            },
                            "response": [{
                                "name": "Login Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Login successful", "api_key": "7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c..."}, null, 2)
                            }]
                        },
                        {
                            "name": "Change Password",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"current_password": "securepassword123", "new_password": "brandnewpassword999", "confirm_password": "brandnewpassword999"}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/change-password", "host": ["{{baseUrl}}"], "path": ["api", "change-password"] }
                            },
                            "response": [{
                                "name": "Password Update Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Password successfully updated!"}, null, 2)
                            }]
                        }
                    ]
                },
                {
                    "name": "Interaction & Chat Engine",
                    "item": [
                        {
                            "name": "Retrieve Chat History",
                            "request": {
                                "method": "GET",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": {
                                    "raw": "{{baseUrl}}/api/chat?soul_id=1&session_token=unique_session_id_123",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "chat"],
                                    "query": [
                                        {"key": "soul_id", "value": "1"},
                                        {"key": "session_token", "value": "unique_session_id_123"}
                                    ]
                                }
                            },
                            "response": [{
                                "name": "History Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "messages": [{"role": "user", "content": "Hello!"}, {"role": "assistant", "content": "Hi there!"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Send Chat Message (Headless)",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"action": "chat", "soul_id": 1, "session_token": "unique_session_id_123", "content": "Analyze this architecture.", "is_private": false}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/chat", "host": ["{{baseUrl}}"], "path": ["api", "chat"] }
                            },
                            "response": [{
                                "name": "AI Reply Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "reply": "Based on the provided architecture..."}, null, 2)
                            }]
                        }
                    ]
                },
                {
                    "name": "Core Souls",
                    "item": [
                        {
                            "name": "List Available Categories/Roles",
                            "request": {
                                "method": "GET",
                                "header": [],
                                "url": { "raw": "{{baseUrl}}/api/categories", "host": ["{{baseUrl}}"], "path": ["api", "categories"] }
                            },
                            "response": [{
                                "name": "Categories Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "count": 2, "data": [{"id": 1, "name": "Developer", "slug": "Developer", "icon": "💻"}, {"id": 2, "name": "Writer", "slug": "Writer", "icon": "✍️"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "List Public Souls",
                            "request": {
                                "method": "GET",
                                "header": [],
                                "url": {
                                    "raw": "{{baseUrl}}/api/souls?limit=20&offset=0&sort=popular",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "souls"],
                                    "query": [
                                        {"key": "limit", "value": "20"},
                                        {"key": "offset", "value": "0"},
                                        {"key": "sort", "value": "popular"},
                                        {"key": "q", "value": "ai", "disabled": true},
                                        {"key": "role", "value": "Developer", "disabled": true},
                                        {"key": "file_type", "value": "full_soul_folder", "disabled": true}
                                    ]
                                }
                            },
                            "response": [{
                                "name": "List Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "count": 1, "data": [{"id": 1, "title": "Expert Translator", "description": "Translates documents contextually", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet", "file_type": "single_md", "like_count": 12, "fork_count": 3, "created_at": "2026-05-21 12:00:00"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Get Single Soul Details",
                            "request": {
                                "method": "GET",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": { 
                                    "raw": "{{baseUrl}}/api/soul/:id", 
                                    "host": ["{{baseUrl}}"], 
                                    "path": ["api", "soul", ":id"],
                                    "variable": [{ "key": "id", "value": "1" }]
                                }
                            },
                            "response": [
                                {
                                    "name": "Single MD Response Sample",
                                    "status": "OK",
                                    "code": 200,
                                    "_postman_previewlanguage": "json",
                                    "header": [{"key": "Content-Type", "value": "application/json"}],
                                    "body": JSON.stringify({"success": true, "data": {"id": 1, "user_id": 5, "title": "Expert Translator", "description": "Translates documents contextually", "content": "## Identity\nYou are an expert translator...", "file_type": "single_md", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet", "is_public": 1, "like_count": 12, "fork_count": 3, "created_at": "2026-05-21 12:00:00"}}, null, 2)
                                },
                                {
                                    "name": "Modular Folder Response Sample",
                                    "status": "OK",
                                    "code": 200,
                                    "_postman_previewlanguage": "json",
                                    "header": [{"key": "Content-Type", "value": "application/json"}],
                                    "body": JSON.stringify({"success": true, "data": {"id": 2, "user_id": 5, "title": "Advanced Dev Architecture", "description": "Full-stack code assistant package layout", "content": "{\n  \"SOUL.md\": \"## Identity\\nYou are a senior developer...\",\n  \"STYLE.md\": \"## Voice\\nConcise, code-heavy...\",\n  \"RULES.md\": \"## Hard Rules\\nNever write legacy code...\"\n}", "file_type": "full_soul_folder", "role": "Developer", "domain": "Coding & Dev", "compatibility": "GPT-4o", "is_public": 1, "like_count": 88, "fork_count": 15, "created_at": "2026-05-21 14:22:10"}}, null, 2)
                                }
                            ]
                        },
                        {
                            "name": "Publish New Soul",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"title": "Expert Translator", "description": "Translates documents contextually", "content": "## Identity\nYou are an expert...", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet"}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/souls", "host": ["{{baseUrl}}"], "path": ["api", "souls"] }
                            },
                            "response": [{
                                "name": "Creation Success",
                                "status": "Created",
                                "code": 201,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Soul created successfully", "id": 42, "url": "<?= $baseUrl ?>/soul/42"}, null, 2)
                            }]
                        },
                        {
                            "name": "Update Existing Soul",
                            "request": {
                                "method": "PUT",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"title": "Expert Translator v2", "description": "Updated translation engine", "content": "## Identity\nYou are...", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet", "is_public": 1}, null, 2)
                                },
                                "url": { 
                                    "raw": "{{baseUrl}}/api/soul/:id", 
                                    "host": ["{{baseUrl}}"], 
                                    "path": ["api", "soul", ":id"],
                                    "variable": [{ "key": "id", "value": "1" }]
                                }
                            },
                            "response": [{
                                "name": "Update Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Soul updated successfully"}, null, 2)
                            }]
                        },
                        {
                            "name": "Delete Soul",
                            "request": {
                                "method": "DELETE",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": { 
                                    "raw": "{{baseUrl}}/api/soul/:id", 
                                    "host": ["{{baseUrl}}"], 
                                    "path": ["api", "soul", ":id"],
                                    "variable": [{ "key": "id", "value": "1" }]
                                }
                            },
                            "response": [{
                                "name": "Deletion Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Soul deleted successfully"}, null, 2)
                            }]
                        }
                    ]
                },
                {
                    "name": "Profiles & Social Actions",
                    "item": [
                        {
                            "name": "Get User Profile Data",
                            "request": {
                                "method": "GET",
                                "header": [],
                                "url": {
                                    "raw": "{{baseUrl}}/api/profile?username=developer101",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "profile"],
                                    "query": [{"key": "username", "value": "developer101"}]
                                }
                            },
                            "response": [{
                                "name": "Profile Data Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "user": {"username": "developer101", "joined_at": "2026-05-20 10:00:00"}, "stats": {"total_souls": 5, "total_likes": 24, "total_forks": 8}, "souls": [{"id": 1, "title": "Expert Translator", "description": "Translates documents...", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet", "file_type": "single_md", "like_count": 12, "fork_count": 3, "created_at": "2026-05-21 12:00:00"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Get Soul History Versions",
                            "request": {
                                "method": "GET",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": {
                                    "raw": "{{baseUrl}}/api/versions?soul_id=1",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "versions"],
                                    "query": [{"key": "soul_id", "value": "1"}]
                                }
                            },
                            "response": [{
                                "name": "Timeline Versions Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "count": 1, "data": [{"id": 12, "soul_id": 1, "title": "Expert Translator v1", "content": "## Identity\nYou are...", "edited_at": "2026-05-21 15:30:00"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Restore Historical Version",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 1, "version_id": 5}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/versions", "host": ["{{baseUrl}}"], "path": ["api", "versions"] }
                            },
                            "response": [{
                                "name": "Rollback Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Version restored successfully"}, null, 2)
                            }]
                        },
                        {
                            "name": "Fork Public Soul",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 1}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/fork", "host": ["{{baseUrl}}"], "path": ["api", "fork"] }
                            },
                            "response": [{
                                "name": "Fork Clone Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "new_soul_id": 43, "url": "<?= $baseUrl ?>/soul/43", "message": "Soul forked successfully!"}, null, 2)
                            }]
                        },
                        {
                            "name": "Toggle Like Status",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 1}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/like", "host": ["{{baseUrl}}"], "path": ["api", "like"] }
                            },
                            "response": [{
                                "name": "Like Toggled Successfully",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "liked": true, "message": "Soul liked successfully"}, null, 2)
                            }]
                        },
                        {
                            "name": "Rate Soul (1-5 Stars)",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 1, "rating": 5}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/rate", "host": ["{{baseUrl}}"], "path": ["api", "rate"] }
                            },
                            "response": [{
                                "name": "Rating Saved Successfully",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Rating submitted successfully", "avg_rating": 4.5, "total_ratings": 18}, null, 2)
                            }]
                        }
                    ]
                }
            ],
            "variable": [
                { "key": "baseUrl", "value": "<?= $baseUrl ?>", "type": "string" },
                { "key": "apiKey", "value": currentApiKey, "type": "string" }
            ]
        };

        const jsonStr = JSON.stringify(collection, null, 2);
        const blob = new Blob([jsonStr], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement("a");
        a.href = url;
        a.download = "soulmd_hub.postman_collection.json";
        document.body.appendChild(a);
        a.click();
        
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>