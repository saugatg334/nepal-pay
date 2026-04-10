<?php
require_once __DIR__ . '/../config/database.php';

class AuditLog {
    private $conn;
    private $table_name = "audit_logs";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function log($user_id, $action, $description, $ip = null, $user_agent = null) {
        $ip = $ip ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $user_agent ?: $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $query = "INSERT INTO " . $this->table_name . " (user_id, action, description, ip_address, user_agent, created_at) VALUES (:user_id, :action, :description, :ip, :user_agent, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':user_agent', $user_agent);
        return $stmt->execute();
    }

    public function getRecent($limit = 50) {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

