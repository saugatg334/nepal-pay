<?php
require_once __DIR__ . '/../app/config/database.php';
$db = new Database();
$conn = $db->connect();

echo "=== DATABASE STRUCTURE ===\n\n";

try {
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n\n";
    
    foreach ($tables as $table) {
        echo "--- $table ---\n";
        $stmt = $conn->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo $col['Field'] . " (" . $col['Type'] . ")\n";
        }
        echo "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
