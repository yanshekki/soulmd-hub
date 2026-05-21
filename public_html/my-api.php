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

$baseUrl = "https://" . $_SERVER['HTTP_HOST'];

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
            <p class="text-zinc-400 mt-2">Manage your authentication key and integrate SoulMD Hub programmatically.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-8 text-sm text-emerald-100 shadow-lg flex items-center gap-2">
            <i class="fas fa-check-circle"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <div class="xl:col-span-4 space-y-6">
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 backdrop-blur-sm shadow-xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
                <h3 class="text-lg font-bold mb-1">Your Secret Key</h3>
                <p class="text-xs text-zinc-400 mb-6 leading-relaxed">This key grants full access to create and interact with souls on your behalf. Keep it secure.</p>
                
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
            </div>

            <div class="bg-zinc-900/60 border border-emerald-500/20 rounded-3xl p-6 backdrop-blur-sm shadow-xl">
                <h3 class="text-emerald-400 font-bold mb-2 flex items-center gap-2"><i class="fas fa-shield-alt"></i> Authentication</h3>
                <p class="text-xs text-zinc-300 leading-relaxed mb-4">Pass your API key via the HTTP <code>Authorization</code> header for endpoints that require it.</p>
                <div class="bg-zinc-950 border border-white/10 p-3 rounded-xl text-xs font-mono text-zinc-400 overflow-x-auto whitespace-nowrap">
                    Authorization: Bearer <span class="text-emerald-300">YOUR_API_KEY</span>
                </div>
            </div>
        </div>

        <div class="xl:col-span-8 space-y-8">
            <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 md:p-8 backdrop-blur-sm shadow-xl">
                <h2 class="text-2xl font-bold mb-8 border-b border-white/10 pb-4">API Reference</h2>

                <div class="mb-12 border-b border-white/5 pb-10">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-400 font-mono text-xs font-bold rounded-lg border border-blue-500/30">GET</span>
                        <code class="text-lg font-bold text-white">/api/souls</code>
                    </div>
                    <p class="text-sm text-zinc-400 mb-4">List and search public souls. No API Key required.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Query Parameters</h4>
                            <ul class="text-xs text-zinc-400 space-y-2">
                                <li><code class="text-emerald-300">limit</code> (int) Max 100. Default 20.</li>
                                <li><code class="text-emerald-300">offset</code> (int) Pagination offset.</li>
                                <li><code class="text-emerald-300">q</code> (string) Search keyword.</li>
                                <li><code class="text-emerald-300">role</code> (string) Filter by role slug.</li>
                                <li><code class="text-emerald-300">file_type</code> (string) 'single_md' or 'full_soul_folder'.</li>
                            </ul>
                        </div>
                        <div class="bg-zinc-950 border border-white/5 rounded-2xl overflow-hidden">
                            <div class="bg-white/5 px-4 py-2 border-b border-white/5 text-[10px] text-zinc-500 font-mono">cURL Example</div>
                            <pre class="p-4 text-xs font-mono text-zinc-300 overflow-x-auto whitespace-pre">curl -X GET "<?= $baseUrl ?>/api/souls?limit=5"</pre>
                        </div>
                    </div>
                </div>

                <div class="mb-12 border-b border-white/5 pb-10">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-400 font-mono text-xs font-bold rounded-lg border border-blue-500/30">GET</span>
                        <code class="text-lg font-bold text-white">/api/soul/{id}</code>
                    </div>
                    <p class="text-sm text-zinc-400 mb-4">Retrieve details and raw content of a specific public soul. No API Key required.</p>
                    
                    <div class="bg-zinc-950 border border-white/5 rounded-2xl overflow-hidden">
                        <div class="bg-white/5 px-4 py-2 border-b border-white/5 text-[10px] text-zinc-500 font-mono">cURL Example</div>
                        <pre class="p-4 text-xs font-mono text-zinc-300 overflow-x-auto whitespace-pre">curl -X GET "<?= $baseUrl ?>/api/soul/1"</pre>
                    </div>
                </div>

                <div class="mb-12 border-b border-white/5 pb-10">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 font-mono text-xs font-bold rounded-lg border border-emerald-500/30">POST</span>
                        <code class="text-lg font-bold text-white">/api/souls</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20"><i class="fas fa-lock text-[8px]"></i> Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-4">Publish a new AI soul to your account.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">JSON Body</h4>
                            <ul class="text-xs text-zinc-400 space-y-2">
                                <li><code class="text-emerald-300">title*</code> (string) Required.</li>
                                <li><code class="text-emerald-300">content*</code> (string) Required. Markdown or JSON folder string.</li>
                                <li><code class="text-white">description</code> (string)</li>
                                <li><code class="text-white">role</code> (string) Valid role slug.</li>
                                <li><code class="text-white">domain</code> (string) Comma-separated tags.</li>
                                <li><code class="text-white">compatibility</code> (string) Comma-separated tags.</li>
                            </ul>
                        </div>
                        <div class="bg-zinc-950 border border-white/5 rounded-2xl overflow-hidden">
                            <div class="bg-white/5 px-4 py-2 border-b border-white/5 text-[10px] text-zinc-500 font-mono">cURL Example</div>
                            <pre class="p-4 text-xs font-mono text-zinc-300 overflow-x-auto whitespace-pre">curl -X POST "<?= $baseUrl ?>/api/souls" \
  -H "Authorization: Bearer <?= $apiKey ?>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "My API Agent",
    "content": "## Core\nBe helpful."
  }'</pre>
                        </div>
                    </div>
                </div>

                <div class="mb-12 border-b border-white/5 pb-10">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 font-mono text-xs font-bold rounded-lg border border-emerald-500/30">POST</span>
                        <code class="text-lg font-bold text-white">/api/fork</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20"><i class="fas fa-lock text-[8px]"></i> Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-4">Fork an existing public soul into your own account.</p>

                    <div class="bg-zinc-950 border border-white/5 rounded-2xl overflow-hidden">
                        <div class="bg-white/5 px-4 py-2 border-b border-white/5 text-[10px] text-zinc-500 font-mono">cURL Example</div>
                        <pre class="p-4 text-xs font-mono text-zinc-300 overflow-x-auto whitespace-pre">curl -X POST "<?= $baseUrl ?>/api/fork" \
  -H "Authorization: Bearer <?= $apiKey ?>" \
  -H "Content-Type: application/json" \
  -d '{ "soul_id": 1 }'</pre>
                    </div>
                </div>

                <div class="mb-12 border-b border-white/5 pb-10">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 font-mono text-xs font-bold rounded-lg border border-emerald-500/30">POST</span>
                        <code class="text-lg font-bold text-white">/api/like</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20"><i class="fas fa-lock text-[8px]"></i> Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-4">Like a specific soul to increase its popularity.</p>

                    <div class="bg-zinc-950 border border-white/5 rounded-2xl overflow-hidden">
                        <div class="bg-white/5 px-4 py-2 border-b border-white/5 text-[10px] text-zinc-500 font-mono">cURL Example</div>
                        <pre class="p-4 text-xs font-mono text-zinc-300 overflow-x-auto whitespace-pre">curl -X POST "<?= $baseUrl ?>/api/like" \
  -H "Authorization: Bearer <?= $apiKey ?>" \
  -H "Content-Type: application/json" \
  -d '{ "soul_id": 1 }'</pre>
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 font-mono text-xs font-bold rounded-lg border border-emerald-500/30">POST</span>
                        <code class="text-lg font-bold text-white">/api/rate</code>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20"><i class="fas fa-lock text-[8px]"></i> Auth Required</span>
                    </div>
                    <p class="text-sm text-zinc-400 mb-4">Rate a soul between 1 to 5 stars. Submitting again overwrites your previous rating.</p>

                    <div class="bg-zinc-950 border border-white/5 rounded-2xl overflow-hidden">
                        <div class="bg-white/5 px-4 py-2 border-b border-white/5 text-[10px] text-zinc-500 font-mono">cURL Example</div>
                        <pre class="p-4 text-xs font-mono text-zinc-300 overflow-x-auto whitespace-pre">curl -X POST "<?= $baseUrl ?>/api/rate" \
  -H "Authorization: Bearer <?= $apiKey ?>" \
  -H "Content-Type: application/json" \
  -d '{ "soul_id": 1, "rating": 5 }'</pre>
                    </div>
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