<?php
echo "Creating 'saugat' database and tables...\n";

$pdo = new PDO("mysql:host=localhost", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE DATABASE IF NOT EXISTS saugat");
$pdo->exec("USE saugat");

$sql = file_get_contents('saugat_setup.sql');
$pdo->exec($sql);

echo "✅ Database 'saugat' ready with test users.\n";
echo "Test login: phone 9841234567 / 12345678\n";
?>

