# SoulMD Hub

**The simplest & fastest platform to share AI agent souls as .md files**

A lightweight PHP + MySQL web platform designed for both **humans and AI** to upload, browse, categorize, and fork SOUL.md, STYLE.md, full soul folders, and prompt packs.

## Features
- AI Soul Generator (one-click)
- User system (register/login)
- Upload .md or zip soul folders
- Browse + powerful search & filters
- Beautiful soul detail pages
- Fork & Like system
- My Souls management
- **Public API** (JSON)

## Quick Start

```bash
git clone https://github.com/yanshekki/soulmd-hub.git
cd soulmd-hub
cp config.example.php config.php
php -S localhost:8000 -t public
```

## Public API

### List Souls
```http
GET /api/souls?limit=20&offset=0
```

### Get Single Soul
```http
GET /api/soul.php?id=123
```

### Create Soul (from AI/tools)
```http
POST /api/souls
Content-Type: application/json

{
  "title": "My Developer Soul",
  "content": "## Identity\nYou are...",
  "role": "Developer",
  "domain": "Tech"
}
```

## Tech Stack
- PHP 8.2+
- MySQL / MariaDB
- Tailwind CSS (CDN)

## Folder Structure
```
soulmd-hub/
├── public/          # All pages + /api/
├── src/             # Database class
├── sql/             # Schema
├── includes/        # SEO helper
├── uploads/         # (not used - everything in DB)
└── README.md
```