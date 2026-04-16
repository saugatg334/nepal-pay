<?php
/**
 * Seed auth test users
 */
require_once __DIR__ . '/../app/config/database.php';

$database = new Database();
$conn = $database->connect();

// Test user (existing phone updated)
$test_user_pass = password_hash('123456', PASSWORD_DEFAULT);
$conn->exec("UPDATE users SET full_name = 'Test User', email = 'test@nepalpay.com', password_hash = '$test_user_pass', is_admin = 0 WHERE phone = '9746587923'");

// Admin user
$admin_pass = password_hash('admin', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (full_name, phone, email, password_hash, is_admin) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE is_admin = 1");
$stmt->execute(['Admin User', '9800000001', 'admin@nepalpay.com', $admin_pass]);

// Another test user
$stmt = $conn->prepare("INSERT INTO users (full_name, phone, email, password_hash) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)");
$stmt->execute(['Demo User', '9800000002', 'demo@nepalpay.com', password_hash('demo123', PASSWORD_DEFAULT)]);

echo "✅ Auth users seeded!\n";
echo "- User: 9746587923 / 123456\n";
echo "- Admin: admin@nepalpay.com / admin\n";
echo "- Demo: 9800000002 / demo123\n";
?>

