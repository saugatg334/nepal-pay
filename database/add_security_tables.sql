-- Security Tables for Device Fingerprint, OTP, and Notifications
-- Run this migration after the base tables

-- Devices table for trusted device tracking
CREATE TABLE IF NOT EXISTS devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_hash VARCHAR(64) NOT NULL,
    device_name VARCHAR(100) DEFAULT NULL,
    device_type VARCHAR(50) DEFAULT NULL,
    browser VARCHAR(100) DEFAULT NULL,
    os VARCHAR(50) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    last_login TIMESTAMP DEFAULT NULL,
    is_trusted TINYINT(1) DEFAULT 0,
    trust_level INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_device_hash (device_hash),
    INDEX idx_trusted (is_trusted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OTP codes table
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash VARCHAR(64) NOT NULL,
    code_plain VARCHAR(10) NOT NULL,
    type ENUM('register', 'login', 'transaction', 'password_reset', 'device_verify') DEFAULT 'login',
    expires_at TIMESTAMP NOT NULL,
    attempts INT DEFAULT 0,
    is_used TINYINT(1) DEFAULT 0,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_code_hash (code_hash),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table for real transaction notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('money_sent', 'money_received', 'bill_paid', 'topup', 'deposit', 'withdrawal', 'kyc', 'security', 'system') DEFAULT 'system',
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    data JSON DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add device_id column to users if not exists
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS device_id VARCHAR(64) DEFAULT NULL AFTER pin;