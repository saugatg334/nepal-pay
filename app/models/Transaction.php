<?php
require_once __DIR__ . '/../config/database.php';

class Transaction {
    private $conn;
    private $table_name = "transactions";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Create new transaction
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (sender_id, receiver_id, amount, fee, type, status, reference_id, note, 
                   sender_balance_before, sender_balance_after, receiver_balance_before, receiver_balance_after, ip_address) 
                  VALUES (:sender_id, :receiver_id, :amount, :fee, :type, :status, :reference_id, :note,
                          :sender_balance_before, :sender_balance_after, :receiver_balance_before, :receiver_balance_after, :ip_address)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':sender_id', $data['sender_id']);
        $stmt->bindParam(':receiver_id', $data['receiver_id']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':fee', $data['fee']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':reference_id', $data['reference_id']);
        $stmt->bindParam(':note', $data['note']);
        $stmt->bindParam(':sender_balance_before', $data['sender_balance_before']);
        $stmt->bindParam(':sender_balance_after', $data['sender_balance_after']);
        $stmt->bindParam(':receiver_balance_before', $data['receiver_balance_before']);
        $stmt->bindParam(':receiver_balance_after', $data['receiver_balance_after']);
        $stmt->bindParam(':ip_address', $data['ip_address']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Get transaction by ID
    public function getById($id) {
        $query = "SELECT t.*, 
                  sender.name as sender_name, sender.phone as sender_phone,
                  receiver.name as receiver_name, receiver.phone as receiver_phone
                  FROM " . $this->table_name . " t
                  LEFT JOIN users sender ON t.sender_id = sender.id
                  LEFT JOIN users receiver ON t.receiver_id = receiver.id
                  WHERE t.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get user transaction history
    public function getUserTransactions($user_id, $limit = 20, $offset = 0, $type = null, $status = null) {
        $query = "SELECT t.*, 
                  sender.name as sender_name, sender.phone as sender_phone,
                  receiver.name as receiver_name, receiver.phone as receiver_phone
                  FROM " . $this->table_name . " t
                  LEFT JOIN users sender ON t.sender_id = sender.id
                  LEFT JOIN users receiver ON t.receiver_id = receiver.id
                  WHERE t.sender_id = :user_id OR t.receiver_id = :user_id";
        
        $params = ['user_id' => $user_id];
        
        if ($type) {
            $query .= " AND t.type = :type";
            $params['type'] = $type;
        }
        
        if ($status) {
            $query .= " AND t.status = :status";
            $params['status'] = $status;
        }
        
        $query .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $params['user_id']);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        if ($type) $stmt->bindParam(':type', $params['type']);
        if ($status) $stmt->bindParam(':status', $params['status']);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all transactions (admin)
    public function getAll($limit = 50, $offset = 0, $filters = []) {
        $query = "SELECT t.*, 
                  sender.name as sender_name, sender.phone as sender_phone,
                  receiver.name as receiver_name, receiver.phone as receiver_phone
                  FROM " . $this->table_name . " t
                  LEFT JOIN users sender ON t.sender_id = sender.id
                  LEFT JOIN users receiver ON t.receiver_id = receiver.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $query .= " AND (t.sender_id = :user_id OR t.receiver_id = :user_id)";
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['type'])) {
            $query .= " AND t.type = :type";
            $params['type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND t.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND t.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update transaction status
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Get transaction statistics
    public function getStats($user_id = null) {
        $query = "SELECT 
                  COUNT(*) as total_count,
                  SUM(CASE WHEN type = 'send' AND sender_id = :user_id1 THEN amount ELSE 0 END) as total_sent,
                  SUM(CASE WHEN type = 'receive' AND receiver_id = :user_id2 THEN amount ELSE 0 END) as total_received,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                  SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
                  FROM " . $this->table_name;
        
        $params = ['user_id1' => $user_id, 'user_id2' => $user_id];
        
        if ($user_id) {
            $query .= " WHERE sender_id = :user_id3 OR receiver_id = :user_id4";
            $params['user_id3'] = $user_id;
            $params['user_id4'] = $user_id;
        }
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Generate unique reference ID
    public function generateReferenceId() {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }

    // Get user's recent transactions
    public function getRecentTransactions($user_id, $limit = 5) {
        $query = "SELECT t.*, 
                  sender.name as sender_name,
                  receiver.name as receiver_name
                  FROM " . $this->table_name . " t
                  LEFT JOIN users sender ON t.sender_id = sender.id
                  LEFT JOIN users receiver ON t.receiver_id = receiver.id
                  WHERE (t.sender_id = :user_id OR t.receiver_id = :user_id)
                  AND t.status = 'completed'
                  ORDER BY t.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user's last transaction
    public function getLastTransaction($user_id) {
        $query = "SELECT t.*, 
                  sender.name as sender_name,
                  receiver.name as receiver_name
                  FROM " . $this->table_name . " t
                  LEFT JOIN users sender ON t.sender_id = sender.id
                  LEFT JOIN users receiver ON t.receiver_id = receiver.id
                  WHERE (t.sender_id = :user_id OR t.receiver_id = :user_id)
                  ORDER BY t.created_at DESC
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
