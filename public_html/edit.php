<?php
/**
 * SoulMD Hub - Edit Model Dashboard
 * (DRY Refactored - Unified Form Extracted to soul-form.php)
 * 🚀 Patched: Phantom NFT Self-Healing, Lazy Sync & SEO Meta i18n
 */

require_once __DIR__ . '/../private/config.php';
require_once __DIR__ . '/../private/src/Database.php';
require_once __DIR__ . '/../private/includes/seo.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/login'));
    exit;
}

loadTranslations('upload');

$db = Database::getInstance();
$pdo = $db->getConnection();
$user_id = $_SESSION['user_id'];

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
    $rpcNodes = defined('NEAR_RPC_NODES') ? NEAR_RPC_NODES : ["https://free.rpc.fastnear.com", "https://rpc.mainnet.near.org"];
    $rpcPayload = json_encode([
        "jsonrpc" => "2.0", "id" => "dontcare", "method" => "query",
        "params" => [
            "request_type" => "call_function", "finality" => "final",
            "account_id" => defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near', 
            "method_name" => "get_soul", 
            "args_base64" => base64_encode(json_encode(["token_id" => "soul_" . $soulId]))
        ]
    ]);

    foreach ($rpcNodes as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $rpcPayload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_TIMEOUT => 3
        ]);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $res) {
            $data = json_decode($res, true);
            if (isset($data['result']['result'])) {
                $resString = implode(array_map('chr', $data['result']['result']));
                if (trim($resString) === 'null') {
                    // 區塊鏈上查無此物！執行降級自癒，解鎖前端表單！
                    $pdo->prepare("UPDATE souls SET is_nft = 0, nft_owner_wallet = NULL, nft_salt = NULL, nft_hash = NULL WHERE id = ?")->execute([$soulId]);
                    $soulData['is_nft'] = 0;
                } else {
                    // 🔄 Lazy Sync 擁有權移交 (保障買家能立即編輯)
                    $tokenInfo = json_decode($resString, true);
                    if ($tokenInfo && isset($tokenInfo['owner_id'])) {
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
                }
            }
            break; 
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