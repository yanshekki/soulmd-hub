-- SoulMD Hub Full Schema (with Ratings + API Keys + Categories + Remember Token)

CREATE DATABASE IF NOT EXISTS ki_soulmd_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ki_soulmd_hub;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,              -- 新增：用於 Remember Me 30日免登入
    api_key VARCHAR(64) UNIQUE,                    -- For API access
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Souls Table (Main Content)
CREATE TABLE IF NOT EXISTS souls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content LONGTEXT NOT NULL,
    file_type ENUM('single_md', 'full_soul_folder') DEFAULT 'single_md',
    role VARCHAR(100),
    domain VARCHAR(255),                           -- 擴充長度：支援多選標籤 (逗號分隔)
    compatibility VARCHAR(255),                    -- 擴充長度：支援多選標籤 (逗號分隔)
    is_public BOOLEAN DEFAULT TRUE,
    like_count INT DEFAULT 0,
    fork_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Version History Table
CREATE TABLE IF NOT EXISTS soul_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soul_id INT,
    content LONGTEXT NOT NULL,
    title VARCHAR(255),
    edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (soul_id) REFERENCES souls(id) ON DELETE CASCADE
);

-- Ratings Table (1-5 stars)
CREATE TABLE IF NOT EXISTS soul_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soul_id INT,
    user_id INT,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (soul_id, user_id),
    FOREIGN KEY (soul_id) REFERENCES souls(id) ON DELETE CASCADE
);

-- Categories Table (With Emoji Icons)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    icon VARCHAR(20) DEFAULT '✨'
);

-- Insert Default Categories (Ignored if already exists)
INSERT IGNORE INTO categories (name, slug, icon) VALUES 
('Developer', 'Developer', '💻'),
('Writer', 'Writer', '✍️'),
('Business Analyst', 'Business Analyst', '📊'),
('Researcher', 'Researcher', '🔬'),
('Creative', 'Creative', '🎨'),
('Personal Assistant', 'Personal Assistant', '🤖'),
('Marketing', 'Marketing', '📈'),
('Education', 'Education', '👨‍🏫');

-- (Optional) 保留作未來擴充，目前 Tag 系統已整合進 souls 表的 domain 與 compatibility 欄位
CREATE TABLE IF NOT EXISTS soul_tags (
    soul_id INT,
    tag VARCHAR(100),
    PRIMARY KEY (soul_id, tag)
);

CREATE TABLE IF NOT EXISTS tags_domain (
    name VARCHAR(100) PRIMARY KEY,
    usage_count INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tags_compatibility (
    name VARCHAR(100) PRIMARY KEY,
    usage_count INT DEFAULT 0
);

-- 預設插入初始的 Tags
INSERT IGNORE INTO tags_domain (name, usage_count) VALUES 
('Tech', 0), ('Content Creation', 0), ('Finance & Business', 0), 
('Coding & Dev', 0), ('Gaming', 0), ('Education', 0), 
('Marketing', 0), ('Productivity', 0), ('Healthcare', 0);

INSERT IGNORE INTO tags_compatibility (name, usage_count) VALUES 
('Claude 3.5 Sonnet', 0), ('GPT-4o', 0), ('GPT-4', 0), 
('Gemini 1.5 Pro', 0), ('DeepSeek-V3', 0), ('Llama 3', 0), 
('Qwen 2.5', 0), ('General LLM', 0);