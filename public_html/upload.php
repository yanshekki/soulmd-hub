<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// 從 MySQL 的 categories 資料表獲取真實分類數據 (Role)
$categories = $pdo->query("SELECT name, slug, icon FROM categories ORDER BY id ASC")->fetchAll();

// 檢查是否有從 AI Generator 帶過來的預設資料
$presetTitle = $_SESSION['preset_title'] ?? '';
$presetContent = $_SESSION['preset_content'] ?? '';
$presetRole = $_SESSION['preset_role'] ?? '';

// 智慧模糊匹配：將 generate.php 產生的具體職稱智慧對應到主分類 slug
if (!empty($presetRole)) {
    $matched = false;
    foreach ($categories as $cat) {
        if (strcasecmp($presetRole, $cat['name']) === 0 || strcasecmp($presetRole, $cat['slug']) === 0) {
            $presetRole = $cat['slug'];
            $matched = true;
            break;
        }
    }
    if (!$matched) {
        if (stripos($presetRole, 'Engineer') !== false || stripos($presetRole, 'Coder') !== false || stripos($presetRole, 'Developer') !== false) {
            $presetRole = 'Developer';
        } elseif (stripos($presetRole, 'Writer') !== false || stripos($presetRole, 'Copywriter') !== false) {
            $presetRole = 'Writer';
        } elseif (stripos($presetRole, 'Assistant') !== false) {
            $presetRole = 'Personal Assistant';
        } else {
            $presetRole = 'Other';
        }
    }
}

// 讀取後立刻清空，避免下次重新點進 upload 頁面時依然存在
unset($_SESSION['preset_title']);
unset($_SESSION['preset_content']);
unset($_SESSION['preset_role']);

