<?php
/**
 * SoulMD Hub - Shared Token Gating Logic (NFT AgentFi Access Control)
 * 
 * Extracted from chat.php + self-chat.php to eliminate duplication.
 * Use: require_once .../private/includes/token-gate.php;
 *      enforceSoulAccess($pdo, $soul, $chatUserWallet, $currentUser);
 *
 * This function performs the full Web3 token-gating + integrity check + owner repair.
 * It will echo JSON error + exit() if access is denied.
 * $soul is passed by reference so demotion/owner update can be reflected.
 *
 * Also provides applyLazySync() for soul management pages (view/edit) to keep
 * on-chain owner/prices in sync without full access gating.
 */

function enforceSoulAccess(PDO $pdo, array &$soul, string $chatUserWallet, array $currentUser): void {
    $soulId = (int)$soul['id'];
    $hasAccess = false;

    if ($soul['is_nft'] == 1) {
        $contractId = defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near';
        $rpc = NearRpcService::getInstance();
        
        $rpcRes = $rpc->viewCall($contractId, 'get_soul', ['token_id' => 'soul_' . $soulId]);
        $rpcStatus = $rpcRes['status'];
        $tokenInfo = $rpcRes['data'];

        if ($rpcStatus === 'not_found') {
            $pdo->prepare("UPDATE souls SET is_nft = 0, nft_owner_wallet = NULL, nft_salt = NULL, nft_hash = NULL, is_public = 0 WHERE id = ?")->execute([$soulId]);
            $soul['is_nft'] = 0;
            $soul['is_public'] = 0;
        } elseif ($rpcStatus === 'success' && $tokenInfo) {
            $currentDbHash = 'sha256:' . hash('sha256', $soul['content'] . $soul['nft_salt']);
            if (isset($tokenInfo['metadata']['extra']) && $tokenInfo['metadata']['extra'] !== $currentDbHash) {
                echo json_encode(['success' => true, 'reply' => __("Security Interception")], JSON_UNESCAPED_UNICODE); exit;
            }

            $chainOwner = $tokenInfo['owner_id'];
            if ($chainOwner !== $soul['nft_owner_wallet']) {
                $userStmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ?");
                $userStmt->execute([$chainOwner]);
                $newOwnerId = $userStmt->fetchColumn() ?: null; 
                
                $pdo->prepare("UPDATE souls SET user_id = ?, nft_owner_wallet = ? WHERE id = ?")->execute([$newOwnerId, $chainOwner, $soulId]);
                $soul['user_id'] = $newOwnerId;
                $soul['nft_owner_wallet'] = $chainOwner;
            }

            if ($chainOwner === $chatUserWallet) {
                $hasAccess = true;
            } elseif (isset($tokenInfo['renters'][$chatUserWallet])) {
                $expiryNano = (int)$tokenInfo['renters'][$chatUserWallet];
                if ($expiryNano > time() * 1000000000) $hasAccess = true;
            }
            
            if (!$hasAccess) {
                echo json_encode(['success' => true, 'reply' => __("Access Denied Web3")], JSON_UNESCAPED_UNICODE); 
                exit;
            }
        } elseif ($rpcStatus === 'timeout' || $rpcStatus === 'error') {
            if ($chatUserWallet === $soul['nft_owner_wallet']) {
                $hasAccess = true;
            } else {
                echo json_encode(['success' => true, 'reply' => __("RPC Pool Blocked")], JSON_UNESCAPED_UNICODE); 
                exit;
            }
        }
    }

    if ($soul['is_nft'] == 0) {
        $currentUserId = $currentUser['id'] !== null ? (int)$currentUser['id'] : null;
        if (!evaluateNonNftSoulAccess($soul, $currentUserId)) {
            echo json_encode(['success' => true, 'reply' => __("Access Denied Private")], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

/**
 * Pure access decision for non-NFT souls (public or owner).
 * NFT paths are handled separately inside enforceSoulAccess.
 */
function evaluateNonNftSoulAccess(array $soul, ?int $currentUserId): bool
{
    if ((int)($soul['is_nft'] ?? 0) !== 0) {
        return true;
    }
    if ((int)($soul['is_public'] ?? 0) === 1) {
        return true;
    }
    if ($currentUserId !== null && (int)($soul['user_id'] ?? 0) === $currentUserId) {
        return true;
    }
    return false;
}

/**
 * Compare on-chain metadata hash with the DB soul payload.
 */
function nftContentHashMatches(array $soul, array $tokenInfo): bool
{
    if (empty($soul['nft_salt'])) {
        return false;
    }
    $currentDbHash = 'sha256:' . hash('sha256', ($soul['content'] ?? '') . $soul['nft_salt']);
    return isset($tokenInfo['metadata']['extra'])
        && $tokenInfo['metadata']['extra'] === $currentDbHash;
}

/**
 * Decide whether a wallet has on-chain access to an NFT soul payload.
 */
function evaluateNftWalletAccess(array $tokenInfo, string $wallet): bool
{
    $chainOwner = $tokenInfo['owner_id'] ?? '';
    if ($chainOwner === $wallet) {
        return true;
    }
    if (!isset($tokenInfo['renters'][$wallet])) {
        return false;
    }
    $expiryNano = (int)$tokenInfo['renters'][$wallet];
    return $expiryNano > time() * 1000000000;
}

/**
 * 核心同步機制：改用大一統 NearRpcService 執行 Lazy Sync
 * Extracted from soul.php to eliminate duplication with gating logic.
 * Use after fetching a soul to refresh NFT owner/prices from chain.
 * $soul passed by reference for updates.
 */
function applyLazySync(&$soul, $pdo) {
    if ($soul['is_nft'] != 1) return;

    $contractId = defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near';
    $rpc = NearRpcService::getInstance();
    
    // 🌟 呼叫中央 RPC 服務 (自動處理 Base64 編碼及節點輪詢)
    $rpcRes = $rpc->viewCall($contractId, 'get_soul', ['token_id' => 'soul_' . $soul['id']], 'optimistic');
    
    if ($rpcRes['status'] === 'not_found') {
        $stmt = $pdo->prepare("UPDATE souls SET is_nft = 0, nft_owner_wallet = NULL, nft_salt = NULL, nft_hash = NULL, is_public = 0, sale_price = NULL, rent_price = NULL WHERE id = ?");
        $stmt->execute([$soul['id']]);
        
        $soul['is_nft'] = 0;
        $soul['nft_owner_wallet'] = null;
        $soul['nft_salt'] = null;
        $soul['nft_hash'] = null;
        $soul['is_public'] = 0; 
        $soul['sale_price'] = null;
        $soul['rent_price'] = null;
        return;
    }

    if ($rpcRes['status'] === 'success' && !empty($rpcRes['data']['owner_id'])) {
        $chainOwner = $rpcRes['data']['owner_id'];
        $salePrice = isset($rpcRes['data']['sale_price']) ? (string)$rpcRes['data']['sale_price'] : null;
        $rentPrice = isset($rpcRes['data']['rent_price']) ? (string)$rpcRes['data']['rent_price'] : null;
        
        $userStmt = $pdo->prepare("SELECT id FROM users WHERE near_wallet_address = ?");
        $userStmt->execute([$chainOwner]);
        $newOwnerId = $userStmt->fetchColumn() ?: null; 
        
        $stmt = $pdo->prepare("UPDATE souls SET user_id = ?, nft_owner_wallet = ?, sale_price = ?, rent_price = ? WHERE id = ?");
        $stmt->execute([$newOwnerId, $chainOwner, $salePrice, $rentPrice, $soul['id']]);
        
        $soul['user_id'] = $newOwnerId;
        $soul['nft_owner_wallet'] = $chainOwner;
        $soul['sale_price'] = $salePrice;
        $soul['rent_price'] = $rentPrice;
    }
}
