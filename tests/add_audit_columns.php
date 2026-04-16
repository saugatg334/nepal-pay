<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "Adding missing columns to audit_logs table...\n\n";

$columns = [
    "ALTER TABLE audit_logs ADD COLUMN entity_type VARCHAR(50) AFTER user_id",
    "ALTER TABLE audit_logs ADD COLUMN entity_id INT AFTER entity_type",
    "ALTER TABLE audit_logs ADD COLUMN old_value TEXT AFTER description",
    "ALTER TABLE audit_logs ADD COLUMN new_value TEXT AFTER old_value"
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

echo "\nDone.\n";
