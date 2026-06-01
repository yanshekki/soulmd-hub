<?php
/**
 * SoulMD Hub - Solutions & Architecture Mitigation Language Dictionary
 * Target: /docs/solutions
 */
return [
    'en' => [
        'Solutions_Title' => 'Enterprise Security & Architectural Mitigation',
        'Solutions_Desc' => 'Operating multi-modal agents exposes platforms to severe bottlenecks: computation debt from runtime inflation, gateway congestion from uncompressed binary streams, exponential token bleeding from history leakage, and direct asset duplication from transparent configurations. SoulMD Hub bypasses these obstacles via programmatic isolation fences.',
        
        // 1. BYOK
        'Sol_BYOK_Title' => '1. Stateless BYOK (Bring Your Own Key) Proxy Pipeline',
        'Sol_BYOK_Desc' => 'Standard architectures absorb immense text token overheads when processing complex user workflows. Malicious adversarial scripts manipulate dialogue prompts, forcing standard backend applications to expend official server balances, triggering API bankruptcy loops.',
        'Sol_BYOK_Storage' => 'AES-256-CBC Ledger Storage',
        'Sol_BYOK_Storage_Desc' => 'Credentials are salted with a unique initialization vector (IV) and deeply encrypted via a master key before database insertion.',
        'Sol_BYOK_Runtime' => 'Ephemeral Memory Garbage Collection',
        'Sol_BYOK_Runtime_Desc' => 'Keys are decrypted strictly within runtime memory. Once streaming terminates, explicit unset routines erase variables immediately. Zero footprint, zero token debt.',
        
        // 2. Canvas
        'Sol_Canvas_Title' => '2. GPU-Accelerated Client Canvas Re-Sampling',
        'Sol_Canvas_Desc' => 'Raw multimodal image frames heavily saturate transport lines. Large packet streaming slams into explicit request limits, consistently triggering gateway timeouts (HTTP 524) and payload buffer failures over standard web ports.',
        'Sol_Canvas_Code_Title' => 'Headless Canvas Interceptor Routine',
        'Sol_Canvas_Code_Desc' => 'The client-side engine offloads compression directly onto local browser hardware before packet transmission occurs:',
        'Sol_Canvas_Result' => 'Architecture Effect',
        'Sol_Canvas_Result_Desc' => 'Resamples raw megabyte assets down into a lean Base64 JPEG buffer (40KB - 90KB) locally. Transport overhead drops by 95%, permanently eliminating proxy delay.',

        // 3. Sliding Memory
        'Sol_Memory_Title' => '3. Deterministic Slide-Window Summary Matrix',
        'Sol_Memory_Desc' => 'Linear data growth inside historical statement tables causes input prompt bloat. Repeated iterations burn token allocations rapidly and trigger prompt context window exhaustion.',
        'Sol_Memory_Step1' => 'Matrix Polling',
        'Sol_Memory_Step1_Desc' => 'The background routine polls history depth metrics against the user subscription threshold.',
        'Sol_Memory_Step2' => 'Window Splitting',
        'Sol_Memory_Step2_Desc' => 'Older blocks are pruned instantly, keeping only recent core turns intact in memory.',
        'Sol_Memory_Step3' => 'Facts Distillation',
        'Sol_Memory_Step3_Desc' => 'Pruned arrays are routed to high-speed text summarizers, condensing legacy statements into a sub-150-word index.',
        'Sol_Memory_Step4' => 'Atomic Flashing',
        'Sol_Memory_Step4_Desc' => 'The data block is committed as a persistent facts system prefix, cutting context bleed entirely.',

        // 4. Off-Chain Fingerprint
        'Sol_Hash_Title' => '4. Cryptographic Hash & On-Chain Integrity Radar',
        'Sol_Hash_Desc' => 'Prompt creators face structural plagiarism if source prompts are exposed publicly. However, complete obfuscation prevents structural verification on secondary markets and leaves room for database tampering.',
        'Sol_Hash_Code_Title' => 'Web2.5 Hybrid Integrity Verification',
        'Sol_Hash_Code_Desc' => 'During asset creation, the local node combines the prompt architecture with a unique random salt, outputting a SHA-256 fingerprint hash. The smart contract logs strictly this hash.',
    ],
    'zh' => [
        'Solutions_Title' => '系統邊界防禦與技術痛點治理工程',
        'Solutions_Desc' => 'SaaS 平台在營運多模態智能體架構時，面臨四大毀滅性瓶頸：惡意刷量導致的算力破產、二進制大檔上傳引發的網關超時熔斷、對話歷史造成的 Token 幾何級數通脹、以及開源產權暴露危機。SoulMD Hub 從底層通信鏈路出發，全面實施雙軌安全邊界隔離。',
        
        // 1. BYOK
        'Sol_BYOK_Title' => '一、 算力邊界解耦：無狀態 BYOK 黑盒代理',
        'Sol_BYOK_Desc' => '傳統 AI SaaS 採用平台統一代付模式，惡意用戶可透過提示詞注入攻擊操縱上下文，迫使下游推理節點陷入高耗能死循環，瞬間燒乾平台官方金鑰額度，導致平台財政破產。我們的無狀態網關徹底隔離了此風險：',
        'Sol_BYOK_Storage' => 'AES-256-CBC 向量儲存層',
        'Sol_BYOK_Storage_Desc' => '用戶密鑰附加獨立初始化向量 (IV) 後，透過伺服器超級主鑰執行高密度對稱加密。即使數據庫物理洩漏亦無法還原明文。',
        'Sol_BYOK_Runtime' => 'Ephemeral Memory 垃圾回收',
        'Sol_BYOK_Runtime_Desc' => '端點僅在臨時內存解密金鑰並裝配 Prompt。數據流傳輸結束瞬間，立即觸發 unset() 物理級內存歸零。伺服器零持久化、零密鑰洩漏。',
        
        // 2. Canvas
        'Sol_Canvas_Title' => '二、 網絡拓撲超時熔斷：GPU 畫布硬件預壓縮',
        'Sol_Canvas_Desc' => '多模態場景中，用戶直傳的 4K 高清原圖體積龐大，經過網關係統時會產生極高擁塞 (Congestion)，直接撞擊 Cloudflare 100 秒網關生死線，導致 HTTP 524 超時錯誤。',
        'Sol_Canvas_Code_Title' => 'Headless Canvas 非同步截獲矩陣',
        'Sol_Canvas_Code_Desc' => '我們將耗費伺服器 CPU 的圖像縮放工作流，完美前置解耦到用戶的瀏覽器沙盒，利用底層硬體加速：',
        'Sol_Canvas_Result' => '絕對架構效應',
        'Sol_Canvas_Result_Desc' => '在網絡傳輸發生前，將幾 MB 的 Raw 圖片降維打擊為 40KB - 90KB 的 JPEG Base64 流。頻寬佔用暴跌 95%，完美跨越網關超時界限。',

        // 3. Sliding Memory
        'Sol_Memory_Title' => '三、 記憶體雪崩與 Token 通脹：確定性滑動視窗壓縮',
        'Sol_Memory_Desc' => '長對話 (Long-Context) 中，歷史訊息呈幾何級數堆疊，導致 Request 消耗的 Input Tokens 指數型通脹，迅速燃燒餘額並引發 Context Window 溢出。',
        'Sol_Memory_Step1' => '矩陣輪詢 (Matrix Polling)',
        'Sol_Memory_Step1_Desc' => '後台常駐執行緒自動依據用戶的訂閱計劃，對當前會話的歷史深度指標與熔斷閾值進行比對。',
        'Sol_Memory_Step2' => '視窗切分 (Window Splitting)',
        'Sol_Memory_Step2_Desc' => '一旦輪數超標，最舊的歷史區塊會瞬間從內存堆疊剝離，只保留最近的兩輪核心活躍緩存。',
        'Sol_Memory_Step3' => '事實蒸餾 (Facts Distillation)',
        'Sol_Memory_Step3_Desc' => '剝離的數據非同步投遞至高速推理節點，強行濃縮為低於 150 字的純事實摘要 (Facts Digest)。',
        'Sol_Memory_Step4' => '原子性寫入 (Atomic Flashing)',
        'Sol_Memory_Step4_Desc' => '摘要原子性寫入緩存，作為 System Frame 頂端前綴，徹底截斷 Token 歷史滲漏。',

        // 4. Off-Chain Fingerprint
        'Sol_Hash_Title' => '四、 產權洩漏與線下篡改：鏈上防篡改熔斷雷達',
        'Sol_Hash_Desc' => '提示詞若以 Web2 形式公開會遭惡意白嫖；若完全隱藏，租客又無法驗證其真實性，且無法防範平台管理員線下惡意篡改指令 (Rug Pull)。',
        'Sol_Hash_Code_Title' => 'Web2.5 混合數據密碼學校驗',
        'Sol_Hash_Code_Desc' => '區塊鏈上只記錄 SHA-256 指紋，不存放明文 Prompt。每次租客發起對話呼叫時，無狀態後端會向區塊鏈發起 RPC 查詢校驗：',
    ]
];