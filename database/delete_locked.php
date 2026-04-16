<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$pdo = $db->connect();

$phone = '9746587923';

$stmt = $pdo->prepare("DELETE FROM users WHERE phone = ?");
$stmt->execute([$phone]);

echo "Deleted user $phone. Row affected: " . $stmt->rowCount() . "\n";

echo "Now register new account at /register.php\n";

?>

