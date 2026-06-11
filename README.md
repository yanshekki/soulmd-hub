# 🌌 SoulMD Hub (V5 Web2.5 AgentFi Architecture)

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![NEAR Protocol](https://img.shields.io/badge/NEAR-Protocol-black?style=flat-square&logo=near&logoColor=white)](https://near.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-emerald.svg?style=flat-square)](https://opensource.org/licenses/MIT)

Welcome to **SoulMD Hub**, the enterprise-grade platform bridging traditional Web2 user experiences with Web3 decentralized AI asset ownership (AgentFi). 

Version 5 introduces a mathematically verifiable security model, a zero-latency multiplayer chat engine, and a dual-track Bring-Your-Own-Key (BYOK) proxy architecture.

---

## ✨ Core Innovations

* **🔐 Cryptographic Web3 Authentication:** Eliminates identity spoofing by requiring physical Ed25519 detached signatures generated via `near-api-js` and verified natively by backend `libsodium`.
* **⛓️ AgentFi & Token-Gating:** AI Agents can be minted as NFTs on the NEAR blockchain. The engine utilizes real-time RPC view calls to enforce strict owner/renter access control.
* **⚡ Multiplayer Delta Sync Engine:** Enables real-time, shared AI interactions using a highly optimized Short-Polling Heartbeat mechanism, complete with localized deduplication to prevent echo-rendering.
* **🧠 Dual-Track Routing (Platform vs. BYOK):** Seamlessly routes traffic through official paywalled AI gateways (DeepSeek / Together AI Vision) or acts as a stateless, unmetered proxy for users utilizing custom AES-256-CBC encrypted API keys.
* **🗜️ Sliding-Window Memory Compression:** Automatically condenses deep conversation histories to prevent context window overflow while preserving factual continuity.

---

## 📚 Technical Documentation

For deep dives into the system mechanics, security audits, or API integrations, please refer to the official documentation suite located in the `docs/` directory:

1. [**Architecture Overview**](docs/01_ARCHITECTURE.md)
   * High-level topology, tech stack, and the Grand Unified Schema.
2. [**Security & Web3 Integration**](docs/02_SECURITY_AND_WEB3.md)
   * Ed25519 payload mechanics, Token-Gating RPC interception, and anti-twin vulnerabilities.
3. [**Chat Engine & Multiplayer**](docs/03_CHAT_ENGINE.md)
   * Dual-track routing logic, Delta Sync, and Sliding-Window Memory Compression.
4. [**RESTful API Reference**](docs/04_API_REFERENCE.md)
   * Headless integration endpoints, strict schemas, and error responses.
5. [**Security, Logic & NEAR Web3 Audit Plan**](docs/05_SECURITY_AUDIT_PLAN.md)
   * Vulnerability audit, operational logic issues, NEAR wallet/Web3 attack surfaces, and remediation handling solutions (this document).

---

## 🛠️ Technology Stack

* **Frontend:** Vanilla JS, TailwindCSS, `near-api-js`, DOMPurify, Marked.js.
* **Backend:** PHP 8+ (PDO, Libsodium, cURL), RESTful Controllers.
* **Database:** MySQL (InnoDB) with heavily optimized indexing for high-concurrency.
* **Blockchain:** NEAR Protocol Mainnet (`soulmd-hub.near` Smart Contract).

---

## 🚀 Quick Start (Development)

1. Clone the repository to your local LAMP/LEMP server.
2. Import the latest database schema from `private/sql/init.sql`.
3. Copy `private/config.example.php` to `private/config.php` and fill in your database credentials and API keys (DeepSeek, Together AI).
4. Configure your local virtual host to point to the `public_html/` directory as the document root.
5. Access the local URL and log in using the pre-seeded admin accounts.

---

## 👤 Creator

**Ki (yanshekki)** — Full-stack developer, quant trader, founder of [YSK Limited](https://ysk.hk/).

🌐 [linktr.ee/yanshekki](https://linktr.ee/yanshekki) · 🏢 [ysk.hk](https://ysk.hk/)

### ☕ Support / Donate

If SoulMD Hub accelerates your SaaS journey, consider buying me a coffee!

| Network | Address |
| --- | --- |
| **EVM** (ETH/BSC/AVAX) | `yanshekki.eth` |
| **NEAR** | `yanshekki.near` |
| **ADA** (Cardano) | `$yanshekki` |

---

## 📄 License

MIT © Ki (yanshekki)

---

Powered by [YSK Limited](https://ysk.hk/) — Hong Kong Remote Dev Team & Enterprise Solutions
