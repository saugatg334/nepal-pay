-- Complete NepalPay DB Setup
USE nepalpay;

-- Core Users Table
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) UNIQUE NOT NULL,
  email VARCHAR(100),
  password VARCHAR(255) NOT NULL,
  wallet_balance DECIMAL(12,2) DEFAULT 0.00,
  is_admin TINYINT(1) DEFAULT 0,
  kyc_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  kyc_level INT DEFAULT 0,
  pin VARCHAR(255),
  pin_attempts INT DEFAULT 0,
  pin_locked_until DATETIME NULL,
  failed_login_attempts INT DEFAULT 0,
  account_locked_until DATETIME NULL,
  last_login TIMESTAMP NULL,
  device_token VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transactions Table
DROP TABLE IF EXISTS transactions;
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT,
  receiver_id INT,
  amount DECIMAL(12,2) NOT NULL,
  fee DECIMAL(8,2) DEFAULT 0,
  type VARCHAR(50) NOT NULL,
  status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
  description TEXT,
  txn_id VARCHAR(100),
  provider VARCHAR(100),
  customer_ref VARCHAR(100),
  method VARCHAR(50),
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id),
  FOREIGN KEY (receiver_id) REFERENCES users(id)
);

-- Test Admin User (bcrypt: admin123)
INSERT INTO users (name, phone, password, is_admin, wallet_balance) VALUES
('Admin User', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 100000.00);

-- Test Users
INSERT INTO users (name, phone, password, kyc_status, wallet_balance) VALUES
('Test User1', '9841234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'approved', 5000.00),
('Test User2', '9841234568', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pending', 2500.00);

-- Sample Transactions
INSERT INTO transactions (sender_id, receiver_id, amount, type, status, description) VALUES
(2, 3, 1000.00, 'transfer', 'completed', 'Test transfer'),
(1, 2, 500.00, 'deposit', 'completed', 'Admin deposit');

SELECT 'DB Setup Complete! Admin: phone=admin/pass=admin123 | Users: 9841234567/123456' as status;

