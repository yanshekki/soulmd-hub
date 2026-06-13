<?php
/**
 * SoulMD Hub Public API
 * GET    /api/soul/{id} - Get single soul details (Lazy Sync & Self-Healing Edition)
 * PUT    /api/soul/{id} - Update a soul & Generate new NFT Hash
 * DELETE /api/soul/{id} - Delete a soul 
 * (100% Dynamic i18n Internationalized & Web2.5 AgentFi V5 Architecture)
 * 🚀 Patched: Replaced hardcoded cURL loops with centralized NearRpcService
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/src/NearRpcService.php'; // 🚀 引入中央 RPC 服務
require_once __DIR__ . '/../../private/includes/token-gate.php';
require_once __DIR__ . '/../../private/src/ApiSecurity.php';

$security = ApiSecurity::initialize(false);   // allow public GET; auth checked inside per-method
$userId   = $security['user_id'];
$pdo      = $security['pdo'];
$isApiKey = $security['is_api_key'];

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

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

// 路由主矩陣 - Lazy Sync 現已統一由 private/includes/token-gate.php 提供
// ==========================================
// 路由主矩陣
// ==========================================
if ($method === 'GET') {
    // $userId may be null here (public access allowed; ownership checked below)
    $stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ?");
    $stmt->execute([$id]);
    $soul = $stmt->fetch();

    if (!$soul) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    applyLazySync($soul, $pdo);

    if (!$soul['is_public'] && $soul['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Access Denied Private')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $soul], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} elseif ($method === 'PUT') {
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CSRF/auth handled centrally by ApiSecurity::initialize() at top of file
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('Invalid JSON payload')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo->prepare("SELECT user_id, is_nft, nft_owner_wallet, title, description, content, role, domain, compatibility, is_public FROM souls WHERE id = ?");
    $stmt->execute([$id]);
    $old = $stmt->fetch();

    if (!$old) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 取得當前用戶綁定的 Web3 錢包
    $walletStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
    $walletStmt->execute([$userId]);
    $myWallet = $walletStmt->fetchColumn();

    // 雙軌制權限核對 (Web2 ID 吻合，或是 Web3 錢包吻合皆可放行)
    $hasEditPerm = false;
    if ($old['user_id'] == $userId) {
        $hasEditPerm = true;
    } elseif ($old['is_nft'] == 1 && !empty($myWallet) && $myWallet === $old['nft_owner_wallet']) {
        $hasEditPerm = true;
    }

    if (!$hasEditPerm) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Soul not found or no edit perm')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!empty($input['is_minting'])) {
        // 允許重試 mint / 完成 NFT 上鏈，即使 DB 有 stale is_nft=1（之前 mint 失敗但 web2 save 已經標記）
        // 前端 submit 時會 live check on-chain，如果 on-chain 唔存在先 set is_minting=1 要求呢度處理
        if (empty($myWallet)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => __('Please connect NEAR wallet first')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $nft_salt = bin2hex(random_bytes(16));
        $nft_hash = 'sha256:' . hash('sha256', $old['content'] . $nft_salt);
        
        try {
            $pdo->prepare("UPDATE souls SET is_nft = 1, is_public = 0, nft_salt = ?, nft_hash = ?, nft_owner_wallet = ? WHERE id = ?")
                ->execute([$nft_salt, $nft_hash, $myWallet, $id]);

            $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $username = $uStmt->fetchColumn() ?: 'anonymous';

            if (!function_exists('makeSlugLocal')) {
                function makeSlugLocal($str) {
                    if (empty($str)) return 'unassigned';
                    $str = mb_strtolower($str, 'UTF-8');
                    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
                    return rawurlencode(trim($str, '-'));
                }
            }

            $seoUrl = "https://" . $_SERVER['HTTP_HOST'] . "/soul/" . rawurlencode($username) . "/" . $id . "/" . makeSlugLocal($old['role']) . "/" . makeSlugLocal($old['title']);

            echo json_encode([
                'success' => true,
                'hash' => $nft_hash,
                'soul_title' => $old['title'],
                'soul_description' => $old['description'],
                'url' => $seoUrl
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $title = isset($input['title']) ? trim($input['title']) : $old['title'];
    $description = isset($input['description']) ? trim($input['description']) : ($old['description'] ?? '');
    $content = isset($input['content']) ? $input['content'] : $old['content'];
    $role = isset($input['role']) ? $input['role'] : ($old['role'] ?? '');
    $domain = isset($input['domain']) ? trim($input['domain']) : ($old['domain'] ?? '');
    $compatibility = isset($input['compatibility']) ? trim($input['compatibility']) : ($old['compatibility'] ?? '');
    
    // 如果是 NFT，強制鎖死 is_public 為 0
    $is_public = ($old['is_nft'] == 1) ? 0 : (isset($input['is_public']) ? (int)$input['is_public'] : (int)$old['is_public']);

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

    $contentHash = '';
    
    try {
        $pdo->beginTransaction();

        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$id, $old['title'], $old['content']]);

        if ($old['is_nft'] == 1) {
            $nft_salt = bin2hex(random_bytes(16));
            $nft_hash = 'sha256:' . hash('sha256', $content . $nft_salt);
            $contentHash = $nft_hash;
            
            $updStmt = $pdo->prepare("UPDATE souls SET title = ?, description = ?, content = ?, role = ?, domain = ?, compatibility = ?, is_public = 0, file_type = ?, nft_salt = ?, nft_hash = ? WHERE id = ?");
            $updStmt->execute([$title, $description, $content, $role, $domain, $compatibility, $fileType, $nft_salt, $nft_hash, $id]);
        } else {
            $contentHash = 'sha256:' . hash('sha256', $content);
            $updStmt = $pdo->prepare("UPDATE souls SET title = ?, description = ?, content = ?, role = ?, domain = ?, compatibility = ?, is_public = ?, file_type = ? WHERE id = ?");
            $updStmt->execute([$title, $description, $content, $role, $domain, $compatibility, $is_public, $fileType, $id]);
        }

        syncTags($pdo, 'tags_domain', $old['domain'], $domain);
        syncTags($pdo, 'tags_compatibility', $old['compatibility'], $compatibility);

        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => __('Soul updated successfully'), 'hash' => $contentHash], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'DELETE') {
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CSRF/auth handled centrally
    $stmt = $pdo->prepare("SELECT user_id, is_nft, nft_owner_wallet, domain, compatibility FROM souls WHERE id = ?");
    $stmt->execute([$id]);
    $old = $stmt->fetch();

    if (!$old) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $walletStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
    $walletStmt->execute([$userId]);
    $myWallet = $walletStmt->fetchColumn();

    $hasDeletePerm = false;
    if ($old['user_id'] == $userId) {
        $hasDeletePerm = true;
    } elseif ($old['is_nft'] == 1 && !empty($myWallet) && $myWallet === $old['nft_owner_wallet']) {
        $hasDeletePerm = true;
    }

    if (!$hasDeletePerm) {
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
?>