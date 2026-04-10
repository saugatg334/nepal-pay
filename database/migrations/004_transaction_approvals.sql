-- Migration: Transaction Approvals + Enhanced Security
-- Run: mysql -u root -p nepalpay < database/migrations/004_transaction_approvals.sql

-- Deposits table (admin approval)
CREATE TABLE IF NOT EXISTS `deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_id` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Withdrawals table (admin approval)
CREATE TABLE IF NOT EXISTS `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_id` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhance transactions table
ALTER TABLE `transactions` 
ADD COLUMN IF NOT EXISTS `status` enum('pending','completed','failed') DEFAULT 'completed',
ADD COLUMN IF NOT EXISTS `reference_id` varchar(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `ip_address` varchar(45) DEFAULT NULL,
ADD INDEX `reference_id` (`reference_id`),
ADD INDEX `status` (`status`);

-- Add locked_balance to users (prevent spending during approval)
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `locked_balance` decimal(12,2) DEFAULT 0.00;
ALTER TABLE `users` ADD INDEX `kyc_status` (`kyc_status`);

-- Rate limiting table
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_ip` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

