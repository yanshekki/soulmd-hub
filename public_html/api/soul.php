<?php
/**
 * SoulMD Hub Public API
 * GET    /api/soul/{id} - Get single soul details
 * PUT    /api/soul/{id} - Update a soul (Requires Auth: Session or API Key)
 * DELETE /api/soul/{id} - Delete a soul (Requires Auth: Session or API Key)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID is required in the URL']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ==========================================
// 權限助手函數：獲取當前用戶 ID (API Key 或 Session)
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
// 標籤同步助手函數
// ==========================================
function syncTags($pdo, $table, $oldStr, $newStr) {
    $oldTags = array_filter(array_map('trim', explode(',', $oldStr)));
    $newTags = array_filter(array_map('trim', explode(',', $newStr)));

    $added = array_diff($newTags, $oldTags);
    $removed = array_diff($oldTags, $newTags);

    foreach ($added as $tag) {
        if(empty($tag)) continue;
        $pdo->prepare("INSERT INTO {$table} (name, usage_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1")->execute([$tag]);
    }
    foreach ($removed as $tag) {
        if(empty($tag)) continue;
        $pdo->prepare("UPDATE {$table} SET usage_count = GREATEST(usage_count - 1, 0) WHERE name = ?")->execute([$tag]);
    }
}

// ==========================================
// 路由處理
// ==========================================
if ($method === 'GET') {
    // 獲取 Soul 詳情
    $userId = getAuthUserId($pdo);
    
    $stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ?");
    $stmt->execute([$id]);
    $soul = $stmt->fetch();

    if (!$soul) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Soul not found']);
        exit;
    }

    // 檢查私有權限
    if (!$soul['is_public'] && $soul['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied. This soul is private.']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $soul], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} elseif ($method === 'PUT') {
    // 修改 Soul
    $userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Valid Session or API Key required.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON payload']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT title, description, content, role, domain, compatibility, is_public FROM souls WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $old = $stmt->fetch();

    if (!$old) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Soul not found or you do not have permission to edit it.']);
        exit;
    }

    // 🚨 完美修復：實現真正的 Partial Update (局部更新)，沒傳入的欄位自動沿用資料庫舊值
    $title = isset($input['title']) ? trim($input['title']) : $old['title'];
    $description = isset($input['description']) ? trim($input['description']) : ($old['description'] ?? '');
    $content = isset($input['content']) ? $input['content'] : $old['content'];
    $role = isset($input['role']) ? $input['role'] : ($old['role'] ?? '');
    $domain = isset($input['domain']) ? trim($input['domain']) : ($old['domain'] ?? '');
    $compatibility = isset($input['compatibility']) ? trim($input['compatibility']) : ($old['compatibility'] ?? '');
    $is_public = isset($input['is_public']) ? (int)$input['is_public'] : (int)$old['is_public'];

    if (empty($title) || empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Title and content cannot be empty']);
        exit;
    }

    $fileType = strpos(trim($content), '{') === 0 ? 'full_soul_folder' : 'single_md';

    try {
        $pdo->beginTransaction();

        // 備份舊版本至 History
        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$id, $old['title'], $old['content']]);

        // 更新當前 Soul
        $updStmt = $pdo->prepare("UPDATE souls SET title = ?, description = ?, content = ?, role = ?, domain = ?, compatibility = ?, is_public = ?, file_type = ? WHERE id = ?");
        $updStmt->execute([$title, $description, $content, $role, $domain, $compatibility, $is_public, $fileType, $id]);

        // 更新標籤統計
        syncTags($pdo, 'tags_domain', $old['domain'], $domain);
        syncTags($pdo, 'tags_compatibility', $old['compatibility'], $compatibility);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Soul updated successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update soul']);
    }

} elseif ($method === 'DELETE') {
    // 刪除 Soul
    $userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Valid Session or API Key required.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT domain, compatibility FROM souls WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $old = $stmt->fetch();

    if (!$old) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Soul not found or you do not have permission to delete it.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM souls WHERE id = ?")->execute([$id]);

        // 清除標籤統計
        syncTags($pdo, 'tags_domain', $old['domain'], '');
        syncTags($pdo, 'tags_compatibility', $old['compatibility'], '');

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Soul deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete soul']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}