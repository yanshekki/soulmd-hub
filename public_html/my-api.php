<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'];
$message = '';

// Regenerate API Key
if (isset($_POST['regenerate'])) {
    $newKey = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$newKey, $userId]);
    $message = '✅ API Key regenerated successfully!';
}

// Get current API Key
$stmt = $pdo->prepare("SELECT api_key FROM users WHERE id = ?");
$stmt->execute([$userId]);
$apiKey = $stmt->fetch()['api_key'] ?? null;

if (!$apiKey) {
    $apiKey = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$apiKey, $userId]);
}

// 自動讀取 config.php 的 BASE_URL，保持全站變數統一
$baseUrl = defined('BASE_URL') ? BASE_URL : ("https://" . $_SERVER['HTTP_HOST']);

$pageTitle = 'Developer API';
$pageDesc = 'Manage your API key and read integration docs for SoulMD Hub.';
require_once __DIR__ . '/../private/includes/header.php';
?>

<div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-8">
        <div>
            <a href="/my-souls" class="text-sm text-zinc-400 hover:text-emerald-400 flex items-center gap-2 mb-3 transition w-fit">
                <i class="fas fa-arrow-left"></i> Back to My Souls
            </a>
            <h1 class="text-4xl font-bold tracking-tighter">Developer API</h1>
            <p class="text-zinc-400 mt-2">Integrate SoulMD Hub programmatically. 100% API-Driven Architecture.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-8 text-sm text-emerald-100 shadow-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
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

                <form method="POST">
                    <button type="submit" name="regenerate" onclick="return confirm('Are you sure you want to roll your API Key? All applications using the old key will lose access immediately.')" class="w-full py-3 bg-zinc-800 hover:bg-red-500/20 text-zinc-300 hover:text-red-400 border border-white/5 hover:border-red-500/30 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fas fa-redo text-xs"></i> Roll API Key
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-white/10">
                    <h3 class="text-emerald-400 font-bold mb-2 flex items-center gap-2"><i class="fas fa-shield-alt"></i> Authentication</h3>
                    <p class="text-xs text-zinc-300 leading-relaxed mb-4">Pass your API key via the HTTP <code>Authorization</code> header for endpoints that require it.</p>
                    <div class="bg-zinc-950 border border-white/10 p-3 rounded-xl text-xs font-mono text-zinc-400 overflow-x-auto whitespace-nowrap">
                        Authorization: Bearer <span class="text-emerald-300">YOUR_API_KEY</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="xl:col-span-8 space-y-8">
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 md:p-8 backdrop-blur-sm shadow-xl">
                <h2 class="text-2xl font-bold mb-8 border-b border-white/10 pb-4">API Reference</h2>

                <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-10"><i class="fas fa-user-shield mr-2"></i> Authentication</h3>
                
                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/register</code>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Register a new user and generate an API key. Enforces secure alpha-numeric URL constraints.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"username": "...", "password": "...", "email": "..."}</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/login</code>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Authenticate user. Returns API Key and sets a secure 30-day web session if requested.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"username": "...", "password": "...", "remember": true}</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/change-password</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Change the current logged-in user's password password securely.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"current_password": "...", "new_password": "...", "confirm_password": "..."}</p>
                </div>

                <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-12"><i class="fas fa-brain mr-2"></i> Core Souls Hub</h3>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/souls</code>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">List, search and filter public souls. Optimized with strict DB select limits.</p>
                    <p class="text-xs text-zinc-500 font-mono">Query params: ?limit=20&offset=0&q=ai&sort=popular&role=Developer</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/soul/{id}</code>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Retrieve raw architecture files, tags, and stats of a single public or owned soul.</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/souls</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Publish a brand new AI agent. Automatically detects single .md prompt or full Modular configuration folders.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"title": "...", "content": "...", "description": "...", "role": "...", "domain": "...", "compatibility": "..."}</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 font-mono text-[10px] font-bold rounded border border-amber-500/30">PUT</span>
                        <code class="text-base font-bold text-white">/api/soul/{id}</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Update an existing soul module layout. Automatically creates an incremental version timeline backup record.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"title": "...", "content": "...", "description": "...", "is_public": 1, "domain": "..."}</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-red-500/20 text-red-400 font-mono text-[10px] font-bold rounded border border-red-500/30">DELETE</span>
                        <code class="text-base font-bold text-white">/api/soul/{id}</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Permanently delete a soul architecture configuration and gracefully updates relational metadata tracking statistics.</p>
                </div>

                <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-12"><i class="fas fa-code-branch mr-2"></i> Profiles & Social Interactions</h3>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/profile</code>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Fetch public indicators (aggregated likes, forks, total models) and public soul array mapping for any developer.</p>
                    <p class="text-xs text-zinc-500 font-mono">Query params: ?username=ysklimited</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                        <code class="text-base font-bold text-white">/api/versions</code>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Retrieve full historical rollback archive versions of a soul. Protected by strict IDOR multi-tenant permission validation check.</p>
                    <p class="text-xs text-zinc-500 font-mono">Query params: ?soul_id={id}</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/versions</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Instantly restore active state content layout to a historical milestone setup version identifier point.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"soul_id": 1, "version_id": 5}</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/fork</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Clone a public agent model directly into your workspace account as an independent project fork tree line node.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"soul_id": 1}</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/like</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Toggle like/unlike state. Enforces atomic uniqueness index mapping constraints. Returns boolean state indicating if currently liked.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"soul_id": 1}</p>
                </div>

                <div class="mb-10 border-l-2 border-zinc-800 pl-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                        <code class="text-base font-bold text-white">/api/rate</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-2">Rate between 1 to 5 stars. Submitting again overrides previous row entry record. Returns updated global live averages for instant interface refresh.</p>
                    <p class="text-xs text-zinc-500 font-mono">Body: {"soul_id": 1, "rating": 5}</p>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function copyKey(btn) {
        const key = document.getElementById('key-display').innerText;
        navigator.clipboard.writeText(key).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-emerald-400"></i>';
            setTimeout(() => btn.innerHTML = original, 2000);
        });
    }
</script>

<?php require_once __DIR__ . '/../private/includes/footer.php'; ?>