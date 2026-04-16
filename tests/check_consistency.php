<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "=== DATABASE CONSISTENCY CHECK ===\n\n";

$txnCount = $conn->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
echo "Transactions count: $txnCount\n";

$ledgerCount = $conn->query("SELECT COUNT(*) FROM ledger_entries")->fetchColumn();
echo "Ledger entries count: $ledgerCount\n";

echo "\n=== Ledger Status Values ===\n";
$stmt = $conn->query("SELECT status, COUNT(*) as cnt FROM ledger_entries GROUP BY status");
echo $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== Transaction Status Values ===\n";
$stmt = $conn->query("SELECT status, COUNT(*) as cnt FROM transactions GROUP BY status");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
