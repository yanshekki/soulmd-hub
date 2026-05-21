<?php
/**
 * SoulMD Hub Public API
 * GET  /api/versions?soul_id={id} - List all versions of a specific soul
 * POST /api/versions              - Restore a specific version (Requires Auth)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// 權限助手函數
function getAuthUserId($pdo) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $apiKey = trim(str_replace('Bearer', '', $authHeader));
    if ($apiKey) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        if ($user = $stmt->fetch()) return $user['id'];
    } else {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_id'])) return $_SESSION['user_id'];
    }
    return null;
}

if ($method === 'GET') {
    // 獲取歷史版本列表
    $soulId = (int)($_GET['soul_id'] ?? 0);
    if (!$soulId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'soul_id is required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, soul_id, title, content, edited_at FROM soul_versions WHERE soul_id = ? ORDER BY edited_at DESC");
    $stmt->execute([$soulId]);
    $versions = $stmt->fetchAll();

    echo json_encode(['success' => true, 'count' => count($versions), 'data' => $versions], JSON_UNESCAPED_UNICODE);

} elseif ($method === 'POST') {
    // 還原歷史版本
    $userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Valid Session or API Key required.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $versionId = (int)($input['version_id'] ?? 0);
    $soulId = (int)($input['soul_id'] ?? 0);

    if (!$versionId || !$soulId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'version_id and soul_id are required']);
        exit;
    }

    // 確認用戶擁有該 Soul
    $stmt = $pdo->prepare("SELECT title, content FROM souls WHERE id = ? AND user_id = ?");
    $stmt->execute([$soulId, $userId]);
    $soul = $stmt->fetch();

    if (!$soul) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Soul not found or access denied.']);
        exit;
    }

    // 取得目標版本內容
    $vStmt = $pdo->prepare("SELECT title, content FROM soul_versions WHERE id = ? AND soul_id = ?");
    $vStmt->execute([$versionId, $soulId]);
    $version = $vStmt->fetch();

    if (!$version) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Version not found.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 將當前狀態存入歷史備份
        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$soulId, $soul['title'], $soul['content']]);

        // 還原目標版本
        $fileType = strpos(trim($version['content']), '{') === 0 ? 'full_soul_folder' : 'single_md';
        $pdo->prepare("UPDATE souls SET title = ?, content = ?, file_type = ? WHERE id = ? AND user_id = ?")
            ->execute([$version['title'], $version['content'], $fileType, $soulId, $userId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Version restored successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Restore failed']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}