<?php
/*
 * Seed test user: phone=9746587923, password=123456
 * Run: php database/seed_test_user.php
 */
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = new Database();
    $pdo = $db->connect();
    
    // Hash password
$password = password_hash('123456', PASSWORD_DEFAULT); // matches $user['password'] in AuthController
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute(['9746587923']);
    if ($stmt->fetch()) {
        echo "Test user already exists.\n";
        exit(0);
    }
    
    // Insert
$stmt = $pdo->prepare("INSERT INTO users (full_name, phone, password_hash) VALUES (?, ?, ?)"); 
    $result = $stmt->execute(['Test User', '9746587923', $password]);
    
    if ($result) {
        echo "Test user created successfully! Login: 9746587923 / 123456\n";
    } else {
        echo "Failed to create test user.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
