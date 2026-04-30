-- NepalPay Migration 007
-- Replay Attack Protection
-- Adds nonce tracking to prevent request replay attacks

CREATE TABLE IF NOT EXISTS request_nonces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nonce VARCHAR(64) NOT NULL COMMENT 'Unique request identifier',
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_nonce (nonce),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Secrets storage for sensitive credentials
CREATE TABLE IF NOT EXISTS secrets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    secret_key VARCHAR(255) NOT NULL,
    secret_value TEXT NOT NULL,
    version VARCHAR(50) DEFAULT 'default',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    rotated_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    UNIQUE KEY idx_secret_key (secret_key),
    INDEX idx_version (version),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Monitoring table for suspicious activity
CREATE TABLE IF NOT EXISTS security_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_type VARCHAR(50) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    request_data JSON DEFAULT NULL,
    description TEXT,
    handled TINYINT(1) DEFAULT 0,
    handled_by INT DEFAULT NULL,
    handled_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_severity (severity),
    INDEX idx_user (user_id),
    INDEX idx_ip (ip_address),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
