# SoulMD Hub Architecture

## Core Principles
- **.md First** — All content is .md files
- **Extremely Simple** — Single main PHP file + simple frontend
- **Human + AI Friendly** — Easy upload for both
- **Clear Categories** — Role / Domain / FileType / Compatibility

## Database Design (MySQL)

### Main Tables
- `souls` — Main table for each .md or soul folder
- `users`
- `categories`
- `soul_tags`
- `forks`
- `ratings`

## Upload Flow
1. User/AI uploads .md file or zip (full soul folder)
2. System parses YAML frontmatter
3. Save to `souls` table + store raw file in /uploads/
4. Generate preview + auto-categorize

## Frontend Highlights
- Homepage with Trending + Categories
- Browse page with filters & search
- Beautiful .md rendering on detail page
- Upload form with drag & drop + AI generation button

## Security & Simplicity
- Prepared statements
- File size & type limits
- No external dependencies except Tailwind CDN