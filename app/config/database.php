<?php
class Database {
    public $conn;

    public function connect() {
        // Load .env
        $dotenv = parse_ini_file(__DIR__ . '/../../.env', false, INI_SCANNER_RAW);
        
        $host = $dotenv['DB_HOST'] ?? 'localhost';
        $dbname = $dotenv['DB_NAME'] ?? 'nepalpay';
        $username = $dotenv['DB_USER'] ?? 'root';
        $password = $dotenv['DB_PASS'] ?? '';
        
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $host . ";dbname=" . $dbname, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            return $this->conn;
        } catch(PDOException $e) {
            error_log("DB Connection failed: " . $e->getMessage());
            throw $e;
        }
    }

}
?>

