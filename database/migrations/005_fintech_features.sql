-- NepalPay v5 Fintech Features
-- Payment gateway, OTP, notifications, fraud

USE nepalpay;

-- OTP table
CREATE TABLE IF NOT EXISTS otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  otp_code VARCHAR(6),
  expires_at TIMESTAMP,
  attempts INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Enhanced notifications
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  type ENUM('transaction', 'otp', 'kyc', 'alert') NOT NULL,
  title VARCHAR(255),
  message TEXT,
  data JSON,
  is_read BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Fraud rules (transaction limits)
ALTER TABLE users ADD COLUMN IF NOT EXISTS daily_transaction_limit DECIMAL(10,2) DEFAULT 50000.00;
ALTER TABLE users ADD COLUMN IF NOT EXISTS transaction_count_today INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_transaction_date DATE NULL;

-- QR codes
CREATE TABLE IF NOT EXISTS qr_wallets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNIQUE,
  qr_code VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Payment gateway sessions
CREATE TABLE IF NOT EXISTS payment_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  amount DECIMAL(10,2),
  status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
  gateway_ref VARCHAR(100),
  callback_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Indexes
CREATE INDEX idx_otps_user_expires ON otps(user_id, expires_at);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_payment_sessions_status ON payment_sessions(status);

SELECT 'Fintech features ready!' as status;

