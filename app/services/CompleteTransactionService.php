<?php
/**
 * Complete Transaction Processing Service
 * Production-level with fraud detection, notifications, and audit logging
 * Uses service layer pattern
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../services/FraudDetection.php';

class CompleteTransactionService {
    private $conn;
    private $notificationService;
    private $fraudDetection;
    
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_BILL = 'bill';
    const TYPE_TOPUP = 'topup';
    
    const MIN_AMOUNT = 10;
    const MAX_AMOUNT = 100000;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->notificationService = new NotificationService();
        $this->fraudDetection = new FraudDetection();
    }
    
    /**
     * Process a deposit/credit to wallet
     */
    public function processDeposit($user_id, $amount, $description = '', $metadata = []) {
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return ['success' => false, 'error' => 'Invalid amount'];
        }
        
        $idempotencyKey = $metadata['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = $this->checkIdempotency($idempotencyKey);
            if ($existing) {
                return [
                    'success' => true,
                    'duplicate' => true,
                    'txn_id' => $existing['txn_id'],
                    'message' => 'Duplicate transaction'
                ];
            }
        }
        
        $this->conn->beginTransaction();
        
        try {
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$user_id]);
            $balanceBefore = (float) $stmt->fetchColumn();
            
            $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            
            $txn_id = $this->generateTransactionId('DEP');
            $balanceAfter = $balanceBefore + $amount;
            $ipAddress = $this->getClientIP();
            
            $stmt = $this->conn->prepare("
                INSERT INTO transactions 
                (sender_id, amount, type, description, txn_id, status, idempotency_key, sender_balance_before, sender_balance_after, ip_address)
                VALUES (?, ?, 'deposit', ?, ?, 'completed', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $amount,
                $description ?: 'Deposit',
                $txn_id,
                $idempotencyKey,
                $balanceBefore,
                $balanceAfter,
                $ipAddress
            ]);
            
            $this->conn->commit();
            
            $this->notificationService->notifyDeposit($user_id, $amount, $txn_id);
            $this->audit('deposit', $user_id, $amount, ['txn_id' => $txn_id]);
            
            return [
                'success' => true,
                'txn_id' => $txn_id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Deposit error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Transaction failed'];
        }
    }
    
    /**
     * Process withdrawal/debit from wallet
     */
    public function processWithdrawal($user_id, $amount, $description = '', $metadata = []) {
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return ['success' => false, 'error' => 'Invalid amount'];
        }
        
        $fraudCheck = $this->fraudDetection->analyzeTransaction($user_id, $amount, 'withdrawal', $metadata);
        if ($fraudCheck['should_block']) {
            return ['success' => false, 'error' => 'Transaction blocked due to security concerns'];
        }
        
        $idempotencyKey = $metadata['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = $this->checkIdempotency($idempotencyKey);
            if ($existing) {
                return [
                    'success' => true,
                    'duplicate' => true,
                    'txn_id' => $existing['txn_id'],
                    'message' => 'Duplicate transaction'
                ];
            }
        }
        
        $this->conn->beginTransaction();
        
        try {
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$user_id]);
            $balanceBefore = (float) $stmt->fetchColumn();
            
            if ($balanceBefore < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            
            $txn_id = $this->generateTransactionId('WTH');
            $balanceAfter = $balanceBefore - $amount;
            $ipAddress = $this->getClientIP();
            
            $stmt = $this->conn->prepare("
                INSERT INTO transactions 
                (sender_id, amount, type, description, txn_id, status, idempotency_key, sender_balance_before, sender_balance_after, ip_address)
                VALUES (?, ?, 'withdrawal', ?, ?, 'completed', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $amount,
                $description ?: 'Withdrawal',
                $txn_id,
                $idempotencyKey,
                $balanceBefore,
                $balanceAfter,
                $ipAddress
            ]);
            
            $this->conn->commit();
            
            $this->notificationService->notifyWithdrawal($user_id, $amount, $txn_id);
            $this->audit('withdrawal', $user_id, $amount, ['txn_id' => $txn_id]);
            
            return [
                'success' => true,
                'txn_id' => $txn_id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Withdrawal error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process transfer between users
     */
    public function processTransfer($from_user_id, $to_user_id, $amount, $note = '', $metadata = []) {
        if ($from_user_id == $to_user_id) {
            return ['success' => false, 'error' => 'Cannot transfer to yourself'];
        }
        
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return ['success' => false, 'error' => 'Invalid amount'];
        }
        
        $metadata['receiver_id'] = $to_user_id;
        $fraudCheck = $this->fraudDetection->analyzeTransaction($from_user_id, $amount, 'transfer', $metadata);
        if ($fraudCheck['should_block']) {
            return ['success' => false, 'error' => 'Transaction blocked due to security concerns'];
        }
        
        $idempotencyKey = $metadata['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = $this->checkIdempotency($idempotencyKey);
            if ($existing) {
                return [
                    'success' => true,
                    'duplicate' => true,
                    'txn_id' => $existing['txn_id'],
                    'message' => 'Duplicate transaction'
                ];
            }
        }
        
        $this->conn->beginTransaction();
        
        try {
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$from_user_id]);
            $senderBalanceBefore = (float) $stmt->fetchColumn();
            
            if ($senderBalanceBefore < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$to_user_id]);
            $receiverBalanceBefore = (float) $stmt->fetchColumn();
            
            $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $from_user_id]);
            
            $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $to_user_id]);
            
            $senderBalanceAfter = $senderBalanceBefore - $amount;
            $receiverBalanceAfter = $receiverBalanceBefore + $amount;
            
            $txn_id = $this->generateTransactionId('TRF');
            $ipAddress = $this->getClientIP();
            
            $stmt = $this->conn->prepare("
                INSERT INTO transactions 
                (sender_id, receiver_id, amount, type, description, txn_id, status, idempotency_key, 
                 sender_balance_before, sender_balance_after, receiver_balance_before, receiver_balance_after, ip_address)
                VALUES (?, ?, ?, 'transfer', ?, ?, 'completed', ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $from_user_id,
                $to_user_id,
                $amount,
                $note ?: 'Transfer',
                $txn_id,
                $idempotencyKey,
                $senderBalanceBefore,
                $senderBalanceAfter,
                $receiverBalanceBefore,
                $receiverBalanceAfter,
                $ipAddress
            ]);
            
            $this->conn->commit();
            
            $senderName = $this->getUserName($from_user_id);
            $receiverName = $this->getUserName($to_user_id);
            
            $this->notificationService->notifyMoneySent($from_user_id, $amount, $receiverName, $txn_id);
            $this->notificationService->notifyMoneyReceived($to_user_id, $amount, $senderName, $txn_id);
            $this->audit('transfer', $from_user_id, $amount, ['to_user' => $to_user_id, 'txn_id' => $txn_id]);
            
            return [
                'success' => true,
                'txn_id' => $txn_id,
                'amount' => $amount,
                'sender_balance_after' => $senderBalanceAfter,
                'receiver_balance_after' => $receiverBalanceAfter
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Transfer error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process bill payment
     */
    public function processBillPayment($user_id, $billType, $customerId, $amount, $metadata = []) {
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return ['success' => false, 'error' => 'Invalid amount'];
        }
        
        $fraudCheck = $this->fraudDetection->analyzeTransaction($user_id, $amount, 'bill', $metadata);
        if ($fraudCheck['should_block']) {
            return ['success' => false, 'error' => 'Transaction blocked due to security concerns'];
        }
        
        $this->conn->beginTransaction();
        
        try {
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$user_id]);
            $balanceBefore = (float) $stmt->fetchColumn();
            
            if ($balanceBefore < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            
            $balanceAfter = $balanceBefore - $amount;
            $txn_id = $this->generateTransactionId('BLL');
            $description = ucfirst($billType) . ' payment for ' . $customerId;
            $ipAddress = $this->getClientIP();
            
            $stmt = $this->conn->prepare("
                INSERT INTO transactions 
                (sender_id, amount, type, description, txn_id, status, customer_ref, method, sender_balance_before, sender_balance_after, ip_address)
                VALUES (?, ?, 'bill', ?, ?, 'completed', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $amount,
                $description,
                $txn_id,
                $customerId,
                $billType,
                $balanceBefore,
                $balanceAfter,
                $ipAddress
            ]);
            
            $this->conn->commit();
            
            $this->notificationService->notifyBillPaid($user_id, $amount, $billType, $customerId, $txn_id);
            $this->audit('bill_payment', $user_id, $amount, ['bill_type' => $billType, 'txn_id' => $txn_id]);
            
            return [
                'success' => true,
                'txn_id' => $txn_id,
                'amount' => $amount,
                'balance_after' => $balanceAfter
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Bill payment error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process mobile topup
     */
    public function processTopup($user_id, $phoneNumber, $amount, $provider = null, $metadata = []) {
        if (!$this->validatePhoneNumber($phoneNumber)) {
            return ['success' => false, 'error' => 'Invalid phone number'];
        }
        
        if (!$provider) {
            $provider = $this->detectProvider($phoneNumber);
        }
        
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return ['success' => false, 'error' => 'Invalid amount'];
        }
        
        $fraudCheck = $this->fraudDetection->analyzeTransaction($user_id, $amount, 'topup', $metadata);
        if ($fraudCheck['should_block']) {
            return ['success' => false, 'error' => 'Transaction blocked due to security concerns'];
        }
        
        $this->conn->beginTransaction();
        
        try {
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$user_id]);
            $balanceBefore = (float) $stmt->fetchColumn();
            
            if ($balanceBefore < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            
            $balanceAfter = $balanceBefore - $amount;
            $txn_id = $this->generateTransactionId('TOP');
            $description = 'Topup to ' . $phoneNumber . ' (' . $provider . ')';
            $ipAddress = $this->getClientIP();
            
            $stmt = $this->conn->prepare("
                INSERT INTO transactions 
                (sender_id, amount, type, description, txn_id, status, customer_ref, provider, sender_balance_before, sender_balance_after, ip_address)
                VALUES (?, ?, 'topup', ?, ?, 'completed', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $amount,
                $description,
                $txn_id,
                $phoneNumber,
                $provider,
                $balanceBefore,
                $balanceAfter,
                $ipAddress
            ]);
            
            $this->conn->commit();
            
            $this->notificationService->notifyTopup($user_id, $amount, $phoneNumber, $txn_id);
            $this->audit('topup', $user_id, $amount, ['phone' => $phoneNumber, 'provider' => $provider, 'txn_id' => $txn_id]);
            
            return [
                'success' => true,
                'txn_id' => $txn_id,
                'amount' => $amount,
                'phone' => $phoneNumber,
                'provider' => $provider,
                'balance_after' => $balanceAfter
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Topup error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function checkIdempotency($key) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE idempotency_key = ? AND status = 'completed'");
            $stmt->execute([$key]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    private function generateTransactionId($prefix) {
        return $prefix . date('YmdHis') . rand(1000, 9999);
    }
    
    private function getUserName($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn() ?? 'Unknown';
        } catch (PDOException $e) {
            return 'Unknown';
        }
    }
    
    private function validatePhoneNumber($phone) {
        return preg_match('/^9[678]\d{8}$/', $phone) === 1;
    }
    
    private function detectProvider($phone) {
        $prefix = substr($phone, 0, 3);
        $ntcPrefixes = ['984', '981'];
        $ncellPrefixes = ['986', '980'];
        $smartPrefixes = ['988'];
        
        if (in_array($prefix, $ntcPrefixes)) return 'NTC';
        if (in_array($prefix, $ncellPrefixes)) return 'Ncell';
        if (in_array($prefix, $smartPrefixes)) return 'Smart Cell';
        return 'Unknown';
    }
    
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private function audit($action, $user_id, $amount, $data = []) {
        try {
            require_once __DIR__ . '/../models/AuditLog.php';
            $auditLog = new AuditLog();
            $auditLog->log($action, $user_id, ucfirst($action) . ' of ' . $amount, $data, 'transaction', $data['txn_id'] ?? null);
        } catch (Exception $e) {
            error_log("Audit error: " . $e->getMessage());
        }
    }
}
