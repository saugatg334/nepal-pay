CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NULL,
  receiver_id INT NULL,
  amount DECIMAL(10,2) NOT NULL,
  type ENUM('deposit','transfer','withdrawal','government_payment','bill_payment','bank_payment') NOT NULL,
  status ENUM('pending','completed','failed') DEFAULT 'completed',
  description VARCHAR(255) NULL,
  txn_id VARCHAR(64) NULL UNIQUE,
  provider VARCHAR(100) NULL,
  customer_ref VARCHAR(150) NULL,
  method VARCHAR(50) NULL,
  provider_response TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL
);
