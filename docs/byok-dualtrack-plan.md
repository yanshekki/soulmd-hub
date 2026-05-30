## 🛠️ 第一章：核心設計理念與架構分離

為了解決 Web2.5 混合模式下的計費污染、彈窗錯位及安全漏洞，本專案捨棄一體化修改，全面採用 **「關注點分離 (Separation of Concerns)」** 的雙軌制端點架構：

1. **`api/chat.php` (官方計費軌道)**
   * **定位**：完全沿用平台既有的商業計劃。
   * **規則**：針對免費用戶及付費用戶執行嚴格的 `DAILY_LIMIT` 與 `MAX_TURNS` 扣減。
   * **金鑰**：燃燒平台官方的 API 額度。

2. **`api/self-chat.php` (用戶自備金鑰軌道 - BYOK Proxy)**
   * **定位**：100% 左手交右手、免扣除平台次數的無狀態代理。
   * **規則**：**不限制** 對話輪數與每日上限。即使是免費用戶，只要設定了自備金鑰並持有/租用了 AI 靈魂，即可無限次 Call API。
   * **金鑰**：動態解密並燃燒用戶存放在數據庫中的自備金鑰。

---

## 🔒 第二章：金融級數據庫安全與加密架構 (Requirement 2)

用戶的 OpenAI / DeepSeek 金鑰隨時綁定了信用卡，**絕對不能明文 (Plain Text) 存入數據庫**。

### 1. 系統超級金鑰 (Master Key)
* 於 `private/config.php` 中寫死一條 32 位元的隨機強加密鹽：`define('APP_ENCRYPTION_KEY', '...');`。

### 2. 雙向加密演算法 (AES-256-CBC)
* 創建 `private/includes/encryption.php` 安全模組。
* **寫入時**：利用 `openssl_encrypt` 將明文 API Key 加上隨機 IV 加密，並以 `Base64(密文::IV)` 存入資料表。
* **讀取時**：即時切分密文與 IV，於內存 (Memory) 中即時解密，絕不留痕。
* **脫敏防護 (Masking)**：`api/settings.php` 讀取資料回傳給前端時，必須自動將金鑰遮蔽（例如：`sk-p...4x9z`），防止用戶在畫面上被竊看。

### 3. 全新獨立數據表結構 (`user_llm_settings`)

```

```text
✅ Success: File written to docs/byok-dualtrack-plan.md

```sql
CREATE TABLE `user_llm_settings` (
  `user_id` bigint(20) NOT NULL,
  `use_byok` tinyint(1) DEFAULT 0,                 -- 0: 平台 AI, 1: 自備引擎
  `memory_compress_threshold` int(11) DEFAULT 10,  -- 記憶體壓縮頻率滑桿設定值
  
  -- 文字推理模型設定 (Text LLM)
  `text_provider` varchar(50) DEFAULT 'openai',
  `text_model` varchar(100) DEFAULT 'gpt-4o',
  `text_api_url` varchar(255) DEFAULT '[https://api.openai.com/v1/chat/completions](https://api.openai.com/v1/chat/completions)',
  `text_api_key` text DEFAULT NULL,                -- AES-256 加密存儲
  
  -- 圖像分析模型設定 (Vision LLM)
  `vision_provider` varchar(50) DEFAULT 'openai',
  `vision_model` varchar(100) DEFAULT 'gpt-4o',
  `vision_api_url` varchar(255) DEFAULT '[https://api.openai.com/v1/chat/completions](https://api.openai.com/v1/chat/completions)',
  `vision_api_key` text DEFAULT NULL,                -- AES-256 加密存儲
  
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

```

---

## 🎛️ 第三章：大一統控制台 `my-setting.php` 設計 (Requirement 5)

徹底剷除老舊死板的黑色底色，全面換上 **「高對比翡翠綠至藍綠漸變 (`bg-gradient-to-r from-emerald-400 to-teal-500`)」** 的硬核科技感 Dashboard。介面採用左側側邊欄分頁 (Tabs) 結構：

### Tab 1：帳戶安全 (Account)

* 修改密碼功能（舊密碼、新密碼、確認新密碼）。

### Tab 2：Web3 錢包連接 (Web3 Identity)

* 整合 MyNearWallet 跳轉連接，展示當前永久綁定的 NEAR 錢包地址（維持每個帳號只能綁定一次的硬限制）。

### Tab 3：平台 API 金鑰 (Developer Token)

* 供外接程式呼叫平台使用的平台 API KEY 生成與重置輪替 (Rotation) 功能。

### Tab 4：自訂 AI 引擎 (BYOK Engine) (Requirement 3 & 4)

