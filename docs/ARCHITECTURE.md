# SoulMD Hub Architecture

## Core Principles
- **.md First** — All content is .md files stored in MySQL
- **Extremely Simple** — Single main PHP file + simple frontend
- **Human + AI Friendly** — Easy upload for both
- **Clear Categories** — Role / Domain / FileType / Compatibility
- **SEO Optimized** — Good for Google "site:soulmd-hub.ysk.hk"

## SEO Strategy
- Dynamic sitemap.xml (public/sitemap.php)
- robots.txt
- Proper meta title & description per page
- Clean URLs (planned with .htaccess)
- JSON-LD structured data for souls (future)
- Fast loading with Tailwind CDN

## Database Design (MySQL)

### Main Tables
- `souls` — Main table (content stored directly in `content LONGTEXT`)
- `users`
- `categories`
- `soul_tags`
- `forks`
- `ratings`

## Upload Flow
1. User/AI pastes .md content or uploads file
2. System saves title + content directly into MySQL
3. Auto categorize + generate preview

## Frontend Highlights
- Homepage with Trending + Categories
- Browse page with filters & search
- Beautiful .md rendering on detail page
- Upload form with drag & drop + AI generation

## Security & Simplicity
- Prepared statements (PDO)
- No file uploads to disk (everything in DB)