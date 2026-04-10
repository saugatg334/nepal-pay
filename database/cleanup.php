<?php
/**
 * NepalPay Table Cleanup - Fixes tablespace error
 * Run this BEFORE setup.php if tablespace issues
 */

// Secure access
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') die('Access denied');

echo "<h2>NepalPay Cleanup</h2>";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=nepalpay', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>🔍 Checking tables...</p>";
    
    // Discard tablespace for users table
    $pdo->exec("ALTER TABLE users DISCARD TABLESPACE;");
    echo "<p>✅ Users tablespace discarded</p>";
    
    // Drop corrupted tables
    $pdo->exec("DROP TABLE IF EXISTS users, transactions, transaction_logs;");
    echo "<p>✅ Corrupted tables dropped</p>";
    
    // Recreate clean
    $pdo->exec("
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            phone VARCHAR(20) UNIQUE,
            password VARCHAR(255),
            wallet_balance DECIMAL(10,2) DEFAULT 0,
            is_admin TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");
    echo "<p>✅ Clean users table created</p>";
    
    echo "<p><a href='setup.php' style='background:green;color:white;padding:10px 20px;text-decoration:none;'>RUN SETUP.PHP NOW →</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>

