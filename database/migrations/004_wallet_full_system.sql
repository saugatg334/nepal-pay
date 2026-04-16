-- Complete Digital Wallet Schema Migration

-- Add missing user fields
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0;

-- KYC table
CREATE TABLE IF NOT EXISTS kyc_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  citizenship_number VARCHAR(50),
  front_photo TEXT,
  back_photo TEXT,
  selfie_photo TEXT,
  status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  reviewed_by INT NULL,
  reviewed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- Enhanced transactions table
ALTER TABLE transactions 
ADD COLUMN IF NOT EXISTS status ENUM('pending','completed','failed') DEFAULT 'completed',
ADD COLUMN IF NOT EXISTS reference_id VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS notes TEXT NULL;

CREATE INDEX IF NOT EXISTS idx_transactions_user ON transactions(sender_id, receiver_id);
CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(created_at);

-- Seed KYC test data
INSERT INTO kyc_requests (user_id, status) VALUES 
(1, 'pending'), -- Test user KYC
(2, 'approved'); -- Demo user approved

echo "✅ Full wallet system schema ready!";

