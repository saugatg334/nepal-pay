<?php
/**
 * Transaction Service
 * Handles transaction status, idempotency, and audit logging
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class TransactionService {
    private $conn;
    private $userModel;
    private $idempotency;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REVERSED = 'reversed';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->userModel = new User();
    }

    /**
     * Create pending transaction
     */
    public function create($userId, $type, $amount, $description = '', $metadata = []) {
        $this->conn->beginTransaction();
        
        try {
            $idempotencyKey = $this->generateIdempotencyKey($userId, $type);
            $txnRef = $this->generateTxRef($type);

            $query = "INSERT INTO transactions 
                      (sender_id, amount, type, status, description, idempotency_key, txn_id, created_at) 
                      VALUES (:sender_id, :amount, :type, :status, :description, :idempotency_key, :txn_id, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':sender_id', $userId);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':type', $type);
            $status = self::STATUS_PENDING;
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':idempotency_key', $idempotencyKey);
            $stmt->bindParam(':txn_id', $txnRef);

            $stmt->execute();
            $transactionId = $this->conn->lastInsertId();

            $this->conn->commit();

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'txn_id' => $txnRef,
                'idempotency_key' => $idempotencyKey,
                'status' => self::STATUS_PENDING
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'error' => 'Failed to create transaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update transaction status
     */
    public function updateStatus($transactionId, $status, $reason = '', $providerTxnId = null) {
        try {
            $query = "UPDATE transactions 
                    SET status = :status, status_reason = :reason, provider_txn_id = :provider_txn_id 
                    WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindValue(':provider_txn_id', $providerTxnId, PDO::PARAM_STR);
            $stmt->bindParam(':id', $transactionId);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("UpdateStatus error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process transaction with status handling
     */
    public function process($transactionId, $callback) {
        $this->conn->beginTransaction();

        try {
            $this->updateStatus($transactionId, self::STATUS_PROCESSING);

            $result = call_user_func($callback);

            if ($result['success']) {
                $this->updateStatus($transactionId, self::STATUS_COMPLETED, '', $result['provider_txn_id'] ?? null);
                $this->conn->commit();
                return [
                    'success' => true,
                    'status' => self::STATUS_COMPLETED,
                    'data' => $result
                ];
            } else {
                $this->updateStatus($transactionId, self::STATUS_FAILED, $result['error'] ?? 'Processing failed');
                $this->conn->commit();
                return [
                    'success' => false,
                    'status' => self::STATUS_FAILED,
                    'error' => $result['error']
                ];
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->updateStatus($transactionId, self::STATUS_FAILED, $e->getMessage());
            
            return [
                'success' => false,
                'status' => self::STATUS_FAILED,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reverse a transaction (refund)
     */
    public function reverse($transactionId, $reason = '') {
        $this->conn->beginTransaction();

        try {
            $query = "SELECT * FROM transactions WHERE id = :id FOR UPDATE";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $transactionId);
            $stmt->execute();
            
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception('Transaction not found');
            }

            if ($transaction['status'] !== self::STATUS_COMPLETED) {
                throw new Exception('Can only reverse completed transactions');
            }

            $this->updateStatus($transactionId, self::STATUS_REVERSED, $reason);

            $amount = $transaction['amount'];
            $senderId = $transaction['sender_id'];

            if ($senderId) {
                $this->userModel->updateWalletBalance($senderId, $amount);
            }

            $this->conn->commit();

            return ['success' => true, 'reversed' => true];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get transaction by ID
     */
    public function getById($id) {
        try {
            $query = "SELECT t.*, 
                      u_sender.full_name as sender_name, sender.phone as sender_phone,
                      u_receiver.full_name as receiver_name, receiver.phone as receiver_phone
                      FROM transactions t
                      LEFT JOIN users u_sender ON t.sender_id = u_sender.id
                      LEFT JOIN users u_receiver ON t.receiver_id = u_receiver.id
                      WHERE t.id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get transaction by idempotency key
     */
    public function getByIdempotencyKey($key) {
        try {
            $query = "SELECT * FROM transactions WHERE idempotency_key = :key";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key', $key);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate transaction reference
     */
    private function generateTxRef($type) {
        $prefix = strtoupper(substr($type, 0, 3));
        return $prefix . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * Generate idempotency key
     */
    private function generateIdempotencyKey($userId, $type) {
        return "IDM_" . $type . "_" . $userId . "_" . microtime(true) . "_" . bin2hex(random_bytes(4));
    }
}

/**
 * Helper to process transaction safely
 */
function processTransactionSafely($transactionId, $callback) {
    $service = new TransactionService();
    return $service->process($transactionId, $callback);
}

/**
 * Helper to reverse transaction
 */
function reverseTransaction($transactionId, $reason = '') {
    $service = new TransactionService();
    return $service->reverse($transactionId, $reason);
}