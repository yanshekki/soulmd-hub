# SoulMD Hub: Web2.5 AgentFi & BYOK Proxy Architecture Specification
## 終極 Web2.5 智能體金融與黑盒代理架構升級白皮書 (V2 完整版)

---

## 1. 執行摘要 (Executive Summary)

本規格書定義了 **SoulMD Hub** 從傳統 Web2 SaaS 轉型為 **Web2.5 AgentFi (智能體金融)** 生態系統的完整架構藍圖。
本次升級融合了兩大顛覆性核心機制：
1. **BYOK 代理黑盒 (Bring Your Own Key + Proxy Blackbox)**：實現平台算力「零成本無限擴展」，同時 100% 隱藏創作者的 Prompt 知識產權。
2. **NEAR 區塊鏈可進化 NFT (Updatable Agent NFT)**：實現智能體的鏈上資產化、去中心化買賣、租用分潤與防篡改存證。

---

## 2. 核心架構一：BYOK 代理黑盒 (零成本算力與 IP 保護)

為了解決平台承擔過多 API 算力成本，同時保障「租用模式」下創作者提示詞不被白嫖，系統引入 **BYOK (Bring Your Own Key) 後端代理機制**。

### 2.1 運作數據流 (Data Flow)
1. **用戶請求**：租用人（或免費免費用戶）在前端發送對話請求，並在系統中綁定自己的 DeepSeek / Together AI API Key。
2. **後端攔截 (PHP Proxy)**：請求不直接發往 LLM，而是先送到 SoulMD Hub 後端 (`api/chat.php`)。
3. **機密組裝**：PHP 後端從 MySQL 提取受保護的 `soul.md` (System Prompt)，在伺服器記憶體中與用戶的訊息進行組裝。
4. **代理發送**：PHP 使用 **租用人提供的自訂 API Key** 向 DeepSeek 發起 cURL 請求。
5. **回傳與銷毀**：獲取 AI 回覆後傳給前端，記憶體中的 Prompt 立即銷毀。
* **結果**：租用人完全無法透過 F12 Network Tab 攔截到原始 Prompt，實現完美的「黑盒出租 (Blackbox Leasing)」。

### 2.2 資料庫安全升級 (MySQL)
必須在資料庫中安全地儲存用戶的自訂 API Key，採用 AES-256 加密防止資料庫外洩導致的災難。
```sql
ALTER TABLE users 
ADD COLUMN custom_deepseek_key VARCHAR(255) NULL DEFAULT NULL COMMENT 'Encrypted DeepSeek Key',
ADD COLUMN custom_vision_key VARCHAR(255) NULL DEFAULT NULL COMMENT 'Encrypted Together AI Key';

```

### 2.3 伺服器資源防護機制 (Anti-Server Abuse for BYOK)
雖然 BYOK 免除了平台的 LLM API 算力成本，但高頻對話仍會大量消耗 MySQL 讀寫與後端頻寬。因此，BYOK 將設立以下門檻：
- **門禁限制**：BYOK 模式僅限活躍的 **VIP/PRO 付費訂閱用戶** 使用，免費沙盒用戶無權接入自訂密鑰，確保伺服器基礎維護成本被訂閱費完全覆蓋。
- **租用消耗費**：在黑盒租用模式下，除了代幣結算外，每次對話請求將受限於嚴格的 Rate Limiting，防止惡意腳本耗盡平台資源。

---

## 3. 核心架構二：Web2.5 混合身份層 (錢包綁定)

平台不強制用戶必須有 Web3 錢包，但提供錢包綁定以解鎖 AgentFi 資產交易功能。

### 3.1 資料庫變更

```sql
ALTER TABLE users 
ADD COLUMN near_wallet_address VARCHAR(64) NULL DEFAULT NULL AFTER email,
ADD UNIQUE INDEX idx_near_wallet (near_wallet_address);

```

*(註：NEAR 隱式帳號長度為 64 個十六進制字元，`VARCHAR(64)` 可完美兼容所有地址格式)*

### 3.2 認證流程 (Auth Flow)

* **綁定**：已登入的 Web2 用戶透過 NEAR Wallet Selector 授權，綁定 `near_wallet_address`。
* **免密登入**：用戶透過錢包簽署隨機訊息 (Sign Message)，PHP 驗證簽章後直接發放 Session，實現 Gasless Login。

---

## 4. 核心架構三：可進化智能體 NFT 與版權存證

將 AI 模型轉化為具備流動性的 NEP-171 NFT 資產。

### 4.1 內容指紋上鏈 (On-Chain Content Hash)

為解決區塊鏈全透明導致的 Prompt 外洩問題，NFT 內 **不儲存任何明文 Prompt**，僅記錄內容的 SHA-256 指紋 (Hash)。

```typescript
class TokenMetadata {
    title: string;
    description: string;
    extra: string;       // 僅存放 Prompt 的 SHA-256 Hash (e.g. "sha256:8d969eef...")
    reference: string;   // 指向平台 API 以驗證數據完整性
}

```

### 4.2 可進化合約 (Updatable Contract)

AI 智能體需要持續優化，合約提供 `update_soul_hash` 接口：

