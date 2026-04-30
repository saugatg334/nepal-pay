-- Migration: Add missing auth tables
-- Created to fix login/register flow issues

USE wallet;

-- OTP Tokens table (used by AuthController for verification)
CREATE TABLE IF NOT EXISTS otp_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(6) NOT NULL,
    type ENUM('verification', 'login', 'reset_password', 'transaction') DEFAULT 'verification',
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_token (user_id, type),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password Resets table (used by forgot password flow)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_token (token),
    INDEX idx_user_reset (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registered Devices table (used for biometric login)
CREATE TABLE IF NOT EXISTS registered_devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    device_name VARCHAR(100) DEFAULT 'Unknown Device',
    public_key TEXT,
    last_used DATETIME DEFAULT NULL,
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_device (device_id),
    INDEX idx_user_device (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

