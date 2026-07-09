-- SoulCorp marketplace & sync extension (Phase 5)

CREATE TABLE IF NOT EXISTS gigs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    poster_user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    budget_usdt DECIMAL(18,8) NOT NULL DEFAULT 0,
    status ENUM('open','assigned','in_progress','in_qc','completed','disputed','cancelled') DEFAULT 'open',
    required_skills JSON,
    deadline DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gigs_status (status),
    INDEX idx_gigs_poster (poster_user_id)
);

CREATE TABLE IF NOT EXISTS gig_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gig_id BIGINT UNSIGNED NOT NULL,
    assignee_user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('assigned','submitted','qc_passed','qc_rejected') DEFAULT 'assigned',
    deliverable_url TEXT,
    qc_score JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gig_assignments_gig (gig_id)
);

CREATE TABLE IF NOT EXISTS platform_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gig_id BIGINT UNSIGNED NULL,
    from_user_id BIGINT UNSIGNED NULL,
    to_user_id BIGINT UNSIGNED NULL,
    amount_usdt DECIMAL(18,8) DEFAULT 0,
    fee_usdt DECIMAL(18,8) DEFAULT 0,
    fee_soul DECIMAL(18,8) DEFAULT 0,
    tx_hash VARCHAR(128),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_tiers (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    tier ENUM('free','pro','vip') DEFAULT 'free',
    soul_staked DECIMAL(18,8) DEFAULT 0,
    soul_balance DECIMAL(18,8) DEFAULT 0,
    expires_at DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sync_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    direction ENUM('push','pull') NOT NULL,
    payload_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sync_logs_user (user_id)
);