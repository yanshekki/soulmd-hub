# Security Policy & Implementations 🛡️

SoulMD Hub employs financial-grade security controls, rigid type enforcement, and automated endpoint validation guards across the entire stack. Below is the technical breakdown of the defensive measures mitigating OWASP Top 10 vulnerabilities.

---

## 1. SQL Injection (SQLi) Mitigation
* **Prepared Statements**: 100% of database interactions are compiled using strict native `PDO` parameter mapping. Standard raw dynamic string concatenations are explicitly banned.
* **Native Preparation Disabling**: The database wrapper sets `PDO::ATTR_EMULATE_PREPARES => false`. This forces the MySQL database server engine to handle type assignment and statement compilation directly, preventing numeric or charset-based bypass vulnerabilities (e.g., within pagination `LIMIT` or `OFFSET` parameters).

---

## 2. Cross-Site Scripting (XSS) Firewalls
* **Contextual Markdown Sanitization**: User-generated personas, modules, and prompt configurations rendered through `marked.js` are passed directly through an active client-side DOM-purging filter (`DOMPurify.sanitize()`) prior to DOM insertion, mitigating persistent HTML/JS script injection vectors.
* **Structural DOM XSS Defenses**: All floating contextual variables, such as dynamically parsed filenames, folder paths in the visual tree editor, usernames, and tag chips, are rendered using a strict escaping utility (`escapeHTML()`).
* **Binary Execution Prevention**: The asset serving and download router (`public_html/download.php`) explicitly blocks raw inline rendering of unknown file attachments. Any non-standard extensions are systematically outputted with a `Content-Type: text/plain` signature to prevent executable HTML or cross-site scripting handshakes inside browser viewports.

---

## 3. Cross-Site Request Forgery (CSRF) & Request Hijacking
* **Immutable Content-Type Enforcement**: All API mutation endpoints (`POST`, `PUT`, `DELETE`) accept **only** explicit `application/json` streams parsed via `php://input`. Traditional form-encoded fallbacks (`$_POST`) are disabled, ensuring modern cross-origin pre-flight CORS protocols implicitly block malicious requests from arbitrary external scopes.
* **Dual-Track Token Firewall**: 
  * **Headless Access**: Bypasses browser-bound nonces by expecting a verified server-issued `Authorization: Bearer` key string.
  * **Browser Sandbox Access**: Direct interface endpoints (such as `api/chat.php` running inside a browser session) strictly evaluate a mandatory custom header (`X-CSRF-Token`) matched cryptographically against `$_SESSION['chat_csrf_token']` using a time-constant hash comparison (`hash_equals()`).

---

## 4. Session Protection & Hijacking Defenses
* **Session Lifecycle Regeneration**: To completely neutralize Session Fixation attacks, the platform executes an explicit `session_regenerate_id(true)` sequence immediately upon every successful registration and login handshake.
* **Cryptographic Remember-Me Tokens**: Persistent session cookies ("Remember Me") decouple plaintext password records. Instead, they store a 32-byte cryptographically secure random token generated via `random_bytes(32)`. The server stores only the sha256 lookup hash of this token, preventing exposure even during database leaks.

---

## 5. Directory Traversal & Zip Slip Countermeasures
* **JSZip Client Isolation**: Multi-file repository extraction happens completely inside the client sandbox using frontend scripts, eliminating server-side zip-bomb and decompression CPU exhaustion risks.
* **Regex Path Normalization**: During zip assembly or file lookups inside `public_html/download.php`, custom paths are aggressively passed through strict directory-traversal regex filters (`/\.+[\/\\]+/`). This instantly strips out relative back-step characters like `../` or `..\`, locking execution tightly inside the designated local repository context boundaries.

---

## 6. SaaS Financial Security & Fraud Prevention
* **Pre-flight Tier Verification**: The capture endpoint (`api/paypal.php`) executes a synchronous check against the current database row prior to making a programmatic call to checkout gateways. If an active `PRO` account holder triggers a lower `VIP` transaction, the framework kills the event before executing a payment request, ensuring zero unintended downgrades or erroneous transaction fees.
* **Gross Ledger Matching Integrity**: The transaction callback verifies the exact gross captured currency asset amounts matching parameters against our internal pricing metrics array (`PRICE_PRO_MONTHLY` / `PRICE_VIP_MONTHLY`). Any altered payload data spikes an alert, blocking entitlement execution.

---

## 7. API Gateway Integrity & Anti-Abuse Rate Controls
* **Headless API Shielding**: Headless REST access to the core chat endpoints (`/api/chat`) evaluates the requesting profile's active subscription layer. Free tier or expired accounts are flatly rejected with an explicit `HTTP 403 Forbidden` status code, preventing external automated application bridging without premium licenses.
* **Guest Rate-Limit Quotas**: Unauthenticated guest accounts operating inside web-browser sandboxes are logged and tracked via transient session arrays (`$_SESSION['guest_daily_count']`) and dated timestamps. Once the daily sandbox limit (`FREE_DAILY_LIMIT`) is breached, request pipelines freeze instantly, neutralizing bulk bot scripts from draining API token balances.