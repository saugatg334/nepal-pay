<?php
require_once __DIR__ . '/../config/database.php';

class Provider {
    private $conn;
    private $table = 'providers';

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function getAllBillers() {
        try {
            $sql = "SELECT b.id, b.name, b.external_code, p.id as provider_id, p.code as provider_code, p.name as provider_name, p.mock_endpoint FROM billers b JOIN providers p ON b.provider_id = p.id ORDER BY b.name";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch billers: ' . $e->getMessage());
        }
    }

    public function getBillerById($id) {
        try {
            $sql = "SELECT b.id, b.name, b.external_code, p.id as provider_id, p.code as provider_code, p.name as provider_name, p.mock_endpoint FROM billers b JOIN providers p ON b.provider_id = p.id WHERE b.id = :id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch biller: ' . $e->getMessage());
        }
    }
}
?>