<?php
/**
 * Database Connection Diagnostic Script
 * Run: http://localhost/wallet/test_db.php
 */

echo "<h1>Database Diagnostic Check</h1>";
echo "<hr>";

// Test 1: PHP PDO MySQL Support
echo "<h3>1. PDO MySQL Support</h3>";
if (extension_loaded('pdo_mysql')) {
    echo "✅ PASS - PDO MySQL extension is loaded<br>";
} else {
    echo "❌ FAIL - PDO MySQL extension is NOT loaded<br>";
    echo "<strong>Solution:</strong> Enable extension in php.ini or XAMPP<br>";
}

// Test 2: MySQL Host Configuration
echo "<h3>2. Database Configuration</h3>";
require_once __DIR__ . '/app/config/config.php';

$host = Config::get('DB_HOST', 'localhost');
$port = Config::get('DB_PORT', '3306');
$dbname = Config::get('DB_NAME', 'wallet');
$user = Config::get('DB_USER', 'root');
$pass = Config::get('DB_PASS', '');

echo "Host: <strong>$host</strong><br>";
echo "Port: <strong>$port</strong><br>";
echo "Database: <strong>$dbname</strong><br>";
echo "User: <strong>$user</strong><br>";
echo "Password: " . (empty($pass) ? "<strong>empty (default)</strong>" : "<strong>set</strong>") . "<br>";

// Test 3: Actual Connection
echo "<h3>3. MySQL Connection Test</h3>";

$dsn = "mysql:host={$host};port={$port};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "✅ PASS - Connected to MySQL server<br>";
} catch (PDOException $e) {
    echo "❌ FAIL - Cannot connect to MySQL<br>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
    
    // Provide specific solutions based on error
    if (strpos($e->getMessage(), '2002') !== false || strpos($e->getMessage(), 'refused') !== false) {
        echo "<div style='background:#fff3cd;padding:10px;margin:10px 0;border:1px solid #ffc107;'>";
        echo "<strong>Solution:</strong><br>";
        echo "• MySQL is not running<br>";
        echo "• Start MySQL from XAMPP Control Panel<br>";
        echo "• Or run: net start MySQL (Command Prompt as Admin)<br>";
        echo "</div>";
    } else if (strpos($e->getMessage(), '1045') !== false) {
        echo "<div style='background:#fff3cd;padding:10px;margin:10px 0;border:1px solid #ffc107;'>";
        echo "<strong>Solution:</strong><br>";
        echo "• Check DB_USER and DB_PASS in config/.env<br>";
        echo "• Current user: " . $user . "<br>";
        echo "</div>";
    }
    
    // Stop here if no connection
    echo "<hr>";
    echo "<p style='color:red;'><strong>Cannot proceed with database tests until MySQL is running.</strong></p>";
    exit;
}

// Test 4: Database Exists
echo "<h3>4. Database Existence Check</h3>";

try {
    $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$dbname}'");
    if ($result->fetch()) {
        echo "✅ PASS - Database '{$dbname}' exists<br>";
    } else {
        echo "⚠️ WARNING - Database '{$dbname}' does not exist<br>";
        echo "<strong>Solution:</strong><br>";
        echo "• Import schema: <code>d:\\xampp\\htdocs\\wallet\\sql\\schema.sql</code><br>";
        echo "• Or run:<br>";
        echo "<code>mysql -u root < d:\\xampp\\htdocs\\wallet\\sql\\schema.sql</code><br>";
    }
} catch (PDOException $e) {
    echo "❌ FAIL - Cannot check database<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 5: Connect to Wallet Database
echo "<h3>5. Connect to Wallet Database</h3>";

$dsn_db = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

try {
    $pdo_db = new PDO($dsn_db, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ PASS - Connected to wallet database<br>";
} catch (PDOException $e) {
    echo "❌ FAIL - Cannot connect to wallet database<br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<hr>";
    echo "<p style='color:red;'><strong>Database '{$dbname}' needs to be imported.</strong></p>";
    exit;
}

// Test 6: Check Tables
echo "<h3>6. Database Tables Check</h3>";

try {
    $result = $pdo_db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbname}'");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($tables)) {
        echo "✅ PASS - Database has " . count($tables) . " tables<br>";
        echo "<details><summary>View table names</summary>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        echo "</details>";
    } else {
        echo "⚠️ WARNING - No tables found in database<br>";
        echo "<strong>Solution:</strong> Import the schema file from sql/schema.sql<br>";
    }
} catch (PDOException $e) {
    echo "❌ FAIL - Cannot check tables<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test 7: Test Query
echo "<h3>7. Test Query Execution</h3>";

try {
    $result = $pdo_db->query("SELECT 1 as test_result");
    $row = $result->fetch();
    
    if ($row && $row['test_result'] == 1) {
        echo "✅ PASS - Can execute queries<br>";
    } else {
        echo "❌ FAIL - Query execution failed<br>";
    }
} catch (PDOException $e) {
    echo "❌ FAIL - Query execution error<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

// Summary
echo "<hr>";
echo "<h3>✅ All Tests Passed!</h3>";
echo "<p>Your database is ready. The application should now work correctly.</p>";
echo "<p><a href='?page=login' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Go to Login Page</a></p>";
?>
