<?php
require_once __DIR__ . '/../config/database.php';

class Deposit {
    private $conn;
    private $table_name = 'deposits';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create($user_id, $amount, $method, $reference) {
        try {
            $this->conn->beginTransaction();
            
            $query = "INSERT INTO {$this->table_name} (user_id, amount, method, reference, status) 
                      VALUES (:user_id, :amount, :method, :reference, 'pending')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':method', $method);
            $stmt->bindParam(':reference', $reference);
            
            if ($stmt->execute()) {
                $deposit_id = $this->conn->lastInsertId();
                $this->conn->commit();
                return $deposit_id;
            }
            
            $this->conn->rollBack();
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getPending($limit = 10) {
        $query = "SELECT d.*, u.name, u.phone FROM {$this->table_name} d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE status = 'pending' ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approve($deposit_id, $admin_id) {
        try {
            $this->conn->beginTransaction();
            
            // Get deposit
            $query = "SELECT * FROM {$this->table_name} WHERE id = :id AND status = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $deposit_id);
            $stmt->execute();
            $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$deposit) {
                throw new Exception('Deposit not found or already processed');
            }
            
            $userModel = new User();
            
            // Update user balance
            $userModel->updateWalletBalance($deposit['user_id'], $deposit['amount']);
            
            // Log transaction
            $userModel->recordTransaction(null, $deposit['user_id'], $deposit['amount'], 'deposit', 'Approved deposit #' . $deposit_id);
            
            // Update deposit status
            $query = "UPDATE {$this->table_name} SET status = 'approved', admin_id = :admin_id, processed_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->bindParam(':id', $deposit_id);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function reject($deposit_id, $admin_id, $reason = null) {
        $query = "UPDATE {$this->table_name} SET status = 'rejected', admin_id = :admin_id, processed_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':id', $deposit_id);
        if ($reason) $stmt->bindParam(':reason', $reason);
        return $stmt->execute();
    }
}
?>

