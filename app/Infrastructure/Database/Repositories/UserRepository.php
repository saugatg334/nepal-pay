<?php
/**
 * UserRepository - Pure DB access for User data
 * No business logic - pure CRUD
 */
require_once __DIR__ . '/../../../config/database.php';

class UserRepository {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function findByPhone($phone) {
        $query = "SELECT * FROM {$this->table_name} WHERE phone = :phone";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }

    public function findById($id) {
        $query = "SELECT * FROM {$this->table_name} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM {$this->table_name} WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    }

    public function getWalletBalance($id) {
        $query = "SELECT wallet_balance FROM {$this->table_name} WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['wallet_balance'] : 0.00;
    }

    public function updateWalletBalance($id, $amount) {
        $query = "UPDATE {$this->table_name} SET wallet_balance = wallet_balance + :amount WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAllUsers($limit = 50, $offset = 0, $search = '') {
        $sql = "SELECT * FROM {$this->table_name}";
        $params = [];
        if (!empty($search)) {
            $sql .= " WHERE full_name LIKE ? OR phone LIKE ? OR email LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

