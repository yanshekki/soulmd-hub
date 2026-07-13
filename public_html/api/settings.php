<?php
/**
 * SoulMD Hub - Unified Settings API (LLM & BYOK)
 * Handles encrypted storage of API Keys. (i18n Fully Patched)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../private/includes/encryption.php';
require_once __DIR__ . '/../../private/src/AppBootstrap.php';
$app = AppBootstrap::forApi([
    'require_user' => true,
    'enforce_csrf' => true,
    'translations' => 'api',
    'json_header' => false,
]);
$userId = $app['user_id'];
$pdo = $app['pdo'];
$isApiKey = !empty($app['is_api_key']);
$apiKey = $app['api_key'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM user_llm_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch();
    
    if (!$settings) {
        echo json_encode(['success' => true, 'data' => [
            'use_byok' => 0, 
            'memory_compress_threshold' => 10, 
            'text_provider' => 'openai', 
            'text_model' => 'gpt-4o', 
            'text_api_url' => 'https://api.openai.com/v1/chat/completions', 
            'text_api_key' => '', 
            'vision_provider' => 'openai', 
            'vision_model' => 'gpt-4o', 
            'vision_api_url' => 'https://api.openai.com/v1/chat/completions', 
            'vision_api_key' => ''
        ]]);
        exit;
    }

    if (!empty($settings['text_api_key'])) {
        $realKey = decryptData($settings['text_api_key']);
        $settings['text_api_key'] = substr($realKey, 0, 4) . '...' . substr($realKey, -4);
    }
    if (!empty($settings['vision_api_key'])) {
        $realKey = decryptData($settings['vision_api_key']);
        $settings['vision_api_key'] = substr($realKey, 0, 4) . '...' . substr($realKey, -4);
    }
    
    echo json_encode(['success' => true, 'data' => $settings]);
    exit;
}

if ($method === 'POST') {
    // CSRF already enforced centrally by ApiSecurity::initialize() for session users
    $input = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("SELECT text_api_key, vision_api_key FROM user_llm_settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $oldData = $stmt->fetch();

    $use_byok = (int)($input['use_byok'] ?? 0);
    $compress = (int)($input['memory_compress_threshold'] ?? 10);
    
    $t_provider = $input['text_provider'] ?? 'openai';
    $t_model = $input['text_model'] ?? '';
    $t_url = $input['text_api_url'] ?? '';
    $t_key_input = $input['text_api_key'] ?? '';
    
    $t_key_final = (strpos($t_key_input, '...') !== false && $oldData) ? $oldData['text_api_key'] : encryptData($t_key_input);

    $v_provider = $input['vision_provider'] ?? 'openai';
    $v_model = $input['vision_model'] ?? '';
    $v_url = $input['vision_api_url'] ?? '';
    $v_key_input = $input['vision_api_key'] ?? '';
    
    $v_key_final = (strpos($v_key_input, '...') !== false && $oldData) ? $oldData['vision_api_key'] : encryptData($v_key_input);

    $sql = "INSERT INTO user_llm_settings 
            (user_id, use_byok, memory_compress_threshold, text_provider, text_model, text_api_url, text_api_key, vision_provider, vision_model, vision_api_url, vision_api_key) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            use_byok=VALUES(use_byok), memory_compress_threshold=VALUES(memory_compress_threshold),
            text_provider=VALUES(text_provider), text_model=VALUES(text_model), text_api_url=VALUES(text_api_url), text_api_key=VALUES(text_api_key),
            vision_provider=VALUES(vision_provider), vision_model=VALUES(vision_model), vision_api_url=VALUES(vision_api_url), vision_api_key=VALUES(vision_api_key)";
            
    try {
        $pdo->prepare($sql)->execute([
            $userId, $use_byok, $compress, 
            $t_provider, $t_model, $t_url, $t_key_final, 
            $v_provider, $v_model, $v_url, $v_key_final
        ]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500); 
        echo json_encode(['success' => false, 'error' => __('Internal Server Error')], JSON_UNESCAPED_UNICODE);
    }
}
?>