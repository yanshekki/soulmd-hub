<?php
/**
 * SoulMD Hub - BYOK Proxy API (self-chat.php)
 * 100% 左手交右手無狀態代理，不扣除平台 Daily Limit (Fallback 例外)。
 * 包含 Web3 Token-Gating, 自訂記憶壓縮, 及 Vision Fallback。
 * (V5 Web2.5 AgentFi Architecture: Unified NearRpcService & Self-Healing Edition)
 * 🚀 Patched: Added sender_name identity tracking for Multiplayer Chat support.
 */

set_time_limit(180);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/src/AppBootstrap.php';
require_once __DIR__ . '/../../private/includes/encryption.php';
require_once __DIR__ . '/../../private/src/NearRpcService.php';
require_once __DIR__ . '/../../private/includes/token-gate.php';
require_once __DIR__ . '/../../private/src/LlmStreamProxy.php';

// Unified API bootstrap (config + ApiSecurity + translations). Trust $app['user_id']
// for both session and api_key paths — do NOT overwrite with $_SESSION only.
$app = AppBootstrap::forApi([
    'require_user' => true,
    'translations' => ['api', 'chat'],
    'json_header' => false, // CORS/Content-Type already set above
]);
$userId = $app['user_id'] ? (int)$app['user_id'] : null;
$pdo = $app['pdo'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE); 
    exit;
}

if (!$userId) {
    http_response_code(401); 
    echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE); 
    exit;
}

// ==========================================
// 🚀 核心防白嫖引擎：過期降級與跨日重置 (Fallback 扣費所需)
// ==========================================
function getCurrentUser($pdo, int $userId) {
    $today = date('Y-m-d');

    // 🚀 補上撈取 username
    $stmt = $pdo->prepare("SELECT id, username, tier, daily_chat_count, last_chat_date, vip_expires_at, near_wallet_address FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) return null;

    $isExpired = false;
    if ($user['tier'] !== 'free' && $user['vip_expires_at'] && strtotime($user['vip_expires_at']) < time()) {
        $pdo->prepare("UPDATE users SET tier = 'free' WHERE id = ?")->execute([$userId]);
        $user['tier'] = 'free';
        $isExpired = true;
    }

    if ($user['last_chat_date'] !== $today) {
        // ✅ Phase 2 業務邏輯修復：加條件減 race（concurrent day reset）
        $pdo->prepare("UPDATE users SET daily_chat_count = 0, last_chat_date = ? WHERE id = ? AND last_chat_date != ?")->execute([$today, $userId, $today]);
        $user['daily_chat_count'] = 0;
    }

    return [
        'id' => $user['id'],
        'username' => $user['username'],
        'tier' => $user['tier'],
        'daily_count' => $user['daily_chat_count'],
        'is_expired' => $isExpired,
        'near_wallet' => $user['near_wallet_address']
    ];
}

$currentUser = getCurrentUser($pdo, $userId);
if (!$currentUser) {
    http_response_code(401); 
    echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE); 
    exit;
}

// CSRF / auth already enforced by AppBootstrap::forApi / ApiSecurity

// 接收參數與 BYOK 檢查
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$soulId = (int)($input['soul_id'] ?? 0);
$sessionToken = trim($input['session_token'] ?? '');
$userMessageText = trim($input['content'] ?? '');
$imageBase64 = $input['image'] ?? null;
$isPrivate = isset($input['is_private']) ? (bool)$input['is_private'] : false;
$isVisionRequest = !empty($imageBase64);

if (!$soulId || empty($sessionToken) || !preg_match('/^[a-zA-Z0-9_-]{8,128}$/', $sessionToken) || (empty($userMessageText) && empty($imageBase64))) {
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => __('Invalid request parameters')], JSON_UNESCAPED_UNICODE); 
    exit;
}

$setStmt = $pdo->prepare("SELECT * FROM user_llm_settings WHERE user_id = ?");
$setStmt->execute([$userId]);
$settings = $setStmt->fetch();

