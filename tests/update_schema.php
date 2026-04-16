<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "Adding schema updates for production services...\n\n";

// Add new columns
$columns = [
    "ALTER TABLE transactions ADD COLUMN sender_balance_before DECIMAL(15,2) AFTER sender_id",
    "ALTER TABLE transactions ADD COLUMN sender_balance_after DECIMAL(15,2) AFTER sender_balance_before",
    "ALTER TABLE transactions ADD COLUMN receiver_balance_before DECIMAL(15,2) AFTER receiver_id",
    "ALTER TABLE transactions ADD COLUMN receiver_balance_after DECIMAL(15,2) AFTER receiver_balance_before"
];

foreach ($columns as $sql) {
    try {
        $conn->exec($sql);
        echo "Added column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column already exists\n";
        } else {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

// Create health_check_log if not exists
$createHealthLog = "
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
    $conn->exec($createHealthLog);
    echo "Created health_check_log\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "health_check_log exists\n";
    } else {
        echo "Error creating health_check_log: " . $e->getMessage() . "\n";
    }
}

// Insert default rate limit settings
$settings = [
    ['rate_limit_per_minute', '10', 'Max requests per minute'],
    ['rate_limit_per_hour', '50', 'Max requests per hour'],
    ['rate_limit_per_day', '200', 'Max requests per day'],
    ['rate_limit_amount_per_hour', '100000', 'Max amount per hour in NPR'],
    ['rate_limit_amount_per_day', '200000', 'Max amount per day in NPR']
];

foreach ($settings as $setting) {
    try {
        $stmt = $conn->prepare("
            INSERT IGNORE INTO system_settings (setting_key, setting_value, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute($setting);
    } catch (PDOException $e) {
        // Ignore
    }
}
echo "Added rate limit settings\n";

echo "\nDone.\n";