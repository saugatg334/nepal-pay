-- NepalPay Production Migration 002
-- Adds rate limiting and API token tables

-- Rate Limits Table (for centralized rate limiting)
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    identifier VARCHAR(255) NOT NULL COMMENT 'IP or user-based key',
    attempts INT DEFAULT 1,
    last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
    window_start DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_identifier (identifier),
    INDEX idx_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Tokens Table (for mobile app authentication)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    token_type ENUM('access', 'refresh') DEFAULT 'access',
    device_info VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME DEFAULT NULL,
    revoked TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_token (user_id, revoked),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin Actions Table (if missing from initial schema)
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    target_user_id INT DEFAULT NULL,
    action_type VARCHAR(50) NOT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_admin (admin_id),
    INDEX idx_target (target_user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Logs Table (comprehensive change tracking)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL COMMENT 'users, wallets, transactions, etc.',
    entity_id INT DEFAULT NULL,
    old_data JSON DEFAULT NULL,
    new_data JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Performance Indexes for existing tables
ALTER TABLE transactions ADD INDEX IF NOT EXISTS idx_status_created (status, created_at);
ALTER TABLE notifications ADD INDEX IF NOT EXISTS idx_user_read_created (user_id, is_read, created_at);
ALTER TABLE login_attempts ADD INDEX IF NOT EXISTS idx_ip_locked (ip_address, locked_until);

