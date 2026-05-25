<?php
/**
 * SoulMD Hub - API Reference Documentation UI Component
 * Included by my-api.php and api-docs.php
 */
?>

<div class="<?= $isPublicApiPage ? 'xl:col-span-12 max-w-5xl mx-auto w-full' : 'xl:col-span-8' ?> space-y-8">
    <div class="bg-zinc-900/60 border border-white/10 rounded-3xl p-6 md:p-8 backdrop-blur-sm shadow-xl">
        
        <?php if ($isPublicApiPage): ?>
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-8 border-b border-white/10 pb-4">
                <h2 class="text-2xl font-bold">API Reference</h2>
                <button onclick="downloadPostmanCollection()" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-zinc-950 text-sm font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/10">
                    <i class="fas fa-file-download"></i> Download Postman Collection
                </button>
            </div>
        <?php else: ?>
            <h2 class="text-2xl font-bold mb-8 border-b border-white/10 pb-4">API Reference</h2>
        <?php endif; ?>

        <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-10"><i class="fas fa-user-shield mr-2"></i> Authentication & Account</h3>
        
        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/register</code>
            </div>
            <p class="text-sm text-zinc-400">Register a new user and generate an API key. Enforces secure alpha-numeric URL constraints.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "username": "developer101",
  "password": "securepassword123",
  "email": "dev@example.com"
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Account created successfully",
  "api_key": "7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c..."
}</pre>
                </details>
            </div>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/login</code>
            </div>
            <p class="text-sm text-zinc-400">Authenticate user. Returns API Key and sets a secure 30-day web session if requested.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "username": "developer101",
  "password": "securepassword123",
  "remember": true
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Login successful",
  "api_key": "7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c..."
}</pre>
                </details>
            </div>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/change-password</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
            </div>
            <p class="text-sm text-zinc-400">Change the current logged-in user's password securely.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "current_password": "securepassword123",
  "new_password": "brandnewpassword999",
  "confirm_password": "brandnewpassword999"
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Password successfully updated!"
}</pre>
                </details>
            </div>
        </div>

        <h3 class="text-xl font-bold text-amber-400 mb-6 mt-12"><i class="fas fa-comments mr-2"></i> Interaction & Chat Engine</h3>

        <div class="mb-10 border-l-2 border-amber-500 pl-6 space-y-3 relative">
            <div class="flex items-center flex-wrap gap-2">
                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                <code class="text-base font-bold text-white">/api/chat</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded border border-red-500/20">Auth Required</span>
                <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded border border-amber-500/20"><i class="fas fa-crown mr-1"></i>VIP / PRO Only</span>
            </div>
            <p class="text-sm text-zinc-400">Headless API access to retrieve conversation history. Strict permission controls prevent accessing private sessions.</p>
            <p class="text-xs text-zinc-500 font-mono">Query params: ?soul_id=1&session_token=random_token_here</p>
            <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "messages": [
    {
      "role": "user",
      "content": "Hello! How can you help me today?"
    },
    {
      "role": "assistant",
      "content": "I am an expert assistant. I can help you with coding and reasoning tasks."
    }
  ]
}</pre>
            </details>
        </div>

        <div class="mb-10 border-l-2 border-amber-500 pl-6 space-y-3 relative">
            <div class="flex items-center flex-wrap gap-2">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/chat</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded border border-red-500/20">Auth Required</span>
                <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded border border-amber-500/20"><i class="fas fa-crown mr-1"></i>VIP / PRO Only</span>
            </div>
            <p class="text-sm text-zinc-400">Headless API access to interact with the core routing engine. Send messages, optionally attach base64 images (Vision AI), and receive responses. Free tier requests to this endpoint will be strictly rejected with a 403 Forbidden status.</p>
            <p class="text-xs text-amber-400 font-semibold bg-amber-500/10 border border-amber-500/20 p-2.5 rounded-xl"><i class="fas fa-exclamation-triangle"></i> <strong>Subscription Policy:</strong> Direct API integration requires an active VIP or PRO license. If your subscription period expires, your integration will be automatically disabled until a renewal is processed.</p>
            
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "action": "chat",
  "soul_id": 1,
  "session_token": "unique_session_id_123",
  "content": "Can you analyze this architecture diagram?",
  "image": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQ...", // Optional Base64 Image
  "is_private": false
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "reply": "Based on the provided architecture diagram, here is the technical breakdown..."
}</pre>
                </details>
            </div>
        </div>

        <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-12"><i class="fas fa-brain mr-2"></i> Core Souls Hub</h3>

        <div class="mb-10 border-l-2 border-purple-500 pl-6 space-y-2">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                <code class="text-base font-bold text-white">/api/categories</code>
            </div>
            <p class="text-sm text-zinc-400">Fetch the complete white-list of roles/categories including their corresponding slug names and emoji icons.</p>
            <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "count": 2,
  "data": [
    { "id": 1, "name": "Developer", "slug": "Developer", "icon": "💻" },
    { "id": 2, "name": "Writer", "slug": "Writer", "icon": "✍️" }
  ]
}</pre>
            </details>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                <code class="text-base font-bold text-white">/api/souls</code>
            </div>
            <p class="text-sm text-zinc-400">List, search and filter public souls. Optimized with strict DB select limits.</p>
            <p class="text-xs text-zinc-500 font-mono">Query params: ?limit=20&offset=0&q=ai&sort=popular&role=Developer</p>
            <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "count": 1,
  "data": [
    {
      "id": 1,
      "title": "Expert Translator",
      "description": "Translates documents contextually",
      "role": "Translator",
      "domain": "Education",
      "compatibility": "Claude 3.5 Sonnet",
      "file_type": "single_md",
      "like_count": 12,
      "fork_count": 3,
      "created_at": "2026-05-21 12:00:00"
    }
  ]
}</pre>
            </details>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                <code class="text-base font-bold text-white">/api/soul/{id}</code>
            </div>
            <p class="text-sm text-zinc-400">Retrieve raw architecture files, tags, and stats of a single public or owned soul.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample (file_type: single_md)</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 5,
    "title": "Expert Translator",
    "description": "Translates documents contextually",
    "content": "## Identity\nYou are an expert translator...",
    "file_type": "single_md",
    "role": "Translator",
    "domain": "Education",
    "compatibility": "Claude 3.5 Sonnet",
    "is_public": 1,
    "like_count": 12,
    "fork_count": 3,
    "created_at": "2026-05-21 12:00:00"
  }
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-purple-400 cursor-pointer select-none font-medium hover:underline">View Response Sample (file_type: full_soul_folder)</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "data": {
    "id": 2,
    "user_id": 5,
    "title": "Advanced Dev Architecture",
    "description": "Full-stack code assistant package layout",
    "content": "{\n  \"SOUL.md\": \"## Identity\\nYou are a senior developer...\",\n  \"STYLE.md\": \"## Voice\\nConcise, code-heavy...\",\n  \"RULES.md\": \"## Hard Rules\\nNever write legacy code...\"\n}",
    "file_type": "full_soul_folder",
    "role": "Developer",
    "domain": "Coding & Dev",
    "compatibility": "GPT-4o",
    "is_public": 1,
    "like_count": 88,
    "fork_count": 15,
    "created_at": "2026-05-21 14:22:10"
  }
}</pre>
                </details>
            </div>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/souls</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
            </div>
            <p class="text-sm text-zinc-400">Publish a brand new AI agent. Automatically detects single .md prompt or full Modular configuration folders.</p>
            <p class="text-xs text-red-400 font-semibold bg-red-500/10 border border-red-500/20 p-2.5 rounded-xl"><i class="fas fa-exclamation-triangle"></i> CRITICAL CONSTRAINT: The <code>role</code> field inside the request body MUST strictly use one of the <code>slug</code> values provided by the <code>/api/categories</code> API. Invalid roles will be forcefully fallbacked to 'Other'.</p>
            
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "title": "Expert Translator",
  "description": "Translates documents contextually",
  "content": "## Identity\nYou are an expert translator...",
  "role": "Translator",
  "domain": "Education",
  "compatibility": "Claude 3.5 Sonnet"
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Soul created successfully",
  "id": 42,
  "url": "<?= $baseUrl ?>/soul/42"
}</pre>
                </details>
            </div>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 font-mono text-[10px] font-bold rounded border border-amber-500/30">PUT</span>
                <code class="text-base font-bold text-white">/api/soul/{id}</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
            </div>
            <p class="text-sm text-zinc-400">Update an existing soul module layout. Automatically creates an incremental version timeline backup record.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "title": "Expert Translator v2",
  "description": "Updated translation engine",
  "content": "## Identity\nYou are...",
  "role": "Translator",
  "domain": "Education",
  "compatibility": "Claude 3.5 Sonnet",
  "is_public": 1
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Soul updated successfully"
}</pre>
                </details>
            </div>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-red-500/20 text-red-400 font-mono text-[10px] font-bold rounded border border-red-500/30">DELETE</span>
                <code class="text-base font-bold text-white">/api/soul/{id}</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
            </div>
            <p class="text-sm text-zinc-400">Permanently delete a soul architecture configuration and gracefully updates relational metadata tracking statistics.</p>
            <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Soul deleted successfully"
}</pre>
            </details>
        </div>

        <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-12"><i class="fas fa-code-branch mr-2"></i> Profiles & Social Interactions</h3>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                <code class="text-base font-bold text-white">/api/profile</code>
            </div>
            <p class="text-sm text-zinc-400">Fetch public indicators (aggregated likes, forks, total models) and public soul array mapping for any developer.</p>
            <p class="text-xs text-zinc-500 font-mono">Query params: ?username=developer101</p>
            <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "user": {
    "username": "developer101",
    "joined_at": "2026-05-20 10:00:00"
  },
  "stats": {
    "total_souls": 5,
    "total_likes": 24,
    "total_forks": 8
  },
  "souls": [
    {
      "id": 1,
      "title": "Expert Translator",
      "description": "Translates documents...",
      "role": "Translator",
      "domain": "Education",
      "compatibility": "Claude 3.5 Sonnet",
      "file_type": "single_md",
      "like_count": 12,
      "fork_count": 3,
      "created_at": "2026-05-21 12:00:00"
    }
  ]
}</pre>
            </details>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-2">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 font-mono text-[10px] font-bold rounded border border-blue-500/30">GET</span>
                <code class="text-base font-bold text-white">/api/versions</code>
            </div>
            <p class="text-sm text-zinc-400">Retrieve full historical rollback archive versions of a soul. Protected by strict IDOR multi-tenant permission validation check.</p>
            <p class="text-xs text-zinc-500 font-mono">Query params: ?soul_id={id}</p>
            <details class="text-xs group"><summary class="text-emerald-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "count": 1,
  "data": [
    {
      "id": 12,
      "soul_id": 1,
      "title": "Expert Translator v1",
      "content": "## Identity\nYou are...",
      "edited_at": "2026-05-21 15:30:00"
    }
  ]
}</pre>
            </details>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/versions</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
            </div>
            <p class="text-sm text-zinc-400">Instantly restore active state content layout to a historical milestone setup version identifier point.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "soul_id": 1,
  "version_id": 5
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Version restored successfully"
}</pre>
                </details>
            </div>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/fork</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
            </div>
            <p class="text-sm text-zinc-400">Clone a public agent model directly into your workspace account as an independent project fork tree line node.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "soul_id": 1
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "new_soul_id": 43,
  "url": "<?= $baseUrl ?>/soul/43",
  "message": "Soul forked successfully!"
}</pre>
                </details>
            </div>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/like</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
            </div>
            <p class="text-sm text-zinc-400">Toggle like/unlike state. Enforces atomic uniqueness index mapping constraints. Returns boolean state indicating if currently liked.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "soul_id": 1
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "liked": true,
  "message": "Soul liked successfully"
}</pre>
                </details>
            </div>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/rate</code>
                <span class="text-[10px] bg-red-500/20 text-red-400 px-2 py-0.5 rounded ml-2 border border-red-500/20">Auth Required</span>
            </div>
            <p class="text-sm text-zinc-400">Rate between 1 to 5 stars. Submitting again overrides previous row entry record. Returns updated global live averages for instant interface refresh.</p>
            <div class="pt-1 flex flex-col gap-2">
                <details class="text-xs group"><summary class="text-cyan-400 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Request Body Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-cyan-300/90 overflow-x-auto">{
  "soul_id": 1,
  "rating": 5
}</pre>
                </details>
                <details class="text-xs group"><summary class="text-emerald-500 group-open:text-zinc-500 cursor-pointer select-none font-medium hover:underline">View Response Sample</summary>
                    <pre class="bg-zinc-950 border border-white/5 p-3 rounded-xl mt-2 font-mono text-zinc-400 overflow-x-auto">{
  "success": true,
  "message": "Rating submitted successfully",
  "avg_rating": 4.5,
  "total_ratings": 18
}</pre>
                </details>
            </div>
        </div>

        <?php if (!$isPublicApiPage): ?>
        <h3 class="text-xl font-bold text-emerald-400 mb-6 mt-12"><i class="fas fa-tools mr-2"></i> Internal Web Utilities</h3>
        <p class="text-sm text-zinc-500 mb-6 bg-amber-500/10 border border-amber-500/20 p-3 rounded-xl text-amber-200"><i class="fas fa-exclamation-triangle"></i> Note: The following endpoints rely on browser Session Cookies and cannot be authenticated via API Keys. They are excluded from the Postman Collection.</p>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/logout</code>
                <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded ml-2 border border-amber-500/20">Session Cookie Required</span>
            </div>
            <p class="text-sm text-zinc-400">Clear session and remember me tokens for the current browser session.</p>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/regenerate-key</code>
                <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded ml-2 border border-amber-500/20">Session Cookie Required</span>
            </div>
            <p class="text-sm text-zinc-400">Invalidate the current API key and issue a new 32-byte hex key securely to the logged-in user.</p>
        </div>

        <div class="mb-10 border-l-2 border-zinc-800 pl-6 space-y-3">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold rounded border border-emerald-500/30">POST</span>
                <code class="text-base font-bold text-white">/api/save-preset</code>
                <span class="text-[10px] bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded ml-2 border border-amber-500/20">Session Cookie Required</span>
            </div>
            <p class="text-sm text-zinc-400">Internal endpoint to temporarily save generated AI layouts into current user's session cache memory.</p>
        </div>
        <?php endif; ?>

    </div>
</div>