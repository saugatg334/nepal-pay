<?php
require_once __DIR__ . '/../config/database.php';

class Withdrawal {
    private $conn;
    private $table_name = 'withdrawals';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function create($user_id, $amount, $method, $account_number) {
        try {
            $userModel = new User();
            $balance = $userModel->getWalletBalance($user_id);
            
            if ($balance < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $this->conn->beginTransaction();
            
            $query = "INSERT INTO {$this->table_name} (user_id, amount, method, account_number, status) 
                      VALUES (:user_id, :amount, :method, :account_number, 'pending')";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':method', $method);
            $stmt->bindParam(':account_number', $account_number);
            
            if ($stmt->execute()) {
                $withdrawal_id = $this->conn->lastInsertId();
                $this->conn->commit();
                return $withdrawal_id;
            }
            
            $this->conn->rollBack();
            return false;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getPending($limit = 10) {
        $query = "SELECT w.*, u.name, u.phone FROM {$this->table_name} w 
                  JOIN users u ON w.user_id = u.id 
                  WHERE status = 'pending' ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approve($withdrawal_id, $admin_id) {
        try {
            $this->conn->beginTransaction();
            
            $query = "SELECT * FROM {$this->table_name} WHERE id = :id AND status = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $withdrawal_id);
            $stmt->execute();
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$withdrawal) {
                throw new Exception('Withdrawal not found or already processed');
            }
            
            $userModel = new User();
            
            $balance = $userModel->getWalletBalance($withdrawal['user_id']);
            if ($balance < $withdrawal['amount']) {
                throw new Exception('Insufficient balance for withdrawal');
            }
            
            // Deduct balance
            $userModel->updateWalletBalance($withdrawal['user_id'], -$withdrawal['amount']);
            
            // Log transaction
            $userModel->recordTransaction($withdrawal['user_id'], null, $withdrawal['amount'], 'withdrawal', 'Approved withdrawal #' . $withdrawal_id);
            
            // Update status
            $query = "UPDATE {$this->table_name} SET status = 'approved', admin_id = :admin_id, processed_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->bindParam(':id', $withdrawal_id);
            $stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function reject($withdrawal_id, $admin_id, $reason = null) {
        $query = "UPDATE {$this->table_name} SET status = 'rejected', admin_id = :admin_id, processed_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':admin_id', $admin_id);
        $stmt->bindParam(':id', $withdrawal_id);
        return $stmt->execute();
    }
}
?>

