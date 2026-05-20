# SoulMD Hub

**The simplest & fastest platform to share AI agent souls as .md files**

A lightweight PHP + MySQL web platform designed for both **humans and AI** to upload, browse, categorize, and fork SOUL.md, STYLE.md, full soul folders, and prompt packs.

## Goals
- Extremely simple and fast
- Human + AI friendly
- Clear categorization
- Full support for .md files (single file + entire soul/ folder)

## Tech Stack
- PHP 8.2+
- MySQL / MariaDB
- Tailwind CSS (CDN) + Vanilla JS

## Quick Start (Local)

```bash
git clone https://github.com/yanshekki/soulmd-hub.git
cd soulmd-hub
cp config.example.php config.php   # edit your MySQL credentials
php -S localhost:8000 -t public
```

## Folder Structure
```
soulmd-hub/
├── public/          # Frontend entry
├── src/             # PHP logic
├── sql/             # Database schema
├── docs/            # Documentation
├── uploads/         # Uploaded .md files
└── README.md
```

## Next Steps
See docs/ARCHITECTURE.md for full design
See docs/DEVELOPMENT_PLAN.md for development roadmap