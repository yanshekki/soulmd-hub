# Security Implementations 🛡️

SoulMD Hub takes data integrity and system security extremely seriously. Below are the core mitigation strategies implemented across the platform.

## 1. Cross-Site Scripting (XSS)
- **Backend**: Strict Type Casting and forced UTF-8 JSON encoding to prevent array-injection fatal errors.
- **Frontend**: All user-generated content (including dynamically loaded usernames, titles, and tags) is sanitized using `escapeHTML()` before DOM injection.
- **Markdown Rendering**: `marked.js` output is thoroughly sanitized through `DOMPurify.sanitize()` before rendering to prevent malicious `<script>` or `onload` injections inside AI souls.
- **Download Protection**: Non-standard files are forcefully served as `text/plain` via the `download.php` endpoint to prevent inline execution of malicious HTML.

## 2. Cross-Site Request Forgery (CSRF)
- All mutating endpoints (`POST`, `PUT`, `DELETE`) are strictly configured to accept **only** `application/json` payloads via `php://input`.
- Traditional `$_POST` fallback processing has been disabled. Form-based CSRF attacks are inherently blocked because browsers cannot forge raw JSON payloads across domains without pre-flight CORS approval.

## 3. SQL Injection (SQLi)
- 100% coverage using `PDO` Prepared Statements.
- `PDO::ATTR_EMULATE_PREPARES` is disabled to ensure the MySQL engine handles the typing and statement compilation natively, guarding against edge-case numeric/string bypasses (e.g., in `LIMIT` and `OFFSET` clauses).

## 4. Directory Traversal / Zip Slip
- Uploaded `.zip` architectures are rigorously validated.
- During dynamic ZIP packaging in `/download.php`, file paths like `../../../etc/passwd` or `..\..\Windows` are explicitly neutralized via Regex filtering to prevent archive-extraction vulnerabilities on the client's local machine.

## 5. Session Fixation & Hijacking
- `session_regenerate_id(true)` is strictly called upon successful login and registration.
- Persistent login mechanisms ("Remember Me") use cryptographically secure random tokens (`random_bytes(32)`) hashed into the database, rather than storing plaintext user data in cookies.