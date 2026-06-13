<?php
/**
 * SoulMD Hub Public API
 * GET  /api/versions?soul_id={id}&page={page} - List versions (Paginated)
 * POST /api/versions - Restore a specific version (Requires Auth)
 * (100% Dynamic i18n Internationalized Error Stack & Paginated Edition)
 * 🚀 Patched: Dual-Track Permission Verification for Version Restore
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';

// 🌍 載入後端 API 全域專屬語言包
loadTranslations('api');

$security = ApiSecurity::initialize(false);
$userId   = $security['user_id'];
$pdo      = $security['pdo'];
$isApiKey = $security['is_api_key'];

$method = $_SERVER['REQUEST_METHOD'];

// ==========================================
// 路由處理
// ==========================================
if ($method === 'GET') {
    $soulId = (int)($_GET['soul_id'] ?? 0);
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min((int)($_GET['limit'] ?? 10), 50); 
    
    if (!$soulId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('soul_id required')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // $userId resolved at top (may be null for public souls)
    
    // 🚨 完美修復 3：允許 Web3 持有人讀取歷史
    $checkStmt = $pdo->prepare("SELECT is_public, user_id, is_nft, nft_owner_wallet FROM souls WHERE id = ?");
    $checkStmt->execute([$soulId]);
    $soulCheck = $checkStmt->fetch();

    if (!$soulCheck) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $hasReadAccess = false;
    if ($soulCheck['is_public'] == 1) {
        $hasReadAccess = true;
    } elseif ($soulCheck['user_id'] == $userId) {
        $hasReadAccess = true;
    } elseif ($soulCheck['is_nft'] == 1) {
        $wStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
        $wStmt->execute([$userId]);
        $myWallet = $wStmt->fetchColumn();
        if (!empty($myWallet) && $myWallet === $soulCheck['nft_owner_wallet']) {
            $hasReadAccess = true;
        }
    }

    if (!$hasReadAccess) {
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
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CSRF handled centrally at top of file by ApiSecurity

    $input = json_decode(file_get_contents('php://input'), true);
    $versionId = (int)($input['version_id'] ?? 0);
    $soulId = (int)($input['soul_id'] ?? 0);

    if (!$versionId || !$soulId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('version_id and soul_id required')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 🚨 完美修復 4：改用雙軌制驗證是否有還原權限
    $stmt = $pdo->prepare("SELECT title, content, user_id, is_nft, nft_owner_wallet FROM souls WHERE id = ?");
    $stmt->execute([$soulId]);
    $soul = $stmt->fetch();

    if (!$soul) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $walletStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
    $walletStmt->execute([$userId]);
    $myWallet = $walletStmt->fetchColumn();

    $hasRestoreAccess = false;
    if ($soul['user_id'] == $userId) {
        $hasRestoreAccess = true;
    } elseif ($soul['is_nft'] == 1 && !empty($myWallet) && $myWallet === $soul['nft_owner_wallet']) {
        $hasRestoreAccess = true;
    }

    if (!$hasRestoreAccess) {
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
        $pdo->prepare("UPDATE souls SET title = ?, content = ?, file_type = ? WHERE id = ?")
            ->execute([$version['title'], $version['content'], $fileType, $soulId]);

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