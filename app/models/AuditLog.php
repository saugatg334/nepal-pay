<?php
/**
 * AuditLog Model - Security Audit Trail
 * Logs every sensitive action for compliance and security
 */
require_once __DIR__ . '/../config/database.php';

class AuditLog {
    private $conn;
    private $table_name = "audit_logs";

    const ACTION_LOGIN = 'login';
    const ACTION_LOGIN_FAILED = 'login_failed';
    const ACTION_LOGOUT = 'logout';
    const ACTION_PAYMENT = 'payment';
    const ACTION_WALLET_DEPOSIT = 'wallet_deposit';
    const ACTION_WALLET_WITHDRAW = 'wallet_withdraw';
    const ACTION_WALLET_TRANSFER = 'wallet_transfer';
    const ACTION_BILL_PAYMENT = 'bill_payment';
    const ACTION_TOPUP = 'topup';
    const ACTION_PROFILE_UPDATE = 'profile_update';
    const ACTION_PASSWORD_CHANGE = 'password_change';
    const ACTION_KYC_SUBMIT = 'kyc_submit';
    const ACTION_ADMIN_ACTION = 'admin_action';
    const ACTION_FRAUD_ALERT = 'fraud_alert';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Log an action
     */
    public function log($action, $userId = null, $description = '', $metadata = [], $entityType = null, $entityId = null) {
        try {
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, action, entity_type, entity_id, description, metadata, ip_address, user_agent) 
                      VALUES (:user_id, :action, :entity_type, :entity_id, :description, :metadata, :ip_address, :user_agent)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_NULL);
            $stmt->bindParam(':action', $action);
            $stmt->bindValue(':entity_type', $entityType, PDO::PARAM_NULL);
            $stmt->bindValue(':entity_id', $entityId, PDO::PARAM_NULL);
            $stmt->bindParam(':description', $description);
            $stmt->bindValue(':metadata', json_encode($metadata), PDO::PARAM_STR);
            $stmt->bindParam(':ip_address', $ipAddress);
            $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("AuditLog error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user audit trail
     */
    public function getUserAuditTrail($userId, $limit = 100) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE user_id = :user_id 
                      ORDER BY created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get audit logs by action
     */
    public function getByAction($action, $limit = 100) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE action = :action 
                      ORDER BY created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get recent logs (admin)
     */
    public function getRecent($limit = 100) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      ORDER BY created_at DESC 
                      LIMIT :limit";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Search audit logs
     */
    public function search($filters = [], $limit = 100, $offset = 0) {
        $conditions = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $conditions[] = "user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $conditions[] = "action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "created_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['ip_address'])) {
            $conditions[] = "ip_address LIKE :ip_address";
            $params['ip_address'] = '%' . $filters['ip_address'] . '%';
        }

        $where = empty($conditions) ? "1=1" : implode(" AND ", $conditions);

        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE {$where}
                      ORDER BY created_at DESC 
                      LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Helper function to log audit events
 */
function auditLog($action, $userId = null, $description = '', $metadata = [], $entityType = null, $entityId = null) {
    $audit = new AuditLog();
    return $audit->log($action, $userId, $description, $metadata, $entityType, $entityId);
}