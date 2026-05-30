# SoulMD Hub: Web2.5 AgentFi & Lazy Sync Architecture Specification (V5)
**狀態：** 準備實作 (Ready for Implementation)  
**核心目標：** 解決 Web2.5 狀態不一致問題、防範 RPC 單點故障、實現無縫擁有權移交與資產防護。

---

## 1. 核心設計理念 (Core Philosophy)
本系統採用 **「區塊鏈為最終真理 (Source of Truth) + MySQL 懶同步緩存 (Lazy Sync Cache)」** 的混合架構。
* **拒絕提前轉移**：在與區塊鏈確認狀態前，絕不提早在 Web2 資料庫中剝奪用戶的擁有權。
* **自癒機制 (Self-Healing)**：任何中途斷線、RPC 錯誤或智能合約銷毀 (Burn) 行為，系統必須能自動降級並回復到最後一個安全的 Web2 狀態。

---

## 2. 資料庫架構升級 (Database Schema)
`souls` 資料表新增以下 AgentFi 核心欄位：
* `is_nft` (TINYINT): 標記此模型是否已進入 Web3 狀態 (0 = 否, 1 = 是)。
* `nft_salt` (VARCHAR): 伺服器生成的隨機鹽 (Random Key)，用於防止 Hash 被彩虹表破解。
* `nft_hash` (VARCHAR): 伺服器端計算的 SHA-256 指紋 `hash(content + nft_salt)`。
* `nft_owner_wallet` (VARCHAR): 緩存當前鏈上的擁有者錢包地址，作為 RPC 斷線時的備援。

---

## 3. 鑄造流程與斷線防護 (Minting & Disconnect Protection)
為防止用戶點擊 Mint 後在錢包端斷線導致「模型變孤兒」，流程如下：

1.  **API 觸發 (`/api/mint-sync.php` 或整合於 `souls.php`)**：
    * 驗證用戶是否已綁定 Web3 錢包 (否則 HTTP 403)。
    * 伺服器生成 `nft_salt` 與 `nft_hash`。
    * 更新狀態：`is_nft = 1`, `is_public = 0` (強制轉為私密，防止白嫖)。
    * 更新緩存：`nft_owner_wallet = [原作者錢包]`。
    * **【絕對限制】：** `user_id` **保持不變** (保留原作者 Web2 擁有權)。
2.  **前端喚起錢包**：將 `nft_hash` 傳入合約進行鑄造。
3.  **斷線自癒**：若用戶未成功鑄造，下次讀取時 RPC 會回傳「Token 不存在」，系統將自動觸發「銷毀自癒機制」(見第 5 點)。

---

## 4. 懶同步擁有權移交與防偷睇 (Lazy Sync & Anti-Peeping)
當任何人 (包含原作者或買家) 透過 `/api/soul.php` 讀取模型，或進入 `my-souls.php` 時觸發：

1.  **RPC 查詢**：PHP 向 NEAR RPC 查詢 `soul_[id]`。
2.  **擁有權變更比對**：
    * 若 RPC 回傳的 `owner_id` **不等於** 資料庫的 `nft_owner_wallet`（代表已售出）。
3.  **執行 Web2 移交 (Transfer)**：
    * PHP 在 `users` 表中尋找對應 `owner_id` 的帳號。
    * 將該 `soul` 的 `user_id` 正式更新為「新買家」的 ID。
    * 更新 `nft_owner_wallet = [新買家錢包]`。
    * **結果**：舊買家 (或原作者) 瞬間失去該模型的 Web2 權限，無法再讀取 `content` (防偷睇)，但 **歷史版本 (soul_versions) 會被新買家完整繼承**。
4.  **RPC 備援池 (Failover Pool)**：
    * 若所有 RPC 節點皆無法連線 (Timeout)，PHP 降級依賴 MySQL 中的 `nft_owner_wallet` 進行權限放行，確保已購資產的可用性。

---

## 5. 銷毀與自癒降級 (Burn & Self-Healing)
當模型從區塊鏈上被銷毀 (Burn)，或因 Mint 失敗導致鏈上無紀錄時：

1.  **偵測**：PHP 查詢 RPC 得到「Token Not Found」。
2.  **降級 (Downgrade)**：
    * 將 `is_nft` 設為 `0`。
    * 清空 `nft_owner_wallet = NULL`、`nft_salt = NULL`、`nft_hash = NULL`。
    * 確保 `is_public = 0` (維持私密狀態)。
3.  **結果**：模型自動回歸為當前 `user_id` (銷毀者或原作者) 名下的一個普通 Web2 私密模型。

---

## 6. 頁面資料分流邏輯 (Page Routing & Filtering)

為了區分 Web2 與 Web3 資產，各頁面列表的 SQL 條件必須嚴格隔離：

* **`browse.php` (Web2 大廳)**：
    * 過濾條件：`is_public = 1` 且 **`is_nft = 0`**。
    * 行為：不顯示任何已 NFT 化的模型。
* **`marketplace.php` (Web3 市集)**：
    * 過濾條件：**`is_nft = 1`** (無視 `is_public` 狀態)。
    * 行為：僅顯示 NFT 資產，必須結合 RPC 確認其售價 / 租金狀態。
* **`my-souls.php` (創作者後台 - 雙區域顯示)**：
    * **Section A (Web2 模型)**：`user_id = me` 且 `is_nft = 0`。若 `is_public = 0`，前端需強制顯示「🔒 Private」標籤提示用戶。
    * **Section B (AgentFi 資產)**：`nft_owner_wallet = me_wallet` 且 `is_nft = 1`。此區域提供掛牌、租用等操作。

---

## 7. 安全與身分驗證邊界 (Security Boundaries)
* **UI 攔截**：未綁定 NEAR 錢包的帳戶，前端必須強制 Disable (灰態) 並隱藏「Mint to NEAR」、「掛牌」、「租用」等 AgentFi 操作按鈕。
* **Wallet Login 強制性**：只有在 MySQL 中已存在 `near_wallet_address` 紀錄的錢包，才被允許進行 Web3 登入 (`api/wallet-login.php`)，確保所有錢包操作必定能追溯至一個有效的 Web2 `user_id`。