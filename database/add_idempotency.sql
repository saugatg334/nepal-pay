-- Idempotency Keys Table for Duplicate Prevention
-- Run this migration to create the idempotency_keys table

CREATE TABLE IF NOT EXISTS idempotency_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_value VARCHAR(100) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    amount DECIMAL(15,2) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    response TEXT DEFAULT NULL,
    expires_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_value (key_value),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;