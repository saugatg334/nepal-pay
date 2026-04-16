<?php
/**
 * LedgerRepository - Pure DB for ledger entries
 */
require_once __DIR__ . '/../../Infrastructure/Database/Repositories/UserRepository.php';

class LedgerRepository {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function createEntry($userId, $entryType, $amount, $runningBalance, $description, $refType, $refId, $metadata) {
        $metaJson = json_encode($metadata);
        $query = "INSERT INTO ledger_entries (user_id, entry_type, amount, running_balance, description, reference_type, reference_id, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId, $entryType, $amount, $runningBalance, $description, $refType, $refId, $metaJson]);
        return $this->conn->lastInsertId();
    }

    public function getBalance($userId) {
        $query = "SELECT COALESCE(SUM(CASE WHEN entry_type = 'credit' THEN amount WHEN entry_type = 'debit' THEN -amount ELSE 0 END), 0) as balance FROM ledger_entries WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return floatval($stmt->fetchColumn());
    }
}
?>

