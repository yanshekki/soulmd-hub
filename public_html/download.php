<?php
/**
 * SoulMD Hub - Download & Raw File Handler
 * 支援單一檔案輸出、ZIP 打包、Cloudflare Edge Caching 以及多國語言檔名
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';

session_start();

$db = Database::getInstance();
$pdo = $db->getConnection();

$username = $_GET['username'] ?? '';
$soulId = (int)($_GET['id'] ?? 0);
$titleSlug = $_GET['title'] ?? '';
$format = $_GET['format'] ?? 'file'; 
$requestedFile = $_GET['file'] ?? 'SOUL.md'; 

if (!$soulId || !$username) {
    http_response_code(400);
    die('Invalid request parameters.');
}

$stmt = $pdo->prepare("
    SELECT s.*, u.username, 
           COALESCE((SELECT MAX(edited_at) FROM soul_versions v WHERE v.soul_id = s.id), s.created_at) as last_modified
    FROM souls s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ? AND u.username = ?
");
$stmt->execute([$soulId, $username]);
$soul = $stmt->fetch();

if (!$soul) {
    http_response_code(404);
    die('Soul not found.');
}

$isOwner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $soul['user_id']);
if (!$soul['is_public'] && !$isOwner) {
    http_response_code(403);
    die('Access denied. This soul is private.');
}

// 緩存控制
$lastModifiedTime = strtotime($soul['last_modified']);
$etag = '"' . md5($soulId . $lastModifiedTime . $format . $requestedFile) . '"';

header("Cache-Control: public, max-age=300, s-maxage=300");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModifiedTime) . " GMT");
header("Etag: {$etag}");

if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModifiedTime)
) {
    header("HTTP/1.1 304 Not Modified");
    exit;
}

$isFolder = $soul['file_type'] === 'full_soul_folder';
$filesData = [];

if ($isFolder) {
    $filesData = json_decode($soul['content'], true) ?: [];
} else {
    $filesData = ['SOUL.md' => $soul['content']];
}

if ($format === 'zip') {
    $tmpFile = tempnam(sys_get_temp_dir(), 'soul_zip_');
    $zip = new ZipArchive();
    
    if ($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE) {
        http_response_code(500);
        die('Could not create ZIP file.');
    }
    
    foreach ($filesData as $filename => $content) {
        $zip->addFromString($filename, $content);
    }
    $zip->close();
    
    $safeTitle = preg_replace('/[\/\\\:\*\?\"\<\>\|]/', '_', $soul['title']);
    
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $safeTitle . '.zip"; filename*=UTF-8\'\'' . rawurlencode($safeTitle) . '.zip');
    header('Content-Length: ' . filesize($tmpFile));
    
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

if (!isset($filesData[$requestedFile])) {
    http_response_code(404);
    die('File not found inside this soul.');
}

$fileContent = $filesData[$requestedFile];
$ext = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));

// 🚨 完美安全修復：拔除 text/html，強制所有不明檔案及前端代碼渲染為 text/plain，徹底封殺 Inline XSS 攻擊
if ($ext === 'md') {
    $mimeType = 'text/markdown';
} elseif ($ext === 'json') {
    $mimeType = 'application/json';
} else {
    $mimeType = 'text/plain'; 
}

$baseFilename = basename($requestedFile);

header('Content-Type: ' . $mimeType . '; charset=utf-8');
header('Content-Disposition: inline; filename="' . $baseFilename . '"; filename*=UTF-8\'\'' . rawurlencode($baseFilename));
header('Content-Length: ' . strlen($fileContent));

echo $fileContent;
exit;