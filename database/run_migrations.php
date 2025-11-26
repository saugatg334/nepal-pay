<?php
/*
 Simple migration runner for local development.
 Run: php run_migrations.php
 It will execute all .sql files in this directory or in migrations/ in alphabetical order.
*/
require_once __DIR__ . '/../app/config/database.php';

$base = __DIR__;
$migDirs = [$base, $base . '/migrations'];
$sqlFiles = [];
foreach ($migDirs as $d) {
    if (!is_dir($d)) continue;
    foreach (scandir($d) as $f) {
        if (preg_match('/\.sql$/i', $f)) {
            $sqlFiles[] = $d . DIRECTORY_SEPARATOR . $f;
        }
    }
}
if (empty($sqlFiles)) {
    echo "No .sql files found in \n" . implode("\n", $migDirs) . "\n";
    exit(0);
}
sort($sqlFiles);

try {
    $db = new Database();
    $pdo = $db->connect();
    foreach ($sqlFiles as $file) {
        echo "Applying: $file\n";
        $sql = file_get_contents($file);
        // Split on ; followed by newline to allow multiple statements
        $parts = preg_split('/;\s*\n/', $sql);
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            try {
                $pdo->exec($part);
            } catch (PDOException $e) {
                echo "Error running statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($part,0,200) . "...\n";
            }
        }
        echo "Done: $file\n\n";
    }
    echo "Migrations finished. Verify your DB schema.\n";
} catch (Exception $e) {
    echo "Migration runner failed: " . $e->getMessage() . "\n";
    exit(1);
}

?>