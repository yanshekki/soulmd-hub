<?php
/**
 * SoulMD Hub Public API
 * GET  /api/souls          - List souls (AgentFi Market & Web2 Hub strict separation)
 * POST /api/souls          - Create soul (Web2 or Web3 Minting initialization V5)
 * (100% Dynamic i18n Internationalized Edition)
 * 🚀 Patched: NULL is_nft handling for legacy data
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

loadTranslations('api');

$db = Database::getInstance();
$pdo = $db->getConnection();
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

function incrementTags($pdo, $table, $tagsString) {
    $tags = array_filter(array_map('trim', explode(',', $tagsString)));
    foreach ($tags as $tag) {
        if (empty($tag)) continue;
        $stmt = $pdo->prepare("INSERT INTO {$table} (name, usage_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1");
        $stmt->execute([$tag]);
    }
}

function makeSlug($str) {
    if (empty($str)) return 'unassigned';
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[\s_:\/?#\[\]@!$&\'()*+,;=<>\\\|]+/', '-', $str);
    return rawurlencode(trim($str, '-'));
}

if ($method === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min((int)($_GET['limit'] ?? 12), 100); 
    $offset = ($page - 1) * $limit;
    
    $q = trim($_GET['q'] ?? '');
    $role = $_GET['role'] ?? '';
    $fileType = $_GET['file_type'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';
    $userIdFilter = $_GET['user_id'] ?? '';
    $isNftFilter = $_GET['is_nft'] ?? '0'; 

    $whereSql = " WHERE 1=1";
    $binds = [];

    // 🚨 完美修復：加入 OR s.is_nft IS NULL 兼容舊數據
    if ($isNftFilter === '1') {
        $whereSql .= " AND s.is_nft = 1";
    } else {
        $whereSql .= " AND s.is_public = 1 AND (s.is_nft = 0 OR s.is_nft IS NULL)";
    }

    if ($userIdFilter !== '') {
        $whereSql .= " AND s.user_id = ?";
        $binds[] = [(int)$userIdFilter, PDO::PARAM_INT];
    }

    if ($q) {
        $keywords = preg_split('/\s+(and|or)\s+|[,|\s]+/i', $q, -1, PREG_SPLIT_NO_EMPTY);
        $keywords = array_unique($keywords);
        $keywords = array_slice($keywords, 0, 5); 

        if (!empty($keywords)) {
            foreach ($keywords as $kw) {
                $whereSql .= " AND (s.title LIKE ? OR s.role LIKE ? OR s.domain LIKE ? OR s.compatibility LIKE ?)";
                $binds[] = ["%$kw%", PDO::PARAM_STR];
                $binds[] = ["%$kw%", PDO::PARAM_STR];
                $binds[] = ["%$kw%", PDO::PARAM_STR];
                $binds[] = ["%$kw%", PDO::PARAM_STR];
            }
        }
    }
    
    if ($role) {
        $whereSql .= " AND s.role = ?";
        $binds[] = [$role, PDO::PARAM_STR];
    }
    if ($fileType) {
        $whereSql .= " AND s.file_type = ?";
        $binds[] = [$fileType, PDO::PARAM_STR];
    }

    try {
        $countSql = "SELECT COUNT(*) FROM souls s" . $whereSql;
        $countStmt = $pdo->prepare($countSql);
        $paramIndex = 1;
        foreach ($binds as $bind) {
            $countStmt->bindValue($paramIndex++, $bind[0], $bind[1]);
        }
        $countStmt->execute();
        $totalCount = (int)$countStmt->fetchColumn();
        $totalPages = ceil($totalCount / $limit);

        $dataSql = "SELECT s.id, s.title, s.description, s.role, s.domain, s.compatibility, s.file_type, s.like_count, s.fork_count, s.created_at, u.username, s.nft_owner_wallet 
                    FROM souls s 
                    LEFT JOIN users u ON s.user_id = u.id" . $whereSql;

        if ($sort === 'popular') {
            $dataSql .= " ORDER BY s.like_count DESC, s.created_at DESC";
        } elseif ($sort === 'forks') {
            $dataSql .= " ORDER BY s.fork_count DESC, s.created_at DESC";
        } elseif ($sort === 'oldest') {
            $dataSql .= " ORDER BY s.created_at ASC";
        } elseif ($sort === 'az') {
            $dataSql .= " ORDER BY s.title ASC, s.created_at DESC";
        } elseif ($sort === 'za') {
            $dataSql .= " ORDER BY s.title DESC, s.created_at DESC";
        } else {
            $dataSql .= " ORDER BY s.created_at DESC";
        }

        $dataSql .= " LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($dataSql);
        $paramIndex = 1;
        foreach ($binds as $bind) {
            $stmt->bindValue($paramIndex++, $bind[0], $bind[1]);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $souls = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'count' => count($souls),
            'total_count' => $totalCount,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'data' => $souls
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Database query failed')], JSON_UNESCAPED_UNICODE);
    }

} elseif ($method === 'POST') {
    $userId = getAuthUserId($pdo);
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $title = trim($input['title'] ?? '');
    $content = $input['content'] ?? '';
    $description = trim($input['description'] ?? '');
    $role = $input['role'] ?? '';
    $domain = trim($input['domain'] ?? '');
    $compatibility = trim($input['compatibility'] ?? '');
    
    $is_minting = !empty($input['is_minting']) ? 1 : 0;
    
    $walletStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
    $walletStmt->execute([$userId]);
    $nearWallet = $walletStmt->fetchColumn();

    if ($is_minting && empty($nearWallet)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => __('Wallet address missing')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (empty($title) || empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('Fields required title content')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!empty($role) && $role !== 'Other') {
        $roleCheckStmt = $pdo->prepare("SELECT slug FROM categories WHERE slug = ?");
        $roleCheckStmt->execute([$role]);
        if (!$roleCheckStmt->fetch()) {
            $role = 'Other'; 
        }
    }

    $fileType = strpos(trim($content), '{') === 0 ? 'full_soul_folder' : 'single_md';

    if ($fileType === 'full_soul_folder') {
        $cleanedContent = str_replace("\\'", "'", $content);
        $parsed = json_decode($cleanedContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => __('Invalid Modular JSON general')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $content = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    $nft_salt = null;
    $nft_hash = null;
    $nft_owner_wallet = null;
    $legacy_hash = 'sha256:' . hash('sha256', $content); 

    if ($is_minting) {
        $is_public = 0; 
        $is_nft = 1;
        $nft_salt = bin2hex(random_bytes(16)); 
        $nft_hash = 'sha256:' . hash('sha256', $content . $nft_salt);
        $nft_owner_wallet = $nearWallet; 
    } else {
        $is_public = isset($input['is_public']) ? (int)$input['is_public'] : 1;
        $is_nft = 0;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO souls 
            (user_id, title, description, content, file_type, role, domain, compatibility, is_public, is_nft, nft_salt, nft_hash, nft_owner_wallet) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId, $title, $description, $content, $fileType, $role, $domain, $compatibility, 
            $is_public, $is_nft, $nft_salt, $nft_hash, $nft_owner_wallet
        ]);

        $newId = $pdo->lastInsertId();

        incrementTags($pdo, 'tags_domain', $domain);
        incrementTags($pdo, 'tags_compatibility', $compatibility);

        $pdo->commit();

        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $uStmt->execute([$userId]);
        $username = $uStmt->fetchColumn() ?: 'anonymous';
        
        $seoUrl = "https://" . $_SERVER['HTTP_HOST'] . "/soul/" . rawurlencode($username) . "/" . $newId . "/" . makeSlug($role) . "/" . makeSlug($title);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => __('Soul created successfully'),
            'id' => $newId,
            'url' => $seoUrl,
            'hash' => $is_minting ? $nft_hash : $legacy_hash
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
}