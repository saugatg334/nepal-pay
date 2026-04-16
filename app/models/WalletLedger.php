<?php
/**
 * Wallet Ledger Model - Immutable Record of All Balance Changes
 */
require_once __DIR__ . '/../config/database.php';

class WalletLedger {
    private $conn;
    private $table = 'wallet_ledger';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Atomic wallet operation with ledger logging
     * Uses SELECT FOR UPDATE to prevent race conditions
     */
    public function transfer($sender_id, $receiver_id, $amount, $description = '', $reference_id = null) {
        $this->conn->beginTransaction();
        try {
            // Lock sender balance
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$sender_id]);
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sender || $sender['wallet_balance'] < $amount) {
                throw new Exception("Insufficient balance");
            }

            // Lock receiver
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$receiver_id]);
            $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$receiver) {
                throw new Exception("Receiver not found");
            }

            // Update balances
            $this->conn->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$amount, $sender_id]);
            $this->conn->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$amount, $receiver_id]);

            // Sender ledger debit
            $sender_new_balance = $sender['wallet_balance'] - $amount;
            $this->logEntry($sender_id, 'debit', $amount, $sender_new_balance, $description, $reference_id);

            // Receiver ledger credit
            $receiver_new_balance = $receiver['wallet_balance'] + $amount;
            $this->logEntry($receiver_id, 'credit', $amount, $receiver_new_balance, $description, $reference_id);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function deposit($user_id, $amount, $description = 'Deposit') {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) throw new Exception("User not found");

            $new_balance = $user['wallet_balance'] + $amount;
            $this->conn->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")->execute([$new_balance, $user_id]);

            $this->logEntry($user_id, 'credit', $amount, $new_balance, $description);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function logEntry($user_id, $type, $amount, $running_balance, $description, $reference_id = null) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (user_id, type, amount, running_balance, description, reference_id, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->execute([$user_id, $type, $amount, $running_balance, $description, $reference_id, $ip]);
    }

    public function getLedger($user_id, $limit = 50, $offset = 0, $type = null) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ";
        $params = [$user_id];
        if ($type) {
            $sql .= "AND type = ? ";
            $params[] = $type;
        }
        $sql .= "ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBalanceHistory($user_id, $days = 30) {
        $sql = "SELECT DATE(created_at) as date, type, SUM(amount) as net_change, MAX(running_balance) as end_balance 
                FROM {$this->table} WHERE user_id = ? AND DATE(created_at) >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at), type ORDER BY date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