if (!$settings || $settings['use_byok'] != 1) {
    http_response_code(403); 
    echo json_encode(['success' => false, 'error' => __('BYOK mode is not enabled on your account.')], JSON_UNESCAPED_UNICODE); 
    exit;
}

// ==========================================
// 3. 智能 Fallback 與 API 分流邏輯
// ==========================================
$isVisionFallback = false;
$targetApiUrl = '';
$targetApiKey = '';
$targetModel = '';

if ($isVisionRequest) {
    if (empty($settings['vision_api_key'])) {
        if ($currentUser['tier'] === 'free') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => __('Vision BYOK fallback error'), 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tierPrefix = strtoupper($currentUser['tier']);
        $dailyLimit = defined("{$tierPrefix}_DAILY_LIMIT") ? constant("{$tierPrefix}_DAILY_LIMIT") : 10;
        
        if ($currentUser['daily_count'] >= $dailyLimit) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => __('Vision BYOK fallback error'), 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $isVisionFallback = true;
        $targetApiUrl = defined('VISION_API_URL') ? VISION_API_URL : 'https://api.openai.com/v1/chat/completions';
        $targetApiKey = defined('VISION_API_KEY') ? VISION_API_KEY : '';
        
        if ($currentUser['tier'] === 'pro' && defined('PRO_VISION_MODEL')) {
            $targetModel = PRO_VISION_MODEL; 
        } else {
            $targetModel = defined('VIP_VISION_MODEL') ? VIP_VISION_MODEL : 'meta-llama/Llama-3.2-90B-Vision-Instruct-Turbo';
        }
    } else {
        $targetApiUrl = $settings['vision_api_url'];
        $targetApiKey = decryptData($settings['vision_api_key']);
        $targetModel = $settings['vision_model'];
    }
} else {
    if (empty($settings['text_api_key'])) {
        http_response_code(403); 
        echo json_encode(['success' => false, 'error' => __('Text API Key is not set in your BYOK settings.')], JSON_UNESCAPED_UNICODE); 
        exit;
    }
    $targetApiUrl = $settings['text_api_url'];
    $targetApiKey = decryptData($settings['text_api_key']);
    $targetModel = $settings['text_model'];
}

// ==========================================
// 4. Session 管理與 Web3/Web2 混合門禁 (V5)
// ==========================================
$sessStmt = $pdo->prepare("SELECT user_id, is_private FROM chat_sessions WHERE session_token = ?");
$sessStmt->execute([$sessionToken]);
$chatSession = $sessStmt->fetch();

// 🌟 允許多人共享同一個 URL 進行群聊！只阻擋 Private
if ($chatSession) {
    if ($chatSession['is_private'] && $currentUser['id'] !== $chatSession['user_id']) {
        http_response_code(403); 
        echo json_encode(['success' => false, 'error' => __('Access Denied Private')], JSON_UNESCAPED_UNICODE); 
        exit;
    }
    if ($currentUser['id'] === $chatSession['user_id'] && $chatSession['is_private'] != $isPrivate) {
        $pdo->prepare("UPDATE chat_sessions SET is_private = ? WHERE session_token = ?")->execute([(int)$isPrivate, $sessionToken]);
    }
} else {
    $pdo->prepare("INSERT INTO chat_sessions (session_token, soul_id, user_id, is_private) VALUES (?, ?, ?, ?)")
        ->execute([$sessionToken, $soulId, $currentUser['id'], (int)$isPrivate]);
}

$soulStmt = $pdo->prepare("SELECT * FROM souls WHERE id = ?");
$soulStmt->execute([$soulId]);
$soul = $soulStmt->fetch();
if (!$soul) { 
    http_response_code(404); 
    echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE); 
    exit; 
}

$chatUserWallet = $currentUser['near_wallet'] ?? '';
enforceSoulAccess($pdo, $soul, $chatUserWallet, $currentUser);

