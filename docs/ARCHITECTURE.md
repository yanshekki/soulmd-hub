# SoulMD Hub Architecture 🏗️

SoulMD Hub is a enterprise-grade, high-performance SaaS platform built on a **100% API-First** and **SPA-like (Single Page Application)** model. It intentionally rejects bloated modern heavy frontend frameworks to deliver raw vanilla execution speed and lightweight infrastructure overhead.

---

## 1. Relational Database Schema (MySQL 8.0+)

The relational database layer utilizes rigid indexing, explicit engine constraints, and cascade triggers to guarantee data integrity across subscriptions and chat frames.

* `users`: Account identities with cryptographically secure password hashes, active membership tiers (`free`, `vip`, `pro`), time-based license expiry tracking (`vip_expires_at`), anti-abuse API limit accounting, and server-issued `api_key` hashes.
* `souls`: The central repository containing single prompt sheets (`.md`) or complex modular multi-file JSON bundles (`LONGTEXT`). 
* `soul_versions`: Automated time-stamped history timeline archiving. Any updates to a soul partition automatically snapshot the old state here to allow instant structural rollbacks.
* `soul_ratings` & `soul_likes`: Atomic relation matrices tracking social interactions with strict unique constraint keys to prevent double-voting or duplication exploits.
* `chat_sessions`: Tracks single chat threads, mapping ownership identifiers, cryptographic session tokens, and strict `is_private` encryption toggle flags.
* `chat_messages`: Multi-tenant message ledger containing conversation context strings. Configured with a `MEDIUMTEXT` buffer capable of holding large formatted strings or complex Base64 vision payloads up to 16MB.
* `chat_memory`: High-performance contextual lookups caching compressed thread summaries and floating snapshot window offsets.
* `payments`: Immutable financial ledger logging PayPal checkout outcomes, gross amounts received, status machines, and legal contract signatures.

---

## 2. Dynamic Smart Dual-Engine Routing Matrix

SoulMD Hub decouples natural language processing into two specialized architectural pathways to optimize cost-efficiency, processing speed, and multimodal accuracy:


```

[User Chat Action]
│
├──► Contains Attached Image Template?
│           │
│           ├──► YES (multimodal) ──► Canvas Compression (800px) ──► Together AI Gateway (Qwen 9B)
│           └──► NO (pure text) ──────────────────────────────────► DeepSeek API Gateway (V4/Pro)

```

1. **Pure Text Reasoning Engine**: Routed directly via the **DeepSeek API Endpoint**. Standard requests trigger the high-efficiency `deepseek-v4-flash` framework, while PRO users leverage the advanced deep-thinking `deepseek-v4-pro` reasoning model with internal thoughts chain (CoT) tracking.
2. **Multimodal Vision Engine**: Triggered automatically when binary assets are processed. Requests are redirected to the **Together AI Serverless Platform** deploying optimized open-source multimodal models (`Qwen/Qwen3.5-9B`), delivering rapid OCR and chart analysis under 15 seconds.

---

## 3. High-Traffic Engineering & Timeout Mitigation

### A. Client-Side Image Pre-Compression (Canvas Engine)
To eliminate the rigid **Cloudflare 100-Second Request Deadline (HTTP 524 Timeout)** and Nginx chunked packet congestion, image processing happens entirely within the client's web-browser.
Before a binary payload touches the network interface, a headless HTML5 `<canvas>` redraws the picture, enforcing a gold standard layout limit: `IMAGE_MAX_DIMENSION = 800` and `IMAGE_QUALITY = 0.6`. This forces megabyte-scale files down to an efficient **40KB - 90KB JPEG Base64 payload**, reducing network latency and server overhead by over 90%.

### B. Anti-Lock Session Separation
Large Language Model calls suffer from high Time-To-First-Token (TTFT) delays. Traditional PHP sessions will block subsequent page-loads during this wait window. SoulMD Hub calls `session_write_close()` *immediately* before initializing external curl streams, freeing user session state locks and allowing full concurrent cross-page execution while the AI completes its reasoning pipeline.

### C. Exponential Backoff & DB Reconnection Engine
To guard against serverless capacity rate spikes (`HTTP 429 Too Many Requests`), the API client engine encapsulates network connections in an exponential backoff loop (`sleep($delay); $delay *= 2`), attempting up to 3 automated retries seamlessly. 
Since a long-running cURL process may cause the underlying MySQL socket to drop or timeout, the system explicitly terminates the old descriptor and re-allocates a fresh `new PDO()` transaction frame right before logging AI responses, completely preventing catastrophic thread-drops.

---

## 4. Smart Memory Summarization Layer

To protect context context limits and prevent excessive prompt token bleeding, conversational memory is structured inside a dynamic slide-window layer:

1. The platform polls history buffers matching set subscription thresholds (`memory_threshold`).
2. When the threshold is breached, the older message matrix blocks are extracted, leaving only the most recent two turns completely intact.
3. The extracted historical context is compiled into a lightweight facts summary block (under 150 words) via a high-speed text summarizer.
4. The outcome is atomically written into `chat_memory` and injected dynamically into the next AI system frame instruction prefix.

---

## 5. Dual-Auth API Gateway & Security Framework

The API core enforces a strict dual-track security authentication firewall depending on the origin of the network request:


```
              ┌──────────────────────┐
              │   Incoming Request   │
              └──────────┬___________┘
                         │
          ⚡ Has Authorization Bearer Header?
                         │
          ┌──────────────┴──────────────┐
          ▼ YES                         ▼ NO (Web Client)
  [API Key Validation]           [CSRF Token Validation]



Verify Active Premium Tier       Check HTTP_X_CSRF_TOKEN
Allow Direct Headless Access      Enforce Browser Execution Boundary

```

* **Web UI Execution Pathway**: Validates requests via nonces, session identifiers, and custom secure tokens passed via custom headers (`HTTP_X_CSRF_TOKEN`). Unauthenticated guest users are allowed to execute chat threads here, bounded by rigid temporary session cookies and low daily protection quotas to neutralize automated brute-force scripts.
* **Headless Developer Gateway Pathway**: Bypasses CSRF validation by expecting an API Key passed natively via an `Authorization: Bearer` block. The system parses this key to verify authorization credentials. Direct headless API usage is explicitly locked and restricted to active `VIP` and `PRO` membership nodes; any free or expired tier accounts attempting direct calls are rejected instantly with an explicit `HTTP 403 Forbidden` error.

---

## 6. Financial-Grade Paypal SDK & Subscription Entitlement Matrix

The subscription engine applies explicit pre-checks and multi-tenant calculations during checkout events:

1. **Pre-flight Downgrade Guard**: Before initializing a call to capture funds from the PayPal API endpoint, the system cross-references the database configuration. If an active `PRO` account holder inadvertently initiates a payment for a `VIP` pass, the engine intercepts the transaction *before execution*, preventing accidental downgrades and wrong financial charges.
2. **Prorated Valuation Upgrade Conversion**: If an active `VIP` license holder elects to purchase an upgrade to a `PRO` seat, the system dynamically calculates the residual monetary cash value of his remaining unexpired VIP license pass duration. This duration is mathematically converted based on the price ratio of the two plans and appended automatically onto his new 30-day PRO subscription window as an extra extension credit.
3. **Status Machine Sync**: Handles state flows natively via programmatic transaction commits. Orders that fall into a `PENDING` state logged by clearing houses are tracked silently; premium access boundaries are only unlocked when a definitive `COMPLETED` webhook or signature statement is executed.
