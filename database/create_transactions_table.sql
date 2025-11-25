CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NULL,
  receiver_id INT NULL,
  amount DECIMAL(10,2) NOT NULL,
  type ENUM('deposit','transfer','withdrawal','government_payment') NOT NULL,
  status ENUM('pending','completed','failed') DEFAULT 'completed',
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE SET NULL
);
