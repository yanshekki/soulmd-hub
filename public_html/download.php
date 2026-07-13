<?php
/**
 * SoulMD Hub - Download & Raw File Handler
 * (Web2.5 AgentFi Edition)
 * 🚀 Patched: Integrated Web3 Ownership (NFT) Authorization Checks for Secure Downloads
 */

require_once __DIR__ . '/../private/src/AppBootstrap.php';

$app = AppBootstrap::forPage([
    'translations' => 'download',
    'csrf' => false,
    'db' => true,
    'require_login' => false,
    'seo' => false,
]);

$pdo = $app['pdo'];

$username = $_GET['username'] ?? '';
$soulId = (int)($_GET['id'] ?? 0);
$titleSlug = $_GET['title'] ?? '';
$format = $_GET['format'] ?? 'file'; 
$requestedFile = $_GET['file'] ?? 'SOUL.md'; 

if (!$soulId || !$username) {
    http_response_code(400);
    die(__('invalid_params'));
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
    die(__('soul_not_found'));
}

// ==========================================
// 🚨 完美修復：Web2 與 Web3 雙軌權限驗證
// ==========================================
$userId = $_SESSION['user_id'] ?? 0;
$isOwner = ($userId > 0 && $userId === $soul['user_id']);
$isChainOwner = false;

if ($userId > 0 && $soul['is_nft'] == 1) {
    $wStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
    $wStmt->execute([$userId]);
    $currentUserWallet = $wStmt->fetchColumn();
    
    if (!empty($currentUserWallet) && $currentUserWallet === $soul['nft_owner_wallet']) {
        $isChainOwner = true;
    }
}

// 🛡️ 只有 公開模型、Web2 原作者，或 Web3 錢包持有者 才能下載源碼
if (!$soul['is_public'] && !$isOwner && !$isChainOwner) {
    http_response_code(403);
    die(__('access_denied'));
}
// ==========================================

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

// 🚨 完美 JSON 容錯修復機制：清洗 AI 生成的非法 \' (Single Quote Escape)
if ($isFolder) {
    $cleanedContent = str_replace("\\'", "'", $soul['content']);
    $filesData = json_decode($cleanedContent, true);
    
    // 如果爛到連修復完都解讀唔到，就輸出為 ERROR.md
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($filesData)) {
        $filesData = ['ERROR.md' => __('invalid_json') . "\n\n" . __('raw_content') . "\n" . $soul['content']];
    }
} else {
    $filesData = ['SOUL.md' => $soul['content']];
}

// ==========================================
// 輸出模式 1：打包為 ZIP 下載
// ==========================================
if ($format === 'zip') {
    $tmpFile = tempnam(sys_get_temp_dir(), 'soul_zip_');
    $zip = new ZipArchive();
    
    if ($zip->open($tmpFile, ZipArchive::CREATE) !== TRUE) {
        http_response_code(500);
        die(__('zip_create_failed'));
    }
    
    foreach ($filesData as $filename => $content) {
        // 🚨 完美安全修復 1：過濾 ../ 同 ..\ 防止 Zip Slip (目錄穿越攻擊)
        $safeFileName = preg_replace('/\.+[\/\\\]+/', '', $filename);
        $safeFileName = ltrim($safeFileName, '/\\');
        
        $strContent = is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        $zip->addFromString($safeFileName, $strContent);
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

// ==========================================
// 輸出模式 2：單一檔案 Raw 輸出
// ==========================================
if (!isset($filesData[$requestedFile])) {
    http_response_code(404);
    die(__('file_not_found'));
}

// 強制轉為字串
$fileContent = is_string($filesData[$requestedFile]) ? $filesData[$requestedFile] : json_encode($filesData[$requestedFile], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$ext = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));

// 🚨 完美安全修復 2：拔除 text/html，強制所有不明檔案及前端代碼渲染為 text/plain，徹底封殺 Inline XSS 攻擊
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