<?php
/**
 * SoulMD Hub - Upload Page
 * Supports: Paste .md content + Upload .md file + Zip (full soul folder)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $role = $_POST['role'] ?? '';
    $domain = $_POST['domain'] ?? '';
    $compatibility = $_POST['compatibility'] ?? '';
    $content = '';
    $file_type = 'single_md';

    // Handle paste content
    if (!empty($_POST['content'])) {
        $content = $_POST['content'];
    }
    // Handle file upload (.md or .zip)
    elseif (!empty($_FILES['soul_file']['tmp_name'])) {
        $file = $_FILES['soul_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext === 'md') {
            $content = file_get_contents($file['tmp_name']);
        } elseif ($ext === 'zip') {
            $file_type = 'full_soul_folder';
            // Simple zip handling - store as JSON for now
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
                $error = '無法開啟 zip 檔案';
            }
        } else {
            $error = '只支援 .md 或 .zip 檔案';
        }
    }

    if (empty($error) && !empty($title) && !empty($content)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO souls 
                (title, description, content, file_type, role, domain, compatibility, is_public) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$title, $description, $content, $file_type, $role, $domain, $compatibility]);
            
            $newId = $pdo->lastInsertId();
            $message = "上傳成功！Soul ID: #{$newId} <a href='soul.php?id={$newId}' class='underline'>查看</a>";
        } catch (Exception $e) {
            $error = '儲存失敗: ' . $e->getMessage();
        }
    } elseif (empty($error)) {
        $error = '請填寫標題同內容';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-HK">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上傳 Soul - SoulMD Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-3xl mx-auto px-6 py-12">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold">上傳 Soul</h1>
            <a href="index.php" class="text-zinc-400 hover:text-white">← 返回首頁</a>
        </div>

        <?php if ($message): ?>
            <div class="bg-emerald-900/50 border border-emerald-500 p-4 rounded-2xl mb-6">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-900/50 border border-red-500 p-4 rounded-2xl mb-6">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <!-- Title -->
            <div>
                <label class="block text-sm font-medium mb-2">Soul 標題 *</label>
                <input type="text" name="title" required class="w-full bg-zinc-900 border border-white/20 rounded-2xl px-4 py-3 focus:outline-none focus:border-white">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium mb-2">簡短描述</label>
                <textarea name="description" rows="2" class="w-full bg-zinc-900 border border-white/20 rounded-2xl px-4 py-3"></textarea>
            </div>

            <!-- Metadata -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">角色 (Role)</label>
                    <select name="role" class="w-full bg-zinc-900 border border-white/20 rounded-2xl px-4 py-3">
                        <option value="">-- 選擇 --</option>
                        <option value="Developer">Developer</option>
                        <option value="Writer">Writer</option>
                        <option value="Business Analyst">Business Analyst</option>
                        <option value="Researcher">Researcher</option>
                        <option value="Creative">Creative</option>
                        <option value="Personal Assistant">Personal Assistant</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">領域 (Domain)</label>
                    <input type="text" name="domain" placeholder="Tech, Content, Business..." class="w-full bg-zinc-900 border border-white/20 rounded-2xl px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">相容 Model</label>
                    <input type="text" name="compatibility" placeholder="Claude, GPT-4o, All..." class="w-full bg-zinc-900 border border-white/20 rounded-2xl px-4 py-3">
                </div>
            </div>

            <!-- Content Input -->
            <div>
                <label class="block text-sm font-medium mb-3">Soul 內容 *</label>
                
                <!-- Tabs -->
                <div class="flex border-b border-white/20 mb-4">
                    <button type="button" onclick="showTab('paste')" class="tab-btn active px-6 py-2 text-sm font-medium border-b-2 border-white">貼上文字</button>
                    <button type="button" onclick="showTab('upload')" class="tab-btn px-6 py-2 text-sm font-medium text-zinc-400">上傳檔案</button>
                </div>

                <!-- Paste Tab -->
                <div id="paste-tab">
                    <textarea name="content" rows="12" placeholder="貼上 SOUL.md / STYLE.md 內容..." 
                              class="w-full bg-zinc-900 border border-white/20 rounded-2xl px-4 py-3 font-mono text-sm"></textarea>
                </div>

                <!-- Upload Tab -->
                <div id="upload-tab" class="hidden">
                    <div class="border-2 border-dashed border-white/30 rounded-3xl p-8 text-center">
                        <input type="file" name="soul_file" accept=".md,.zip" class="hidden" id="file-input">
                        <label for="file-input" class="cursor-pointer">
                            <div class="text-4xl mb-3">↓</div>
                            <div class="font-medium">拖放或點擊上傳 .md / .zip</div>
                            <div class="text-xs text-zinc-500 mt-1">支援單一 .md 或完整 soul/ 資料夾 (zip)</div>
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" 
                    class="w-full py-4 bg-white text-black font-semibold rounded-2xl hover:bg-zinc-200 transition">
                上傳 Soul
            </button>
        </form>
    </div>

    <script>
        function showTab(tab) {
            document.getElementById('paste-tab').classList.toggle('hidden', tab !== 'paste');
            document.getElementById('upload-tab').classList.toggle('hidden', tab !== 'upload');
            
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active', 'border-b-2', 'border-white'));
            event.target.classList.add('active', 'border-b-2', 'border-white');
        }
    </script>
</body>
</html>