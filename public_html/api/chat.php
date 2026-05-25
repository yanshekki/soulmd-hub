<?php
/**
 * SoulMD Hub Public API - Multimodal Edition
 * GET  /api/chat - Fetch complete chat history (Includes historical Base64 images)
 * POST /api/chat - Send message to deepseek-v4-flash / deepseek-v4-pro with Vision AI
 */

// 延長 PHP 腳本 execution 時間限制，確保大圖傳輸與 PRO 模型思考不超時
set_time_limit(180);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();
$pdo = $db->getConnection();

// ==========================================
// 🛡️ 輔助函數：獲取當前用戶資訊、階級與過期檢查
// ==========================================
function getCurrentUser($pdo) {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return ['id' => null, 'tier' => 'free', 'daily_count' => 0];

    $stmt = $pdo->prepare("SELECT id, tier, daily_chat_count, last_chat_date, vip_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) return ['id' => null, 'tier' => 'free', 'daily_count' => 0];

    // 自動過期檢測：如果過期，即時降回 Free 階級
    if ($user['tier'] !== 'free' && $user['vip_expires_at'] && strtotime($user['vip_expires_at']) < time()) {
        $pdo->prepare("UPDATE users SET tier = 'free' WHERE id = ?")->execute([$userId]);
        $user['tier'] = 'free';
    }

    // 凌晨發言次數全自動重置邏輯
    $today = date('Y-m-d');
    if ($user['last_chat_date'] !== $today) {
        $pdo->prepare("UPDATE users SET daily_chat_count = 0, last_chat_date = ? WHERE id = ?")->execute([$today, $userId]);
        $user['daily_chat_count'] = 0;
    }

    return [
        'id' => $user['id'],
        'tier' => $user['tier'],
        'daily_count' => $user['daily_chat_count']
    ];
}

// ==========================================
// 🧠 輔助函數：根據 Tier 動態載入配置中心
// ==========================================
function getTierConfig($tier) {
    $prefix = strtoupper($tier);
    return [
        'model' => constant("{$prefix}_MODEL"),
        'max_turns' => constant("{$prefix}_MAX_TURNS"),
        'daily_limit' => constant("{$prefix}_DAILY_LIMIT"),
        'max_input' => constant("{$prefix}_MAX_INPUT_CHARS"),
        'max_tokens' => constant("{$prefix}_MAX_AI_TOKENS"),
        'memory_threshold' => constant("{$prefix}_MEMORY_THRESHOLD"),
        'allow_image' => constant("{$prefix}_ALLOW_IMAGE")
    ];
}

// ==========================================
// Handle GET: Fetch Chat History (🚨 完美支援圖片重現)
// ==========================================
if ($method === 'GET') {
    $soulId = (int)($_GET['soul_id'] ?? 0);
    $sessionToken = $_GET['session_token'] ?? '';
    $currentUser = getCurrentUser($pdo);

    if (!$soulId || empty($sessionToken)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
        exit;
    }

    // 🔒 嚴格私隱對話鎖定檢查 (防止 IDOR 惡意偷看 URL)
    $sessStmt = $pdo->prepare("SELECT user_id, is_private FROM chat_sessions WHERE session_token = ?");
    $sessStmt->execute([$sessionToken]);
    $chatSession = $sessStmt->fetch();

    if ($chatSession && $chatSession['is_private']) {
        if ($currentUser['id'] === null || $currentUser['id'] !== $chatSession['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access Denied. This chat session is private.']);
            exit;
        }
    }

    try {
        // 🚨 完美優化：直接撈取最原始嘅 MEDIUMTEXT，原封不動回傳 Base64 數據畀前端解碼顯示！
        $stmt = $pdo->prepare("SELECT role, content FROM chat_messages WHERE soul_id = ? AND session_token = ? ORDER BY id ASC");
        $stmt->execute([$soulId, $sessionToken]); 
        $messages = $stmt->fetchAll();

        echo json_encode(['success' => true, 'messages' => $messages], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) { // 👇 同時將 Exception 改為 Throwable，確保能捕捉所有 PHP 嚴重錯誤
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to load chat history.']);
    }
    exit;
}

// ==========================================
// Handle POST: Send Message to AI (最新 Vision 多模態整合)
// ==========================================
if ($method === 'POST') {
    // CSRF 令牌安全防禦 (兼容 Apache / Nginx)
    $userCsrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($userCsrfToken) && function_exists('getallheaders')) {
        $headers = getallheaders();
        $userCsrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
    }
    $serverCsrfToken = $_SESSION['chat_csrf_token'] ?? '';

    if (empty($serverCsrfToken) || empty($userCsrfToken) || !hash_equals($serverCsrfToken, $userCsrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security validation failed. Token mismatch.']);
        exit;
    }

    // 頻率限制 (Rate Limiting)
    $currentTime = time();
    if (($currentTime - ($_SESSION['last_chat_time'] ?? 0)) < 3) {
        http_response_code(429); 
        echo json_encode(['success' => false, 'error' => 'Sending too fast. Please wait 3 seconds.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $soulId = (int)($input['soul_id'] ?? 0);
    $sessionToken = trim($input['session_token'] ?? '');
    $userMessageText = trim($input['content'] ?? '');
    $imageBase64 = $input['image'] ?? null; // 接收來自前端 Canvas 壓縮後嘅 Base64 字串
    $isPrivate = isset($input['is_private']) ? (bool)$input['is_private'] : false;

    if (!$soulId || empty($sessionToken) || (empty($userMessageText) && empty($imageBase64))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }

    // 獲取當前發言用戶嘅等級及動態限制設定
    $currentUser = getCurrentUser($pdo);
    $tierConfig = getTierConfig($currentUser['tier']);

    // 🚨 【終極防破產硬上限】檢查今日發言次數
    if ($currentUser['daily_count'] >= $tierConfig['daily_limit']) {
        http_response_code(403);
        $upgradeMsg = $currentUser['tier'] === 'free' ? " Upgrade your tier to unlock higher daily capacity!" : "";
        echo json_encode(['success' => false, 'error' => "Daily anti-bot limit reached ({$tierConfig['daily_limit']} messages). Please try again tomorrow.{$upgradeMsg}", 'needs_upgrade' => true]);
        exit;
    }

    // 驗證輸入字數限制
    if (mb_strlen($userMessageText, 'UTF-8') > $tierConfig['max_input']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Message exceeds the {$tierConfig['max_input']} characters limit for your tier."]);
        exit;
    }

    // 驗證圖片多模態解鎖權限
    if ($imageBase64 && !$tierConfig['allow_image']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Vision AI (Image Upload) is an exclusive feature for VIP & PRO members.', 'needs_upgrade' => true]);
        exit;
    }

    try {
        // 🔒 初始化與動態更新 Chat Session (控制私隱鎖狀態)
        $sessStmt = $pdo->prepare("SELECT user_id, is_private FROM chat_sessions WHERE session_token = ?");
        $sessStmt->execute([$sessionToken]);
        $chatSession = $sessStmt->fetch();

        if ($chatSession) {
            if ($chatSession['is_private'] && $currentUser['id'] !== $chatSession['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access Denied to this private session.']);
                exit;
            }
            // 只有對話擁有人可以隨時切換 公開/私密 模式
            if ($currentUser['id'] === $chatSession['user_id'] && $chatSession['is_private'] != $isPrivate) {
                $pdo->prepare("UPDATE chat_sessions SET is_private = ? WHERE session_token = ?")->execute([(int)$isPrivate, $sessionToken]);
            }
        } else {
            // 第一次發言：Free 階級強制公開 (0)，VIP/PRO 遵循用戶開關設定
            $actualPrivate = ($currentUser['tier'] !== 'free') ? (int)$isPrivate : 0;
            $pdo->prepare("INSERT INTO chat_sessions (session_token, soul_id, user_id, is_private) VALUES (?, ?, ?, ?)")
                ->execute([$sessionToken, $soulId, $currentUser['id'], $actualPrivate]);
        }

        // 🚨 【商業鎖定】檢查單次對話 Session 嘅發言總次數 (主要針對 Free 限制 10 次)
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE soul_id = ? AND session_token = ? AND role = 'user'");
        $countStmt->execute([$soulId, $sessionToken]);
        $userMsgCount = (int)$countStmt->fetchColumn();

        if ($userMsgCount >= $tierConfig['max_turns']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => "You have reached the free preview capacity of {$tierConfig['max_turns']} messages. Upgrade to unlock completely unlimited conversations.", 'needs_upgrade' => true]);
            exit;
        }

        // 1. 撈取大腦人格特質設定 (System Prompt)
        $stmt = $pdo->prepare("SELECT content, file_type FROM souls WHERE id = ? AND is_public = 1");
        $stmt->execute([$soulId]);
        $soul = $stmt->fetch();
        if (!$soul) { http_response_code(404); echo json_encode(['success' => false, 'error' => 'Persona not found.']); exit; }

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

        $maxWords = max(50, floor($tierConfig['max_tokens'] * 0.6));
        $systemPrompt .= "\n\n[CRITICAL DIRECTIVE: Keep responses extremely concise and under {$maxWords} words.]";

        // =========================================================
        // 🚨 智能記憶壓縮層 (相容多模態文字提取)
        // =========================================================
        $memStmt = $pdo->prepare("SELECT summary, last_message_id FROM chat_memory WHERE session_token = ?");
        $memStmt->execute([$sessionToken]);
        $memoryRow = $memStmt->fetch();
        $chatMemory = $memoryRow['summary'] ?? '';
        $lastMessageId = (int)($memoryRow['last_message_id'] ?? 0);

        $msgStmt = $pdo->prepare("SELECT id, role, content FROM chat_messages WHERE soul_id = ? AND session_token = ? AND id > ? ORDER BY id ASC");
        $msgStmt->execute([$soulId, $sessionToken, $lastMessageId]);
        $unsummarized = $msgStmt->fetchAll();

        if (count($unsummarized) >= $tierConfig['memory_threshold']) {
            $toSummarize = array_slice($unsummarized, 0, -2);
            $keptMessages = array_slice($unsummarized, -2);
            $sumPrompt = "Compress the following chat log into a short summary under 150 words focus on facts.\n[OLD MEMORY]\n{$chatMemory}\n[NEW LOGS]\n";
            foreach ($toSummarize as $m) {
                $txt = $m['content'];
                $parsedTxt = json_decode($txt, true);
                // 如果歷史紀錄係多模態陣列，壓縮時只提取純文字部分，避免把 Base64 塞給總結大腦
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
                CURLOPT_URL => DEEPSEEK_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(["model" => "deepseek-v4-flash", "messages" => [["role" => "system", "content" => $sumPrompt]], "max_tokens" => 300, "temperature" => 0.3], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . DEEPSEEK_API_KEY],
                CURLOPT_TIMEOUT => 15
            ]);
            $sumRes = curl_exec($chSum);
            if (curl_getinfo($chSum, CURLINFO_HTTP_CODE) === 200 && $sumRes) {
                $newSummary = json_decode($sumRes, true)['choices'][0]['message']['content'] ?? '';
                if ($newSummary) {
                    $chatMemory = $newSummary;
                    $lastMessageId = end($toSummarize)['id'];
                    $pdo->prepare("INSERT INTO chat_memory (session_token, summary, last_message_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE summary = VALUES(summary), last_message_id = VALUES(last_message_id)")->execute([$sessionToken, $chatMemory, $lastMessageId]);
                    $unsummarized = $keptMessages;
                }
            }
            curl_close($chSum);
        }

        // =========================================================
        // 🚨 核心改寫：根據最新多模態 API 規格構建 Payload
        // =========================================================
        if ($chatMemory) $systemPrompt .= "\n\n[CONTEXT MEMORY]\n" . $chatMemory;
        
        $apiMessages = [["role" => "system", "content" => $systemPrompt]];
        
        // 載入未壓縮的歷史紀錄
        foreach ($unsummarized as $msg) {
            $parsed = json_decode($msg['content'], true);
            // 如果原本儲存嘅係多模態 JSON Array，直接還原成 Array 推畀 DeepSeek 保持記憶完美重現
            $apiMessages[] = ["role" => $msg['role'], "content" => (is_array($parsed) ? $parsed : $msg['content'])];
        }
        
        // 處理當前最新發言
        $dbContentToSave = $userMessageText;
        if ($imageBase64 && $tierConfig['allow_image']) {
            // 🚨 嚴格對齊最新官方多模態 JSON 陣列結構
            $visionPayload = [
                ["type" => "text", "text" => $userMessageText],
                ["type" => "image_url", "image_url" => ["url" => $imageBase64]]
            ];
            $apiMessages[] = ["role" => "user", "content" => $visionPayload];
            // 將多模態陣列 JSON 化存入 MEDIUMTEXT 資料庫，方便下次完美重現歷史圖文！
            $dbContentToSave = json_encode($visionPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $apiMessages[] = ["role" => "user", "content" => $userMessageText];
        }

        // 呼叫大模型 (Pro 會員會自動被分流路由去極限深思大腦 deepseek-v4-pro)
        $ch = curl_init();
        $payload = json_encode([
            "model" => $tierConfig['model'], 
            "messages" => $apiMessages,
            "max_tokens" => $tierConfig['max_tokens'], 
            "temperature" => 0.7,
            "stream" => false
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        curl_setopt_array($ch, [
            CURLOPT_URL => DEEPSEEK_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 65, // Pro 模型思考較慢，放寬至 65 秒防斷線
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . DEEPSEEK_API_KEY]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) { curl_close($ch); http_response_code(504); echo json_encode(['success' => false, 'error' => 'AI Service processing timeout.']); exit; }
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        if ($httpCode !== 200 || !empty($responseData['error'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "DeepSeek Engine Error: " . ($responseData['error']['message'] ?? 'Unknown Connection Failure')]);
            exit;
        }

        $aiReply = $responseData['choices'][0]['message']['content'] ?? '';

        // ==========================================
        // 5. 獨立事務安全存檔與計次
        // ==========================================
        $freshPdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $freshPdo->beginTransaction();
        
        $ins = $freshPdo->prepare("INSERT INTO chat_messages (soul_id, session_token, role, content) VALUES (?, ?, ?, ?)");
        $ins->execute([$soulId, $sessionToken, 'user', $dbContentToSave]);
        $ins->execute([$soulId, $sessionToken, 'assistant', $aiReply]);
        
        if ($currentUser['id']) {
            $freshPdo->prepare("UPDATE users SET daily_chat_count = daily_chat_count + 1 WHERE id = ?")->execute([$currentUser['id']]);
        }
        $freshPdo->commit();

        $_SESSION['last_chat_time'] = $currentTime;
        echo json_encode(['success' => true, 'reply' => $aiReply], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        if (isset($freshPdo) && $freshPdo->inTransaction()) $freshPdo->rollBack();
        http_response_code(500); echo json_encode(['success' => false, 'error' => 'Internal Server Error while synchronizing chat frames.']);
    }
} else {
    http_response_code(405); echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}