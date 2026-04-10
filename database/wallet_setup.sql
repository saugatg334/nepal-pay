-- NepalPay Wallet DB Setup (wallet database)
-- Run in phpMyAdmin: wallet → SQL tab

CREATE DATABASE IF NOT EXISTS `wallet`;
USE `wallet`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) UNIQUE NOT NULL,
  `email` VARCHAR(100),
  `password` VARCHAR(255) NOT NULL,
  `wallet_balance` DECIMAL(15,2) DEFAULT 0.00,
  `kyc_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `is_admin` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Your test users (password: 12345678)
INSERT INTO `users` (`name`, `phone`, `email`, `password`, `wallet_balance`, `is_admin`) VALUES
('Admin', '9746587923', 'saugatg334@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10000.00, 1),
('Test User', '9812345678', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1000.00, 0);

-- Verify
SELECT * FROM users;

-- Complete!

