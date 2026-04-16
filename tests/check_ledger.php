<?php
require_once __DIR__ . '/../app/config/database.php';
$db = new Database();
$conn = $db->connect();
$stmt = $conn->query("DESCRIBE ledger_entries");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));