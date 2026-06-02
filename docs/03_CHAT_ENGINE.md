# SoulMD Hub: Chat Engine & Multiplayer Synchronization

## 1. Overview
The SoulMD Hub Chat Engine is a highly optimized, stateless message routing controller designed for low-latency AI interactions. It features a unique **Multiplayer Shared Session** capability, allowing multiple users to simultaneously interact with the same AI Agent in a public setting.

## 2. Dual-Track Routing Architecture
To accommodate both standard consumers and power users, the system implements a Dual-Track execution pipeline:

### 2.1. Official Platform Gateway (`/api/chat.php`)
* **Purpose:** Handles requests utilizing the platform's official API keys and compute quotas.
* **Constraints:** Strictly enforces daily usage limits, maximum conversation turns, and input character counts based on the user's subscription tier (`FREE`, `VIP`, `PRO`).
* **Capabilities:** Routes text-only requests to deep reasoning models and multimodal (image) requests to Vision AI models, provided the user's tier permits it.

### 2.2. BYOK Proxy Gateway (`/api/self-chat.php`)
* **Purpose:** Serves as a stateless proxy for users bringing their own API keys (Bring Your Own Key).
* **Constraints:** Bypasses platform-imposed daily limits and turn restrictions. However, it still strictly enforces Web3 Token-Gating access rights for NFT-based AI agents.
* **Smart Fallback:** If a user submits a Vision request but lacks a custom Vision API key, the system seamlessly attempts to fall back to the official platform Vision allowance, deducting from their daily quota if eligible.

## 3. Multiplayer Real-Time Sync (Delta Sync)

SoulMD Hub supports real-time, multiplayer collaborative AI chats without relying on heavy WebSocket infrastructure. This is achieved through a highly optimized Short-Polling Heartbeat mechanism.

### 3.1. Presence Tracking (`chat_presence`)
* Every 3.5 seconds, active clients dispatch a background fetch to `/api/chat-sync.php`.
* The server updates the `last_seen` timestamp for the user's `identifier` (a unique Guest ID or User ID) in the `chat_presence` table.
* Stale connections (no heartbeat for > 12 seconds) are automatically swept and deleted.
* The API returns the exact count of active participants, triggering UI changes (e.g., highlighting the online badge in purple if `count > 1`).

### 3.2. Incremental Payload Delivery (Delta Fetch)
* Along with the heartbeat, the client sends a `last_id` cursor representing the highest `chat_message.id` currently rendered on their screen.
* The server queries only the messages strictly greater than this `last_id` (`id > ?`), reducing database overhead to near zero for idle chats.

### 3.3. Client-Side Deduplication (Echo Cancellation)
To enable an "Optimistic UI" where a user's message appears instantly without waiting for the server sync, the frontend utilizes a deduplication set:
* When a user sends a message, the content is hashed and added to `window.renderedContents`.
* When the Delta Sync subsequently pulls this same message from the database, the client cross-references the set and gracefully ignores the duplicate, preventing echo rendering.

## 4. Sender Identity Management
In multiplayer sessions, it is crucial to distinguish who is speaking. The system tracks identities via the `sender_name` column:
* **Authenticated Users:** Their registered `username` is recorded.
* **Guest Users:** Assigned a persistent anonymous identifier scoped to their browser session (e.g., `Anonymous #A1B2`).
* **AI Responses:** Always strictly tagged as `AI Assistant`.

## 5. Sliding-Window Memory Compression
To prevent the prompt payload from exceeding the AI model's context window limits (which would cause a crash or massive token billing), the engine features an automated memory compressor.

1. **Threshold Trigger:** When the unsummarized message count exceeds `memory_compress_threshold` (e.g., 10 messages).
2. **LLM Condensation:** The system dispatches a background request to the designated LLM, asking it to compress the oldest `N-2` messages into a factual summary.
3. **Database Caching:** The resulting summary is overwritten in the `chat_memory` table, and the `last_message_id` cursor is advanced.
4. **Prompt Assembly:** Subsequent chat requests will inject `[CONTEXT MEMORY]` at the top of the System Prompt, followed only by the remaining uncompressed recent messages.