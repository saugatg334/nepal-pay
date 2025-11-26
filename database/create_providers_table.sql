CREATE TABLE providers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  mock_endpoint VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE billers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider_id INT NOT NULL,
  name VARCHAR(200) NOT NULL,
  external_code VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
);

-- Sample insert (run after creating DB)
-- INSERT INTO providers (code, name, mock_endpoint) VALUES ('nea', 'NEA (Electricity)', '/public/mock/nea.php');
-- INSERT INTO providers (code, name, mock_endpoint) VALUES ('water', 'KUKL (Water)', '/public/mock/water.php');
-- INSERT INTO providers (code, name, mock_endpoint) VALUES ('isp', 'WorldLink (ISP)', '/public/mock/isp.php');

-- INSERT INTO billers (provider_id, name, external_code) VALUES (1, 'Nepal Electricity Authority', 'NEA_MAIN');
