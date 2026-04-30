-- CSP Violation Reporting Table
-- Stores Content-Security-Policy violations for analysis

CREATE TABLE IF NOT EXISTS csp_violations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_uri VARCHAR(500) NOT NULL,
    referrer VARCHAR(500) DEFAULT NULL,
    blocked_uri VARCHAR(500) DEFAULT NULL,
    violated_directive VARCHAR(255) NOT NULL,
    original_policy TEXT,
    source_file VARCHAR(500) DEFAULT NULL,
    line_number INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    
    INDEX idx_created_at (created_at),
    INDEX idx_document_uri (document_uri(100)),
    INDEX idx_violated_directive (violated_directive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
