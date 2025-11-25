<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $database = new Database();
    $conn = $database->connect();

    $conn->exec("ALTER TABLE users ADD COLUMN account_locked_until DATETIME NULL;");
    $conn->exec("ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0;");
    $conn->exec("ALTER TABLE users ADD COLUMN pin VARCHAR(255) NULL;");
    $conn->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;");
    $conn->exec("ALTER TABLE users ADD COLUMN device_token VARCHAR(255) NULL;");
    $conn->exec("ALTER TABLE users ADD COLUMN kyc_documents TEXT NULL;");
    $conn->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL;");
    $conn->exec("ALTER TABLE users ADD COLUMN address TEXT NULL;");
    $conn->exec("ALTER TABLE users ADD COLUMN date_of_birth DATE NULL;");
    $conn->exec("ALTER TABLE users ADD COLUMN gender ENUM('male','female','other') NULL;");

    echo "Database updated successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
