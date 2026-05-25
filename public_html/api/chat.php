<?php
/**
 * SoulMD Hub Public API - Smart Dual-Engine Routing Edition
 * (Bulletproof Session-Unlock, Anti-Timeout DB Reconnect & Exponential Backoff Architecture)
 */

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

function getCurrentUser($pdo) {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return ['id' => null, 'tier' => 'free', 'daily_count' => 0];

    $stmt = $pdo->prepare("SELECT id, tier, daily_chat_count, last_chat_date, vip_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) return ['id' => null, 'tier' => 'free', 'daily_count' => 0];

    if ($user['tier'] !== 'free' && $user['vip_expires_at'] && strtotime($user['vip_expires_at']) < time()) {
        $pdo->prepare("UPDATE users SET tier = 'free' WHERE id = ?")->execute([$userId]);
        $user['tier'] = 'free';
    }

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

if ($method === 'GET') {
    $soulId = (int)($_GET['soul_id'] ?? 0);
    $sessionToken = $_GET['session_token'] ?? '';
    $currentUser = getCurrentUser($pdo);

    if (!$soulId || empty($sessionToken)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
        exit;
    }

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
        $stmt = $pdo->prepare("SELECT role, content FROM chat_messages WHERE soul_id = ? AND session_token = ? ORDER BY id ASC");
        $stmt->execute([$soulId, $sessionToken]); 
        $messages = $stmt->fetchAll();

        echo json_encode(['success' => true, 'messages' => $messages], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) { 
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to load chat history.']);
    }
    exit;
}

if ($method === 'POST') {
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

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? 'chat';
    $soulId = (int)($input['soul_id'] ?? 0);
    $sessionToken = trim($input['session_token'] ?? '');
    
    $currentUser = getCurrentUser($pdo);

    // 🚨 全新獨立接口：實時同步私隱狀態
    if ($action === 'update_privacy') {
        $isPrivate = isset($input['is_private']) ? (bool)$input['is_private'] : false;
        if (!$soulId || empty($sessionToken)) {
            http_response_code(400); echo json_encode(['success' => false]); exit;
        }
        
        $sessStmt = $pdo->prepare("SELECT user_id FROM chat_sessions WHERE session_token = ?");
        $sessStmt->execute([$sessionToken]);
        $chatSession = $sessStmt->fetch();

        if ($chatSession) {
            if ($chatSession['user_id'] === $currentUser['id']) {
                $pdo->prepare("UPDATE chat_sessions SET is_private = ? WHERE session_token = ?")->execute([(int)$isPrivate, $sessionToken]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(403); echo json_encode(['success' => false, 'error' => 'Not the session owner.']);
            }
        } else {
            // 如果連對話都未開始，預先建立對話 Session 並上鎖
            $actualPrivate = ($currentUser['tier'] !== 'free') ? (int)$isPrivate : 0;
            $pdo->prepare("INSERT INTO chat_sessions (session_token, soul_id, user_id, is_private) VALUES (?, ?, ?, ?)")
                ->execute([$sessionToken, $soulId, $currentUser['id'], $actualPrivate]);
            echo json_encode(['success' => true]);
        }
        exit;
    }

    // 防禦炸彈請求 (只對發送對話有效)
    $currentTime = time();
    if (($currentTime - ($_SESSION['last_chat_time'] ?? 0)) < 3) {
        http_response_code(429); 
        echo json_encode(['success' => false, 'error' => 'Sending too fast. Please wait 3 seconds.']);
        exit;
    }

    $userMessageText = trim($input['content'] ?? '');
    $imageBase64 = $input['image'] ?? null;
    $isPrivate = isset($input['is_private']) ? (bool)$input['is_private'] : false;

    if (!$soulId || empty($sessionToken) || (empty($userMessageText) && empty($imageBase64))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }

    $tierConfig = getTierConfig($currentUser['tier']);

    if ($currentUser['daily_count'] >= $tierConfig['daily_limit']) {
        http_response_code(403);
        $upgradeMsg = $currentUser['tier'] === 'free' ? " Upgrade your tier to unlock higher daily capacity!" : "";
        echo json_encode(['success' => false, 'error' => "Daily anti-bot limit reached ({$tierConfig['daily_limit']} messages). Please try again tomorrow.{$upgradeMsg}", 'needs_upgrade' => true]);
        exit;
    }

    if (mb_strlen($userMessageText, 'UTF-8') > $tierConfig['max_input']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Message exceeds the {$tierConfig['max_input']} characters limit for your tier."]);
        exit;
    }

    $isVisionRequest = false;
    if ($imageBase64) {
        if (!$tierConfig['allow_image']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Vision AI (Image Upload) is an exclusive feature for VIP & PRO members.', 'needs_upgrade' => true]);
            exit;
        }
        $isVisionRequest = true;
    }

    try {
        $sessStmt = $pdo->prepare("SELECT user_id, is_private FROM chat_sessions WHERE session_token = ?");
        $sessStmt->execute([$sessionToken]);
        $chatSession = $sessStmt->fetch();

        if ($chatSession) {
            if ($chatSession['is_private'] && $currentUser['id'] !== $chatSession['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Access Denied to this private session.']);
                exit;
            }
            if ($currentUser['id'] === $chatSession['user_id'] && $chatSession['is_private'] != $isPrivate) {
                $pdo->prepare("UPDATE chat_sessions SET is_private = ? WHERE session_token = ?")->execute([(int)$isPrivate, $sessionToken]);
            }
        } else {
            $actualPrivate = ($currentUser['tier'] !== 'free') ? (int)$isPrivate : 0;
            $pdo->prepare("INSERT INTO chat_sessions (session_token, soul_id, user_id, is_private) VALUES (?, ?, ?, ?)")
                ->execute([$sessionToken, $soulId, $currentUser['id'], $actualPrivate]);
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE soul_id = ? AND session_token = ? AND role = 'user'");
        $countStmt->execute([$soulId, $sessionToken]);
        $userMsgCount = (int)$countStmt->fetchColumn();

        if ($userMsgCount >= $tierConfig['max_turns']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => "You have reached the free preview capacity of {$tierConfig['max_turns']} messages. Upgrade to unlock completely unlimited conversations.", 'needs_upgrade' => true]);
            exit;
        }

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

        $memStmt = $pdo->prepare("SELECT summary, last_message_id FROM chat_memory WHERE session_token = ?");
        $memStmt->execute([$sessionToken]);
        $memoryRow = $memStmt->fetch();
        $chatMemory = $memoryRow['summary'] ?? '';
        $lastMessageId = (int)($memoryRow['last_message_id'] ?? 0);

        $msgStmt = $pdo->prepare("SELECT id, role, content FROM chat_messages WHERE soul_id = ? AND session_token = ? AND id > ? ORDER BY id ASC");
        $msgStmt->execute([$soulId, $sessionToken, $lastMessageId]);
        $unsummarized = $msgStmt->fetchAll();

        $updateMemory = false; 

        if (count($unsummarized) >= $tierConfig['memory_threshold']) {
            $toSummarize = array_slice($unsummarized, 0, -2);
            $keptMessages = array_slice($unsummarized, -2);
            $sumPrompt = "Compress the following chat log into a short summary under 150 words focus on facts.\n[OLD MEMORY]\n{$chatMemory}\n[NEW LOGS]\n";
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
                ["type" => "text", "text" => $userMessageText ?: "Please analyze this attached asset template file."],
                ["type" => "image_url", "image_url" => ["url" => $imageBase64]]
            ];
            $apiMessages[] = ["role" => "user", "content" => $visionPayload];
            $dbContentToSave = json_encode($visionPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $apiMessages[] = ["role" => "user", "content" => $userMessageText];
        }

        $targetApiUrl = DEEPSEEK_API_URL;
        $targetApiKey = DEEPSEEK_API_KEY;
        $targetModel = $tierConfig['model'];
        $finalPayloadMessages = [];

        if ($isVisionRequest && defined('VISION_API_URL') && defined('VISION_API_KEY')) {
            $targetApiUrl = VISION_API_URL;
            $targetApiKey = VISION_API_KEY;
            
            if ($currentUser['tier'] === 'pro' && defined('PRO_VISION_MODEL')) {
                $targetModel = PRO_VISION_MODEL; 
            } else {
                $targetModel = defined('VIP_VISION_MODEL') ? VIP_VISION_MODEL : 'meta-llama/Llama-3.2-90B-Vision-Instruct-Turbo';
            }
            $finalPayloadMessages = $apiMessages; 
        } else {
            foreach ($apiMessages as $msg) {
                $role = $msg['role'];
                $content = $msg['content'];
                
                if (is_array($content)) {
                    $textPayloadStr = "";
                    foreach ($content as $part) {
                        if (isset($part['type'])) {
                            if ($part['type'] === 'text') {
                                $textPayloadStr .= $part['text'];
                            } elseif ($part['type'] === 'image_url') {
                                $textPayloadStr .= "\n[System Notice: The user has attached an image template file at this position.]\n";
                            }
                        }
                    }
                    $finalPayloadMessages[] = ["role" => $role, "content" => trim($textPayloadStr)];
                } else {
                    $finalPayloadMessages[] = ["role" => $role, "content" => $content];
                }
            }
        }

        $_SESSION['last_chat_time'] = $currentTime;
        session_write_close(); 

        $pdo = null;
        $db = null;

        $maxRetries = 3;      
        $retryDelay = 2;      

        $response = '';
        $httpCode = 0;
        
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $ch = curl_init();
            $payload = json_encode([
                "model" => $targetModel, 
                "messages" => $finalPayloadMessages,
                "max_tokens" => $tierConfig['max_tokens'], 
                "temperature" => 0.7,
                "stream" => false
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

            curl_setopt_array($ch, [
                CURLOPT_URL => $targetApiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $targetApiKey]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_errno($ch);
            curl_close($ch);

            if ($curlError) { 
                http_response_code(504); 
                echo json_encode(['success' => false, 'error' => 'AI Service processing timeout.']); 
                exit; 
            }

            if ($httpCode !== 429) break;

            if ($attempt < $maxRetries - 1) {
                sleep($retryDelay);
                $retryDelay *= 2; 
            }
        }
        
        $responseData = json_decode($response, true);
        if ($httpCode !== 200 || !empty($responseData['error'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Engine Error: " . ($responseData['error']['message'] ?? 'Unknown Connection Failure')]);
            exit;
        }

        $aiReply = $responseData['choices'][0]['message']['content'] ?? '';

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
            
            if ($currentUser['id']) {
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
            echo json_encode(['success' => false, 'error' => 'DB Sync Error: ' . $e->getMessage()]);
        }
    } catch (Throwable $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => 'Fatal Server Exception: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405); echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}