setSEO('Upload Soul', 'Upload your AI personality as .md or full soul folder.');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $role = $_POST['role'] ?? '';
    $domain = trim($_POST['domain'] ?? ''); // 這裡接收到的會是用逗號分隔的字串
    $compatibility = trim($_POST['compatibility'] ?? ''); // 這裡接收到的會是用逗號分隔的字串
    $content = '';

    // 後端安全驗證：確保傳入的 role 存在於資料庫分類中，或者是 'Other'
    $validSlugs = array_column($categories, 'slug');
    if (!empty($role) && !in_array($role, $validSlugs) && $role !== 'Other') {
        $error = 'Invalid category/role selected.';
    }

    if (empty($error)) {
        if (!empty($_POST['content'])) {
            $content = $_POST['content'];
        } elseif (!empty($_FILES['soul_file']['tmp_name'])) {
            $file = $_FILES['soul_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($ext === 'md') {
                $content = file_get_contents($file['tmp_name']);
            } elseif ($ext === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($file['tmp_name']) === TRUE) {
                    $files = [];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        if (str_ends_with($filename, '.md')) {
                            $files[$filename] = $zip->getFromIndex($i);
                        }
                    }
                    $zip->close();
                    $content = json_encode($files, JSON_UNESCAPED_UNICODE);
                } else {
                    $error = 'Could not open zip file';
                }
            } else {
                $error = 'Only .md or .zip files are supported';
            }
        }
    }

    if (empty($error) && !empty($title) && !empty($content)) {
        try {
            $fileType = strpos($content, '{') === 0 ? 'full_soul_folder' : 'single_md';
            $stmt = $pdo->prepare("INSERT INTO souls (user_id, title, description, content, file_type, role, domain, compatibility, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $content, $fileType, $role, $domain, $compatibility]);
            $newId = $pdo->lastInsertId();
            $message = "✅ Soul uploaded successfully! <a href='soul/$newId' class='underline text-emerald-400'>View it now</a>";
        } catch (Exception $e) {
            $error = 'Failed to save soul';
        }
    } elseif (empty($error)) {
        $error = 'Title and content are required';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Soul - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* 隱藏預設 input 框的邊框，讓它完美融入 Tag 容器 */
        .tag-input-field:focus {
            outline: none !important;
            box-shadow: none !important;
        }
        /* 修正部分瀏覽器 datalist 下拉選單在暗色模式下的原生 UI 樣式問題 */
        ::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold tracking-tighter">Upload Soul</h1>
                <p class="text-zinc-400 mt-1">Share your AI personality with the world</p>
            </div>
            <a href="my-souls" class="text-sm text-zinc-400 hover:text-white flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> My Souls
            </a>
        </div>

        <?php if ($message): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 p-6 rounded-3xl mb-8 text-lg">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-500 p-6 rounded-3xl mb-8">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form id="upload-form" enctype="multipart/form-data" class="space-y-8">
            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Soul Title <span class="text-red-400">*</span></label>
                <input type="text" id="title" name="title" required value="<?= htmlspecialchars($_POST['title'] ?? $presetTitle) ?>" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 text-lg focus:outline-none focus:border-emerald-400">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Short Description</label>
                <textarea id="description" name="description" rows="3" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">Role</label>
                    <select id="role" name="role" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-5 py-4 focus:outline-none focus:border-emerald-400">
                        <?php $selectedRole = $_POST['role'] ?? $presetRole; ?>
                        <option value="">Select role</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $selectedRole === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['icon'] ?? '✨') ?> <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="Other" <?= $selectedRole === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">Domain</label>
                    <div class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-4 py-3 min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition cursor-text" onclick="document.getElementById('domain-input').focus()">
                        <div id="domain-tags" class="flex flex-wrap gap-2 empty:hidden"></div>
                        <input type="text" id="domain-input" list="domain-options" placeholder="Tech, Content..." class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[100px] text-sm p-0 m-0 text-white">
                        <input type="hidden" id="domain" name="domain" value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>">
                    </div>
                    <datalist id="domain-options">
                        <option value="Tech">
                        <option value="Content Creation">
                        <option value="Finance & Business">
                        <option value="Coding & Dev">
                        <option value="Gaming">
                        <option value="Education">
                        <option value="Marketing">
                        <option value="Productivity">
                        <option value="Healthcare">
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">Compatibility</label>
                    <div class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-4 py-3 min-h-[58px] flex flex-wrap items-center gap-2 focus-within:border-emerald-400 transition cursor-text" onclick="document.getElementById('compatibility-input').focus()">
                        <div id="compatibility-tags" class="flex flex-wrap gap-2 empty:hidden"></div>
                        <input type="text" id="compatibility-input" list="compatibility-options" placeholder="Claude, GPT-4o..." class="tag-input-field flex-1 bg-transparent border-none focus:ring-0 min-w-[100px] text-sm p-0 m-0 text-white">
                        <input type="hidden" id="compatibility" name="compatibility" value="<?= htmlspecialchars($_POST['compatibility'] ?? '') ?>">
                    </div>
                    <datalist id="compatibility-options">
                        <option value="Claude 3.5 Sonnet">
                        <option value="GPT-4o">
                        <option value="GPT-4">
                        <option value="Gemini 1.5 Pro">
                        <option value="DeepSeek-V3">
                        <option value="Llama 3">
                        <option value="Qwen 2.5">
                        <option value="General LLM">
                    </datalist>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-3 text-zinc-300">Soul Content <span class="text-red-400">*</span></label>
                
                <div class="flex border-b border-white/20 mb-6">
                    <button type="button" onclick="switchTab(0)" class="tab-btn flex-1 py-4 text-sm font-medium border-b-2 border-white">Paste Text</button>
                    <button type="button" onclick="switchTab(1)" class="tab-btn flex-1 py-4 text-sm font-medium text-zinc-400">Upload File</button>
                </div>

                <div id="paste-tab" class="tab-content">
                    <textarea id="content" name="content" rows="14" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-5 font-mono text-sm focus:outline-none focus:border-emerald-400" placeholder="Paste your SOUL.md content here..."><?= htmlspecialchars($_POST['content'] ?? $presetContent) ?></textarea>
                </div>

                <div id="upload-tab" class="tab-content hidden">
                    <div onclick="document.getElementById('file-input').click()" class="border-2 border-dashed border-white/30 rounded-3xl p-12 text-center hover:border-emerald-400 transition cursor-pointer">
                        <input type="file" id="file-input" name="soul_file" accept=".md,.zip" class="hidden">
                        <i class="fas fa-cloud-upload-alt text-5xl mb-4 text-zinc-400"></i>
                        <div class="font-medium text-lg">Drag & drop or click to upload</div>
                        <div class="text-xs text-zinc-400 mt-2">.md or .zip (full soul folder)</div>
                    </div>
                </div>
            </div>

            <button type="submit" id="submit-btn" class="w-full py-6 bg-white text-black font-semibold text-xl rounded-3xl hover:bg-zinc-200 transition flex items-center justify-center gap-3">
                <span id="submit-text">Upload Soul</span>
                <span id="submit-loading" class="hidden animate-spin h-5 w-5 border-2 border-black border-t-transparent rounded-full"></span>
            </button>
        </form>
    </div>

    <script>
        // === 標籤輸入 (Tag Input) 核心邏輯 ===
        function setupTagInput(inputId) {
            const hiddenInput = document.getElementById(inputId); // 最終送出的 <input type="hidden">
            const visibleInput = document.getElementById(inputId + '-input'); // 用戶打字的輸入框
            const tagsContainer = document.getElementById(inputId + '-tags'); // 裝標籤的 DIV
            
            // 讀取已經存在的標籤 (例如上傳失敗重新載入時)
            let tags = hiddenInput.value ? hiddenInput.value.split(',').map(t => t.trim()).filter(Boolean) : [];

            // 渲染標籤到畫面上
            const renderTags = () => {
                tagsContainer.innerHTML = '';
                tags.forEach((tag, index) => {
                    const tagEl = document.createElement('span');
                    tagEl.className = 'inline-flex items-center gap-1.5 bg-emerald-900/40 text-emerald-400 px-3 py-1 rounded-full text-xs font-medium border border-emerald-500/20';
                    tagEl.innerHTML = `${tag} <button type="button" class="hover:text-white focus:outline-none" onclick="removeTag('${inputId}', ${index})"><i class="fas fa-times"></i></button>`;
                    tagsContainer.appendChild(tagEl);
                });
                
                // 更新隱藏的 input 值 (用逗號隔開)
                hiddenInput.value = tags.join(', ');
                
                // 控制 placeholder 顯示
                if (tags.length > 0) {
                    visibleInput.placeholder = '';
                } else {
                    visibleInput.placeholder = inputId === 'domain' ? 'Tech, Content...' : 'Claude, GPT-4o...';
                }
            };

            // 新增標籤邏輯
            const addTag = (val) => {
                // 如果用戶一次貼上多個用逗號隔開的字詞，自動切開
                const newTags = val.split(',').map(t => t.trim()).filter(Boolean);
                newTags.forEach(t => {
                    if (!tags.includes(t)) tags.push(t); // 避免重複
                });
                visibleInput.value = '';
                renderTags();
            };

            // 1. 當用戶點擊 datalist 的選項時觸發
            visibleInput.addEventListener('change', function() {
                addTag(this.value);
            });

            // 2. 當用戶按下逗號 (,)、Enter 鍵 或 退格鍵 (Backspace) 時觸發
            visibleInput.addEventListener('keydown', function(e) {
                if (e.key === ',' || e.key === 'Enter') {
                    e.preventDefault(); // 防止送出表單
                    addTag(this.value);
                } else if (e.key === 'Backspace' && this.value === '' && tags.length > 0) {
                    tags.pop(); // 若輸入框是空的，按下退格鍵會刪除最後一個標籤
                    renderTags();
                }
            });

            // 3. 表單送出時，確保輸入框還沒按下 Enter 的殘留文字也被加進去
            visibleInput.closest('form').addEventListener('submit', function() {
                if (visibleInput.value.trim()) {
                    addTag(visibleInput.value);
                }
            });

            renderTags();
        }

        // 移除標籤的全域函式
        window.removeTag = function(inputId, index) {
            const hiddenInput = document.getElementById(inputId);
            let tags = hiddenInput.value.split(',').map(t => t.trim()).filter(Boolean);
            tags.splice(index, 1);
            hiddenInput.value = tags.join(', ');
            
            // 重新呼叫該輸入框的 render 邏輯 (簡化寫法)
            const visibleInput = document.getElementById(inputId + '-input');
            visibleInput.focus();
            
            // 觸發重新渲染
            const tagsContainer = document.getElementById(inputId + '-tags');
            tagsContainer.innerHTML = '';
            tags.forEach((tag, idx) => {
                const tagEl = document.createElement('span');
                tagEl.className = 'inline-flex items-center gap-1.5 bg-emerald-900/40 text-emerald-400 px-3 py-1 rounded-full text-xs font-medium border border-emerald-500/20';
                tagEl.innerHTML = `${tag} <button type="button" class="hover:text-white focus:outline-none" onclick="removeTag('${inputId}', ${idx})"><i class="fas fa-times"></i></button>`;
                tagsContainer.appendChild(tagEl);
            });
            visibleInput.placeholder = tags.length > 0 ? '' : (inputId === 'domain' ? 'Tech, Content...' : 'Claude, GPT-4o...');
        };

        // 初始化 Domain 和 Compatibility 的 Tag Input
        setupTagInput('domain');
        setupTagInput('compatibility');


        // === 上傳與分頁切換邏輯 ===
        function switchTab(n) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(['paste-tab', 'upload-tab'][n]).classList.remove('hidden');
            document.querySelectorAll('.tab-btn').forEach((btn, i) => {
                btn.classList.toggle('border-b-2', i === n);
                btn.classList.toggle('border-white', i === n);
                btn.classList.toggle('text-zinc-400', i !== n);
            });
        }

        const form = document.getElementById('upload-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submit-btn');
            const text = document.getElementById('submit-text');
            const loading = document.getElementById('submit-loading');

            text.classList.add('hidden');
            loading.classList.remove('hidden');

            const formData = new FormData(form);
            const res = await fetch('upload', { method: 'POST', body: formData });
            const html = await res.text();
            document.body.innerHTML = html;
        });

        document.getElementById('file-input').addEventListener('change', function() {
            if (this.files.length) {
                document.getElementById('upload-tab').innerHTML = `
                    <div class="text-emerald-400 flex items-center justify-center gap-3 py-8">
                        <i class="fas fa-check-circle text-3xl"></i>
                        <span class="font-medium">${this.files[0].name}</span>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>