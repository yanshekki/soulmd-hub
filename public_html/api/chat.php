<?php
/**
 * SoulMD Hub Public API
 * GET  /api/chat - Fetch chat history for a session
 * POST /api/chat - Send a new message to the AI with Configured CSRF, Rate Limit, & Trial Limit protections
 */

// 延長 PHP 腳本 execution 時間限制
set_time_limit(120);

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
// Handle GET: Fetch Chat History
// ==========================================
if ($method === 'GET') {
    $soulId = (int)($_GET['soul_id'] ?? 0);
    $sessionToken = $_GET['session_token'] ?? '';

    if (!$soulId || empty($sessionToken)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT role, content FROM chat_messages WHERE soul_id = ? AND session_token = ? ORDER BY id ASC");
        $stmt->execute([$soulId, $sessionToken]);
        $messages = $stmt->fetchAll();

        echo json_encode(['success' => true, 'messages' => $messages], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to load chat history.']);
    }
    exit;
}

// ==========================================
// Handle POST: Send Message to AI
// ==========================================
if ($method === 'POST') {
    // 🛡️ 安全防護 1：CSRF Token 驗證
    $headers = getallheaders();
    $userCsrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
    $serverCsrfToken = $_SESSION['chat_csrf_token'] ?? '';

    if (empty($serverCsrfToken) || !hash_equals($serverCsrfToken, $userCsrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security validation failed. Unauthorized API request.']);
        exit;
    }

    // 🛡️ 安全防護 2：Rate Limiting 頻率控制
    $currentTime = time();
    $lastChatTime = $_SESSION['last_chat_time'] ?? 0;
    $minInterval = 3; 

    if (($currentTime - $lastChatTime) < $minInterval) {
        http_response_code(429); 
        echo json_encode(['success' => false, 'error' => 'You are sending messages too fast. Please wait a few seconds.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $soulId = (int)($input['soul_id'] ?? 0);
    $sessionToken = trim($input['session_token'] ?? '');
    $userMessage = trim($input['content'] ?? '');

    if (!$soulId || empty($sessionToken) || empty($userMessage)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }

    if (mb_strlen($userMessage, 'UTF-8') > MAX_INPUT_CHARS) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Message exceeds ' . MAX_INPUT_CHARS . ' characters limit.']);
        exit;
    }

    try {
        // 🛡️ 安全防護 3：動態自 config.php 讀取 MAX_FREE_TURNS 後端攔截
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE soul_id = ? AND session_token = ? AND role = 'user'");
        $countStmt->execute([$soulId, $sessionToken]);
        $userMsgCount = (int)$countStmt->fetchColumn();

        if ($userMsgCount >= MAX_FREE_TURNS) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Trial limit reached. You have exceeded the maximum allowed ' . MAX_FREE_TURNS . ' messages for this free session.']);
            exit;
        }

        // 1. Fetch the Soul details (System Prompt)
        $stmt = $pdo->prepare("SELECT content, file_type FROM souls WHERE id = ? AND is_public = 1");
        $stmt->execute([$soulId]);
        $soul = $stmt->fetch();

        if (!$soul) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Persona not found or is private.']);
            exit;
        }

        $systemPrompt = "";
        if ($soul['file_type'] === 'full_soul_folder') {
            $systemPrompt .= "Please adopt the following modular AI persona. The persona is defined across several modules below. Read and internalize all rules, styles, and context before interacting with me.\n\n";
            
            $cleanedContent = str_replace("\\'", "'", $soul['content']);
            $files = json_decode($cleanedContent, true);
            
            if (is_array($files)) {
                foreach ($files as $filename => $fileContent) {
                    if (strpos($filename, 'ERROR.md') !== false) continue;
                    $systemPrompt .= "=========================================\n";
                    $systemPrompt .= "MODULE: {$filename}\n";
                    $systemPrompt .= "=========================================\n\n";
                    $systemPrompt .= (is_string($fileContent) ? $fileContent : json_encode($fileContent, JSON_UNESCAPED_UNICODE)) . "\n\n";
                }
            }
        } else {
            $systemPrompt = $soul['content'];
        }

        // 🚨 動態指令：根據 MAX_AI_TOKENS 計算字數限制 (安全系數 0.6)
        $maxWords = max(50, floor(MAX_AI_TOKENS * 0.6));
        $systemPrompt .= "\n\n[CRITICAL SYSTEM DIRECTIVE: You must keep your responses extremely concise. Do not exceed {$maxWords} words or Chinese characters per response.]";

        // 2. Fetch the latest 10 messages for context
        $histStmt = $pdo->prepare("SELECT role, content FROM chat_messages WHERE soul_id = ? AND session_token = ? ORDER BY id DESC LIMIT 10");
        $histStmt->execute([$soulId, $sessionToken]);
        $history = array_reverse($histStmt->fetchAll());

        // 3. Build API Payload
        $apiMessages = [];
        $apiMessages[] = [ "role" => "system", "content" => $systemPrompt ];
        
        foreach ($history as $msg) {
            $apiMessages[] = [ "role" => $msg['role'], "content" => $msg['content'] ];
        }
        $apiMessages[] = [ "role" => "user", "content" => $userMessage ];

        // 4. Call AI API
        $ch = curl_init();
        
        // 動態自 config.php 讀取 MAX_AI_TOKENS 參數
        $payload = json_encode([
            "model" => DEEPSEEK_MODEL,
            "messages" => $apiMessages,
            "max_tokens" => MAX_AI_TOKENS, 
            "temperature" => 0.7,
            "stream" => false
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        if (!$payload) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to encode AI payload.']);
            exit;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => DEEPSEEK_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 55,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . DEEPSEEK_API_KEY
            ]
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            http_response_code(504); 
            echo json_encode(['success' => false, 'error' => 'The AI Service is generating a long response and timed out. (' . $error_msg . ')']);
            exit;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $responseData = json_decode($response, true);

        if ($httpCode !== 200 || !empty($responseData['error'])) {
            $apiErrorMsg = $responseData['error']['message'] ?? 'Unknown AI Provider Error';
            $apiErrorMsg = str_ireplace(['deepseek', 'openai', 'anthropic'], 'the AI Provider', $apiErrorMsg);
            
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "API Error: " . $apiErrorMsg]);
            exit;
        }

        $aiReply = $responseData['choices'][0]['message']['content'] ?? '';

        if (empty($aiReply)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Received empty response from the AI Service.']);
            exit;
        }

        // 5. 儲存對話 (強制重連新 PDO 防止 timeout 斷線)
        $freshPdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $freshPdo->beginTransaction();
        $insertStmt = $freshPdo->prepare("INSERT INTO chat_messages (soul_id, session_token, role, content) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$soulId, $sessionToken, 'user', $userMessage]);
        $insertStmt->execute([$soulId, $sessionToken, 'assistant', $aiReply]);
        $freshPdo->commit();

        $_SESSION['last_chat_time'] = $currentTime;

        echo json_encode(['success' => true, 'reply' => $aiReply], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        if (isset($freshPdo) && $freshPdo->inTransaction()) {
            $freshPdo->rollBack();
        } elseif (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'An internal server error occurred while saving the chat.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}