// ==========================================
// 5. 組合 System Prompt (支援 Modular 解析)
// ==========================================
$systemPrompt = "";
if ($soul['file_type'] === 'full_soul_folder') {
    $systemPrompt .= "Please adopt the following modular AI persona:\n\n";
    $files = json_decode(str_replace("\\'", "'", $soul['content']), true);
    if (is_array($files)) {
        foreach ($files as $filename => $fileContent) {
            if (strpos($filename, 'ERROR.md') !== false) continue;
            $systemPrompt .= "=== MODULE: {$filename} ===\n" . (is_string($fileContent) ? $fileContent : json_encode($fileContent, JSON_UNESCAPED_UNICODE)) . "\n\n";
        }
    }
} else {
    $systemPrompt = $soul['content'];
}

// ==========================================
// 6. 自訂記憶體壓縮邏輯 (使用 BYOK 設定)
// ==========================================
$memStmt = $pdo->prepare("SELECT summary, last_message_id FROM chat_memory WHERE session_token = ?");
$memStmt->execute([$sessionToken]);
$memoryRow = $memStmt->fetch();
$chatMemory = $memoryRow['summary'] ?? '';
$lastMessageId = (int)($memoryRow['last_message_id'] ?? 0);

$msgStmt = $pdo->prepare("SELECT id, role, content FROM chat_messages WHERE soul_id = ? AND session_token = ? AND id > ? ORDER BY id ASC");
$msgStmt->execute([$soulId, $sessionToken, $lastMessageId]);
$unsummarized = $msgStmt->fetchAll();

$updateMemory = false; 
$compressThreshold = (int)($settings['memory_compress_threshold'] ?: 10);

if (count($unsummarized) >= $compressThreshold) {
    $toSummarize = array_slice($unsummarized, 0, -2);
    $keptMessages = array_slice($unsummarized, -2);
    $sumPrompt = "Compress the following chat log into a short summary focus on facts.\n[OLD MEMORY]\n{$chatMemory}\n[NEW LOGS]\n";
    
    foreach ($toSummarize as $m) {
        $txt = $m['content'];
        $parsedTxt = json_decode($txt, true);
        if (is_array($parsedTxt)) {
            $txt = '';
            foreach ($parsedTxt as $part) {
                if (isset($part['type']) && $part['type'] === 'text') $txt .= $part['text'];
            }
        }
        $sumPrompt .= strtoupper($m['role']) . ": " . $txt . "\n";
    }

    $chSum = curl_init();
    curl_setopt_array($chSum, [
        CURLOPT_URL => $settings['text_api_url'],
        CURLOPT_RETURNTRANSFER => true, 
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "model" => $settings['text_model'], 
            "messages" => [["role" => "user", "content" => $sumPrompt]], 
            "max_tokens" => 400
        ], JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . decryptData($settings['text_api_key'])],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $sumRes = curl_exec($chSum);
    if (curl_getinfo($chSum, CURLINFO_HTTP_CODE) === 200 && $sumRes) {
        $newSummary = json_decode($sumRes, true)['choices'][0]['message']['content'] ?? '';
        if ($newSummary) {
            $chatMemory = $newSummary;
            $lastMessageId = end($toSummarize)['id'];
            $updateMemory = true; 
            $unsummarized = $keptMessages;
        }
    }
    curl_close($chSum);
}

if ($chatMemory) $systemPrompt .= "\n\n[CONTEXT MEMORY]\n" . $chatMemory;

$apiMessages = [["role" => "system", "content" => $systemPrompt]];
foreach ($unsummarized as $msg) {
    $parsed = json_decode($msg['content'], true);
    $apiMessages[] = ["role" => $msg['role'], "content" => (is_array($parsed) ? $parsed : $msg['content'])];
}

