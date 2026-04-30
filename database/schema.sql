-- NepalPay Wallet Database Schema
-- Complete wallet system with security

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    kyc_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    kyc_document VARCHAR(255),
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    is_frozen BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wallets Table
CREATE TABLE IF NOT EXISTS wallets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    wallet_number VARCHAR(20) UNIQUE NOT NULL,
    balance DECIMAL(15, 2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'NPR',
    is_active BOOLEAN DEFAULT TRUE,
    daily_limit DECIMAL(15, 2) DEFAULT 50000.00,
    monthly_limit DECIMAL(15, 2) DEFAULT 500000.00,
    daily_spent DECIMAL(15, 2) DEFAULT 0.00,
    monthly_spent DECIMAL(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction PIN (stored separately for security)
CREATE TABLE IF NOT EXISTS transaction_pins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions Table
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    txn_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    type ENUM('send', 'receive', 'deposit', 'withdraw', 'bill_payment') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    balance_before DECIMAL(15, 2),
    balance_after DECIMAL(15, 2),
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    description TEXT,
    recipient_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_txn_id (txn_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bill Payments Table
CREATE TABLE IF NOT EXISTS bill_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    txn_id VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    provider VARCHAR(100) NOT NULL,
    category ENUM('electricity', 'water', 'internet', 'mobile', 'tv', 'education') NOT NULL,
    customer_id VARCHAR(100) NOT NULL,
    customer_name VARCHAR(255),
    amount DECIMAL(15, 2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bank Accounts Table
CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_holder VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_account (user_id, account_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beneficiaries Table
CREATE TABLE IF NOT EXISTS beneficiaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    wallet_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Table (for advanced session handling)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Log Table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create initial admin user (optional)
INSERT INTO users (name, email, phone, username, password, kyc_status, is_active) 
VALUES ('Admin User', 'admin@nepalpay.com', '9841234567', 'admin', 
        '$2y$10$slYQmyNdGzin7olVZeymeuK7leSE9IBJx3rF4XwgS1BxrfgL2EPRY', 'verified', TRUE)
ON DUPLICATE KEY UPDATE email=email;

-- Create test user
INSERT INTO users (name, email, phone, username, password, kyc_status, is_active) 
VALUES ('Demo User', 'demo@nepalpay.com', '9845678901', 'demo', 
        '$2y$10$slYQmyNdGzin7olVZeymeuK7leSE9IBJx3rF4XwgS1BxrfgL2EPRY', 'verified', TRUE)
ON DUPLICATE KEY UPDATE email=email;

-- Create test user wallet
INSERT INTO wallets (user_id, wallet_number, balance, is_active) 
SELECT id, CONCAT('WALLET', LPAD(id, 6, '0')), 45320.00, TRUE
FROM users WHERE username = 'demo' AND NOT EXISTS 
(SELECT 1 FROM wallets WHERE user_id = users.id)
LIMIT 1;
