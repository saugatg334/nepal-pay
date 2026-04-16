<?php
require_once '../app/config/database.php';

$db = new Database();
$pdo = $db->connect();

$phone = '9746587923';

$stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE phone = ?");
$stmt->execute([$phone]);

echo "Unlocked $phone\n";
if ($stmt->rowCount() == 0) echo "No such user.\n";
?>

