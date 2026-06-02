<?php
/**
 * SoulMD Hub - Postman Collection Generator
 * Included by my-api.php and api-docs.php
 * (Web2.5 AgentFi & BYOK Proxy Edition)
 * 🚀 Patched: Added chat-sync endpoint and integrated sender_name into all chat response samples.
 */
?>
<script>
    function downloadPostmanCollection() {
        const keyDisplay = document.getElementById('key-display');
        const currentApiKey = keyDisplay ? keyDisplay.innerText : 'YOUR_API_KEY';
        
        const collection = {
            "info": {
                "name": "SoulMD Hub Public API",
                "_postman_id": "soulmd_hub_collection_" + Date.now(),
                "description": "<?= addslashes(__('API_Driven_Desc')) ?>",
                "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
            },
            "item": [
                {
                    "name": "<?= addslashes(__('Authentication & Account')) ?>",
                    "item": [
                        {
                            "name": "Register User",
                            "request": {
                                "method": "POST",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"username": "developer101", "password": "securepassword123", "email": "dev@example.com"}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/register", "host": ["{{baseUrl}}"], "path": ["api", "register"] }
                            },
                            "response": [{
                                "name": "Registration Success",
                                "status": "Created",
                                "code": 201,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Account created successfully", "api_key": "7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c..."}, null, 2)
                            }]
                        },
                        {
                            "name": "Login User",
                            "request": {
                                "method": "POST",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"username": "developer101", "password": "securepassword123", "remember": true}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/login", "host": ["{{baseUrl}}"], "path": ["api", "login"] }
                            },
                            "response": [{
                                "name": "Login Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Login successful", "api_key": "7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c..."}, null, 2)
                            }]
                        },
                        {
                            "name": "Change Password",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"current_password": "securepassword123", "new_password": "brandnewpassword999", "confirm_password": "brandnewpassword999"}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/change-password", "host": ["{{baseUrl}}"], "path": ["api", "change-password"] }
                            },
                            "response": [{
                                "name": "Password Update Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Password successfully updated!"}, null, 2)
                            }]
                        },
                        {
                            "name": "Bind Web3 Wallet",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({
                                        "action": "bind", 
                                        "wallet": "yanshekki.near",
                                        "public_key": "ed25519:G1...",
                                        "signature": "L/xN...",
                                        "message": "soulmd_auth:1716330000000"
                                    }, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/bind-wallet", "host": ["{{baseUrl}}"], "path": ["api", "bind-wallet"] }
                            },
                            "response": [{
                                "name": "Wallet Bound Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Wallet bound successfully!"}, null, 2)
                            }]
                        },
                        {
                            "name": "Web3 Wallet Login",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({
                                        "account_id": "yanshekki.near",
                                        "public_key": "ed25519:G1...",
                                        "signature": "L/xN...",
                                        "message": "soulmd_auth:1716330000000"
                                    }, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/wallet-login", "host": ["{{baseUrl}}"], "path": ["api", "wallet-login"] }
                            },
                            "response": [{
                                "name": "Login Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true}, null, 2)
                            }]
                        }
                    ]
                },
                {
                    "name": "<?= addslashes(__('Interaction & Chat Engine')) ?>",
                    "item": [
                        {
                            "name": "Retrieve Chat History",
                            "request": {
                                "method": "GET",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": {
                                    "raw": "{{baseUrl}}/api/chat?soul_id=1&session_token=unique_session_id_123",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "chat"],
                                    "query": [
                                        {"key": "soul_id", "value": "1"},
                                        {"key": "session_token", "value": "unique_session_id_123"}
                                    ]
                                }
                            },
                            "response": [{
                                "name": "History Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "messages": [{"role": "user", "sender_name": "developer101", "content": "Hello!"}, {"role": "assistant", "sender_name": "AI Assistant", "content": "Hi there!"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Retrieve My Chats List",
                            "request": {
                                "method": "GET",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": {
                                    "raw": "{{baseUrl}}/api/my-chats",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "my-chats"]
                                }
                            },
                            "response": [{
                                "name": "Sessions Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "sessions": [{"session_token": "unique_session_id_123", "soul_id": 1, "summary": "User asked about architecture layout...", "last_updated": "2026-05-21 14:00:00"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Send Chat Message (Official Engine)",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"action": "chat", "soul_id": 1, "session_token": "unique_session_id_123", "content": "Analyze this architecture.", "is_private": false}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/chat", "host": ["{{baseUrl}}"], "path": ["api", "chat"] }
                            },
                            "response": [{
                                "name": "AI Reply Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "reply": "Based on the provided architecture...", "sender_name": "AI Assistant"}, null, 2)
                            }]
                        },
                        {
                            "name": "Send Chat Message (BYOK Proxy Engine)",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 2, "session_token": "byok_session_token_xyz", "content": "Execute high-concurrency trace optimization patterns.", "is_private": true}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/self-chat", "host": ["{{baseUrl}}"], "path": ["api", "self-chat"] }
                            },
                            "response": [{
                                "name": "BYOK Reply Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "reply": "Optimizing memory structures using stateless concurrent relays...", "sender_name": "AI Assistant"}, null, 2)
                            }]
                        },
                        {
                            "name": "Multiplayer Sync & Presence Heartbeat",
                            "request": {
                                "method": "GET",
                                "header": [],
                                "url": {
                                    "raw": "{{baseUrl}}/api/chat-sync?soul_id=1&session_token=unique_session_id_123&last_id=142",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "chat-sync"],
                                    "query": [
                                        {"key": "soul_id", "value": "1"},
                                        {"key": "session_token", "value": "unique_session_id_123"},
                                        {"key": "last_id", "value": "142"}
                                    ]
                                }
                            },
                            "response": [{
                                "name": "Delta Sync Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "online_count": 2, "new_messages": [{"id": 143, "role": "user", "sender_name": "Anonymous #E5A1", "content": "Is anyone else monitoring this cluster thread?"}]}, null, 2)
                            }]
                        }
                    ]
                },
                {
                    "name": "<?= addslashes(__('Core Souls Hub')) ?>",
                    "item": [
                        {
                            "name": "List Available Categories/Roles",
                            "request": {
                                "method": "GET",
                                "header": [],
                                "url": { "raw": "{{baseUrl}}/api/categories", "host": ["{{baseUrl}}"], "path": ["api", "categories"] }
                            },
                            "response": [{
                                "name": "Categories Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "count": 2, "data": [{"id": 1, "name": "Developer", "slug": "Developer", "icon": "💻"}, {"id": 2, "name": "Writer", "slug": "Writer", "icon": "✍️"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "List Public Souls",
                            "request": {
                                "method": "GET",
                                "header": [],
                                "url": {
                                    "raw": "{{baseUrl}}/api/souls?limit=20&offset=0&sort=popular",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "souls"],
                                    "query": [
                                        {"key": "limit", "value": "20"},
                                        {"key": "offset", "value": "0"},
                                        {"key": "sort", "value": "popular"},
                                        {"key": "q", "value": "ai", "disabled": true},
                                        {"key": "role", "value": "Developer", "disabled": true},
                                        {"key": "file_type", "value": "full_soul_folder", "disabled": true}
                                    ]
                                }
                            },
                            "response": [{
                                "name": "List Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "count": 1, "data": [{"id": 1, "title": "Expert Translator", "description": "Translates documents contextually", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet", "file_type": "single_md", "like_count": 12, "fork_count": 3, "created_at": "2026-05-21 12:00:00"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Get Single Soul Details",
                            "request": {
                                "method": "GET",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": { 
                                    "raw": "{{baseUrl}}/api/soul/:id", 
                                    "host": ["{{baseUrl}}"], 
                                    "path": ["api", "soul", ":id"],
                                    "variable": [{ "key": "id", "value": "1" }]
                                }
                            },
                            "response": [
                                {
                                    "name": "Single MD Response Sample",
                                    "status": "OK",
                                    "code": 200,
                                    "_postman_previewlanguage": "json",
                                    "header": [{"key": "Content-Type", "value": "application/json"}],
                                    "body": JSON.stringify({"success": true, "data": {"id": 1, "user_id": 5, "title": "Expert Translator", "description": "Translates documents contextually", "content": "## Identity\nYou are an expert translator...", "file_type": "single_md", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet", "is_public": 1, "like_count": 12, "fork_count": 3, "created_at": "2026-05-21 12:00:00"}}, null, 2)
                                },
                                {
                                    "name": "Modular Folder Response Sample",
                                    "status": "OK",
                                    "code": 200,
                                    "_postman_previewlanguage": "json",
                                    "header": [{"key": "Content-Type", "value": "application/json"}],
                                    "body": JSON.stringify({"success": true, "data": {"id": 2, "user_id": 5, "title": "Advanced Dev Architecture", "description": "Full-stack code assistant package layout", "content": "{\n  \"SOUL.md\": \"## Identity\\nYou are a senior developer...\",\n  \"STYLE.md\": \"## Voice\\nConcise, code-heavy...\",\n  \"RULES.md\": \"## Hard Rules\\nNever write legacy code...\"\n}", "file_type": "full_soul_folder", "role": "Developer", "domain": "Coding & Dev", "compatibility": "GPT-4o", "is_public": 1, "like_count": 88, "fork_count": 15, "created_at": "2026-05-21 14:22:10"}}, null, 2)
                                }
                            ]
                        },
                        {
                            "name": "Publish New Soul",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"title": "Expert Translator", "description": "Translates documents contextually", "content": "## Identity\nYou are an expert...", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet"}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/souls", "host": ["{{baseUrl}}"], "path": ["api", "souls"] }
                            },
                            "response": [{
                                "name": "Creation Success",
                                "status": "Created",
                                "code": 201,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Soul created successfully", "id": 42, "url": "<?= defined('BASE_URL') ? BASE_URL : 'https://soulmd-hub.ysk.hk' ?>/soul/42"}, null, 2)
                            }]
                        },
                        {
                            "name": "Update Existing Soul",
                            "request": {
                                "method": "PUT",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"title": "Expert Translator v2", "description": "Updated translation engine", "content": "## Identity\nYou are...", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet", "is_public": 1}, null, 2)
                                },
                                "url": { 
                                    "raw": "{{baseUrl}}/api/soul/:id", 
                                    "host": ["{{baseUrl}}"], 
                                    "path": ["api", "soul", ":id"],
                                    "variable": [{ "key": "id", "value": "1" }]
                                }
                            },
                            "response": [{
                                "name": "Update Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Soul updated successfully"}, null, 2)
                            }]
                        },
                        {
                            "name": "Delete Soul",
                            "request": {
                                "method": "DELETE",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": { 
                                    "raw": "{{baseUrl}}/api/soul/:id", 
                                    "host": ["{{baseUrl}}"], 
                                    "path": ["api", "soul", ":id"],
                                    "variable": [{ "key": "id", "value": "1" }]
                                }
                            },
                            "response": [{
                                "name": "Deletion Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Soul deleted successfully"}, null, 2)
                            }]
                        }
                    ]
                },
                {
                    "name": "<?= addslashes(__('Profiles & Social Interactions')) ?>",
                    "item": [
                        {
                            "name": "Get User Profile Data",
                            "request": {
                                "method": "GET",
                                "header": [],
                                "url": {
                                    "raw": "{{baseUrl}}/api/profile?username=developer101",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "profile"],
                                    "query": [{"key": "username", "value": "developer101"}]
                                }
                            },
                            "response": [{
                                "name": "Profile Data Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "user": {"username": "developer101", "joined_at": "2026-05-20 10:00:00"}, "stats": {"total_souls": 5, "total_likes": 24, "total_forks": 8}, "souls": [{"id": 1, "title": "Expert Translator", "description": "Translates documents...", "role": "Translator", "domain": "Education", "compatibility": "Claude 3.5 Sonnet", "file_type": "single_md", "like_count": 12, "fork_count": 3, "created_at": "2026-05-21 12:00:00"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Get Soul History Versions",
                            "request": {
                                "method": "GET",
                                "header": [{"key": "Authorization", "value": "Bearer {{apiKey}}"}],
                                "url": {
                                    "raw": "{{baseUrl}}/api/versions?soul_id=1",
                                    "host": ["{{baseUrl}}"],
                                    "path": ["api", "versions"],
                                    "query": [{"key": "soul_id", "value": "1"}]
                                }
                            },
                            "response": [{
                                "name": "Timeline Versions Returned",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "count": 1, "data": [{"id": 12, "soul_id": 1, "title": "Expert Translator v1", "content": "## Identity\nYou are...", "edited_at": "2026-05-21 15:30:00"}]}, null, 2)
                            }]
                        },
                        {
                            "name": "Restore Historical Version",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 1, "version_id": 5}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/versions", "host": ["{{baseUrl}}"], "path": ["api", "versions"] }
                            },
                            "response": [{
                                "name": "Rollback Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Version restored successfully"}, null, 2)
                            }]
                        },
                        {
                            "name": "Fork Public Soul",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 1}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/fork", "host": ["{{baseUrl}}"], "path": ["api", "fork"] }
                            },
                            "response": [{
                                "name": "Fork Clone Success",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "new_soul_id": 43, "url": "<?= defined('BASE_URL') ? BASE_URL : 'https://soulmd-hub.ysk.hk' ?>/soul/43", "message": "Soul forked successfully!"}, null, 2)
                            }]
                        },
                        {
                            "name": "Toggle Like Status",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 1}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/like", "host": ["{{baseUrl}}"], "path": ["api", "like"] }
                            },
                            "response": [{
                                "name": "Like Toggled Successfully",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "liked": true, "message": "Soul liked successfully"}, null, 2)
                            }]
                        },
                        {
                            "name": "Rate Soul (1-5 Stars)",
                            "request": {
                                "method": "POST",
                                "header": [
                                    {"key": "Content-Type", "value": "application/json"},
                                    {"key": "Authorization", "value": "Bearer {{apiKey}}"}
                                ],
                                "body": {
                                    "mode": "raw",
                                    "raw": JSON.stringify({"soul_id": 1, "rating": 5}, null, 2)
                                },
                                "url": { "raw": "{{baseUrl}}/api/rate", "host": ["{{baseUrl}}"], "path": ["api", "rate"] }
                            },
                            "response": [{
                                "name": "Rating Saved Successfully",
                                "status": "OK",
                                "code": 200,
                                "_postman_previewlanguage": "json",
                                "header": [{"key": "Content-Type", "value": "application/json"}],
                                "body": JSON.stringify({"success": true, "message": "Rating submitted successfully", "avg_rating": 4.5, "total_ratings": 18}, null, 2)
                            }]
                        }
                    ]
                }
            ],
            "variable": [
                { "key": "baseUrl", "value": "<?= defined('BASE_URL') ? BASE_URL : 'https://soulmd-hub.ysk.hk' ?>", "type": "string" },
                { "key": "apiKey", "value": currentApiKey, "type": "string" }
            ]
        };

        const jsonStr = JSON.stringify(collection, null, 2);
        const blob = new Blob([jsonStr], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement("a");
        a.href = url;
        a.download = "soulmd_hub.postman_collection.json";
        document.body.appendChild(a);
        a.click();
        
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
</script>