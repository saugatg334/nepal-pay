<?php
// DB Setup for NepalPay Wallet
require_once 'app/config/database.php';

echo "Creating 'wallet' database and tables...\n";

try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $pdo->exec("DROP DATABASE IF EXISTS wallet");
    $pdo->exec("CREATE DATABASE wallet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE wallet");
    
    $schema = file_get_contents('sql/schema.sql');
    $pdo->exec($schema);
    
    echo "Fixed foreign key order. All tables created.\n";
    
    echo "✅ Database 'wallet' created with all tables!\n";
    echo "Login: phone=9800000000, pass=admin123\n";
    echo "Visit: http://localhost/wallet/\n";
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