* **主開關 Toggle**：「使用平台提供的 AI（受方案用量限制）」 VS 「使用自備金鑰（解鎖無限對話）」。
* **OpenAI-Compatible 5 大預設下拉選單**：
1. **OpenAI** (官方：`gpt-4o`, `gpt-4o-mini`)
2. **DeepSeek** (官方：`deepseek-chat`, `deepseek-reasoner`)
3. **Together AI** (開源：`meta-llama/Llama-3.3-70B-Instruct-Turbo`)
4. **Groq** (極速：`llama-3.3-70b-versatile`)
5. **OpenRouter** (最強聚合：支持一條格式 Call 遍 Claude 3.5 / Gemini)
6. **Custom** (允許用戶自由填寫任何相容 OpenAI 格式的第三方 URL 與 Model)


* **雙模型獨立設定**：允許分別設定「文字推理模型」與「圖像分析模型」的 URL、Model 與金鑰。
* **記憶體壓縮頻率控制滑桿 (Slider)**：可自由調整每 4 至 50 輪觸發一次 `chat_memory` 壓縮，並提示用戶：「設定較低的輪數能有效節省您自己的 API 金鑰開支，防止上下文過長導致扣費暴增」。

---

## 🔀 第四章：智能分流路由與混合計費 Fallback 邏輯 (Requirement 1)

當聊天室前端發起對話時，系統會啟動 **「智能路由閘道」** 進行精準分流：

```
                    [ 用戶發起對話請求 ]
                             │
                  查閱 user_llm_settings
                             │
               ┌─────────────┴─────────────┐
        [use_byok == 0]             [use_byok == 1]
               │                           │
       🎯 路由至 chat.php         🎯 路由至 self-chat.php
               │                           │
      扣除平台 Daily Limit         解密用戶專屬加密金鑰
      若配額耗盡則彈出方案牆       不扣除任何平台次數配額

```

### 🚨 核心亮點：混合計費視覺 Fallback 機制

當用戶啟動了 BYOK 模式，但**掟了一張圖片 (Vision Request)** 給 AI 時：

1. 系統檢查用戶是否有填寫自備的 `vision_api_key`？
2. **有填寫** ➡️ 使用用戶自備的視覺模型 ➡️ **不扣除** 平台次數。
3. **無填寫 (觸發 Fallback)** ➡️ 降級使用平台官方的 Vision 金鑰 ➡️ 扣除用戶在平台計劃內的配額 ➡️ 若用戶在平台內的 Quota 亦不幸用盡，則彈出提示：`「您的自訂 Vision 金鑰未設定，且平台視覺額度已耗盡，請前往設定頁補全或升級計劃。」`

---

## 🛡️ 第五章：AgentFi Web3 黑盒安全門禁 (Left-to-Right Proxy)

為了解決用戶「既要用外接 API 程式，又不能暴露出 AI 靈魂底層機密 Prompt (IP)」的商業剛需，`api/self-chat.php` 必須嚴格執行以下 Web3 黑盒代理門禁：

1. **嚴格 Token-Gating 檢查**：
* 100% 移植官方 NEAR RPC 校驗代碼。
* 調用者必須持有該 AI 靈魂的 NFT，或者在合約上有「生效中」的租約 (Active Rent Window)。


2. **無狀態 Prompt 注入 (左手交右手)**：
* 通過合約驗證後，系統從後台資料庫抽取出原創者的 Prompt 設定。
* 將原創者的 Prompt 注入到 `system` 角色，再將用戶的 `api_key` 注入到請求頭，直接在後端發送給大模型。
* 原創 Prompt **全程鎖死在後端**，前端或外接 API 調用者永遠只能拿到最後的文字回覆，100% 捍衛知識產權！



---

## 📅 第六章：四階段安全實施步驟表

* **Phase 1 (底層安全準備)**：
* 修改 `config.php` 納入 Master Key。
* 建立 `encryption.php` 加密 Helper。
* 於 MySQL 執行建表 SQL。


* **Phase 2 (控制台上線)**：
* 部署 `public_html/my-setting.php` 翡翠綠大一統設定介面。
* 部署 `api/settings.php` 讀寫加密接口。
* 更新全站多語言語言包 (`languages/`) 納入設定選單詞典。


* **Phase 3 (雙軌 API 部署)**：
* 編寫 `api/self-chat.php`（納入智能 Fallback、黑盒 Prompt 注入、NEAR 門禁校驗）。
* 優化 `api/chat.php` 保持最純淨的官方計費邏輯。


* **Phase 4 (聊天前端對接與用量視覺化)**：
* 修改 `chat-scripts.php`。在發送訊息前判斷用戶設定：若開起 BYOK 則發送至 `self-chat.php`；若關閉則發送至 `chat.php`。
* 聊天室上方動態顯示用量提示：使用平台時顯示 `「您正在使用平台預設引擎（剩餘 X 次）」`；使用自備金鑰時顯示高亮 `「⚡ BYOK 模式已啟動（無限制暢聊）」`。
