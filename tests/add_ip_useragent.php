<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "Adding ip_address and user_agent to audit_logs...\n\n";

$columns = [
    "ALTER TABLE audit_logs ADD COLUMN ip_address VARCHAR(45) AFTER metadata",
    "ALTER TABLE audit_logs ADD COLUMN user_agent TEXT AFTER ip_address"
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
