<?php
require_once 'app/config/database.php';
$database = new Database();
$conn = $database->connect();

if (!$conn) {
    die('Connection failed');
}

echo "<h2>DB Status</h2>";
echo "Connected to: " . $database->db_name . "<br>";

// Check saugat exists
$stmt = $conn->query("SHOW DATABASES LIKE 'saugat'");
if ($stmt->rowCount() == 0) {
    echo "<p style='color:red;'>❌ saugat DB missing. Run saugat_complete.sql first.</p>";
} else {
    echo "<p style='color:green;'>✅ saugat DB exists</p>";
    
    // Check tables
    $conn->exec('USE saugat');
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red;'>❌ users table missing. Run saugat_complete.sql</p>";
    } else {
        echo "<p style='color:green;'>✅ users table exists</p>";
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "Users count: $count";
    }
}

echo "<p><a href='public/index.php'>Test App</a></p>";
?>

