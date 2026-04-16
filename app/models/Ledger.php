<?php
/**
 * Ledger Model - Double-Entry Bookkeeping System
 * Production-level with immutable entries and balance verification
 */
require_once __DIR__ . '/../config/database.php';

class Ledger {
    private $conn;
    
    const TYPE_DEBIT = 'debit';
    const TYPE_CREDIT = 'credit';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_REVERSAL = 'reversal';
    
    const ENTRY_STATUS_PENDING = 'pending';
    const ENTRY_STATUS_POSTED = 'posted';
    const ENTRY_STATUS_VOIDED = 'voided';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Create a ledger entry (for single entry operations like deposits/withdrawals)
     */
    public function createEntry(
        $user_id,
        $type,
        $amount,
        $running_balance,
        $description,
        $reference_type = null,
        $reference_id = null,
        $metadata = null
    ) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO ledger_entries 
                (user_id, entry_type, amount, running_balance, description, reference_type, reference_id, metadata, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $metadataJson = $metadata ? json_encode($metadata) : null;
            $status = self::ENTRY_STATUS_POSTED;
            
            $stmt->execute([
                $user_id,
                $type,
                $amount,
                $running_balance,
                $description,
                $reference_type,
                $reference_id,
                $metadataJson,
                $status
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Ledger createEntry error: " . $e->getMessage());
            throw new Exception("Failed to create ledger entry: " . $e->getMessage());
        }
    }
    
    /**
     * Create double-entry transaction (debit and credit)
     * For transfers between accounts
     */
    public function createDoubleEntry(
        $from_user_id,
        $to_user_id,
        $amount,
        $description,
        $reference_type,
        $reference_id,
        $metadata = null
    ) {
        try {
            $this->conn->beginTransaction();
            
            // Get balances before
            $fromBalanceBefore = $this->getBalance($from_user_id);
            $toBalanceBefore = $this->getBalance($to_user_id);
            
            // Deduct from sender
            $fromRunningBalance = $fromBalanceBefore - $amount;
            $stmt = $this->conn->prepare("
                INSERT INTO ledger_entries 
                (user_id, entry_type, amount, running_balance, description, reference_type, reference_id, metadata, status, created_at)
                VALUES (?, 'debit', ?, ?, ?, ?, ?, ?, 'posted', NOW())
            ");
            $metadataJson = $metadata ? json_encode($metadata) : null;
            $stmt->execute([
                $from_user_id,
                $amount,
                $fromRunningBalance,
                $description . ' (debit)',
                $reference_type,
                $reference_id,
                $metadataJson
            ]);
            $fromEntryId = $this->conn->lastInsertId();
            
            // Add to receiver
            $toRunningBalance = $toBalanceBefore + $amount;
            $stmt = $this->conn->prepare("
                INSERT INTO ledger_entries 
                (user_id, entry_type, amount, running_balance, description, reference_type, reference_id, metadata, status, created_at)
                VALUES (?, 'credit', ?, ?, ?, ?, ?, ?, 'posted', NOW())
            ");
            $stmt->execute([
                $to_user_id,
                $amount,
                $toRunningBalance,
                $description . ' (credit)',
                $reference_type,
                $reference_id,
                $metadataJson
            ]);
            $toEntryId = $this->conn->lastInsertId();
            
            $this->conn->commit();
            
            return [
                'from_entry_id' => $fromEntryId,
                'to_entry_id' => $toEntryId,
                'from_balance_before' => $fromBalanceBefore,
                'to_balance_before' => $toBalanceBefore
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Ledger createDoubleEntry error: " . $e->getMessage());
            throw new Exception("Failed to create double-entry: " . $e->getMessage());
        }
    }
    
    /**
     * Get running balance from ledger (should match user.wallet_balance)
     */
    public function getBalance($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN entry_type = 'credit' THEN amount
                        WHEN entry_type = 'debit' THEN -amount
                        WHEN entry_type = 'adjustment' THEN amount
                        ELSE 0
                    END
                ), 0) as balance
                FROM ledger_entries 
                WHERE user_id = ? AND status = 'posted'
            ");
            $stmt->execute([$user_id]);
            return (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Ledger getBalance error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get ledger entry by ID
     */
    public function getEntryById($entry_id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM ledger_entries WHERE id = ?");
            $stmt->execute([$entry_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get ledger entries for a user
     */
    public function getEntries($user_id, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM ledger_entries 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get entries by reference
     */
    public function getEntriesByReference($reference_type, $reference_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM ledger_entries 
                WHERE reference_type = ? AND reference_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$reference_type, $reference_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Void a ledger entry (creates reversal)
     */
    public function voidEntry($entry_id, $reason) {
        try {
            $this->conn->beginTransaction();
            
            $originalEntry = $this->getEntryById($entry_id);
            if (!$originalEntry) {
                throw new Exception("Ledger entry not found");
            }
            
            if ($originalEntry['status'] === self::ENTRY_STATUS_VOIDED) {
                throw new Exception("Entry already voided");
            }
            
            // Create reversal entry
            $reversalType = $originalEntry['entry_type'] === self::TYPE_DEBIT 
                ? self::TYPE_CREDIT 
                : self::TYPE_DEBIT;
            
            $newBalance = $this->getBalance($originalEntry['user_id']);
            
            $stmt = $this->conn->prepare("
                INSERT INTO ledger_entries 
                (user_id, entry_type, amount, running_balance, description, reference_type, reference_id, metadata, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'posted', NOW())
            ");
            
            $metadata = [
                'voided_entry_id' => $entry_id,
                'void_reason' => $reason,
                'original_amount' => $originalEntry['amount'],
                'original_entry_type' => $originalEntry['entry_type']
            ];
            
            $stmt->execute([
                $originalEntry['user_id'],
                $reversalType,
                $originalEntry['amount'],
                $newBalance,
                'VOID: ' . $originalEntry['description'],
                'reversal',
                $entry_id,
                json_encode($metadata)
            ]);
            
            // Mark original as voided
            $stmt = $this->conn->prepare("UPDATE ledger_entries SET status = 'voided' WHERE id = ?");
            $stmt->execute([$entry_id]);
            
            $this->conn->commit();
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Ledger voidEntry error: " . $e->getMessage());
            throw new Exception("Failed to void entry: " . $e->getMessage());
        }
    }
    
    /**
     * Reconcile ledger with user wallet balance
     */
    public function reconcile($user_id) {
        try {
            $ledgerBalance = $this->getBalance($user_id);
            
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $walletBalance = (float) $stmt->fetchColumn();
            
            $difference = $walletBalance - $ledgerBalance;
            
            $result = [
                'user_id' => $user_id,
                'ledger_balance' => $ledgerBalance,
                'wallet_balance' => $walletBalance,
                'difference' => $difference,
                'reconciled' => abs($difference) < 0.01
            ];
            
            // Log reconciliation
            $stmt = $this->conn->prepare("
                INSERT INTO reconciliation_log 
                (user_id, ledger_balance, wallet_balance, difference, status, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $status = $result['reconciled'] ? 'matched' : 'discrepancy';
            $stmt->execute([$user_id, $ledgerBalance, $walletBalance, $difference, $status]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Ledger reconcile error: " . $e->getMessage());
            throw new Exception("Reconciliation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Full system reconciliation (for admin)
     */
    public function reconcileAll() {
        try {
            $results = [];
            
            $stmt = $this->conn->query("SELECT id FROM users WHERE status = 'active'");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as $user) {
                $results[] = $this->reconcile($user['id']);
            }
            
            $discrepancies = array_filter($results, function($r) {
                return !$r['reconciled'];
            });
            
            return [
                'total_users' => count($results),
                'matched' => count($results) - count($discrepancies),
                'discrepancies' => count($discrepancies),
                'details' => $results
            ];
        } catch (PDOException $e) {
            error_log("Ledger reconcileAll error: " . $e->getMessage());
            throw new Exception("Full reconciliation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get transaction summary for a date range
     */
    public function getSummary($user_id, $from_date = null, $to_date = null) {
        try {
            $query = "
                SELECT 
                    entry_type,
                    SUM(amount) as total_amount,
                    COUNT(*) as transaction_count
                FROM ledger_entries 
                WHERE user_id = ?
            ";
            
            $params = [$user_id];
            
            if ($from_date) {
                $query .= " AND created_at >= ?";
                $params[] = $from_date;
            }
            
            if ($to_date) {
                $query .= " AND created_at <= ?";
                $params[] = $to_date;
            }
            
            $query .= " GROUP BY entry_type";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
