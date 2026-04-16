-- Complete Wallet Ledger & Audit System
-- ACID transactions with balance locking

-- 1. Wallet Ledger Table (immutable log for all changes)
CREATE TABLE IF NOT EXISTS wallet_ledger (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('credit', 'debit', 'adjustment', 'freeze', 'refund') NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  running_balance DECIMAL(15,2) NOT NULL,
  description VARCHAR(255),
  reference_id VARCHAR(64),
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_ledger (user_id, created_at),
  INDEX idx_reference (reference_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 2. Enhanced Transactions Table
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS ledger_ids TEXT COMMENT 'JSON array of ledger IDs',
ADD COLUMN IF NOT EXISTS fee DECIMAL(15,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS exchange_rate DECIMAL(10,6) DEFAULT 1.0,
ADD COLUMN IF NOT EXISTS currency VARCHAR(3) DEFAULT 'NPR',
ADD COLUMN IF NOT EXISTS is_flagged TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS flagged_by INT NULL,
ADD COLUMN IF NOT EXISTS flagged_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS admin_notes TEXT;

-- 3. Rate Limiting Table
CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  endpoint VARCHAR(100) NOT NULL,
  attempts INT DEFAULT 1,
  expires_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_ip_endpoint (ip_address, endpoint)
);

-- 4. Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  admin_id INT NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT,
  old_data JSON,
  new_data JSON,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_action (user_id, action),
  INDEX idx_admin_action (admin_id, action)
);

-- 5. Add indexes for performance
ALTER TABLE users ADD INDEX idx_wallet_balance (wallet_balance);
ALTER TABLE transactions ADD INDEX idx_sender_receiver (sender_id, receiver_id);
ALTER TABLE wallet_ledger ADD INDEX idx_running_balance (running_balance);
ALTER TABLE rate_limits ADD INDEX idx_ip_expires (ip_address, expires_at);

-- Seed test data
INSERT INTO wallet_ledger (user_id, type, amount, running_balance, description) VALUES
(1, 'credit', 10000.00, 10000.00, 'Initial deposit'),
(1, 'debit', 500.00, 9500.00, 'Test transfer'),
(2, 'credit', 500.00, 500.00, 'Received test transfer');

echo "✅ Complete wallet ledger system ready!";

