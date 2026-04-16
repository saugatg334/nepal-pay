<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$pdo = $db->connect();

echo "Users:\n";
$stmt = $pdo->query("DESCRIBE users");
while ($row = $stmt->fetch()) {
    echo "Phone: {$row['phone']}, attempts: {$row['failed_attempts']}, locked: {$row['locked_until']}, created: {$row['created_at']}\n";
}

$phone = '9746587923';
$stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE phone = ?");
$stmt->execute([$phone]);
echo "\nReset $phone lock.\n";

?>

