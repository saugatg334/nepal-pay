-- Audit Logs Table for Security Tracking
-- Run this migration to create the audit_logs table

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    description TEXT,
    metadata JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add idempotency_key column to transactions table
-- This prevents duplicate transactions
ALTER TABLE transactions 
    ADD COLUMN IF NOT EXISTS idempotency_key VARCHAR(100) DEFAULT NULL AFTER txn_id,
    ADD INDEX IF NOT EXISTS idx_idempotency (idempotency_key);

-- Add status columns to transactions for better tracking
ALTER TABLE transactions 
    ADD COLUMN IF NOT EXISTS status ENUM('pending', 'processing', 'completed', 'failed', 'reversed') DEFAULT 'pending' AFTER type,
    ADD COLUMN IF NOT EXISTS status_reason TEXT DEFAULT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS provider_txn_id VARCHAR(100) DEFAULT NULL AFTER status_reason;