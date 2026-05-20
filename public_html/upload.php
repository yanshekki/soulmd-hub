<?php
require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

setSEO('Upload Soul', 'Upload your AI personality as .md or full soul folder.');

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

    if (empty($error) && !empty($title) && !empty($content)) {
        try {
            $fileType = strpos($content, '{') === 0 ? 'full_soul_folder' : 'single_md';
            $stmt = $pdo->prepare("INSERT INTO souls (user_id, title, description, content, file_type, role, domain, compatibility, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$_SESSION['user_id'], $title, $description, $content, $fileType, $role, $domain, $compatibility]);
            $newId = $pdo->lastInsertId();
            $message = "✅ Soul uploaded successfully! <a href='soul.php?id=$newId' class='underline text-emerald-400'>View it now</a>";
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
</head>
<body class="bg-zinc-950 text-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-bold tracking-tighter">Upload Soul</h1>
                <p class="text-zinc-400 mt-1">Share your AI personality with the world</p>
            </div>
            <a href="my-souls.php" class="text-sm text-zinc-400 hover:text-white flex items-center gap-1">
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
                <input type="text" id="title" name="title" required class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 text-lg focus:outline-none focus:border-emerald-400">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2 text-zinc-300">Short Description</label>
                <textarea id="description" name="description" rows="3" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">Role</label>
                    <select id="role" name="role" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
                        <option value="">Select role</option>
                        <option value="Developer">Developer</option>
                        <option value="Writer">Writer</option>
                        <option value="Business Analyst">Business Analyst</option>
                        <option value="Researcher">Researcher</option>
                        <option value="Creative">Creative</option>
                        <option value="Personal Assistant">Personal Assistant</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">Domain</label>
                    <input type="text" id="domain" name="domain" placeholder="Tech, Content..." class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-zinc-300">Compatibility</label>
                    <input type="text" id="compatibility" name="compatibility" placeholder="Claude, GPT-4o..." class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-4 focus:outline-none focus:border-emerald-400">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-3 text-zinc-300">Soul Content <span class="text-red-400">*</span></label>
                
                <div class="flex border-b border-white/20 mb-6">
                    <button type="button" onclick="switchTab(0)" class="tab-btn flex-1 py-4 text-sm font-medium border-b-2 border-white">Paste Text</button>
                    <button type="button" onclick="switchTab(1)" class="tab-btn flex-1 py-4 text-sm font-medium text-zinc-400">Upload File</button>
                </div>

                <div id="paste-tab" class="tab-content">
                    <textarea id="content" name="content" rows="14" class="w-full bg-zinc-900 border border-white/20 rounded-3xl px-6 py-5 font-mono text-sm focus:outline-none focus:border-emerald-400" placeholder="Paste your SOUL.md content here..."></textarea>
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
            const res = await fetch('upload.php', { method: 'POST', body: formData });
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