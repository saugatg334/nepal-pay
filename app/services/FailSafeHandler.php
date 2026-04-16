<?php
/**
 * Fail-Safe Transaction Handler
 * Handles failed transactions with retry logic and admin alerts
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/JobQueue.php';

class FailSafeHandler {
    private $conn;
    private $jobQueue;
    
    const MAX_RETRIES = 3;
    const RETRY_DELAYS = [30, 60, 120]; // Exponential backoff
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->jobQueue = new JobQueue();
    }
    
    /**
     * Register a failed transaction for retry
     */
    public function registerFailedTransaction($transactionId, $error, $retryType = 'auto') {
        try {
            // Check if already registered
            $stmt = $this->conn->prepare("
                SELECT id, retry_count FROM failed_transaction_retry 
                WHERE original_transaction_id = ? AND status IN ('pending', 'processing')
            ");
            $stmt->execute([$transactionId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                return ['success' => false, 'message' => 'Already registered for retry'];
            }
            
            // Get transaction details
            $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE id = ?");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found'];
            }
            
            // Calculate next retry time
            $nextRetry = date('Y-m-d H:i:s', time() + self::RETRY_DELAYS[0]);
            
            // Register for retry
            $stmt = $this->conn->prepare("
                INSERT INTO failed_transaction_retry 
                (original_transaction_id, retry_count, max_retries, last_error, status, next_retry_at, created_at)
                VALUES (?, 0, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([
                $transactionId,
                self::MAX_RETRIES,
                $error,
                $nextRetry
            ]);
            
            // Log critical failure
            $this->logCriticalFailure($transactionId, $transaction, $error);
            
            // Queue retry job
            $this->jobQueue->schedule(
                'retry_transaction',
                [
                    'transaction_id' => $transactionId,
                    'retry_type' => $retryType
                ],
                self::RETRY_DELAYS[0],
                ['priority' => JobQueue::PRIORITY_HIGH]
            );
            
            return [
                'success' => true,
                'message' => 'Transaction registered for retry',
                'next_retry' => $nextRetry
            ];
            
        } catch (PDOException $e) {
            error_log("Register failed transaction error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Retry a failed transaction
     */
    public function retryTransaction($transactionId) {
        try {
            // Get original transaction
            $stmt = $this->conn->prepare("
                SELECT * FROM failed_transaction_retry 
                WHERE original_transaction_id = ? AND status = 'pending'
            ");
            $stmt->execute([$transactionId]);
            $retryRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$retryRecord) {
                return ['success' => false, 'message' => 'No retry record found'];
            }
            
            if ($retryRecord['retry_count'] >= $retryRecord['max_retries']) {
                // Mark as permanently failed
                $this->markPermanentlyFailed($transactionId);
                return ['success' => false, 'message' => 'Max retries exceeded'];
            }
            
            // Update status to processing
            $stmt = $this->conn->prepare("
                UPDATE failed_transaction_retry SET status = 'processing' WHERE id = ?
            ");
            $stmt->execute([$retryRecord['id']]);
            
            // Get original transaction details
            $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE id = ?");
            $stmt->execute([$transactionId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Attempt to complete the transaction based on type
            $result = $this->processRetry($transaction);
            
            if ($result['success']) {
                // Mark as success
                $stmt = $this->conn->prepare("
                    UPDATE failed_transaction_retry 
                    SET status = 'success', resolved_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$retryRecord['id']]);
                
                // Update original transaction status
                $stmt = $this->conn->prepare("
                    UPDATE transactions SET status = 'completed' WHERE id = ?
                ");
                $stmt->execute([$transactionId]);
                
                return $result;
            } else {
                // Increment retry count
                $newRetryCount = $retryRecord['retry_count'] + 1;
                $delayIndex = min($newRetryCount - 1, count(self::RETRY_DELAYS) - 1);
                $nextRetry = date('Y-m-d H:i:s', time() + self::RETRY_DELAYS[$delayIndex]);
                
                $stmt = $this->conn->prepare("
                    UPDATE failed_transaction_retry 
                    SET status = 'pending',
                        retry_count = ?,
                        last_error = ?,
                        next_retry_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $newRetryCount,
                    $result['error'],
                    $nextRetry,
                    $retryRecord['id']
                ]);
                
                // Schedule next retry
                if ($newRetryCount < self::MAX_RETRIES) {
                    $this->jobQueue->schedule(
                        'retry_transaction',
                        ['transaction_id' => $transactionId, 'retry_type' => 'auto'],
                        self::RETRY_DELAYS[$delayIndex],
                        ['priority' => JobQueue::PRIORITY_HIGH]
                    );
                }
                
                return $result;
            }
            
        } catch (Exception $e) {
            error_log("Retry transaction error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process retry based on transaction type
     */
    private function processRetry($transaction) {
        $type = $transaction['type'];
        
        try {
            $this->conn->beginTransaction();
            
            switch ($type) {
                case 'transfer':
                    // Verify both accounts still exist
                    if (!$transaction['sender_id'] || !$transaction['receiver_id']) {
                        throw new Exception('Invalid transfer - missing accounts');
                    }
                    
                    // Check if already processed
                    $stmt = $this->conn->prepare("
                        SELECT status FROM transactions WHERE id = ?
                    ");
                    $stmt->execute([$transaction['id']]);
                    $currentStatus = $stmt->fetchColumn();
                    
                    if ($currentStatus === 'completed') {
                        $this->conn->rollBack();
                        return ['success' => true, 'message' => 'Already processed'];
                    }
                    
                    // Restore balances if needed
                    $stmt = $this->conn->prepare("
                        UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?
                    ");
                    $stmt->execute([$transaction['amount'], $transaction['sender_id']]);
                    
                    $stmt = $this->conn->prepare("
                        UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?
                    ");
                    $stmt->execute([$transaction['amount'], $transaction['receiver_id']]);
                    
                    // Update transaction status
                    $stmt = $this->conn->prepare("
                        UPDATE transactions SET status = 'completed' WHERE id = ?
                    ");
                    $stmt->execute([$transaction['id']]);
                    break;
                    
                case 'bill':
                case 'topup':
                case 'withdrawal':
                    // Similar logic for deductions
                    $stmt = $this->conn->prepare("
                        UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?
                    ");
                    $stmt->execute([$transaction['amount'], $transaction['sender_id']]);
                    
                    $stmt = $this->conn->prepare("
                        UPDATE transactions SET status = 'completed' WHERE id = ?
                    ");
                    $stmt->execute([$transaction['id']]);
                    break;
                    
                case 'deposit':
                    // For deposits, just mark as completed
                    $stmt = $this->conn->prepare("
                        UPDATE transactions SET status = 'completed' WHERE id = ?
                    ");
                    $stmt->execute([$transaction['id']]);
                    break;
                    
                default:
                    throw new Exception('Unknown transaction type: ' . $type);
            }
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Transaction retried successfully'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Mark transaction as permanently failed
     */
    private function markPermanentlyFailed($transactionId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE failed_transaction_retry 
                SET status = 'failed', resolved_at = NOW() 
                WHERE original_transaction_id = ?
            ");
            $stmt->execute([$transactionId]);
            
            // Update original transaction
            $stmt = $this->conn->prepare("
                UPDATE transactions SET status = 'failed' WHERE id = ?
            ");
            $stmt->execute([$transactionId]);
            
            // Alert admin
            $this->alertAdmin($transactionId);
            
        } catch (PDOException $e) {
            error_log("Mark permanently failed error: " . $e->getMessage());
        }
    }
    
    /**
     * Log critical failure
     */
    private function logCriticalFailure($transactionId, $transaction, $error) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_alerts 
                (alert_type, severity, message, data, created_at)
                VALUES ('transaction_failure', 'critical', ?, ?, NOW())
            ");
            
            $message = "Transaction {$transactionId} failed: {$error}";
            $data = json_encode([
                'transaction_id' => $transactionId,
                'type' => $transaction['type'] ?? 'unknown',
                'amount' => $transaction['amount'] ?? 0,
                'error' => $error
            ]);
            
            $stmt->execute([$message, $data]);
            
        } catch (PDOException $e) {
            error_log("Critical failure log error: " . $e->getMessage());
        }
    }
    
    /**
     * Alert admin about failed transaction
     */
    private function alertAdmin($transactionId) {
        try {
            // Queue admin notification
            $this->jobQueue->enqueue(
                'admin_alert',
                [
                    'alert_type' => 'transaction_failed',
                    'transaction_id' => $transactionId,
                    'message' => 'Transaction ' . $transactionId . ' has permanently failed after max retries'
                ],
                ['priority' => JobQueue::PRIORITY_HIGH]
            );
            
        } catch (Exception $e) {
            error_log("Admin alert error: " . $e->getMessage());
        }
    }
    
    /**
     * Get failed transactions for review
     */
    public function getFailedTransactions($limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT f.*, t.type, t.amount, t.sender_id, t.receiver_id, t.created_at as txn_created_at
                FROM failed_transaction_retry f
                LEFT JOIN transactions t ON f.original_transaction_id = t.id
                ORDER BY f.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Manually resolve a failed transaction
     */
    public function manualResolve($transactionId, $resolution, $notes) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE failed_transaction_retry 
                SET status = 'success', resolved_at = NOW() 
                WHERE original_transaction_id = ?
            ");
            $stmt->execute([$transactionId]);
            
            // Log manual resolution
            $stmt = $this->conn->prepare("
                INSERT INTO system_alerts 
                (alert_type, severity, message, data, created_at)
                VALUES ('manual_resolution', 'info', ?, ?, NOW())
            ");
            $stmt->execute([
                "Transaction {$transactionId} manually resolved: {$resolution}",
                json_encode(['resolution' => $resolution, 'notes' => $notes])
            ]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get pending retries
     */
    public function getPendingRetries() {
        try {
            $stmt = $this->conn->query("
                SELECT * FROM failed_transaction_retry 
                WHERE status = 'pending' AND next_retry_at <= NOW()
                ORDER BY next_retry_at ASC
                LIMIT 10
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

/**
 * Process pending retries - called by cron
 */
function processPendingRetries() {
    $handler = new FailSafeHandler();
    $pending = $handler->getPendingRetries();
    
    $processed = 0;
    $failed = 0;
    
    foreach ($pending as $retry) {
        $result = $handler->retryTransaction($retry['original_transaction_id']);
        
        if ($result['success']) {
            $processed++;
        } else {
            $failed++;
        }
    }
    
    return [
        'processed' => $processed,
        'failed' => $failed,
        'total' => count($pending)
    ];
}
