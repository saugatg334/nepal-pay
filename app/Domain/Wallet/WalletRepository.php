<?php
/**
 * WalletRepository - Pure DB for wallet balances/ledger
 */
require_once __DIR__ . '/../../Infrastructure/Database/Repositories/UserRepository.php';

class WalletRepository {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getBalanceWithLock($userId) {
        $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        return floatval($stmt->fetchColumn());
    }

    public function updateBalance($userId, $newBalance) {
        $stmt = $this->conn->prepare("UPDATE users SET wallet_balance = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newBalance, $userId]);
        return $stmt->rowCount() > 0;
    }
}
?>

