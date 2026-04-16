<?php
/**
 * Idempotency Service
 * Prevents duplicate transactions using unique idempotency keys
 */
require_once __DIR__ . '/../config/database.php';

class IdempotencyService {
    private $conn;
    private $table_name = "idempotency_keys";

    const DEFAULT_TTL = 86400;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Generate idempotency key
     */
    public function generateKey($userId, $action, $amount = null) {
        $timestamp = microtime(true);
        $random = bin2hex(random_bytes(8));
        return "IDM_" . $action . "_" . $userId . "_" . $timestamp . "_" . $random;
    }

    /**
     * Check if key exists (prevent duplicate)
     */
    public function check($idempotencyKey) {
        try {
            $query = "SELECT * FROM " . $this->table_name . " 
                      WHERE key_value = :key AND expires_at > NOW()";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $idempotencyKey);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Reserve idempotency key (check before processing)
     */
    public function reserve($idempotencyKey, $userId, $action, $amount = null, $description = '') {
        $existing = $this->check($idempotencyKey);
        
        if ($existing) {
            if ($existing['status'] === 'completed') {
                return [
                    'success' => false,
                    'duplicate' => true,
                    'existing_result' => json_decode($existing['response'], true)
                ];
            }
            return [
                'success' => false,
                'duplicate' => false,
                'in_progress' => true
            ];
        }

        try {
            $expiresAt = date('Y-m-d H:i:s', time() + self::DEFAULT_TTL);
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (key_value, user_id, action, amount, description, status, response, expires_at) 
                      VALUES (:key, :user_id, :action, :amount, :description, 'pending', '', :expires_at)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $idempotencyKey);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':action', $action);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':expires_at', $expiresAt);
            
            $stmt->execute();
            
            return ['success' => true, 'duplicate' => false];
        } catch (PDOException $e) {
            error_log("Idempotency reserve error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Complete idempotency key (after successful processing)
     */
    public function complete($idempotencyKey, $response) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET status = 'completed', response = :response, completed_at = NOW() 
                      WHERE key_value = :key";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':response', json_encode($response), PDO::PARAM_STR);
            $stmt->bindParam(':key', $idempotencyKey);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Mark as failed
     */
    public function fail($idempotencyKey, $error) {
        try {
            $query = "UPDATE " . $this->table_name . " 
                      SET status = 'failed', response = :response, completed_at = NOW() 
                      WHERE key_value = :key";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':response', json_encode(['error' => $error]), PDO::PARAM_STR);
            $stmt->bindParam(':key', $idempotencyKey);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get current transaction reference
     */
    public function getTransactionRef($userId, $action) {
        return $this->generateKey($userId, $action);
    }

    /**
     * Clean up expired keys
     */
    public function cleanup() {
        try {
            $stmt = $this->conn->query("DELETE FROM " . $this->table_name . " WHERE expires_at < NOW()");
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}

/**
 * Generate transaction reference helper
 */
function generateTxnRef($userId, $action = 'txn', $amount = null) {
    $service = new IdempotencyService();
    return $service->generateKey($userId, $action, $amount);
}

/**
 * Check for duplicate transaction
 */
function checkDuplicate($idempotencyKey) {
    $service = new IdempotencyService();
    return $service->check($idempotencyKey);
}