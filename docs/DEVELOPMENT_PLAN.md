# SoulMD Hub Development Roadmap 🗺️

## ✅ Phase 1: Foundation (Completed)
- [x] MySQL database + schema design.
- [x] Basic routing and connection wrappers (`Database.php`).
- [x] Core Authentication (Register / Login / Password management).

## ✅ Phase 2: Core Features (Completed)
- [x] Categorization system (Role / Domain / FileType).
- [x] Advanced Search + Filters in Browse page.
- [x] Soul detail page with dynamic markdown rendering (`marked.js` + `highlight.js`).
- [x] Social interactions: Forking, Liking, and 5-Star Ratings.

## ✅ Phase 3: AI & Modular Enhancements (Completed)
- [x] Multi-file Modular Builder (Visual Editor).
- [x] Client-side ZIP folder parsing and extraction.
- [x] AI Agent Generator (One-click preset builder).
- [x] Version History with instant Rollback capabilities.

## ✅ Phase 4: Enterprise Security & SPA Refactor (Completed)
- [x] 100% API-Driven frontend (Zero page reloads).
- [x] Enforced JSON-only API payloads to prevent CSRF.
- [x] Advanced DOM XSS sanitization (`DOMPurify`).
- [x] Zip Slip & Path Traversal defense in PHP download handler.
- [x] Postman Collection auto-generator for Public API.

## 🚀 Phase 5: Scaling & DevOps (Upcoming)
- [ ] **Dockerization**: Provide `Dockerfile` and `docker-compose.yml` for instant self-hosting.
- [ ] **OAuth Integration**: Allow login via GitHub and Google.
- [ ] **Rate Limiting**: Implement strict Redis-based rate limiting for the Public API.
- [ ] **CI/CD Pipeline**: Add GitHub Actions for automated syntax checking and PHPUnit testing.