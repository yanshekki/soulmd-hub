<?php
/**
 * SoulMD Hub Public API - Web2.5 BYOK Proxy Edition
 * (Stateless API Forwarding, Anti-Timeout DB Reconnect, Exponential Backoff & 100% i18n Error Matrix)
 */

set_time_limit(180);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// 🚨 放行 BYOK 專用的自訂 Headers 以通過 CORS 檢查
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization, X-Deepseek-Key, X-Vision-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../private/config.php';
require_once __DIR__ . '/../../private/src/Database.php';

// 🌍 載入後端 API 全域專屬語言包
loadTranslations('api');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();
$pdo = $db->getConnection();

$isApiCall = false;
$apiUserId = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$apiKey = trim(str_replace('Bearer', '', $authHeader));

if (!empty($apiKey)) {
    $isApiCall = true;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    if ($user = $stmt->fetch()) {
        $apiUserId = $user['id'];
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => __('Invalid API Key')], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function getCurrentUser($pdo, $apiUserId = null) {
    $userId = $apiUserId ?? ($_SESSION['user_id'] ?? null);
    $today = date('Y-m-d');

    if (!$userId) {
        if (($_SESSION['guest_last_chat_date'] ?? '') !== $today) {
            $_SESSION['guest_daily_count'] = 0;
            $_SESSION['guest_last_chat_date'] = $today;
        }
        return [
            'id' => null, 
            'tier' => 'free', 
            'daily_count' => (int)($_SESSION['guest_daily_count'] ?? 0),
            'is_expired' => false
        ];
    }

    $stmt = $pdo->prepare("SELECT id, tier, daily_chat_count, last_chat_date, vip_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) return ['id' => null, 'tier' => 'free', 'daily_count' => 0, 'is_expired' => false];

    $isExpired = false;
    if ($user['tier'] !== 'free' && $user['vip_expires_at'] && strtotime($user['vip_expires_at']) < time()) {
        $pdo->prepare("UPDATE users SET tier = 'free' WHERE id = ?")->execute([$userId]);
        $user['tier'] = 'free';
        $isExpired = true;
    }

    if ($user['last_chat_date'] !== $today) {
        $pdo->prepare("UPDATE users SET daily_chat_count = 0, last_chat_date = ? WHERE id = ?")->execute([$today, $userId]);
        $user['daily_chat_count'] = 0;
    }

    return [
        'id' => $user['id'],
        'tier' => $user['tier'],
        'daily_count' => $user['daily_chat_count'],
        'is_expired' => $isExpired
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
    $currentUser = getCurrentUser($pdo, $apiUserId);

    if ($isApiCall && $currentUser['tier'] === 'free') {
        http_response_code(403);
        $msg = $currentUser['is_expired'] ? __('API restricted expired') : __('API restricted free');
        echo json_encode(['success' => false, 'error' => $msg, 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$soulId || empty($sessionToken)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sessStmt = $pdo->prepare("SELECT user_id, is_private FROM chat_sessions WHERE session_token = ?");
    $sessStmt->execute([$sessionToken]);
    $chatSession = $sessStmt->fetch();

    if ($chatSession && $chatSession['is_private']) {
        if ($currentUser['id'] === null || $currentUser['id'] !== $chatSession['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => __('Access Denied Private')], JSON_UNESCAPED_UNICODE);
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
        echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($method === 'POST') {
    $currentUser = getCurrentUser($pdo, $apiUserId);

    // 🚀 BYOK 代理：攔截並驗證無狀態密鑰
    $byokDeepSeek = trim($_SERVER['HTTP_X_DEEPSEEK_KEY'] ?? '');
    $byokVision = trim($_SERVER['HTTP_X_VISION_KEY'] ?? '');

    if ($byokDeepSeek || $byokVision) {
        if ($currentUser['tier'] === 'free') {
            http_response_code(403);
            // 此處借用現成的翻譯，亦可使用全新設計的警告字眼
            echo json_encode(['success' => false, 'error' => 'BYOK Proxy Mode is exclusively reserved for VIP/PRO members to prevent server abuse. Please upgrade your plan.', 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($isApiCall) {
        if ($currentUser['tier'] === 'free') {
            http_response_code(403);
            $msg = $currentUser['is_expired'] ? __('API restricted expired') : __('API restricted free');
            echo json_encode(['success' => false, 'error' => $msg, 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
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
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? 'chat';
    $soulId = (int)($input['soul_id'] ?? 0);
    $sessionToken = trim($input['session_token'] ?? '');

    if ($action === 'update_privacy') {
        $isPrivate = isset($input['is_private']) ? (bool)$input['is_private'] : false;
        if (!$soulId || empty($sessionToken)) {
            http_response_code(400); echo json_encode(['success' => false]); exit;
        }
        
        $sessStmt = $pdo->prepare("SELECT user_id FROM chat_sessions WHERE session_token = ?");
        $sessStmt->execute([$sessionToken]);
        $chatSession = $sessStmt->fetch();

        if ($chatSession) {
            if ($currentUser['id'] !== null && $chatSession['user_id'] === $currentUser['id']) {
                $pdo->prepare("UPDATE chat_sessions SET is_private = ? WHERE session_token = ?")->execute([(int)$isPrivate, $sessionToken]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(403); echo json_encode(['success' => false, 'error' => __('Not the session owner')], JSON_UNESCAPED_UNICODE);
            }
        } else {
            $actualPrivate = ($currentUser['tier'] !== 'free') ? (int)$isPrivate : 0;
            $pdo->prepare("INSERT INTO chat_sessions (session_token, soul_id, user_id, is_private) VALUES (?, ?, ?, ?)")
                ->execute([$sessionToken, $soulId, $currentUser['id'], $actualPrivate]);
            echo json_encode(['success' => true]);
        }
        exit;
    }

    if (!$isApiCall) {
        $currentTime = time();
        if (($currentTime - ($_SESSION['last_chat_time'] ?? 0)) < 3) {
            http_response_code(429); 
            echo json_encode(['success' => false, 'error' => __('Sending too fast')], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $userMessageText = trim($input['content'] ?? '');
    $imageBase64 = $input['image'] ?? null;
    $isPrivate = isset($input['is_private']) ? (bool)$input['is_private'] : false;

    if (!$soulId || empty($sessionToken) || (empty($userMessageText) && empty($imageBase64))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => __('Missing required parameters')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tierConfig = getTierConfig($currentUser['tier']);

    if ($currentUser['daily_count'] >= $tierConfig['daily_limit']) {
        http_response_code(403);
        $upgradeMsg = $currentUser['tier'] === 'free' ? __('Upgrade suffix') : "";
        $errorMsg = __('Daily limit reached', ['limit' => $tierConfig['daily_limit'], 'upgrade' => $upgradeMsg]);
        echo json_encode(['success' => false, 'error' => $errorMsg, 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (mb_strlen($userMessageText, 'UTF-8') > $tierConfig['max_input']) {
        http_response_code(400);
        $errorMsg = __('Message exceeds chars', ['limit' => $tierConfig['max_input']]);
        echo json_encode(['success' => false, 'error' => $errorMsg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $isVisionRequest = false;
    if ($imageBase64) {
        if (!$tierConfig['allow_image']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => __('Vision AI exclusive'), 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
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
                echo json_encode(['success' => false, 'error' => __('Access Denied Private')], JSON_UNESCAPED_UNICODE);
                exit;
            }
            if ($currentUser['id'] !== null && $currentUser['id'] === $chatSession['user_id'] && $chatSession['is_private'] != $isPrivate) {
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
            $errorMsg = __('Free preview capacity reached', ['limit' => $tierConfig['max_turns']]);
            echo json_encode(['success' => false, 'error' => $errorMsg, 'needs_upgrade' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("SELECT content, file_type FROM souls WHERE id = ? AND is_public = 1");
        $stmt->execute([$soulId]);
        $soul = $stmt->fetch();
        if (!$soul) { http_response_code(404); echo json_encode(['success' => false, 'error' => __('Soul not found')], JSON_UNESCAPED_UNICODE); exit; }

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

            $compressModel = defined('FREE_MODEL') ? FREE_MODEL : 'deepseek-chat';
            // 🚀 BYOK Apply to Memory Summarizer as well
            $compressApiKey = $byokDeepSeek ?: DEEPSEEK_API_KEY;
            
            $chSum = curl_init();
            curl_setopt_array($chSum, [
                CURLOPT_URL => DEEPSEEK_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(["model" => $compressModel, "messages" => [["role" => "system", "content" => $sumPrompt]], "max_tokens" => 300, "temperature" => 0.3], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $compressApiKey],
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

        // 🚀 核心升級：BYOK 金鑰動態路由分配
        $targetApiUrl = DEEPSEEK_API_URL;
        $targetApiKey = $byokDeepSeek ?: DEEPSEEK_API_KEY;
        $targetModel = $tierConfig['model'];
        $finalPayloadMessages = [];

        if ($isVisionRequest && defined('VISION_API_URL') && defined('VISION_API_KEY')) {
            $targetApiUrl = VISION_API_URL;
            $targetApiKey = $byokVision ?: VISION_API_KEY;
            
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

        if (!$isApiCall) {
            $_SESSION['last_chat_time'] = time();
        }
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
                echo json_encode(['success' => false, 'error' => __('AI Service timeout')], JSON_UNESCAPED_UNICODE); 
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
            $errorDetail = $responseData['error']['message'] ?? 'Unknown Connection Failure';
            echo json_encode(['success' => false, 'error' => __('Engine Error', ['error' => $errorDetail])], JSON_UNESCAPED_UNICODE);
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
            } else {
                if (!$isApiCall) {
                    session_start();
                    $_SESSION['guest_daily_count'] = ($_SESSION['guest_daily_count'] ?? 0) + 1;
                    session_write_close();
                }
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
    } catch (Throwable $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => __('Fatal Server Exception', ['error' => $e->getMessage()])], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(405); echo json_encode(['success' => false, 'error' => __('Method Not Allowed')], JSON_UNESCAPED_UNICODE);
}