<?php
/**
 * SoulMD Hub Public API
 * GET    /api/soul/{id} - Get single soul details
 * PUT    /api/soul/{id} - Update a soul (Requires Auth: Session or API Key, with JSON Auto-Fix)
 * DELETE /api/soul/{id} - Delete a soul (Requires Auth: Session or API Key)
 * (100% Dynamic i18n Internationalized Error Stack & UNESCAPED Edition + Web3 Hash)
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

loadTranslations('api');

$db = Database::getInstance();
$pdo = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

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

if ($method === 'GET') {
    $userId = getAuthUserId($pdo);
    
    $stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ?");
    $stmt->execute([$id]);
    $soul = $stmt->fetch();

    if (!$soul) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$soul['is_public'] && $soul['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Access Denied Private')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $soul], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} elseif ($method === 'PUT') {
    $userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('Invalid JSON payload')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("SELECT title, description, content, role, domain, compatibility, is_public FROM souls WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $old = $stmt->fetch();

    if (!$old) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Soul not found or no edit perm')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $title = isset($input['title']) ? trim($input['title']) : $old['title'];
    $description = isset($input['description']) ? trim($input['description']) : ($old['description'] ?? '');
    $content = isset($input['content']) ? $input['content'] : $old['content'];
    $role = isset($input['role']) ? $input['role'] : ($old['role'] ?? '');
    $domain = isset($input['domain']) ? trim($input['domain']) : ($old['domain'] ?? '');
    $compatibility = isset($input['compatibility']) ? trim($input['compatibility']) : ($old['compatibility'] ?? '');
    $is_public = isset($input['is_public']) ? (int)$input['is_public'] : (int)$old['is_public'];

    if (empty($title) || empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('Fields required title content')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fileType = strpos(trim($content), '{') === 0 ? 'full_soul_folder' : 'single_md';

    if ($fileType === 'full_soul_folder') {
        $cleanedContent = str_replace("\\'", "'", $content);
        $parsed = json_decode($cleanedContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            http_response_code(400);
            $errDetail = json_last_error_msg();
            echo json_encode(['success' => false, 'error' => __('Invalid Modular JSON content', ['error' => $errDetail])], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $content = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // 🚀 核心新增：計算新內容的 Hash
    $contentHash = 'sha256:' . hash('sha256', $content);

    try {
        $pdo->beginTransaction();

        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$id, $old['title'], $old['content']]);

        $updStmt = $pdo->prepare("UPDATE souls SET title = ?, description = ?, content = ?, role = ?, domain = ?, compatibility = ?, is_public = ?, file_type = ? WHERE id = ?");
        $updStmt->execute([$title, $description, $content, $role, $domain, $compatibility, $is_public, $fileType, $id]);

        syncTags($pdo, 'tags_domain', $old['domain'], $domain);
        syncTags($pdo, 'tags_compatibility', $old['compatibility'], $compatibility);

        $pdo->commit();
        
        // 🚀 回傳 Hash 給前端上鏈使用
        echo json_encode(['success' => true, 'message' => __('Soul updated successfully'), 'hash' => $contentHash], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'DELETE') {
    $userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("SELECT domain, compatibility FROM souls WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $old = $stmt->fetch();

    if (!$old) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Soul not found or no delete perm')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM souls WHERE id = ?")->execute([$id]);

        syncTags($pdo, 'tags_domain', $old['domain'], '');
        syncTags($pdo, 'tags_compatibility', $old['compatibility'], '');

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => __('Soul deleted successfully')], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
}