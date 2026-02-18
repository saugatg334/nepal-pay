<?php
require_once __DIR__ . '/../config/database.php';

class ActivityLog {
    private $conn;
    private $table_name = "activity_logs";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Log an activity
    public function log($user_id, $action, $description, $ip_address = null, $device_info = null) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, action, description, ip_address, device_info) 
                  VALUES (:user_id, :action, :description, :ip_address, :device_info)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':device_info', $device_info);
        
        return $stmt->execute();
    }

    // Get user activity logs
    public function getUserLogs($user_id, $limit = 50) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all logs (admin)
    public function getAll($limit = 100, $offset = 0, $filters = []) {
        $query = "SELECT l.*, u.name as user_name, u.phone as user_phone
                  FROM " . $this->table_name . " l
                  LEFT JOIN users u ON l.user_id = u.id
                  WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $query .= " AND l.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['action'])) {
            $query .= " AND l.action = :action";
            $params['action'] = $filters['action'];
        }
        
        if (!empty($filters['date_from'])) {
            $query .= " AND l.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $query .= " AND l.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $query .= " ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get logs by action type
    public function getByAction($action, $limit = 50) {
        $query = "SELECT l.*, u.name as user_name
                  FROM " . $this->table_name . " l
                  LEFT JOIN users u ON l.user_id = u.id
                  WHERE l.action = :action
                  ORDER BY l.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':action', $action);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Common action constants
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_SEND_MONEY = 'send_money';
    const ACTION_RECEIVE_MONEY = 'receive_money';
    const ACTION_DEPOSIT = 'deposit';
    const ACTION_WITHDRAWAL = 'withdrawal';
    const ACTION_ADMIN_ADJUST = 'admin_adjust';
    const ACTION_KYC_SUBMIT = 'kyc_submit';
    const ACTION_KYC_APPROVE = 'kyc_approve';
    const ACTION_KYC_REJECT = 'kyc_reject';
    const ACTION_PIN_CHANGE = 'pin_change';
    const ACTION_PROFILE_UPDATE = 'profile_update';
    const ACTION_WALLET_FREEZE = 'wallet_freeze';
    const ACTION_WALLET_UNFREEZE = 'wallet_unfreeze';
}
