<?php
/**
 * Secure Password Updater for NepalPay
 * Run: http://localhost/wallet/update_passwords.php
 */

// Security
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') die('Local only');

require_once 'app/config/database.php';
$database = new Database();
$conn = $database->connect();

echo "<h2>Password Update Tool</h2>";

// User: phone=9746587923, pass=2580
$phone_user = '9746587923';
$hash_user = password_hash('2580', PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE phone = ?");
if ($stmt->execute([$hash_user, $phone_user])) {
    echo "<p style='color:green;'>✅ User 9746587923 → password '2580'</p>";
} else {
    echo "<p style='color:red;'>❌ User update failed</p>";
}

// Admin: email=saugatg334@gmail.com, pass=6666
$email_admin = 'saugatg334@gmail.com';
$hash_admin = password_hash('6666', PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
if ($stmt->execute([$hash_admin, $email_admin])) {
    echo "<p style='color:green;'>✅ Admin saugatg334@gmail.com → password '6666'</p>";
} else {
    echo "<p style='color:red;'>❌ Admin update failed</p>";
}

// Verify
echo "<h3>Current Users:</h3>";
$stmt = $conn->query("SELECT phone, email FROM users");
while ($row = $stmt->fetch()) {
    echo "Phone: {$row['phone']} | Email: {$row['email']}<br>";
}

echo "<p><a href='public/index.php' style='background:#4CAF50;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test Login →</a></p>";
echo "<p><small>Delete this file after use.</small></p>";
?>

