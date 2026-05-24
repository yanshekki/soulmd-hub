-- SoulMD Hub Full Schema (with Ratings + API Keys + Categories + Remember Token + Tag Tracking + Default Users + SEO Indexes + Chat History)

CREATE DATABASE IF NOT EXISTS ki_soulmd_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ki_soulmd_hub;

-- ==========================================
-- 1. Users Table
-- ==========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    api_key VARCHAR(64) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 🚨 預先插入 4 個預設使用者
-- 注意：所有預設帳號的登入密碼皆為 "password"
INSERT IGNORE INTO users (username, email, password, api_key) VALUES 
('yanshekki', 'yanshekki@ysk.hk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f7g8h9i0j1a2b'),
('ysk', 'ysk@ysk.hk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f7g8h9i0j1a2b3c'),
('ysklimited', 'ysklimited@ysk.hk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '3c4d5e6f7g8h9i0j1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f7g8h9i0j1a2b3c4d'),
('ki', 'ki@ysk.hk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '4d5e6f7g8h9i0j1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e6f7g8h9i0j1a2b3c4d5e');

-- ==========================================
-- 2. Souls Table (Main Content)
-- ==========================================
CREATE TABLE IF NOT EXISTS souls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content LONGTEXT NOT NULL,
    file_type ENUM('single_md', 'full_soul_folder') DEFAULT 'single_md',
    role VARCHAR(100),
    domain VARCHAR(255),
    compatibility VARCHAR(255),
    is_public BOOLEAN DEFAULT TRUE,
    like_count INT DEFAULT 0,
    fork_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_souls_is_public (is_public),
    INDEX idx_souls_file_type (file_type),
    INDEX idx_souls_role (role),
    INDEX idx_souls_title (title),
    INDEX idx_souls_like_count (like_count),
    INDEX idx_souls_fork_count (fork_count),
    INDEX idx_souls_created_at (created_at)
);

-- ==========================================
-- 3. Version History Table
-- ==========================================
CREATE TABLE IF NOT EXISTS soul_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soul_id INT,
    content LONGTEXT NOT NULL,
    title VARCHAR(255),
    edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (soul_id) REFERENCES souls(id) ON DELETE CASCADE,
    INDEX idx_versions_soul_id_date (soul_id, edited_at)
);

-- ==========================================
-- 4. Ratings Table (1-5 stars)
-- ==========================================
CREATE TABLE IF NOT EXISTS soul_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soul_id INT,
    user_id INT,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (soul_id, user_id),
    FOREIGN KEY (soul_id) REFERENCES souls(id) ON DELETE CASCADE
);

-- ==========================================
-- 5. Categories Table (With Emoji Icons)
-- ==========================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    icon VARCHAR(20) DEFAULT '✨'
);

-- Insert Default Categories
INSERT IGNORE INTO categories (name, slug, icon) VALUES 
('Developer', 'Developer', '💻'),
('Writer', 'Writer', '✍️'),
('Business Analyst', 'Business Analyst', '📊'),
('Researcher', 'Researcher', '🔬'),
('Creative', 'Creative', '🎨'),
('Personal Assistant', 'Personal Assistant', '🤖'),
('Marketing', 'Marketing', '📈'),
('Education', 'Education', '👨‍🏫');

-- ==========================================
-- 6. Dynamic Tags Tracking
-- ==========================================
CREATE TABLE IF NOT EXISTS tags_domain (
    name VARCHAR(100) PRIMARY KEY,
    usage_count INT DEFAULT 0,
    INDEX idx_tags_domain_usage (usage_count)
);

CREATE TABLE IF NOT EXISTS tags_compatibility (
    name VARCHAR(100) PRIMARY KEY,
    usage_count INT DEFAULT 0,
    INDEX idx_tags_compat_usage (usage_count)
);

-- Insert Default Tags
INSERT IGNORE INTO tags_domain (name, usage_count) VALUES 
('Tech', 0), ('Content Creation', 0), ('Finance & Business', 0), 
('Coding & Dev', 0), ('Gaming', 0), ('Education', 0), 
('Marketing', 0), ('Productivity', 0), ('Healthcare', 0);

INSERT IGNORE INTO tags_compatibility (name, usage_count) VALUES 
('Claude 3.5 Sonnet', 0), ('GPT-4o', 0), ('GPT-4', 0), 
('Gemini 1.5 Pro', 0), ('DeepSeek-V3', 0), ('Llama 3', 0), 
('Qwen 2.5', 0), ('General LLM', 0);

-- ==========================================
-- 7. Soul Likes Table (per-user like tracking)
-- ==========================================
CREATE TABLE IF NOT EXISTS soul_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soul_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (soul_id, user_id),
    FOREIGN KEY (soul_id) REFERENCES souls(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==========================================
-- 8. Chat Messages Table (One-Click Persona Chats)
-- ==========================================
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soul_id INT NOT NULL,
    session_token VARCHAR(64) NOT NULL,
    role ENUM('user', 'assistant') NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (soul_id) REFERENCES souls(id) ON DELETE CASCADE,
    INDEX idx_chat_session (soul_id, session_token, created_at)
);