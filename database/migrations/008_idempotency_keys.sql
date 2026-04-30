-- Idempotency Keys Table
-- Ensures exactly-once semantics for financial transactions
-- When a client retries with same X-Idempotency-Key, we return cached response

CREATE TABLE IF NOT EXISTS idempotency_keys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_hash VARCHAR(64) NOT NULL,
    response_code INT DEFAULT NULL,
    response_body LONGTEXT DEFAULT NULL,
    response_headers JSON DEFAULT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT (DATE_ADD(NOW(), INTERVAL 24 HOUR)),
    
    -- Prevent duplicate keys per user per endpoint
    UNIQUE KEY unique_idempotency (user_id, idempotency_key),
    INDEX idx_idempotency_key (idempotency_key),
    INDEX idx_user_id (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cleanup expired idempotency keys every 24 hours
-- Note: Run via cron: mysql wallet < cleanup_expired_idempotency_keys.sql
-- Or add event (if global_priv allows):
-- CREATE EVENT cleanup_idempotency_keys
-- ON SCHEDULE EVERY 1 DAY
-- DO DELETE FROM idempotency_keys WHERE expires_at < NOW();
