<?php
/**
 * Reconciliation Script
 * Verifies wallet_balance == sum(ledger) for all users
 * Logs any inconsistencies
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Ledger.php';
require_once __DIR__ . '/../models/AuditLog.php';

class ReconciliationService {
    private $conn;
    private $ledger;
    private $auditLog;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->ledger = new Ledger();
        $this->auditLog = new AuditLog();
    }
    
    /**
     * Run full system reconciliation
     */
    public function runFullReconciliation() {
        echo "==========================================\n";
        echo "Starting Full System Reconciliation\n";
        echo "==========================================\n\n";
        
        $results = [
            'started_at' => date('Y-m-d H:i:s'),
            'users_checked' => 0,
            'matched' => 0,
            'discrepancies' => 0,
            'details' => []
        ];
        
        try {
            // Get all active users
            $stmt = $this->conn->query("SELECT id, full_name, phone, wallet_balance FROM users WHERE status = 'active'");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results['users_checked'] = count($users);
            
            foreach ($users as $user) {
                $reconciliation = $this->reconcileUser($user['id']);
                
                $results['details'][] = [
                    'user_id' => $user['id'],
                    'name' => $user['full_name'],
                    'phone' => $user['phone'],
                    'ledger_balance' => $reconciliation['ledger_balance'],
                    'wallet_balance' => $reconciliation['wallet_balance'],
                    'difference' => $reconciliation['difference'],
                    'status' => $reconciliation['reconciled'] ? 'matched' : 'discrepancy'
                ];
                
                if ($reconciliation['reconciled']) {
                    $results['matched']++;
                } else {
                    $results['discrepancies']++;
                    echo "DISCREPANCY: User {$user['id']} ({$user['full_name']})\n";
                    echo "  Ledger: {$reconciliation['ledger_balance']}\n";
                    echo "  Wallet: {$reconciliation['wallet_balance']}\n";
                    echo "  Diff: {$reconciliation['difference']}\n\n";
                }
            }
            
            $results['completed_at'] = date('Y-m-d H:i:s');
            
            // Log to audit
            $this->auditLog->log(
                'system_reconciliation',
                null,
                "Reconciliation completed: {$results['matched']} matched, {$results['discrepancies']} discrepancies",
                $results,
                'system',
                null
            );
            
            echo "\n==========================================\n";
            echo "Reconciliation Complete\n";
            echo "==========================================\n";
            echo "Users Checked: {$results['users_checked']}\n";
            echo "Matched: {$results['matched']}\n";
            echo "Discrepancies: {$results['discrepancies']}\n";
            
            return $results;
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $results['error'] = $e->getMessage();
            return $results;
        }
    }
    
    /**
     * Reconcile single user
     */
    public function reconcileUser($user_id) {
        // Get ledger balance
        $ledgerBalance = $this->ledger->getBalance($user_id);
        
        // Get wallet balance
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
        
        // Log to reconciliation_log table
        $stmt = $this->conn->prepare("
            INSERT INTO reconciliation_log 
            (user_id, ledger_balance, wallet_balance, difference, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $status = $result['reconciled'] ? 'matched' : 'discrepancy';
        $stmt->execute([
            $user_id,
            $ledgerBalance,
            $walletBalance,
            $difference,
            $status
        ]);
        
        return $result;
    }
    
    /**
     * Auto-fix discrepancies (if small)
     */
    public function autoFixDiscrepancy($user_id, $maxTolerance = 1.00) {
        $reconciliation = $this->reconcileUser($user_id);
        
        if ($reconciliation['reconciled']) {
            return ['fixed' => false, 'message' => 'Already reconciled'];
        }
        
        $difference = abs($reconciliation['difference']);
        
        if ($difference > $maxTolerance) {
            return [
                'fixed' => false, 
                'message' => "Difference ({$difference}) exceeds tolerance ({$maxTolerance})"
            ];
        }
        
        // Adjust wallet balance to match ledger
        try {
            $this->conn->beginTransaction();
            
            $newBalance = $reconciliation['ledger_balance'];
            
            $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $user_id]);
            
            // Create adjustment ledger entry
            $this->ledger->createEntry(
                $user_id,
                'adjustment',
                $difference,
                $newBalance,
                'Auto-reconciliation adjustment',
                'reconciliation',
                null,
                ['old_wallet' => $reconciliation['wallet_balance'], 'new_wallet' => $newBalance]
            );
            
            $this->conn->commit();
            
            // Log the adjustment
            $this->auditLog->log(
                'wallet_adjustment',
                $user_id,
                "Auto-reconciliation: adjusted wallet from {$reconciliation['wallet_balance']} to {$newBalance}",
                ['difference' => $difference],
                'reconciliation',
                null
            );
            
            return [
                'fixed' => true,
                'message' => "Adjusted wallet balance by {$difference}",
                'new_balance' => $newBalance
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['fixed' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get reconciliation history
     */
    public function getHistory($limit = 100) {
        try {
            $stmt = $this->conn->prepare("
                SELECT r.*, u.full_name, u.phone
                FROM reconciliation_log r
                LEFT JOIN users u ON r.user_id = u.id
                ORDER BY r.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get users with discrepancies
     */
    public function getUsersWithDiscrepancies() {
        try {
            $stmt = $this->conn->query("
                SELECT r.user_id, r.ledger_balance, r.wallet_balance, r.difference, 
                       u.full_name, u.phone, r.created_at
                FROM reconciliation_log r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.status = 'discrepancy'
                ORDER BY r.created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

// Run if called directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "Running Reconciliation...\n\n";
    
    $reconciliation = new ReconciliationService();
    $result = $reconciliation->runFullReconciliation();
    
    echo "\nResult: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
