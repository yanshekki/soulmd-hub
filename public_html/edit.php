<?php
/**
 * SoulMD Hub - Edit Model Dashboard
 * (DRY Refactored - Unified Form Extracted to soul-form.php)
 * 🚀 Patched: Fully integrated with the unified NearRpcService layer.
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';
require_once __DIR__ . '/../private/src/NearRpcService.php'; // 🚀 引入中央 RPC 服務

require_once __DIR__ . '/../private/includes/soul-page-setup.php';  // shared session, CSRF, loadTranslations, pdo, user_id with upload.php + edit.php

// edit-specific continues below (soul fetch, lazy sync, permission, etc.)

$soulId = (int)($_GET['id'] ?? 0);
if (!$soulId) {
    header('Location: ' . url('/my-souls'));
    exit;
}

// 🚨 修正 1：先不要檢查 user_id，純粹撈取 Soul，因為可能是剛買入的 NFT (Lazy Sync 尚未觸發)
$stmt = $pdo->prepare("SELECT * FROM souls WHERE id = ?");
$stmt->execute([$soulId]);
$soulData = $stmt->fetch();

if (!$soulData) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$wStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
$wStmt->execute([$user_id]);
$myWallet = $wStmt->fetchColumn();

// 🚨 修正 2：Lazy Sync 與 Phantom NFT 幽靈自癒降級機制
if ($soulData['is_nft'] == 1) {
    $contractId = defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near';
    $rpc = NearRpcService::getInstance();
    
    // 🌟 呼叫中央 RPC 服務
    $rpcRes = $rpc->viewCall($contractId, 'get_soul', ['token_id' => 'soul_' . $soulId], 'optimistic');

    if ($rpcRes['status'] === 'not_found') {
        // 區塊鏈上查無此物！執行降級自癒，解鎖前端表單！（允許重新 mint）
        $pdo->prepare("UPDATE souls SET is_nft = 0, nft_owner_wallet = NULL, nft_salt = NULL, nft_hash = NULL WHERE id = ?")->execute([$soulId]);
        $soulData['is_nft'] = 0;
    } elseif ($rpcRes['status'] === 'success' && !empty($rpcRes['data'])) {
        $tokenInfo = $rpcRes['data'];
        
        // 🔄 Lazy Sync 擁有權移交 (保障買家能立即編輯)
        if (isset($tokenInfo['owner_id'])) {
            $chainOwner = $tokenInfo['owner_id'];
            if ($chainOwner !== $soulData['nft_owner_wallet']) {
                $uStmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ?");
                $uStmt->execute([$chainOwner]);
                $newOwnerId = $uStmt->fetchColumn() ?: null;
                
                $pdo->prepare("UPDATE souls SET user_id = ?, nft_owner_wallet = ? WHERE id = ?")->execute([$newOwnerId, $chainOwner, $soulId]);
                $soulData['user_id'] = $newOwnerId;
                $soulData['nft_owner_wallet'] = $chainOwner;
            }
        }
    } else {
        // RPC 查詢失敗（error / timeout / contract panic 等），無法正面確認 on-chain 確實係 NFT
        // 為避免「第一次 mint 失敗後永遠鎖死喺 update 模式」，降級以便用戶重試 mint
        // （真正係 NFT 嘅情況，下次 RPC 成功時會再 sync 返嚟）
        if ($soulData['is_nft'] == 1) {
            $pdo->prepare("UPDATE souls SET is_nft = 0, nft_owner_wallet = NULL, nft_salt = NULL, nft_hash = NULL WHERE id = ?")->execute([$soulId]);
            $soulData['is_nft'] = 0;
        }
    }
}

// 🚨 修正 3：最終權限校驗 (確認是 Web2 擁有者，或者是 Web3 錢包擁有者)
$hasAccess = false;
if ($soulData['user_id'] == $user_id) {
    $hasAccess = true;
} elseif ($soulData['is_nft'] == 1 && !empty($myWallet) && $myWallet === $soulData['nft_owner_wallet']) {
    $hasAccess = true;
}

if (!$hasAccess) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$isEditMode = true;
$pageTitle = __('SEO Title Edit', ['title' => htmlspecialchars($soulData['title'])]);
$pageDesc = __('SEO Desc Edit');
require_once __DIR__ . '/../private/includes/header.php';

// 引入核心表單
require_once __DIR__ . '/../private/includes/soul-form.php';

require_once __DIR__ . '/../private/includes/footer.php';
?>