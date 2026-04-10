-- NepalPay v3 Security Schema
-- Run after v1 migration

-- Transaction Logs (audit trail)
CREATE TABLE IF NOT EXISTS nepalpay.transaction_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  action VARCHAR(50) NOT NULL, -- 'login', 'transfer', 'deposit'
  transaction_id INT NULL,
  ip_address VARCHAR(45),
  user_agent TEXT,
  details JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES nepalpay.users(id) ON DELETE SET NULL,
  INDEX idx_user_action (user_id, action),
  INDEX idx_transaction (transaction_id)
);

-- Rate Limiting Table
CREATE TABLE IF NOT EXISTS nepalpay.rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(255) NOT NULL, -- IP or session ID
  action_type VARCHAR(50) NOT NULL, -- 'login', 'transfer'
  attempt_count INT DEFAULT 1,
  last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  locked_until TIMESTAMP NULL,
  UNIQUE KEY unique_action_identifier (identifier, action_type),
  INDEX idx_locked (locked_until)
);

-- Notification Table
CREATE TABLE IF NOT EXISTS nepalpay.notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL, -- 'transaction', 'low_balance', 'kyc'
  title VARCHAR(255),
  message TEXT,
  is_read TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES nepalpay.users(id) ON DELETE CASCADE,
  INDEX idx_user_read (user_id, is_read),
  INDEX idx_type (type)
);

-- Seed audit log trigger (simplified)
DELIMITER //
CREATE TRIGGER after_transaction_audit
AFTER INSERT ON nepalpay.transactions
FOR EACH ROW
BEGIN
  INSERT INTO nepalpay.transaction_logs (user_id, action, transaction_id, details)
  VALUES (NEW.sender_id, 'transfer', NEW.id, JSON_OBJECT('amount', NEW.amount, 'receiver_id', NEW.receiver_id));
END//
DELIMITER ;

SELECT 'Security v3 Ready' as status;

