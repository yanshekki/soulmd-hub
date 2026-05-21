# SoulMD Hub Architecture 🏗️

SoulMD Hub is built on a **100% API-First** and **SPA-like (Single Page Application)** architecture, using Vanilla JavaScript and modern PHP 8.2+. It intentionally avoids heavy frontend frameworks to maximize raw performance and maintainability.

## 🌟 Core Principles

- **API-Driven UI**: All frontend interactions (forms, likes, forks, profile loading) are powered by asynchronous `fetch()` API calls. Zero full-page reloads.
- **Markdown (.md) First**: All AI configurations, prompts, and architectures are natively stored and rendered as Markdown.
- **Security by Design**: Native protection against SQLi, CSRF, DOM-based XSS, Session Fixation, and Path Traversal (Zip Slip).
- **SEO Optimized**: Fully dynamic `sitemap.xml` and `robots.txt` supported by Apache `.htaccess` clean URL rewriting.

## 🗄️ Database Schema (MySQL 8.0+)

The relational database is highly optimized with strict foreign key constraints and `ON DELETE CASCADE` behaviors.

* `users`: Stores developer accounts securely (bcrypt password hashing, API keys).
* `souls`: The core table storing modular agent configurations (`LONGTEXT` JSON payload or single markdown).
* `soul_versions`: Automated historical archiving of every edit made to a soul.
* `soul_ratings`: 1-5 star rating system (Atomic uniqueness per user/soul).
* `soul_likes`: Tracks user likes to prevent duplicate spamming.
* `categories` & `tags_*`: Dynamic normalization of tags and roles for high-speed indexing.

## 🚀 Routing Architecture (Apache `.htaccess`)

We utilize aggressive URL rewriting to provide clean, RESTful-looking URLs:
- `/soul/:id` ➡️ Renders `soul.php?id=:id`
- `/profile/:username` ➡️ Renders `profile.php?username=:username`
- `/api/*` ➡️ Internal & Public REST JSON Endpoints

## 📦 Client-Side Zip Extraction

To offload server CPU usage, SoulMD Hub uses **JSZip** to parse uploaded `.zip` files directly within the user's browser, converting them into a structured JSON payload before sending them to the `/api/souls` endpoint.