CREATE TABLE wallet_comparisons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  wallet_name ENUM('esewa', 'khalti', 'ime_pay') NOT NULL,
  ui_rating INT NOT NULL CHECK (ui_rating >= 1 AND ui_rating <= 5),
  ux_rating INT NOT NULL CHECK (ux_rating >= 1 AND ux_rating <= 5),
  security_rating INT NOT NULL CHECK (security_rating >= 1 AND security_rating <= 5),
  ease_of_use_rating INT NOT NULL CHECK (ease_of_use_rating >= 1 AND ease_of_use_rating <= 5),
  features_rating INT NOT NULL CHECK (features_rating >= 1 AND features_rating <= 5),
  overall_rating INT NOT NULL CHECK (overall_rating >= 1 AND overall_rating <= 5),
  strengths TEXT,
  weaknesses TEXT,
  suggestions TEXT,
  preferred_wallet ENUM('esewa', 'khalti', 'ime_pay', 'nepal_pay'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
