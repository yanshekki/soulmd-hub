# SoulMD Hub 🚀

**The Ultimate Multi-Modal AI Agent SaaS Ecosystem.**

A lightweight, high-performance, API-first PHP + MySQL platform designed to build, discover, monetize, and interact with modular AI personas (`SOUL.md`). Powered by a dual-engine routing architecture integrating **DeepSeek** (Logic/Reasoning) and **Together AI** (Vision).

Built with a Single Page Application (SPA) feel, completely free of heavy frontend frameworks, and fully equipped with an automated PayPal subscription system.

---

## ✨ Enterprise-Grade Features

### 🧠 Dual-Engine AI & Vision
* **Smart Model Routing**: Seamlessly routes pure text requests to DeepSeek (V4/Pro) and image analysis requests to Together AI (Qwen/Llama Vision).
* **Client-Side Image Compression**: Utilizes GPU-accelerated HTML5 `<canvas>` to compress images to 800px/60% quality *before* upload, permanently solving Cloudflare 524 Timeouts and Nginx payload limits.
* **Smart Memory Compression**: Dynamic sliding-window summarization keeps AI context highly relevant without exceeding API token limits.

### 💳 Built-in SaaS Billing & Entitlement
* **PayPal Integration**: Financial-grade checkout flow via PayPal REST API.
* **Tiered Access (Free / VIP / PRO)**: Granular control over daily limits, maximum input characters, memory thresholds, and Vision AI capabilities.
* **Prorated Upgrades**: Automatically calculates remaining time value when a user upgrades from VIP to PRO.
* **Downgrade Protection**: Pre-flight guards prevent users from accidentally downgrading their active premium tiers.

### 🛡️ Bulletproof Security
* **Zero-Day Defenses**: 100% PDO Prepared Statements (SQLi proof), `DOMPurify` (XSS proof), strict JSON-only payload parsing (CSRF & Form-hijacking proof).
* **API Key Management**: Secure key rolling mechanism.
* **Guest Rate Limiting**: Strict session-based tracking prevents unauthorized API abuse and bankruptcy loops.
* **Path Traversal Protection**: Regex-filtered client-side `.zip` extraction (JSZip) and secure file downloading.

### 👨‍💻 Headless API & Developer Tools
* **Public & Private APIs**: Build your own external apps using the headless `/api/chat` endpoint (Exclusive for VIP/PRO members).
* **Auto Postman Generation**: One-click download of a fully configured Postman Collection (`.json`) populated with the user's active API Key.

### 🎨 Ultimate UX
* **Visual Modular Editor**: Build multi-file AI architectures directly in the browser.
* **Seamless Chat UI**: Auto-expanding textareas, `Ctrl+V` clipboard image pasting, `Ctrl+Enter` sending, and silent background privacy syncing.
* **Safari & iOS Optimized**: Fixed cross-browser date parsing bugs.

---

## 🚀 Quick Start (Self-Hosting)

### 1. Clone the repository
```bash
git clone [https://github.com/yanshekki/soulmd-hub.git](https://github.com/yanshekki/soulmd-hub.git)
cd soulmd-hub

```

### 2. Setup Database & Configuration

* Import `private/sql/init.sql` to your MySQL server (MySQL 8.0+ recommended).
* Copy the configuration template:

```bash
cp private/config.example.php private/config.php

```

* Edit `private/config.php` and configure your critical keys:
* **Database**: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
* **AI APIs**: `DEEPSEEK_API_KEY` and `VISION_API_KEY` (Together AI)
* **Billing**: `PAYPAL_CLIENT_ID` and `PAYPAL_SECRET`



### 3. Launch the Server

Ensure your web server (Apache/Nginx) points its document root to the `public_html` directory.
For local testing using PHP's built-in server:

```bash
php -S localhost:8000 -t public_html

```

*(Default seeded admin accounts: `yanshekki`, `ysk`, `ki`. Password: `password`)*

---

## 🔗 Clean Routing Architecture (Apache `.htaccess`)

The platform utilizes `mod_rewrite` to provide SEO-friendly, clean URLs, securely hiding the underlying `.php` extensions.

* `/soul/yanshekki/123/developer/expert-coder` → Secure 4-layer SEO path
* `/profile/yanshekki` → Public portfolio
* `/invoice/123` → Dynamic printable financial receipts
* `/api/chat` → Headless LLM engine

---

## 📡 Headless API Overview

SoulMD Hub is API-first. Authenticate requests by passing your generated Secret Key in the header:

```http
Authorization: Bearer YOUR_API_KEY_HERE

```

### Interact with AI (VIP/PRO Only)

```http
POST /api/chat
Content-Type: application/json

{
  "action": "chat",
  "soul_id": 1,
  "session_token": "your_unique_session_string",
  "content": "Analyze this architecture.",
  "is_private": true
}

```

*For the complete API reference, log in to your account and navigate to the **Developer API** section to download the Postman Collection.*

---

## 🛠️ Tech Stack

* **Backend**: PHP 8.2+ (Vanilla, No Frameworks, Exponential Backoff enabled)
* **Database**: MySQL 8.0+ / MariaDB
* **Frontend**: Vanilla JavaScript, Tailwind CSS (CDN), FontAwesome
* **Parsers/Libs**: Marked.js (Markdown), DOMPurify (Sanitization), Highlight.js (Syntax), JSZip (Client-side ZIP packaging)

---

## 👤 Creator

**Ki (yanshekki)** — Full-stack developer, quant trader, founder of [YSK Limited](https://ysk.hk/).

🌐 [linktr.ee/yanshekki](https://linktr.ee/yanshekki) · 🏢 [ysk.hk](https://ysk.hk/)

### ☕ Support / Donate

If SoulMD Hub accelerates your SaaS journey, consider buying me a coffee!

| Network | Address |
| --- | --- |
| **EVM** (ETH/BSC/Polygon) | `yanshekki.eth` |
| **NEAR** | `yanshekki.near` |
| **ADA** (Cardano) | `$yanshekki` |

---

## 📄 License

MIT © Ki (yanshekki)

---

Powered by [YSK Limited](https://ysk.hk/) — Hong Kong Remote Dev Team & Enterprise Solutions
