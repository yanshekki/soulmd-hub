# SoulMD Hub

**The simplest & fastest platform to share AI agent souls as .md files**

A lightweight PHP + MySQL web platform designed for both **humans and AI** to upload, browse, categorize, and fork SOUL.md, STYLE.md, full soul folders, and prompt packs.

## Features
- AI Soul Generator (one-click)
- User system (register/login)
- Upload .md or zip soul folders
- Browse + powerful search & filters
- Beautiful soul detail pages
- Fork, Like & 1-5 Star Rating
- Version History (auto-save)
- My API Key management
- **Public API** (JSON)
- Clean URLs with .htaccess

## Quick Start (Local)

```bash
git clone https://github.com/yanshekki/soulmd-hub.git
cd soulmd-hub
cp config.example.php config.php
php -S localhost:8000 -t public
```

## Clean URLs (with .htaccess)
- `/soul/123` → Soul detail page
- `/api/souls` → List souls
- `/api/soul/123` → Get single soul

## Public API

### List Souls
```http
GET /api/souls?limit=20
```

### Get Single Soul
```http
GET /api/soul/123
```

### Create Soul (with API Key)
```http
POST /api/souls
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "title": "My Developer Soul",
  "content": "## Identity\nYou are..."
}
```

## Tech Stack
- PHP 8.2+
- MySQL
- Tailwind CSS (CDN)

## Folder Structure
```
soulmd-hub/
├── public/          # All pages + .htaccess + /api/
├── src/
├── sql/
├── includes/
└── README.md
```