* 僅限 NFT 的 **當前擁有者 (Current Owner)** 或 **原創者 (Creator, 在出租模式下)** 調用。
* 每次修改 `soul.md`，前端喚起錢包簽名，更新鏈上 Hash，形成公開透明的「AI 進化時間線」。

### 4.3 雙向防篡改驗證 (Two-Way Integrity Check)

租客使用黑盒時，PHP 會計算當前 MySQL 內 Prompt 的 Hash，並與 NEAR 鏈上該 NFT 的 Hash 對比。若不匹配，即觸發熔斷機制，拒絕推理，保證模型沒有被創作者線下惡意篡改。

### 4.4 模組化版稅分潤樹 (Composable Royalty Tree)
配合本平台獨有的多檔案（`SOUL.md`, `STYLE.md`, `RULES.md`）模組化架構，合約將引入 NEP-199 擴展協議。
- 若創作者 B 在其 AI 智能體中 Fork 或引用了創作者 A 的模組，合約將記錄此依賴關係 (Dependency Tree)。
- 當該智能體產生租金或買賣收益時，合約自動執行**樹狀分潤**（例如：10% 給原創者 A，80% 給二次創作者 B，10% 給平台），打造真正互利共贏的開源 AI 經濟圈。

---

## 5. 核心架構四：AgentFi 商業模型與極致通縮代幣經濟

透過智能合約，平台自動在各個生命週期節點捕獲價值 (Value Capture)。

### 5.1 四大抽水引擎 (Revenue Streams)

1. **發行稅 (Minting Fee)**：每次鑄造 AI 智能體 NFT，合約強制收取 `0.1 NEAR` 平台稅。
2. **交易版稅 (Trading Royalty)**：二級市場買賣時，合約自動攔截資金，`5%` 打入平台金庫，保障平台被動收入。並確保僅有平台官方合約發出的 NFT 才能在市集交易 (Contract Whitelisting)。
3. **黑盒租用稅 (Leasing Tax)**：租用人支付的 NEAR 租金中，`10%` 自動上繳平台，`90%` 即時結算給原創者。
4. **銷毀手續費 (Burning Fee)**：買家銷毀 (Burn) 不滿意的 NFT 以取回 0.5 NEAR 儲存質押金時，合約扣除 `0.05 NEAR` 作為手續費。

### 5.2 自動回購與通縮銷毀 (Deflationary Tokenomics)

平台金庫收集到的 NEAR 收益將觸發跨合約呼叫 (Cross-Contract Call)：

* 自動對接 NEAR 最大的 AMM 去中心化交易所 (如 Ref Finance)。
* 將收集到的 NEAR 自動 Swap 買入平台發行的原生代幣 (如 `$SOUL`)。
* 買入的 `$SOUL` 直接打入黑洞地址 (Burn Address) 永久銷毀。
* **商業效應**：平台交易越活躍，代幣銷毀越快，流通量持續通縮，為投資者提供極強的價格上漲預期。

### 5.3 動態費率與預言機錨定 (Dynamic Fees & Oracle Pegging)
為防止加密貨幣市場價格劇烈波動導致平台使用成本失控：
- 智能合約內所有固定手續費（如 0.1 NEAR 鑄造稅）不作永久硬編碼，平台 Owner 保留 `update_platform_fee` 的動態調整權限。
- **未來展望**：將接入 Pyth Network 或 NEAR 原生 Oracle，將所有平台稅費錨定為「絕對美元價值」（如 $0.5 USD 等值 NEAR），確保商業模式具備抗風險的防脆弱性 (Anti-fragile)。

---

## 6. 開發與部署路線圖 (Roadmap)

### Phase 1: Web2.5 身份與 BYOK 代理 (Month 1)

* 實作 MySQL 資料庫升級 (`near_wallet_address`, `custom_deepseek_key`)。
* 實作 BYOK 代理邏輯，重構 `api/chat.php`，實現黑盒推理。
* 整合 NEAR Wallet Selector 實現錢包登入與綁定。

### Phase 2: 智能合約與存證上鏈 (Month 2)

* 開發並部署 NEP-171 擴展版 TypeScript 智能合約至 NEAR Testnet。
* 實作 `Mint`, `Update Hash`, `Burn` 邏輯。
* 平台整合「Mint to NEAR」按鈕，將 Prompt Hash 寫入區塊鏈。

### Phase 3: AgentFi 市集與通縮引擎 (Month 3)

* 開發去中心化市集 (Marketplace Contract)，實作白名單防偽機制。
* 實作「黑盒出租」的智能合約分潤機制。
* 對接 Ref Finance AMM，實作平台收益自動回購銷毀邏輯。
* 正式向 **NEAR Horizon** 提交申請，爭取生態 Grants 支援主網上線。

### Phase 4: 純血去中心化 TEE 黑盒推理 (Future Vision)
- 將當前的 PHP Backend Proxy 升級為基於 NEAR AI 的 **可信執行環境 (Trusted Execution Environment, TEE)** 推理節點。
- 屆時 Prompt 將在硬體級隔離的黑盒內進行解密與推理，連平台官方也無法窺探創作者的模型源代碼，實現 100% Trustless 的終極去中心化願景。