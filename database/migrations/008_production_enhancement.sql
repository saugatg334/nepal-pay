-- Production Enhancement Tables
-- Run this to add ledger, fraud detection, and device security tables

-- 1. Ledger Entries - Double Entry Bookkeeping
CREATE TABLE IF NOT EXISTS ledger_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entry_type ENUM('debit', 'credit', 'adjustment', 'reversal') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    running_balance DECIMAL(15,2) NOT NULL,
    description VARCHAR(255),
    reference_type VARCHAR(50),
    reference_id VARCHAR(64),
    metadata JSON,
    status ENUM('pending', 'posted', 'voided') DEFAULT 'posted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_entries (user_id, created_at),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_entry_type (entry_type),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Reconciliation Log
CREATE TABLE IF NOT EXISTS reconciliation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ledger_balance DECIMAL(15,2) NOT NULL,
    wallet_balance DECIMAL(15,2) NOT NULL,
    difference DECIMAL(15,2) NOT NULL,
    status ENUM('matched', 'discrepancy', 'adjusted') DEFAULT 'matched',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Trusted Devices
CREATE TABLE IF NOT EXISTS trusted_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    device_name VARCHAR(100),
    device_type VARCHAR(50),
    browser VARCHAR(100),
    os VARCHAR(50),
    ip_address VARCHAR(45),
    last_used_at TIMESTAMP NULL,
    is_trusted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    UNIQUE KEY unique_device (user_id, device_fingerprint),
    INDEX idx_user_devices (user_id),
    INDEX idx_fingerprint (device_fingerprint),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. OTP Codes
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    purpose ENUM('login', 'transaction', 'password_reset', 'pin_change', 'kyc') NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    attempts INT DEFAULT 0,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_otp (user_id, purpose, is_used),
    INDEX idx_code (otp_code, purpose),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Fraud Alerts
CREATE TABLE IF NOT EXISTS fraud_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2),
    type VARCHAR(50),
    flags JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('open', 'investigating', 'resolved', 'false_positive') DEFAULT 'open',
    resolution VARCHAR(50),
    resolved_notes TEXT,
    resolved_by INT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_alerts (user_id, status),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Enhanced Notifications
ALTER TABLE notifications 
ADD COLUMN priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal' AFTER message,
ADD COLUMN status ENUM('unread', 'read', 'archived') DEFAULT 'unread' AFTER priority,
ADD COLUMN read_at TIMESTAMP NULL AFTER status,
ADD COLUMN archived_at TIMESTAMP NULL AFTER read_at,
ADD INDEX idx_user_status (user_id, status),
ADD INDEX idx_priority (priority);

-- Add is_read column if not exists (backward compatibility)
-- ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) DEFAULT 0;

-- 7. API Keys (for future API access)
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    permissions JSON,
    ip_whitelist TEXT,
    is_active TINYINT(1) DEFAULT 1,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_keys (user_id),
    INDEX idx_key_hash (key_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Session Log (for security tracking)
CREATE TABLE IF NOT EXISTS session_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_fingerprint VARCHAR(255),
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_user_sessions (user_id, is_active),
    INDEX idx_session (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Rate Limit Events
CREATE TABLE IF NOT EXISTS rate_limit_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    blocked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier, event_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Wallet Freeze Log
CREATE TABLE IF NOT EXISTS wallet_freeze_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action ENUM('frozen', 'unfrozen', 'limit_set') NOT NULL,
    amount DECIMAL(15,2),
    reason TEXT,
    frozen_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (frozen_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample trusted device for testing
-- INSERT INTO trusted_devices (user_id, device_fingerprint, device_name, is_trusted) VALUES (1, 'test-device-001', 'Test Device', 1);

-- Insert sample fraud alert for testing
-- INSERT INTO fraud_alerts (user_id, amount, type, flags, status) VALUES (1, 50000, 'transfer', '["high_value"]', 'open');

SELECT 'Production Enhancement Tables Created Successfully!' as result;