$dbContentToSave = $userMessageText;
if ($isVisionRequest) {
    $visionPayload = [
        ["type" => "text", "text" => $userMessageText ?: "Please analyze this image."],
        ["type" => "image_url", "image_url" => ["url" => $imageBase64]]
    ];
    $apiMessages[] = ["role" => "user", "content" => $visionPayload];
    $dbContentToSave = json_encode($visionPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    $apiMessages[] = ["role" => "user", "content" => $userMessageText];
}

// ==========================================
// 7. 串流請求至 OpenAI-Compatible API (BYOK)
// ==========================================
// 🚀 決定發送者身份（串流前）
$senderName = '';
if ($currentUser['id']) {
    $senderName = $currentUser['username'];
} else {
    if (empty($_SESSION['guest_id'])) {
        $_SESSION['guest_id'] = bin2hex(random_bytes(8));
    }
    $shortId = strtoupper(substr($_SESSION['guest_id'], 0, 4));
    $senderName = __('Anonymous') . ' #' . $shortId;
}

// Streaming contract: all session writes finished; never session_start after beginSse()
AppBootstrap::sessionClose();

LlmStreamProxy::beginSse();
LlmStreamProxy::emit(['type' => 'status', 'phase' => 'generating']);

// BYOK: do not force thinking param (providers vary); stream whatever deltas arrive
$streamResult = LlmStreamProxy::streamCompletion(
    $targetApiUrl,
    $targetApiKey,
    [
        'model' => $targetModel,
        'messages' => $apiMessages,
        'max_tokens' => 2500,
        'temperature' => 0.7,
    ],
    static function (string $kind, string $text): void {
        if ($kind === 'thinking') {
            LlmStreamProxy::emit(['type' => 'thinking', 'text' => $text]);
        } else {
            LlmStreamProxy::emit(['type' => 'content', 'text' => $text]);
        }
    },
    120
);

if (!empty($streamResult['error']) && $streamResult['content'] === '' && $streamResult['reasoning'] === '') {
    $errorDetail = (string)$streamResult['error'];
    LlmStreamProxy::emit([
        'type' => 'error',
        'error' => __('Custom API Engine Error', ['error' => $errorDetail]),
        'needs_upgrade' => false,
    ]);
    LlmStreamProxy::emitDone();
    exit;
}

$finishReason = (string)($streamResult['finish_reason'] ?? '');
$aiReply = LlmStreamProxy::pickReply(
    (string)$streamResult['content'],
    (string)$streamResult['reasoning']
);
$isTruncated = ($aiReply !== '') && in_array($finishReason, ['length', 'max_tokens'], true);

if ($aiReply === '') {
    LlmStreamProxy::emit([
        'type' => 'error',
        'error' => __('Empty AI reply'),
        'finish_reason' => $finishReason,
        'needs_upgrade' => false,
    ]);
    LlmStreamProxy::emitDone();
    exit;
}

// ==========================================
// 8. 儲存對話紀錄 (無縫接軌前端 UI)
// ==========================================
try {
    $freshPdo = Database::getFreshConnection();
    $freshPdo->beginTransaction();
    
    $ins = $freshPdo->prepare("INSERT INTO chat_messages (soul_id, session_token, role, sender_name, content) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$soulId, $sessionToken, 'user', $senderName, $dbContentToSave]);
    $ins->execute([$soulId, $sessionToken, 'assistant', __('AI Assistant'), $aiReply]);
    
    if ($isVisionFallback && $currentUser['id']) {
        $freshPdo->prepare("UPDATE users SET daily_chat_count = daily_chat_count + 1 WHERE id = ?")->execute([$currentUser['id']]);
    }

    if ($updateMemory) {
        $freshPdo->prepare("INSERT INTO chat_memory (session_token, summary, last_message_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE summary = VALUES(summary), last_message_id = VALUES(last_message_id)")
                 ->execute([$sessionToken, $chatMemory, $lastMessageId]);
    }
    
    $freshPdo->commit();

    LlmStreamProxy::emit([
        'type' => 'done',
        'success' => true,
        'reply' => $aiReply,
        'sender_name' => __('AI Assistant'),
        'truncated' => $isTruncated,
        'needs_upgrade' => false,
        'finish_reason' => $finishReason,
    ]);
    LlmStreamProxy::emitDone();

} catch (Throwable $e) {
    if (isset($freshPdo) && $freshPdo->inTransaction()) $freshPdo->rollBack();
    LlmStreamProxy::emit([
        'type' => 'error',
        'error' => __('DB Sync Error', ['error' => $e->getMessage()]),
        'needs_upgrade' => false,
    ]);
    LlmStreamProxy::emitDone();
}
?>