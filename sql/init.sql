-- SoulMD Hub Initial Database Schema

CREATE DATABASE IF NOT EXISTS soulmd_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE soulmd_hub;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS souls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('single_md', 'full_soul_folder') DEFAULT 'single_md',
    role VARCHAR(100),
    domain VARCHAR(100),
    compatibility VARCHAR(100),
    is_public BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    fork_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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