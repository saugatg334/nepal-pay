-- NepalPay Wallet v1 Schema (Production Ready)
-- Run in phpMyAdmin after DROP TABLE users, transactions if corrupted

-- Enhanced Users (add email, limits)
ALTER TABLE nepalpay.users 
ADD COLUMN IF NOT EXISTS email VARCHAR(100) UNIQUE,
ADD COLUMN IF NOT EXISTS daily_limit DECIMAL(10,2) DEFAULT 50000.00,
ADD COLUMN IF NOT EXISTS transaction_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_transaction TIMESTAMP NULL;

-- Transactions Table (if missing)
CREATE TABLE IF NOT EXISTS nepalpay.transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  type ENUM('transfer', 'deposit', 'request_payment', 'add_money') NOT NULL,
  status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
  description VARCHAR(255),
  reference VARCHAR(64) UNIQUE,
  fee DECIMAL(10,2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES nepalpay.users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES nepalpay.users(id) ON DELETE CASCADE
);

-- Request Money Table
CREATE TABLE IF NOT EXISTS nepalpay.money_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  from_user_id INT NOT NULL,
  to_user_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
  message VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (from_user_id) REFERENCES nepalpay.users(id) ON DELETE CASCADE,
  FOREIGN KEY (to_user_id) REFERENCES nepalpay.users(id) ON DELETE CASCADE
);

-- Seed more test data
INSERT IGNORE INTO nepalpay.users (name, phone, password, wallet_balance, email, daily_limit) VALUES
('Test User2', '9859876543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2500.00, 'user2@test.com', 100000.00),
('Merchant', '9811111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 10000.00, 'merchant@test.com', 500000.00);

-- Test transaction
INSERT IGNORE INTO nepalpay.transactions (sender_id, receiver_id, amount, type, status, description, reference) VALUES
(2, 1, 100.00, 'transfer', 'completed', 'Test transfer', 'TXN001');

-- Update balances for test data
UPDATE nepalpay.users SET wallet_balance = 1100.00 WHERE id = 1;
UPDATE nepalpay.users SET wallet_balance = 2400.00 WHERE phone = '9859876543';

-- Indexes for performance
CREATE INDEX idx_transactions_sender ON nepalpay.transactions(sender_id);
CREATE INDEX idx_transactions_receiver ON nepalpay.transactions(receiver_id);
CREATE INDEX idx_transactions_created ON nepalpay.transactions(created_at);
CREATE INDEX idx_users_phone ON nepalpay.users(phone);

-- View: User balance summary
CREATE OR REPLACE VIEW nepalpay.user_balance_summary AS
SELECT 
  u.id, u.name, u.phone,
  u.wallet_balance,
  COALESCE(SUM(CASE WHEN t.sender_id = u.id THEN t.amount ELSE 0 END), 0) as total_sent,
  COALESCE(SUM(CASE WHEN t.receiver_id = u.id THEN t.amount ELSE 0 END), 0) as total_received
FROM nepalpay.users u
LEFT JOIN nepalpay.transactions t ON t.sender_id = u.id OR t.receiver_id = u.id
GROUP BY u.id, u.name, u.phone, u.wallet_balance;

SELECT 'Wallet Schema v1 Complete!' as status;

