<?php
/**
 * SoulMD Hub Public API
 * GET    /api/soul/{id} - Get single soul details (Lazy Sync & Self-Healing Edition)
 * PUT    /api/soul/{id} - Update a soul & Generate new NFT Hash
 * DELETE /api/soul/{id} - Delete a soul 
 * (100% Dynamic i18n Internationalized & Web2.5 AgentFi V5 Architecture)
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

// ==========================================
// 🛡️ 權限與標籤助手函數
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
// 🚀 核心 V5：RPC 備援池與狀態查詢 (Failover Pool)
// ==========================================
function fetchNearRpcToken($tokenId) {
    $rpcNodes = [
        "https://free.rpc.fastnear.com",
        "https://near.lava.build",
        "https://rpc.mainnet.pagoda.co",
        "https://rpc.mainnet.near.org"
    ];
    
    $payload = json_encode([
        "jsonrpc" => "2.0", "id" => "dontcare", "method" => "query",
        "params" => [
            "request_type" => "call_function", "finality" => "final",
            "account_id" => defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near', 
            "method_name" => "get_soul", 
            "args_base64" => base64_encode(json_encode(["token_id" => $tokenId]))
        ]
    ]);

    foreach ($rpcNodes as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 3 // 3 秒極限切換
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $res) {
            $data = json_decode($res, true);
            if (isset($data['result']['result'])) {
                $resString = implode(array_map('chr', $data['result']['result']));
                if (trim($resString) === 'null') {
                    return ['status' => 'not_found']; // 代幣不存在 (Mint 失敗 / 被銷毀)
                }
                return ['status' => 'success', 'data' => json_decode($resString, true)];
            }
        }
    }
    return ['status' => 'timeout']; // 所有節點死亡，動用本地緩存
}

// ==========================================
// 🚀 核心 V5：懶同步與自癒降級引擎 (Lazy Sync & Self-Healing)
// ==========================================
function applyLazySync(&$soul, $pdo) {
    if ($soul['is_nft'] != 1) return;

    $rpcRes = fetchNearRpcToken("soul_" . $soul['id']);
    
    // 情況 A: 鏈上找不到該 NFT (可能因為中途斷線 Mint 失敗，或者已 Burn)
    if ($rpcRes['status'] === 'not_found') {
        $stmt = $pdo->prepare("UPDATE souls SET is_nft = 0, nft_owner_wallet = NULL, nft_salt = NULL, nft_hash = NULL, is_public = 0 WHERE id = ?");
        $stmt->execute([$soul['id']]);
        
        $soul['is_nft'] = 0;
        $soul['nft_owner_wallet'] = null;
        $soul['nft_salt'] = null;
        $soul['nft_hash'] = null;
        $soul['is_public'] = 0; // 強制私密，歸還給原作者
        return;
    }

    // 情況 B: 成功獲取鏈上最新狀態，比對擁有權
    if ($rpcRes['status'] === 'success' && !empty($rpcRes['data']['owner_id'])) {
        $chainOwner = $rpcRes['data']['owner_id'];
        
        if ($chainOwner !== $soul['nft_owner_wallet']) {
            // 🚨 擁有權發生轉移！(買賣成功)
            $userStmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ?");
            $userStmt->execute([$chainOwner]);
            $newOwnerId = $userStmt->fetchColumn() ?: null; 
            
            $stmt = $pdo->prepare("UPDATE souls SET user_id = ?, nft_owner_wallet = ? WHERE id = ?");
            $stmt->execute([$newOwnerId, $chainOwner, $soul['id']]);
            
            $soul['user_id'] = $newOwnerId;
            $soul['nft_owner_wallet'] = $chainOwner;
        }
    }
    // 情況 C: timeout -> 不做任何事，依靠資料庫原本的緩存狀態放行
}

// ==========================================
// 路由處理
// ==========================================
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

    // 🚀 執行 Lazy Sync 懶同步引擎
    applyLazySync($soul, $pdo);

    // 權限檢查：如果不是 Public，就只有擁有者可以查看
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

    $stmt = $pdo->prepare("SELECT is_nft, title, description, content, role, domain, compatibility, is_public FROM souls WHERE id = ? AND user_id = ?");
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

        // 快照備份至版本歷史
        $pdo->prepare("INSERT INTO soul_versions (soul_id, title, content) VALUES (?, ?, ?)")
            ->execute([$id, $old['title'], $old['content']]);

        if ($old['is_nft'] == 1) {
            // 🚀 如果是 NFT：重新生成安全 Salt 與 Hash，供前端更新上鏈
            $nft_salt = bin2hex(random_bytes(16));
            $nft_hash = 'sha256:' . hash('sha256', $content . $nft_salt);
            $contentHash = $nft_hash;
            
            $updStmt = $pdo->prepare("UPDATE souls SET title = ?, description = ?, content = ?, role = ?, domain = ?, compatibility = ?, is_public = 0, file_type = ?, nft_salt = ?, nft_hash = ? WHERE id = ?");
            $updStmt->execute([$title, $description, $content, $role, $domain, $compatibility, $fileType, $nft_salt, $nft_hash, $id]);
        } else {
            // 普通 Web2 模型
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