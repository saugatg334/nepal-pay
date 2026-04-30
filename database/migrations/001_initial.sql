-- NepalPay Digital Wallet System Database Schema
-- Database: wallet
-- Version: 2.0

CREATE DATABASE IF NOT EXISTS wallet;
USE wallet;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_verified TINYINT(1) DEFAULT 0,
    is_frozen TINYINT(1) DEFAULT 0,
    failed_attempts INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    role ENUM('user', 'admin', 'merchant') DEFAULT 'user',
    prefer_lang ENUM('en', 'np') DEFAULT 'en',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wallets Table
CREATE TABLE IF NOT EXISTS wallets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'NPR',
    wallet_number VARCHAR(20) UNIQUE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_user_wallet (user_id),
    INDEX idx_wallet_number (wallet_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessions Table
CREATE TABLE IF NOT EXISTS sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_type VARCHAR(20) DEFAULT NULL,
    browser VARCHAR(50) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_session (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- OTP Codes Table
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    otp_type ENUM('login', 'verify', 'reset_password', 'transaction') DEFAULT 'login',
    purpose VARCHAR(100) DEFAULT NULL,
    is_used TINYINT(1) DEFAULT 0,
    attempts INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_otp (user_id, otp_type, is_used),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Biometric Devices Table (WebAuthn)
CREATE TABLE IF NOT EXISTS user_devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    credential_id VARCHAR(255) NOT NULL,
    public_key TEXT NOT NULL,
    device_name VARCHAR(100) DEFAULT NULL,
    device_type VARCHAR(50) DEFAULT 'unknown',
    is_active TINYINT(1) DEFAULT 1,
    last_used DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_credential (credential_id),
    INDEX idx_user_device (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    sender_id INT,
    receiver_id INT,
    amount DECIMAL(15, 2) NOT NULL,
    fee DECIMAL(15, 2) DEFAULT 0.00,
    type ENUM('send', 'receive', 'add_money', 'withdraw', 'bill_payment', 'merchant_payment', 'refund', 'adjustment') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    description VARCHAR(255) DEFAULT NULL,
    reference_id VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Beneficiaries Table
CREATE TABLE IF NOT EXISTS beneficiaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    beneficiary_user_id INT NOT NULL,
    nickname VARCHAR(50) DEFAULT NULL,
    is_favorite TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (beneficiary_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_user_beneficiary (user_id, beneficiary_user_id),
    INDEX idx_beneficiary_user (beneficiary_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bank Accounts Table
CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_holder_name VARCHAR(100) NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    is_default TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_bank (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Merchant Table
CREATE TABLE IF NOT EXISTS merchants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    business_type VARCHAR(50) DEFAULT NULL,
    business_address TEXT DEFAULT NULL,
    merchant_code VARCHAR(50) UNIQUE NOT NULL,
    qr_prefix VARCHAR(20) DEFAULT 'NP',
    is_active TINYINT(1) DEFAULT 1,
    is_approved TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY idx_merchant_code (merchant_code),
    INDEX idx_user_merchant (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Merchant Payments Table
CREATE TABLE IF NOT EXISTS merchant_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    merchant_id INT NOT NULL,
    transaction_id INT NOT NULL,
    order_id VARCHAR(100) DEFAULT NULL,
    order_amount DECIMAL(15, 2) NOT NULL,
    payment_amount DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE,
    -- FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_merchant (merchant_id),
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('transaction', 'security', 'system', 'promotion') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    data JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notification (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bill Payments Table
CREATE TABLE IF NOT EXISTS bill_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bill_type ENUM('ntc', 'ncell', 'nea', 'internet') NOT NULL,
    bill_number VARCHAR(50) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    transaction_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    INDEX idx_user_bill (user_id, bill_type),
    INDEX idx_bill_number (bill_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login Attempts Table (for rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    attempt_count INT DEFAULT 1,
    isLocked TINYINT(1) DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_attempt (ip_address, phone),
    INDEX idx_ip_email (ip_address, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security Logs Table
CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_type VARCHAR(20) DEFAULT NULL,
    status ENUM('success', 'failed', 'blocked') DEFAULT 'success',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_security (user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin Action Logs Table
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    target_user_id INT,
    action_type VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    notes TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_admin_log (admin_id),
    INDEX idx_target (target_user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wallet Balance Adjustment Logs
CREATE TABLE IF NOT EXISTS balance_adjustments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    wallet_id INT NOT NULL,
    adjustment_type ENUM('credit', 'debit') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    balance_before DECIMAL(15, 2) NOT NULL,
    balance_after DECIMAL(15, 2) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    reference_id VARCHAR(100),
    adjusted_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (adjusted_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_adjustment (user_id),
    INDEX idx_adjusted_by (adjusted_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample admin user (password: 123456)
INSERT INTO users (id, phone, email, password, full_name, role, is_active, is_verified) 
VALUES (1, '9746587923', 'admin@nepalpay.com', '$2y$10$eR7v5QJZxV5YQvK3wP8mL.9tT5rE4qXzL6mN2pJ0hR8dC9fG5eH', 'System Administrator', 'admin', 1, 1) 
ON DUPLICATE KEY UPDATE phone='9746587923';

-- Service Providers Table (for Bill Payments)
CREATE TABLE IF NOT EXISTS providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('mobile', 'electricity', 'internet', 'television', 'water') NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    api_endpoint VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    processing_time INT DEFAULT 10,
    fee_percent DECIMAL(5,2) DEFAULT 0.00,
    cashback_percent DECIMAL(5,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wallet Comparisons Table
CREATE TABLE IF NOT EXISTS wallet_comparisons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    wallet_name VARCHAR(100) NOT NULL,
    transfer_fee DECIMAL(5,2) DEFAULT 0.00,
    bill_payment_fee DECIMAL(5,2) DEFAULT 0.00,
    cashback_percent DECIMAL(5,2) DEFAULT 0.00,
    processing_time_min INT DEFAULT 1,
    user_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    is_available TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default service providers
INSERT INTO providers (name, type, processing_time, fee_percent, cashback_percent) VALUES
('Nepal Telecom', 'mobile', 5, 0.00, 1.50),
('Ncell', 'mobile', 3, 0.00, 2.00),
('Smart Cell', 'mobile', 5, 0.00, 1.00),
('NEA', 'electricity', 15, 0.00, 0.50),
('WorldLink', 'internet', 5, 0.00, 1.00),
('Vianet', 'internet', 5, 0.00, 0.75)
ON DUPLICATE KEY UPDATE name=name;

-- Insert wallet comparison data
INSERT INTO wallet_comparisons (wallet_name, transfer_fee, bill_payment_fee, cashback_percent, processing_time_min, user_count, rating) VALUES
('Nepal Pay', 0.00, 0.00, 2.00, 1, 150000, 4.7),
('eSewa', 0.50, 0.25, 1.50, 2, 850000, 4.3),
('Khalti', 0.25, 0.00, 1.75, 1, 620000, 4.5),
('IME Pay', 0.00, 0.50, 1.00, 3, 410000, 4.1),
('Prabhu Pay', 0.50, 0.25, 1.25, 2, 280000, 4.2)
ON DUPLICATE KEY UPDATE wallet_name=wallet_name;

-- Insert sample wallet for admin
INSERT INTO wallets (user_id, balance, wallet_number) VALUES (1, 100000.00, 'NP9746587923') 
ON DUPLICATE KEY UPDATE balance=100000.00;