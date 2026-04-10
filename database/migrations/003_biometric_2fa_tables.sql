-- Migration: Biometric Credentials + 2FA + Logs
-- Run: mysql -u root -p nepalpay < database/migrations/003_biometric_2fa_tables.sql

-- Biometric credentials table (WebAuthn)
CREATE TABLE IF NOT EXISTS `biometric_credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `public_key` longtext NOT NULL,
  `device_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_used` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credential_id` (`credential_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `biometric_credentials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- OTP verification table
CREATE TABLE IF NOT EXISTS `otp_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `type` enum('login','2fa','password_reset') DEFAULT 'login',
  `expires_at` timestamp NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `phone` (`phone`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login activity logs
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `login_method` enum('password','biometric','otp') DEFAULT 'password',
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add 2FA enabled column to users
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `two_factor_enabled` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `biometric_enabled` TINYINT(1) DEFAULT 0;

-- Indexes for performance
CREATE INDEX idx_otp_expires ON otp_verification(expires_at);
CREATE INDEX idx_login_user ON login_logs(user_id);

