<?php
/**
 * SoulMD Hub - BYOK Proxy API (self-chat.php)
 * 100% 左手交右手無狀態代理，不扣除平台 Daily Limit (Fallback 例外)。
 * 包含 Web3 Token-Gating, 自訂記憶壓縮, 及 Vision Fallback。
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

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';
require_once __DIR__ . '/../../private/includes/encryption.php';

loadTranslations('api');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE); 
    exit;
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(401); 
    echo json_encode(['success' => false, 'error' => __('Unauthorized Session')], JSON_UNESCAPED_UNICODE); 
    exit;
}

// ==========================================
// 1. CSRF 安全檢查
// ==========================================
$userCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($userCsrfToken) && function_exists('getallheaders')) {
    $headers = getallheaders();
    $userCsrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
}
$serverCsrfToken = $_SESSION['chat_csrf_token'] ?? '';

if (empty($serverCsrfToken) || empty($userCsrfToken) || !hash_equals($serverCsrfToken, $userCsrfToken)) {
    http_response_code(403); 
    echo json_encode(['success' => false, 'error' => __('Security validation failed')], JSON_UNESCAPED_UNICODE); 
    exit;
}

// ==========================================
// 2. 接收參數與檢查
// ==========================================
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$soulId = (int)($input['soul_id'] ?? 0);
$sessionToken = trim($input['session_token'] ?? '');
$userMessageText = trim($input['content'] ?? '');
$imageBase64 = $input['image'] ?? null;
$isPrivate = isset($input['is_private']) ? (bool)$input['is_private'] : false;

if (!$soulId || empty($sessionToken) || (empty($userMessageText) && empty($imageBase64))) {
    http_response_code(400); 
    echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE); 
    exit;
}

// 獲取用戶資料與 BYOK 設定
$stmt = $pdo->prepare("SELECT id, tier, daily_chat_count, near_wallet_address FROM users WHERE id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch();

$setStmt = $pdo->prepare("SELECT * FROM user_llm_settings WHERE user_id = ?");
$setStmt->execute([$userId]);
$settings = $setStmt->fetch();

if (!$settings || $settings['use_byok'] != 1) {
    http_response_code(403); 
    echo json_encode(['success' => false, 'error' => 'BYOK mode is not enabled on your account.'], JSON_UNESCAPED_UNICODE); 
    exit;
}

// ==========================================
// 3. 智能 Fallback 與 API 分流邏輯
// ==========================================
$isVisionRequest = !empty($imageBase64);
$isVisionFallback = false;
$targetApiUrl = '';
$targetApiKey = '';
$targetModel = '';

if ($isVisionRequest) {
    if (empty($settings['vision_api_key'])) {
        // 🌟 Fallback 機制: 用戶無設定 Vision Key，降級使用平台配額
        $tierPrefix = strtoupper($currentUser['tier']);
        $dailyLimit = defined("{$tierPrefix}_DAILY_LIMIT") ? constant("{$tierPrefix}_DAILY_LIMIT") : 10;
        
        if ($currentUser['daily_chat_count'] >= $dailyLimit) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => '您的自訂 Vision 金鑰未設定，且平台視覺額度已耗盡，請前往設定頁補全或升級計劃。', 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $isVisionFallback = true;
        $targetApiUrl = defined('VISION_API_URL') ? VISION_API_URL : 'https://api.openai.com/v1/chat/completions';
        $targetApiKey = defined('VISION_API_KEY') ? VISION_API_KEY : '';
        
        // 嚴格跟隨平台等級分配視覺模型
        if ($currentUser['tier'] === 'pro' && defined('PRO_VISION_MODEL')) {
            $targetModel = PRO_VISION_MODEL; 
        } else {
            $targetModel = defined('VIP_VISION_MODEL') ? VIP_VISION_MODEL : 'meta-llama/Llama-3.2-90B-Vision-Instruct-Turbo';
        }
    } else {
        // 使用用戶自訂 Vision 模型
        $targetApiUrl = $settings['vision_api_url'];
        $targetApiKey = decryptData($settings['vision_api_key']);
        $targetModel = $settings['vision_model'];
    }
} else {
    // 🌟 純文字 LLM
    if (empty($settings['text_api_key'])) {
        http_response_code(403); 
        echo json_encode(['success' => false, 'error' => 'Text API Key is not set in your BYOK settings.'], JSON_UNESCAPED_UNICODE); 
        exit;
    }
    $targetApiUrl = $settings['text_api_url'];
    $targetApiKey = decryptData($settings['text_api_key']);
    $targetModel = $settings['text_model'];
}

// ==========================================
// 4. Session 管理與 Web3 Token-Gating 門禁
// ==========================================
$sessStmt = $pdo->prepare("SELECT user_id, is_private FROM chat_sessions WHERE session_token = ?");
$sessStmt->execute([$sessionToken]);
$chatSession = $sessStmt->fetch();

if ($chatSession) {
    if ($chatSession['is_private'] && $currentUser['id'] !== $chatSession['user_id']) {
        http_response_code(403); 
        echo json_encode(['success' => false, 'error' => __('Access Denied Private')], JSON_UNESCAPED_UNICODE); 
        exit;
    }
    // 同步更新 Privacy 狀態
    if ($currentUser['id'] === $chatSession['user_id'] && $chatSession['is_private'] != $isPrivate) {
        $pdo->prepare("UPDATE chat_sessions SET is_private = ? WHERE session_token = ?")->execute([(int)$isPrivate, $sessionToken]);
    }
} else {
    $pdo->prepare("INSERT INTO chat_sessions (session_token, soul_id, user_id, is_private) VALUES (?, ?, ?, ?)")
        ->execute([$sessionToken, $soulId, $currentUser['id'], (int)$isPrivate]);
}

$soulStmt = $pdo->prepare("SELECT content, file_type, user_id FROM souls WHERE id = ? AND is_public = 1");
$soulStmt->execute([$soulId]);
$soul = $soulStmt->fetch();
if (!$soul) { 
    http_response_code(404); 
    echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE); 
    exit; 
}

// 🌟 Web3 防盜門禁 (與 chat.php 完全一致)
$currentDbHash = 'sha256:' . hash('sha256', $soul['content']);
$chatUserWallet = $currentUser['near_wallet_address'] ?? '';
$creatorStmt = $pdo->prepare("SELECT near_wallet_address FROM users WHERE id = ?");
$creatorStmt->execute([$soul['user_id']]);
$creatorWallet = $creatorStmt->fetchColumn();

if (!empty($creatorWallet) && $chatUserWallet !== $creatorWallet) {
    $rpcPayload = json_encode([
        "jsonrpc" => "2.0", "id" => "dontcare", "method" => "query",
        "params" => [
            "request_type" => "call_function", "finality" => "final",
            "account_id" => defined('NEAR_CONTRACT_ID') ? NEAR_CONTRACT_ID : 'soulmd-hub.near', 
            "method_name" => "get_soul", 
            "args_base64" => base64_encode(json_encode(["token_id" => "soul_" . $soulId]))
        ]
    ]);

    $chRpc = curl_init('https://rpc.mainnet.near.org');
    curl_setopt_array($chRpc, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $rpcPayload, CURLOPT_TIMEOUT => 3
    ]);
    $rpcResult = curl_exec($chRpc);
    curl_close($chRpc);

    if ($rpcResult) {
        $rpcData = json_decode($rpcResult, true);
        if (isset($rpcData['result']['result'])) {
            $resString = implode(array_map('chr', $rpcData['result']['result']));
            $tokenInfo = json_decode($resString, true);
            if ($tokenInfo) {
                // Integrity Radar
                if (isset($tokenInfo['metadata']['extra']) && $tokenInfo['metadata']['extra'] !== $currentDbHash) {
                    echo json_encode(['success' => true, 'reply' => __("Security Interception")], JSON_UNESCAPED_UNICODE); exit;
                }
                // Token Gating
                $hasAccess = false;
                if ($tokenInfo['owner_id'] === $chatUserWallet) {
                    $hasAccess = true;
                } elseif (isset($tokenInfo['renters'][$chatUserWallet])) {
                    $expiryNano = (int)$tokenInfo['renters'][$chatUserWallet];
                    if ($expiryNano > time() * 1000000000) $hasAccess = true;
                }
                if (!$hasAccess) {
                    echo json_encode(['success' => true, 'reply' => "🔒 **Web3 Access Denied:** You must purchase or rent this AI Soul on the AgentFi Marketplace to interact with it."], JSON_UNESCAPED_UNICODE); 
                    exit;
                }
            }
        }
    }
}

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

    // 🌟 使用用戶自己的 Text API 進行壓縮，免扣平台錢
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
// 7. 發送請求至 OpenAI-Compatible API (BYOK)
// ==========================================
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetApiUrl,
    CURLOPT_RETURNTRANSFER => true, 
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode([
        "model" => $targetModel, 
        "messages" => $apiMessages, 
        "max_tokens" => 2500, 
        "temperature" => 0.7
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
    CURLOPT_CONNECTTIMEOUT => 10, 
    CURLOPT_TIMEOUT => 90,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $targetApiKey]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$responseData = json_decode($response, true);
if ($httpCode !== 200 || !empty($responseData['error'])) {
    http_response_code(400);
    $errorDetail = $responseData['error']['message'] ?? 'Unknown Connection Failure';
    echo json_encode(['success' => false, 'error' => "自訂 API 引擎錯誤: " . $errorDetail], JSON_UNESCAPED_UNICODE);
    exit;
}

$aiReply = $responseData['choices'][0]['message']['content'] ?? '';

// ==========================================
// 8. 儲存對話紀錄 (無縫接軌前端 UI)
// ==========================================
try {
    $freshPdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 15
    ]);
    
    $freshPdo->beginTransaction();
    
    $ins = $freshPdo->prepare("INSERT INTO chat_messages (soul_id, session_token, role, content) VALUES (?, ?, ?, ?)");
    $ins->execute([$soulId, $sessionToken, 'user', $dbContentToSave]);
    $ins->execute([$soulId, $sessionToken, 'assistant', $aiReply]);
    
    // 🌟 僅在觸發 Vision Fallback 的情況下，才扣除平台每日額度
    if ($isVisionFallback && $currentUser['id']) {
        $freshPdo->prepare("UPDATE users SET daily_chat_count = daily_chat_count + 1 WHERE id = ?")->execute([$currentUser['id']]);
    }

    if ($updateMemory) {
        $freshPdo->prepare("INSERT INTO chat_memory (session_token, summary, last_message_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE summary = VALUES(summary), last_message_id = VALUES(last_message_id)")
                 ->execute([$sessionToken, $chatMemory, $lastMessageId]);
    }
    
    $freshPdo->commit();

    echo json_encode(['success' => true, 'reply' => $aiReply], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($freshPdo) && $freshPdo->inTransaction()) $freshPdo->rollBack();
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => __('DB Sync Error', ['error' => $e->getMessage()])], JSON_UNESCAPED_UNICODE);
}
?>