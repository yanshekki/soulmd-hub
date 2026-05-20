-- SoulMD Hub Full Schema (with Ratings + API Keys)

CREATE DATABASE IF NOT EXISTS soulmd_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE soulmd_hub;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    api_key VARCHAR(64) UNIQUE,                    -- For API access
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS souls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    content LONGTEXT NOT NULL,
    file_type ENUM('single_md', 'full_soul_folder') DEFAULT 'single_md',
    role VARCHAR(100),
    domain VARCHAR(100),
    compatibility VARCHAR(100),
    is_public BOOLEAN DEFAULT TRUE,
    like_count INT DEFAULT 0,
    fork_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Version History
CREATE TABLE IF NOT EXISTS soul_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soul_id INT,
    content LONGTEXT NOT NULL,
    title VARCHAR(255),
    edited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (soul_id) REFERENCES souls(id) ON DELETE CASCADE
);

-- Ratings (1-5 stars)
CREATE TABLE IF NOT EXISTS soul_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soul_id INT,
    user_id INT,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (soul_id, user_id),
    FOREIGN KEY (soul_id) REFERENCES souls(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE
);

CREATE TABLE IF NOT EXISTS soul_tags (
    soul_id INT,
    tag VARCHAR(100),
    PRIMARY KEY (soul_id, tag)
);