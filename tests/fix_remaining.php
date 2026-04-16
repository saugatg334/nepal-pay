<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "Adding remaining missing columns and tables...\n\n";

// Add missing columns to audit_logs
$auditColumns = [
    "ALTER TABLE audit_logs ADD COLUMN metadata JSON AFTER new_value"
];

foreach ($auditColumns as $sql) {
    try {
        $conn->exec($sql);
        echo "Added column to audit_logs\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column already exists\n";
        } else {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

// Create health_check_log table
$healthCheckLog = "
CREATE TABLE IF NOT EXISTS health_check_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_type VARCHAR(50) NOT NULL,
    status ENUM('healthy', 'warning', 'critical') DEFAULT 'healthy',
    response_time_ms INT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_status (check_type, status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

try {
    $conn->exec($healthCheckLog);
    echo "Created: health_check_log\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "Exists: health_check_log\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
