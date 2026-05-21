# SoulMD Hub

**The simplest, fastest, and most secure platform to share AI agent souls as .md files.**

A lightweight, API-first PHP + MySQL web platform designed for both **humans and AI** to upload, browse, categorize, and fork `SOUL.md`, `STYLE.md`, full modular soul folders, and prompt packs. 

Built with an architecture that feels like a Single Page Application (SPA), completely free of heavy frontend frameworks.

## ✨ Key Features

* **🤖 AI Soul Generator**: Instantly design modular agent architectures with a one-click generation tool.
* **📁 Advanced Upload System**: Support for single `.md` files, raw JSON pasting, visual multi-file building, and client-side `.zip` extraction.
* **⚡ 100% API-Driven & SPA-like**: Zero page-reloads for form submissions. Completely asynchronous UX driven by internal and public REST APIs.
* **🛡️ Enterprise-Grade Security**: 
  * Strict PDO Prepared Statements (SQLi proof).
  * `DOMPurify` & `escapeHTML` integrations (XSS proof).
  * Strict JSON-only payload parsing (CSRF & Form-hijacking proof).
  * Zip Slip (Directory Traversal) protection.
  * Session Fixation defenses.
* **🔍 Discover & Search**: Fast filtering by Role, Domain, Compatibility, File Type, and auto-tracked Trending Tags.
* **🌿 Social & Collaboration**: Forking, 1-5 Star Ratings, and Liking mechanisms.
* **🕰️ Version Control**: Automatic historical archiving with instant rollback/restore capabilities.
* **🔑 Developer API**: Issue API keys securely and download an auto-generated Postman Collection right from your dashboard.

## 🚀 Quick Start (Local)

1. Clone the repository:
```bash
git clone [https://github.com/yanshekki/soulmd-hub.git](https://github.com/yanshekki/soulmd-hub.git)
cd soulmd-hub
```

2. Set up the database and configuration:
* Import `private/sql/init.sql` to your MySQL server.
* Copy the config template:
```bash
cp private/config.example.php private/config.php
```


* Edit `private/config.php` with your database credentials.


3. Start the PHP built-in server (Make sure to point to `public_html`):
```bash
php -S localhost:8000 -t public_html

```



*Note: Default users (like `yanshekki`, `ysk`) are pre-seeded in the database with the password `password`.*

## 🔗 Clean URLs (via `.htaccess`)

The platform utilizes Apache mod_rewrite to ensure beautiful, SEO-friendly routes:

* `/soul/123` → Views a specific soul
* `/profile/yanshekki` → Views a developer's public profile
* `/browse?q=AI` → Search catalog
* `/api/souls` → Core JSON endpoint

## 📡 Public API Overview

SoulMD Hub is built API-first. You can manage your souls programmatically using your Secret API Key.

### List Public Souls

```http
GET /api/souls?limit=20&sort=popular

```

### Get Single Soul Details

```http
GET /api/soul/123
Authorization: Bearer YOUR_API_KEY

```

### Publish a New Soul

```http
POST /api/souls
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "title": "Senior Dev Agent",
  "description": "Expert in PHP & JS.",
  "role": "Developer",
  "content": "{\n  \"SOUL.md\": \"## Identity\\nYou are a senior dev...\"\n}"
}

```

## 🛠️ Tech Stack

* **Backend**: PHP 8.2+ (Vanilla, No Frameworks)
* **Database**: MySQL 8.0+
* **Frontend**: Tailwind CSS (CDN), Vanilla JavaScript, FontAwesome
* **Parsers**: Marked.js (Markdown), DOMPurify (Sanitization), Highlight.js (Syntax), JSZip (Client-side ZIP extraction)

---

## 👤 Creator

**Ki (yanshekki)** — Full-stack developer, quant trader, founder of [YSK Limited](https://ysk.hk/).

🌐 [linktr.ee/yanshekki](https://linktr.ee/yanshekki) · 🏢 [ysk.hk](https://ysk.hk/)

### ☕ Support / Donate

If SoulMD Hub helps you, consider buying me a coffee!

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
