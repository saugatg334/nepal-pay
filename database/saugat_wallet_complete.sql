-- NepalPay COMPLETE Database (saugat)
CREATE DATABASE IF NOT EXISTS `saugat`;
USE `saugat`;

-- Clean reset (important)
DROP TABLE IF EXISTS `money_requests`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `otp_codes`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `users`;

-- =========================
-- USERS TABLE
-- =========================
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') DEFAULT 'user',
  `balance` DECIMAL(10,2) DEFAULT 0.00,
  `kyc_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- TRANSACTIONS TABLE
-- =========================
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT,
  `receiver_id` INT,
  `amount` DECIMAL(10,2) NOT NULL,
  `type` ENUM('send','receive','deposit') DEFAULT 'send',
  `reference_id` VARCHAR(50),
  `status` ENUM('success','pending','failed') DEFAULT 'success',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- OTP TABLE
-- =========================
CREATE TABLE `otp_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `otp` VARCHAR(6),
  `expires_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- NOTIFICATIONS TABLE
-- =========================
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `message` TEXT,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- MONEY REQUEST TABLE
-- =========================
CREATE TABLE `money_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT,  
  `receiver_id` INT,
  `amount` DECIMAL(10,2),
  `status` ENUM('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- TEST USERS
-- Password: 12345678
-- =========================
INSERT INTO `users` (`full_name`, `phone`, `email`, `password`, `role`, `balance`) VALUES
('Test User', '9841234567', 'user@nepalpay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1000.00),
('Admin User', '9810000001', 'admin@nepalpay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 5000.00);

-- =========================
-- DONE
-- =========================
SHOW TABLES;
SELECT * FROM users;

