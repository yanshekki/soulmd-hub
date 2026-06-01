# SoulMD Hub 官方生態與文檔規劃書 (Documentation Plan)

## 🏗️ 架構設計 (Architecture Design)

為了解決「多個 Tab 介紹、每個 Tab 獨立 File、且完美支援語言包」的需求，我們將採用以下路由與檔案結構：

### 1. 核心控制器與語言包
* **主頁面**: `public_html/docs.php`
  * 作為 Controller，負責載入 Header、Footer、SEO Meta，以及渲染左側 (或頂部) 的 Tab 導覽列。
  * 透過 `$_GET['tab']` 動態 `require_once` 對應的子檔案。
* **語言包**: `private/includes/languages/docs.php`
  * 統一管理所有文檔分頁的中英文翻譯，避免語言包過於零散。

### 2. 獨立子檔案 (Tab Contents)
將所有的內容模組化，放在 `private/includes/docs/` 資料夾下，防止被用戶直接透過網址存取，確保安全性。

* 📍 **Tab 1: 運作原理與用途 (Introduction)**
  * **檔案**: `private/includes/docs/tab-intro.php`
  * **內容**: 介紹 SoulMD Hub 是一個「Web2.5 模組化 AI 智能體平台」。講解 SOUL.md, STYLE.md, RULES.md 的多檔案架構，以及 Dual-Engine (DeepSeek + Together AI Vision) 的智能路由機制。

* 📍 **Tab 2: 解決的痛點 (Problems Solved)**
  * **檔案**: `private/includes/docs/tab-solutions.php`
  * **內容**: 
    1. **算力與成本問題**: 介紹 BYOK (Bring Your Own Key) 無狀態黑盒代理。
    2. **記憶遺忘問題**: 介紹 Smart Sliding Memory 壓縮技術。
    3. **圖片上傳 Timeout**: 介紹 Client-Side Canvas 壓縮機制，解決 Cloudflare 524 錯誤。
    4. **IP 盜用問題**: 介紹 Web2 私密鎖定與 Web3 內容指紋 (Hash) 驗證。

* 📍 **Tab 3: 日常應用場景 (Daily Use Cases)**
  * **檔案**: `private/includes/docs/tab-usecases.php`
  * **內容**: 舉例說明普通用戶與開發者如何使用：
    - 程式碼審查員 (Expert Coder)
    - 專業文案與翻譯 (Copywriter & Translator)
    - 透過 Headless API (`/api/chat`) 串接自動化客服。

* 📍 **Tab 4: AgentFi 與未來發展 (Future Roadmap)**
  * **檔案**: `private/includes/docs/tab-future.php`
  * **內容**: 介紹去中心化市集 (AgentFi Marketplace)。講解 Mint to NEAR、黑盒出租分潤 (Rentals)、買斷擁有權 (Trade)，以及未來的 $SOUL 代幣通縮經濟學 (Auto-Buyback & Burn)。

---

## ⚙️ SEO 與路由配置 (SEO & Routing Config)

為了讓文檔對搜尋引擎友善 (SEO-friendly) 並整合進全站架構，必須修改以下核心檔案：

* 📍 **`public_html/.htaccess`**:
  * 增加 RewriteRule，將 `/docs` 及 `/docs/tab-name` 乾淨路由指向 `docs.php`，隱藏附檔名。
* 📍 **`public_html/sitemap.php`**:
  * 在 `$staticPages` 陣列中加入 `docs` 及其對應的 tabs 節點，確保 Google 能正確索引 (Index) 並支援 Hreflang 雙語切換。
* 📍 **`public_html/robots.txt`**:
  * 確認 `/docs` 被允許爬蟲抓取 (Allow)。

---

## 🛠️ 開發實作順序 (Implementation Steps)

1. **Step 1**: 建立 `private/includes/languages/docs.php` (語言包字典)。
2. **Step 2**: 建立 `public_html/docs.php` (主頁面與 Tab 導覽架構)。
3. **Step 3**: 逐一建立四個獨立的 Tab 內容檔案 (`tab-intro.php`, `tab-solutions.php`, `tab-usecases.php`, `tab-future.php`)。
4. **Step 4**: 更新 `public_html/.htaccess` 加入乾淨路由。
5. **Step 5**: 更新 `public_html/sitemap.php` 加入 Sitemap 節點。
6. **Step 6**: 確認 `public_html/robots.txt` 設定，並在全站導覽列 (header.php / footer.php) 加上 `/docs` 的連結。
