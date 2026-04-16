<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "Adding missing columns to transactions table...\n\n";

$columns = [
    "ALTER TABLE transactions ADD COLUMN description VARCHAR(255) AFTER amount",
    "ALTER TABLE transactions ADD COLUMN idempotency_key VARCHAR(64) AFTER status",
    "ALTER TABLE transactions ADD COLUMN fee DECIMAL(15,2) DEFAULT 0.00",
    "ALTER TABLE transactions ADD COLUMN sender_balance_before DECIMAL(15,2)",
    "ALTER TABLE transactions ADD COLUMN sender_balance_after DECIMAL(15,2)",
    "ALTER TABLE transactions ADD COLUMN receiver_balance_before DECIMAL(15,2)",
    "ALTER TABLE transactions ADD COLUMN receiver_balance_after DECIMAL(15,2)"
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
