-- Nepal Pay Complete Database Schema
-- Wallet Engine, Transactions, KYC, Activity Logs

-- 1. Update users table with wallet and security fields
ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_balance DECIMAL(15,2) DEFAULT 0.00;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pin VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pin_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS pin_locked_until DATETIME DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS wallet_frozen TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_level INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_status VARCHAR(20) DEFAULT 'pending';
ALTER TABLE users ADD COLUMN IF NOT EXISTS citizenship_number VARCHAR(50) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS dob DATE DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS full_address TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_documents TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS kyc_notes TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_received DECIMAL(15,2) DEFAULT 0.00;
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_sent DECIMAL(15,2) DEFAULT 0.00;
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 2. Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT DEFAULT NULL,
    receiver_id INT DEFAULT NULL,
    amount DECIMAL(15,2) NOT NULL,
    fee DECIMAL(15,2) DEFAULT 0.00,
    type ENUM('deposit', 'send', 'receive', 'withdrawal', 'admin_adjust', 'fee', 'bill_payment', 'recharge') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    reference_id VARCHAR(100) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    sender_balance_before DECIMAL(15,2) DEFAULT NULL,
    sender_balance_after DECIMAL(15,2) DEFAULT NULL,
    receiver_balance_before DECIMAL(15,2) DEFAULT NULL,
    receiver_balance_after DECIMAL(15,2) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    device_info VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    device_info VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. KYC documents table
CREATE TABLE IF NOT EXISTS kyc_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    document_number VARCHAR(100) DEFAULT NULL,
    front_image VARCHAR(255) DEFAULT NULL,
    back_image VARCHAR(255) DEFAULT NULL,
    selfie_image VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. System settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('min_transfer_amount', '10', 'Minimum transfer amount in NPR'),
('max_transfer_amount', '25000', 'Maximum transfer amount per transaction'),
('daily_transfer_limit', '50000', 'Daily transfer limit'),
('monthly_transfer_limit', '200000', 'Monthly transfer limit'),
('transfer_fee_percent', '0', 'Transfer fee percentage'),
('withdrawal_fee', '10', 'Fixed withdrawal fee'),
('pin_max_attempts', '5', 'Maximum PIN attempts before lock'),
('pin_lock_duration', '30', 'PIN lock duration in minutes'),
('maintenance_mode', '0', 'Maintenance mode toggle'),
('user_registration_enabled', '1', 'User registration toggle');

-- 6. Transaction PIN changes table
CREATE TABLE IF NOT EXISTS pin_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    old_pin_hash VARCHAR(255) DEFAULT NULL,
    new_pin_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
