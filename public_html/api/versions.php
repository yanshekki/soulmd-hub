<?php
/**
 * SoulMD Hub Public API
 * GET  /api/versions?soul_id={id}&page={page} - List versions (Paginated)
 * POST /api/versions - Restore a specific version (Requires Auth)
 * (100% Dynamic i18n Internationalized Error Stack & Paginated Edition)
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

// 🌍 載入後端 API 全域專屬語言包
loadTranslations('api');

$db = Database::getInstance();
$pdo = $db->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// ==========================================
// 權限助手函數
// ==========================================
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

// ==========================================
// 路由處理
// ==========================================
if ($method === 'GET') {
    // 獲取歷史版本列表 (Paginated)
    $soulId = (int)($_GET['soul_id'] ?? 0);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min((int)($_GET['limit'] ?? 10), 50); 
    
    if (!$soulId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('soul_id required')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 🚨 安全修復：檢查該 Soul 是否公開，或者請求者是否為作者本人
    $userId = getAuthUserId($pdo);
    $checkStmt = $pdo->prepare("SELECT is_public, user_id FROM souls WHERE id = ?");
    $checkStmt->execute([$soulId]);
    $soulCheck = $checkStmt->fetch();

    if (!$soulCheck) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$soulCheck['is_public'] && $soulCheck['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Access Denied Private')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 🚀 分頁計算
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM soul_versions WHERE soul_id = ?");
    $countStmt->execute([$soulId]);
    $totalCount = (int)$countStmt->fetchColumn();
    
    $totalPages = max(1, ceil($totalCount / $limit));
    $offset = ($page - 1) * $limit;

    $stmt = $pdo->prepare("SELECT id, soul_id, title, content, edited_at FROM soul_versions WHERE soul_id = ? ORDER BY edited_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $soulId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $versions = $stmt->fetchAll();

    echo json_encode([
        'success' => true, 
        'count' => count($versions), 
        'total_count' => $totalCount,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'data' => $versions
    ], JSON_UNESCAPED_UNICODE);

} elseif ($method === 'POST') {
    // 還原歷史版本 (Restore Logic)
    $userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $versionId = (int)($input['version_id'] ?? 0);
    $soulId = (int)($input['soul_id'] ?? 0);

    if (!$versionId || !$soulId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('version_id and soul_id required')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 確認用戶擁有該 Soul
    $stmt = $pdo->prepare("SELECT title, content FROM souls WHERE id = ? AND user_id = ?");
    $stmt->execute([$soulId, $userId]);
    $soul = $stmt->fetch();

    if (!$soul) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Soul not found or access denied')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 取得目標版本內容
    $vStmt = $pdo->prepare("SELECT title, content FROM soul_versions WHERE id = ? AND soul_id = ?");
    $vStmt->execute([$versionId, $soulId]);
    $version = $vStmt->fetch();

    if (!$version) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Version not found')], JSON_UNESCAPED_UNICODE);
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
        echo json_encode(['success' => true, 'message' => __('Version restored successfully')], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Restore failed')], JSON_UNESCAPED_UNICODE);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
}
?>