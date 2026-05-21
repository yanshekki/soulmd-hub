# Public API Reference 📡

SoulMD Hub provides a robust, JSON-based RESTful API for developers to manage AI agent configurations programmatically.

## Authentication

All protected endpoints require an API Key passed via the `Authorization` header:
```http
Authorization: Bearer YOUR_API_KEY_HERE

```

## Endpoints

### 1. Fetch Public Souls

* **GET** `/api/souls`
* **Query Params**:
* `limit` (int, default: 20)
* `offset` (int, default: 0)
* `q` (string, search keyword)
* `sort` (string: `newest`, `popular`, `forks`)
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
* **Behavior**: Performs a partial update and automatically saves the previous state to the `soul_versions` table.

### 5. Delete a Soul (Auth Required)

* **DELETE** `/api/soul/:id`
* **Behavior**: Permanently deletes the soul and cleans up isolated tags.

### 6. Fork a Public Soul (Auth Required)

* **POST** `/api/fork`
* **Payload**: `{ "soul_id": 123 }`

### 7. Toggle Like (Auth Required)

* **POST** `/api/like`
* **Payload**: `{ "soul_id": 123 }`
* **Returns**: `{ "success": true, "liked": true/false }`

### 8. Rate a Soul (Auth Required)

* **POST** `/api/rate`
* **Payload**: `{ "soul_id": 123, "rating": 5 }`