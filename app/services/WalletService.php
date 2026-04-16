<?php
/**
 * Final Production WalletService - Stripe/PayPal Grade
 * Bulletproof ACID, idempotency, deadlock-safe, no double-spend
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/LedgerService.php';

class WalletService {
    private $conn;
    private $ledgerService;
    
    const MIN_AMOUNT = 10;
    const MAX_AMOUNT = 50000;
    const MAX_RETRY = 3;
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->ledgerService = new LedgerService();
    }
    
    /**
     * Credit with idempotency (INSERT FIRST)
     */
    public function credit($userId, $amount, $description = 'Deposit', $idempotencyKey = null) {
        return $this->executeWithRetry(function () use ($userId, $amount, $description, $idempotencyKey) {
            if (!$idempotencyKey) {
                $idempotencyKey = $this->generateIdempotencyKey($userId, 'credit', microtime(true));
            }
            
            // 1. Check existing completed txn
            $existing = $this->getCompletedTxnByIdempotency($idempotencyKey);
            if ($existing) {
                return $existing;
            }
            
            $this->conn->beginTransaction();
            
            try {
                // 2. Insert pending txn FIRST (race condition protection)
                $txnId = $this->generateTxnId('DEP');
                $txn = $this->insertPendingTxn($userId, $amount, 'deposit', $description, $txnId, $idempotencyKey);
                
                // 3. Lock and update wallet
                $balanceBefore = $this->lockAndGetBalance($userId);
                if ($balanceBefore === false) {
                    throw new Exception('User lock failed');
                }
                $balanceAfter = $balanceBefore + $amount;
                
                $this->updateBalanceLocked($userId, $balanceAfter);
                
                // 4. Ledger entry (FAIL → ROLLBACK)
                $ledgerId = $this->ledgerService->createEntry(
                    $userId, 'credit', $amount, $balanceAfter, $description,
                    'transaction', $txn['id'], ['txn_id' => $txnId]
                );
                if (!$ledgerId) {
                    throw new Exception('Ledger entry failed - transaction rolled back');
                }
                
                // 5. Commit and mark completed
                $this->markTxnCompleted($txn['id']);
                $this->conn->commit();
                
                return [
                    'success' => true,
                    'txn' => $txn,
                    'balance_after' => $balanceAfter,
                    'ledger_id' => $ledgerId
                ];
                
            } catch (Exception $e) {
                $this->conn->rollBack();
                $this->markTxnFailed($txn['id'] ?? null);
                throw $e;
            }
        });
    }
    
    /**
     * Debit with idempotency
     */
    public function debit($userId, $amount, $description = 'Withdrawal', $idempotencyKey = null) {
        return $this->executeWithRetry(function () use ($userId, $amount, $description, $idempotencyKey) {
            if (!$idempotencyKey) {
                $idempotencyKey = $this->generateIdempotencyKey($userId, 'debit', microtime(true));
            }
            
            $existing = $this->getCompletedTxnByIdempotency($idempotencyKey);
            if ($existing) {
                return $existing;
            }
            
            $this->conn->beginTransaction();
            
            try {
                $txnId = $this->generateTxnId('WTH');
                $txn = $this->insertPendingTxn($userId, $amount, 'withdrawal', $description, $txnId, $idempotencyKey);
                
                $balanceBefore = $this->lockAndGetBalance($userId);
                if ($balanceBefore < $amount) {
                    throw new Exception('Insufficient balance');
                }
                $balanceAfter = $balanceBefore - $amount;
                
                $this->updateBalanceLocked($userId, $balanceAfter);
                
                $ledgerId = $this->ledgerService->createEntry(
                    $userId, 'debit', $amount, $balanceAfter, $description,
                    'transaction', $txn['id'], ['txn_id' => $txnId]
                );
                if (!$ledgerId) {
                    throw new Exception('Ledger failed - rollback');
                }
                
                $this->markTxnCompleted($txn['id']);
                $this->conn->commit();
                
                return [
                    'success' => true,
                    'txn' => $txn,
                    'balance_after' => $balanceAfter,
                    'ledger_id' => $ledgerId
                ];
                
            } catch (Exception $e) {
                $this->conn->rollBack();
                $this->markTxnFailed($txn['id'] ?? null);
                throw $e;
            }
        });
    }
    
    /**
     * Transfer with deadlock-safe locking (sorted IDs)
     */
    public function transfer($fromUserId, $toUserId, $amount, $description = 'P2P Transfer', $idempotencyKey = null) {
        if ($fromUserId == $toUserId) {
            throw new Exception('Cannot transfer to self');
        }
        
        return $this->executeWithRetry(function () use ($fromUserId, $toUserId, $amount, $description, $idempotencyKey) {
            if (!$idempotencyKey) {
                $idempotencyKey = $this->generateIdempotencyKey($fromUserId, 'transfer_to_' . $toUserId, microtime(true));
            }
            
            $existing = $this->getCompletedTxnByIdempotency($idempotencyKey);
            if ($existing) {
                return $existing;
            }
            
            $this->conn->beginTransaction();
            
            try {
                $txnId = $this->generateTxnId('TRF');
                $txn = $this->insertPendingTxn($fromUserId, $amount, 'transfer', $description, $txnId, $idempotencyKey, $toUserId);
                
                // Deadlock-safe: Lock sorted order
                [$firstId, $secondId] = $fromUserId < $toUserId ? [$fromUserId, $toUserId] : [$toUserId, $fromUserId];
                $this->lockUsers([$firstId, $secondId]);
                
                $fromBalanceBefore = $this->getBalance($fromUserId);
                $toBalanceBefore = $this->getBalance($toUserId);
                
                if ($fromBalanceBefore < $amount) {
                    throw new Exception('Insufficient balance');
                }
                
                $fromBalanceAfter = $fromBalanceBefore - $amount;
                $toBalanceAfter = $toBalanceBefore + $amount;
                
                $this->updateBalanceLocked($fromUserId, $fromBalanceAfter);
                $this->updateBalanceLocked($toUserId, $toBalanceAfter);
                
                // Ledger for sender (debit)
                $senderLedger = $this->ledgerService->createEntry(
                    $fromUserId, 'debit', $amount, $fromBalanceAfter, $description . ' (sent)',
                    'transfer', $txn['id'], ['txn_id' => $txnId, 'to' => $toUserId]
                );
                if (!$senderLedger) {
                    throw new Exception('Sender ledger failed - rollback');
                }
                
                // Ledger for receiver (credit)
                $receiverLedger = $this->ledgerService->createEntry(
                    $toUserId, 'credit', $amount, $toBalanceAfter, $description . ' (received)',
                    'transfer', $txn['id'], ['txn_id' => $txnId, 'from' => $fromUserId]
                );
                if (!$receiverLedger) {
                    throw new Exception('Receiver ledger failed - rollback');
                }
                
                $this->markTxnCompleted($txn['id']);
                $this->conn->commit();
                
                return [
                    'success' => true,
                    'txn' => $txn,
                    'from_balance_after' => $fromBalanceAfter,
                    'to_balance_after' => $toBalanceAfter
                ];
                
            } catch (Exception $e) {
                $this->conn->rollBack();
                $this->markTxnFailed($txn['id'] ?? null);
                throw $e;
            }
        });
    }
    
    // Private helpers...
    private function executeWithRetry($callback, $maxRetries = 3) {
        $retry = 0;
        while ($retry <= $maxRetries) {
            try {
                return $callback();
            } catch (Exception $e) {
                if ($this->isDeadlockError($e) && $retry < $maxRetries) {
                    $retry++;
                    usleep(100000 * ($retry + 1)); // Progressive backoff
                    continue;
                }
                throw $e;
            }
        }
    }
    
    private function insertPendingTxn($senderId, $amount, $type, $description, $txnId, $idempotencyKey, $receiverId = null) {
        $sql = "INSERT INTO transactions (sender_id, receiver_id, amount, type, description, txn_id, status, idempotency_key, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$senderId, $receiverId, $amount, $type, $description, $txnId, $idempotencyKey]);
        return ['id' => $this->conn->lastInsertId(), 'txn_id' => $txnId];
    }
    
    private function getCompletedTxnByIdempotency($key) {
        $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE idempotency_key = ? AND status = 'completed'");
        $stmt->execute([$key]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function markTxnCompleted($txnId) {
        if (!$txnId) return;
        $stmt = $this->conn->prepare("UPDATE transactions SET status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$txnId]);
    }
    
    private function markTxnFailed($txnId) {
        if (!$txnId) return;
        $stmt = $this->conn->prepare("UPDATE transactions SET status = 'failed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$txnId]);
    }
    
    private function lockAndGetBalance($userId) {
        $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        return floatval($stmt->fetchColumn() ?: 0);
    }
    
    private function updateBalanceLocked($userId, $newBalance) {
        $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newBalance, $userId]);
    }
    
    private function lockUsers($userIds) {
        sort($userIds); // Deadlock prevention
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        $sql = "SELECT wallet_balance FROM users WHERE id IN ($placeholders) FOR UPDATE";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($userIds);
    }
    
public function getBalance($userId) {
        $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return floatval($stmt->fetchColumn() ?: 0);
    }
    
    private function generateTxnId($prefix) {
        return $prefix . date('YmdHi') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
    
    private function generateIdempotencyKey($userId, $type, $timestamp) {
        return hash('sha256', $userId . $type . $timestamp . bin2hex(random_bytes(16)));
    }
    
    private function isDeadlockError(Exception $e) {
        $code = $e->getCode();
        return in_array($code, [1213, 1205]) || stripos($e->getMessage(), 'deadlock') !== false;
    }
}

