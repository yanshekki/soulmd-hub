<?php
/**
 * SoulMD Hub - Multiplayer Chat Sync & Heartbeat API
 * 處理多人連線心跳、在線人數統計、以及增量下發新訊息 (Delta Sync)
 * 🚀 Patched: Added sender_name retrieval for accurate Multiplayer UI rendering.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); exit;
}

$soulId = (int)($_GET['soul_id'] ?? 0);
$sessionToken = trim($_GET['session_token'] ?? '');
$lastMsgId = (int)($_GET['last_id'] ?? 0);

// 🚨 嚴格校驗 Token 格式
if (!$soulId || empty($sessionToken) || !preg_match('/^[a-zA-Z0-9_-]{8,128}$/', $sessionToken)) {
    http_response_code(400); exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// 產生獨一無二嘅訪客/用戶識別碼 (用於區分有幾多個人)
$userId = $_SESSION['user_id'] ?? null;
if (empty($_SESSION['guest_id'])) {
    $_SESSION['guest_id'] = bin2hex(random_bytes(8));
}
$guestId = $_SESSION['guest_id'];
$identifier = $userId ? "user_{$userId}" : "guest_{$guestId}";

$now = time();

// ✅ Phase 2 業務邏輯修復：簡單 throttle 防 sync abuse (每請求 ~1s)
if (!empty($_SESSION['last_sync_time']) && (time() - $_SESSION['last_sync_time']) < 1) {
    http_response_code(429); exit;
}
$_SESSION['last_sync_time'] = time();

try {
    // 1. 寫入或更新心跳 (Heartbeat)
    $pdo->prepare("INSERT INTO chat_presence (session_token, identifier, last_seen) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_seen = ?")
        ->execute([$sessionToken, $identifier, $now, $now]);

    // 2. 清理過期連線 (超過 12 秒冇心跳即當斷線離場)
    $pdo->prepare("DELETE FROM chat_presence WHERE last_seen < ?")->execute([$now - 12]);

    // 3. 統計目前同一個對話中嘅在線人數
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_presence WHERE session_token = ?");
    $countStmt->execute([$sessionToken]);
    $onlineCount = (int)$countStmt->fetchColumn();

    // 4. 🚀 撈取大於 $lastMsgId 的新訊息，並帶上 sender_name
    $newMessages = [];
    if ($lastMsgId >= 0) {
        // LIMIT 50 防止一次過拉太多拖慢效能
        $msgStmt = $pdo->prepare("SELECT id, role, sender_name, content FROM chat_messages WHERE soul_id = ? AND session_token = ? AND id > ? ORDER BY id ASC LIMIT 50");
        $msgStmt->execute([$soulId, $sessionToken, $lastMsgId]);
        $newMessages = $msgStmt->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'online_count' => $onlineCount,
        'new_messages' => $newMessages
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500); exit;
}
?>