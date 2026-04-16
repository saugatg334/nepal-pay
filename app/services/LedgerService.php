<?php
/**
 * LedgerService - Double-Entry Bookkeeping
 */
require_once __DIR__ . '/../config/database.php';

class LedgerService {
    private $conn;
    
    const ENTRY_DEBIT = 'debit';
    const ENTRY_CREDIT = 'credit';
    const ENTRY_ADJUSTMENT = 'adjustment';
    const ENTRY_REVERSAL = 'reversal';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Create ledger entry
     */
    public function createEntry(
        $userId,
        $entryType,
        $amount,
        $runningBalance,
        $description = '',
        $referenceType = null,
        $referenceId = null,
        $metadata = []
    ) {
        try {
            $metaJson = json_encode($metadata);
            
$sql = "INSERT INTO ledger_entries 
                    (user_id, entry_type, amount, running_balance, description, 
                     reference_type, reference_id, metadata, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'posted')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $userId,
                $entryType,
                $amount,
                $runningBalance,
                $description,
                $referenceType,
                $referenceId,
                $metaJson,
                'posted'
            ]);
            
            return $this->conn->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("LedgerService createEntry error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get ledger balance
     */
    public function getLedgerBalance($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(
                    CASE 
                        WHEN entry_type = 'credit' THEN amount 
                        WHEN entry_type = 'debit' THEN -amount 
                        ELSE 0
                    END
                ), 0) as balance
                FROM ledger_entries 
                WHERE user_id = ? AND status = 'posted'
            ");
            $stmt->execute([$userId]);
            return floatval($stmt->fetchColumn());
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Reconcile wallet balance with ledger
     */
    public function reconcile($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $walletBalance = floatval($stmt->fetchColumn());
            
            $ledgerBalance = $this->getLedgerBalance($userId);
            $difference = $walletBalance - $ledgerBalance;
            
            return [
                'user_id' => $userId,
                'wallet_balance' => $walletBalance,
                'ledger_balance' => $ledgerBalance,
                'difference' => $difference,
                'matched' => abs($difference) < 0.01
            ];
            
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}