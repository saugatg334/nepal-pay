-- NepalPay Fintech Wallet - Production DB Setup
-- Paste entire script into phpMyAdmin SQL tab → Run

CREATE DATABASE IF NOT EXISTS saugat;
USE saugat;

-- Drop tables safely (idempotent)
DROP TABLE IF EXISTS transactions, otp_codes, notifications, money_requests, users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    balance DECIMAL(10,2) DEFAULT 0,
    failed_login_attempts INT DEFAULT 0,
    account_locked_until DATETIME NULL,
    kyc_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Transactions
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    amount DECIMAL(10,2),
    type ENUM('send','receive','deposit') DEFAULT 'send',
    reference_id VARCHAR(50),
    status ENUM('success','pending','failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL
);

-- OTP codes
CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    otp VARCHAR(6),
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    `is_read` BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Money requests
CREATE TABLE money_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    amount DECIMAL(10,2),
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Test users (plain password: 12345678 → hashed)
INSERT INTO users (full_name, phone, email, password, role, balance) VALUES 
('Test User', '9841234567', 'user@nepalpay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1000.00),
('Saugat Gautam', '9746587923', 'saugatg334@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 5000.00),
('Admin User', '9810000001', 'admin@nepalpay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 10000.00);

-- Verify setup
SELECT 'DB Ready' as status, COUNT(*) as user_count FROM users;

