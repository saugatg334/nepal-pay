<?php
/**
 * Wallet Model - Handles wallet operations
 * Production-level with idempotency, status tracking, and audit logging
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';

class Wallet {
    private $conn;
    private $auditLog;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->auditLog = new AuditLog();
    }

    /**
     * Get wallet balance
     */
    public function getBalance($user_id) {
        try {
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn() ?? 0;
        } catch (PDOException $e) {
            error_log("Wallet getBalance error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check idempotency - prevent duplicate transactions
     */
    public function checkIdempotency($idempotencyKey) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE idempotency_key = ? AND status = 'completed'");
            $stmt->execute([$idempotencyKey]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Credit wallet (deposit) with idempotency and status tracking
     */
    public function credit($user_id, $amount, $description = '', $idempotencyKey = null) {
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

throw new Exception("Direct wallet access forbidden. Use WalletService::credit()");

            $txn_id = 'DEP' . date('YmdHis') . rand(1000, 9999);
            $status = self::STATUS_COMPLETED;
            $stmt = $this->conn->prepare("INSERT INTO transactions 
                (sender_id, amount, type, description, txn_id, status, idempotency_key) 
                VALUES (?, ?, 'deposit', ?, ?, ?, ?)");
            $stmt->execute([$user_id, $amount, $description, $txn_id, $status, $idempotencyKey]);

            $this->conn->commit();

            $this->auditLog->log(
                AuditLog::ACTION_WALLET_DEPOSIT,
                $user_id,
                "Deposit completed",
                [
                    'amount' => $amount,
                    'txn_id' => $txn_id,
                    'balance_before' => $this->getBalance($user_id) - $amount
                ],
                'transaction',
                $txn_id
            );

            return ['success' => true, 'txn_id' => $txn_id];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Wallet credit error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Transaction failed. Please try again.'];
        }
    }

    /**
     * Debit wallet (withdraw/send) with idempotency and status tracking
     */
    public function debit($user_id, $amount, $description = '', $idempotencyKey = null) {
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
            $balance = $stmt->fetchColumn();

            if ($balance < $amount) {
                throw new Exception('Insufficient balance');
            }

            $this->conn->exec("UPDATE users SET wallet_balance = wallet_balance - $amount WHERE id = $user_id");

            $txn_id = 'WTH' . date('YmdHis') . rand(1000, 9999);
            $status = self::STATUS_COMPLETED;
            $stmt = $this->conn->prepare("INSERT INTO transactions 
                (sender_id, amount, type, description, txn_id, status, idempotency_key) 
                VALUES (?, ?, 'withdrawal', ?, ?, ?, ?)");
            $stmt->execute([$user_id, $amount, $description, $txn_id, $status, $idempotencyKey]);

            $this->conn->commit();

            $this->auditLog->log(
                AuditLog::ACTION_WALLET_WITHDRAW,
                $user_id,
                "Withdrawal completed",
                [
                    'amount' => $amount,
                    'txn_id' => $txn_id
                ],
                'transaction',
                $txn_id
            );

            return ['success' => true, 'txn_id' => $txn_id];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Wallet debit error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Transaction failed. Please try again.'];
        }
    }

    /**
     * Transfer between users with idempotency and audit logging
     */
    public function transfer($from_user, $to_user, $amount, $note = '', $idempotencyKey = null) {
        if ($from_user == $to_user) {
            return ['success' => false, 'error' => 'Cannot transfer to yourself'];
        }

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
            $stmt->execute([$from_user]);
            $balance = $stmt->fetchColumn();

            if ($balance < $amount) {
                throw new Exception('Insufficient balance');
            }

throw new Exception("Direct wallet access forbidden. Use WalletService");
            $this->conn->exec("UPDATE users SET wallet_balance = wallet_balance + $amount WHERE id = $to_user");

            $txn_id = 'TRF' . date('YmdHis') . rand(1000, 9999);
            $status = self::STATUS_COMPLETED;
            $stmt = $this->conn->prepare("INSERT INTO transactions 
                (sender_id, receiver_id, amount, type, description, txn_id, status, idempotency_key) 
                VALUES (?, ?, ?, 'transfer', ?, ?, ?, ?)");
            $stmt->execute([$from_user, $to_user, $amount, $note ?: 'Transfer', $txn_id, $status, $idempotencyKey]);

            $this->conn->commit();

            $this->auditLog->log(
                AuditLog::ACTION_WALLET_TRANSFER,
                $from_user,
                "Transfer completed",
                [
                    'amount' => $amount,
                    'to_user' => $to_user,
                    'txn_id' => $txn_id
                ],
                'transaction',
                $txn_id
            );

            return ['success' => true, 'txn_id' => $txn_id];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Wallet transfer error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Transaction failed. Please try again.'];
        }
    }

    /**
     * Generate idempotency key for transaction
     */
    public function generateIdempotencyKey($user_id, $type) {
        return "WAL_" . $type . "_" . $user_id . "_" . microtime(true) . "_" . bin2hex(random_bytes(4));
    }
}
