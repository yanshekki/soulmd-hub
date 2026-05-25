# Public API Reference 📡

SoulMD Hub provides a robust, JSON-based RESTful API for developers to manage AI agent configurations and integrate the Core Chat Engine programmatically. 

All endpoints strictly consume and return `application/json` payloads.

---

## 🔑 Authentication

Most protected endpoints require a Secret API Key passed via the `Authorization` header. You can manage or roll your API Key from the Developer Dashboard (`/my-api`).

```http
Authorization: Bearer YOUR_API_KEY_HERE

```

*Note: Free or expired accounts are strictly prohibited from executing headless requests to the Chat Engine. Upgrading to the VIP or PRO tier is required.*

---

## 📥 Postman Collection

SoulMD Hub features an automated Postman Collection generator. Navigate to your Developer Dashboard (`/my-api`) to download a `.json` collection pre-populated with all available routes and your active API key.

---

## 🤖 Interaction & Chat Engine (VIP / PRO Only)

### 1. Retrieve Chat History

* **GET** `/api/chat`
* **Query Params**:
* `soul_id` (int, Required)
* `session_token` (string, Required)


* **Behavior**: Retrieves the full message array for a given session. Access to private sessions is strictly validated against the active API Key owner.

### 2. Send Message (Headless Chat)

* **POST** `/api/chat`
* **Payload**:
```json
{
  "action": "chat",
  "soul_id": 123,
  "session_token": "unique_session_token",
  "content": "Analyze this data...",
  "image": "data:image/jpeg;base64,...", 
  "is_private": false
}

```


* **Behavior**: Connects to the Dual-Engine router (DeepSeek for text, Together AI for vision). Applies memory compression and returns the AI's response.

### 3. Silent Privacy Sync

* **POST** `/api/chat`
* **Payload**:
```json
{
  "action": "update_privacy",
  "soul_id": 123,
  "session_token": "unique_session_token",
  "is_private": true
}

```



---

## 🛡️ Authentication & Account

* **POST** `/api/register`: Register a new user and generate an API key.
* **POST** `/api/login`: Authenticate user and return the API key (supports 30-day Remember Me token).
* **POST** `/api/change-password` *(Auth Required)*: Securely update the current user's password.
* **POST** `/api/regenerate-key` *(Session Required)*: Invalidates the current API key and issues a new 32-byte hex key.

---

## 🧠 Core Souls Management

### 1. Fetch Public Souls

* **GET** `/api/souls`
* **Query Params**:
* `limit` (int, default: 12)
* `offset` (int, default: 0)
* `q` (string, supports multi-keyword intelligent search)
* `sort` (string: `newest`, `oldest`, `popular`, `forks`, `az`, `za`)
* `role` (string)
* `file_type` (string: `single_md`, `full_soul_folder`)



### 2. Get Single Soul

* **GET** `/api/soul/:id`
* Returns full details, tags, stats, and the raw markdown/JSON content of a soul.

### 3. Publish a New Soul (Auth Required)

* **POST** `/api/souls`
* **Payload**:
```json
{
  "title": "My Awesome Agent",
  "description": "Short bio...",
  "content": "## Identity\nYou are...",
  "role": "Developer",
  "domain": "Tech",
  "compatibility": "GPT-4o",
  "is_public": 1
}

```



### 4. Update an Existing Soul (Auth Required)

* **PUT** `/api/soul/:id`
* **Behavior**: Performs a partial update and automatically saves the previous state to the `soul_versions` table for rollback capability.

### 5. Delete a Soul (Auth Required)

* **DELETE** `/api/soul/:id`
* **Behavior**: Permanently deletes the soul and cleans up isolated tags metadata.

---

## 🌿 Profiles & Social Actions

* **GET** `/api/profile?username={username}`: Fetch public indicators (likes, forks) and active soul arrays.
* **GET** `/api/versions?soul_id={id}` *(Auth Required for Private)*: Retrieve the historical rollback archive.
* **POST** `/api/versions` *(Auth Required)*: Instantly restore active content layout to a historical milestone.
* **POST** `/api/fork` *(Auth Required)*: Clone a public agent directly into your workspace.
* **POST** `/api/like` *(Auth Required)*: Toggle atomic like/unlike state.
* **POST** `/api/rate` *(Auth Required)*: Submit a 1-5 star rating and return global live averages.
