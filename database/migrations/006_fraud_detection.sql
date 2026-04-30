-- NepalPay Migration 006
-- Fraud Detection Tables
-- Adds fraud assessment logging and IP blacklisting

-- Fraud Assessments Table
CREATE TABLE IF NOT EXISTS fraud_assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    risk_level ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'low',
    risk_score INT DEFAULT 0,
    reasons JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    assessed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_risk (user_id, risk_level),
    INDEX idx_risk_level (risk_level),
    INDEX idx_assessed (assessed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Blacklisted IPs Table
CREATE TABLE IF NOT EXISTS blacklisted_ips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    reason TEXT DEFAULT NULL,
    source VARCHAR(50) DEFAULT 'system',
    blocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_ip (ip_address),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transaction Limits Table (for user-specific limits)
CREATE TABLE IF NOT EXISTS transaction_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    daily_limit DECIMAL(15, 2) DEFAULT NULL,
    single_limit DECIMAL(15, 2) DEFAULT NULL,
    monthly_limit DECIMAL(15, 2) DEFAULT NULL,
    enforced_by ENUM('system', 'admin', 'user') DEFAULT 'system',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_user_limit (user_id),
    INDEX idx_enforced (enforced_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
