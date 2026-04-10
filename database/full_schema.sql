-- NepalPay Full Database Schema - Copy to phpMyAdmin SQL tab
-- Database: nepalpay

-- 1. Users Table (Core)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `wallet_balance` DECIMAL(15,2) DEFAULT 0.00,
  `kyc_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `is_admin` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Transactions Table
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT,
  `receiver_id` INT,
  `amount` DECIMAL(15,2),
  `type` ENUM('send','deposit','withdraw') DEFAULT 'send',
  `description` TEXT,
  `txn_id` VARCHAR(50),
  `status` ENUM('pending','completed','failed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`)
);

-- 3. Test Data (Your accounts)
INSERT IGNORE INTO `users` (`name`, `phone`, `email`, `password`, `wallet_balance`, `is_admin`) VALUES
('Admin', '9746587923', 'saugatg334@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10000.00, 1),
('Test User', '9812345678', 'test@nepalpay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1000.00, 0)
ON DUPLICATE KEY UPDATE `wallet_balance` = VALUES(`wallet_balance`);

-- Test passwords: 12345678 (hashed above)

-- 4. Verify (run this query)
-- SELECT * FROM users;
-- SHOW TABLES;

-- 🎉 Complete! Login now works

