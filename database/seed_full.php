<?php
/**
 * Full seed for NepalPay - Users, Admins, Test Transactions
 */
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Auth.php';

$db = new Database();
$pdo = $db->connect();
$userModel = new User();
$auth = new Auth();

echo "Seeding NepalPay...\n";

// Clear existing data (dev only)
$pdo->exec("TRUNCATE TABLE transactions");
$pdo->exec("TRUNCATE TABLE kyc_requests");
$pdo->exec("TRUNCATE TABLE activity_logs");
$pdo->exec("UPDATE users SET wallet_balance = 0");

try {
    // Admin user
    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO users (full_name, phone, email, password_hash, is_admin, wallet_balance, kyc_status) VALUES 
        ('Admin User', '9800000001', 'admin@nepalpay.com', '$admin_hash', 1, 10000.00, 'approved') ON DUPLICATE KEY UPDATE password_hash = '$admin_hash'");
    $admin_id = $pdo->lastInsertId();
    echo "Admin seeded\n";

    // Test users
    $test_users = [
        ['Ram Bahadur', '9841234567', 'ram@test.com', 5000.00],
        ['Sita Devi', '9841234568', 'sita@test.com', 2500.00],
        ['Hari Maya', '9841234569', 'hari@test.com', 7500.00],
    ];

    foreach ($test_users as $u) {
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (full_name, phone, email, password_hash, wallet_balance, kyc_status) VALUES 
            ('{$u[0]}', '{$u[1]}', '{$u[2]}', '$hash', {$u[3]}, 'approved') ON DUPLICATE KEY UPDATE password_hash = '$hash'");
        echo "User {$u[0]} seeded\n";
    }

    // Test transactions
    $user_ids = $pdo->query("SELECT id FROM users WHERE is_admin = 0 LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($user_ids as $i => $from_id) {
        $to_id = $user_ids[($i+1) % 3];
        $pdo->exec("INSERT INTO transactions (sender_id, receiver_id, amount, type, status) VALUES ($from_id, $to_id, 100.00, 'transfer', 'completed')");
    }
    echo "Test transactions seeded\n";

    // System settings
    $pdo->exec("INSERT INTO system_settings (setting_key, setting_value) VALUES 
        ('registration_enabled', 'true'),
        ('txn_min_amount', '10'),
        ('txn_max_amount', '50000'),
        ('kyc_required', 'true'),
        ('maintenance_mode', 'false') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    echo "✅ Full seed complete! Test with:\n";
    echo "- Admin: 9800000001 / admin123\n";
    echo "- Users: 9841234567 / password123\n";
    echo "Visit: http://localhost/nepal-pay/public/?path=user/login\n";
} catch (Exception $e) {
    echo "Seed failed: " . $e->getMessage() . "\n";
}
?>

