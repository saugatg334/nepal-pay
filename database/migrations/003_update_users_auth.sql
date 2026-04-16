-- Migration for complete auth system
ALTER TABLE users 
ADD COLUMN email VARCHAR(255) UNIQUE AFTER phone,
ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER kyc_status,
ADD COLUMN password_reset_token VARCHAR(255) NULL AFTER updated_at,
ADD COLUMN otp_code VARCHAR(6) NULL AFTER password_reset_token,
ADD COLUMN otp_expires DATETIME NULL AFTER otp_code;

-- Create notifications table
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('success', 'error', 'info', 'warning') DEFAULT 'info',
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin seed
INSERT INTO users (name, phone, email, password_hash, is_admin) VALUES 
('Admin User', '9800000001', 'admin@nepalpay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1) 
ON DUPLICATE KEY UPDATE is_admin = 1;

