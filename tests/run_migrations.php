<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "Running migrations...\n\n";

$migrations = [
    'database/migrations/008_production_enhancement.sql',
    'database/migrations/009_queue_rate_limit.sql',
    'database/add_idempotency.sql',
    'database/add_rate_limits.sql'
];

foreach ($migrations as $file) {
    echo "Running: $file\n";
    $sql = file_get_contents($file);
    
    // Skip comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $conn->exec($stmt);
        } catch (PDOException $e) {
            // Skip if already exists
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "  Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "  Done\n";
}

echo "\nMigration complete.\n";
