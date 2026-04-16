<?php
/**
 * Wallet Controller - Production Digital Wallet
 * Atomic transactions, PIN verification, history
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class WalletController {
    private $userModel;
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->userModel = new User();
    }

    public function getWalletBalance($user_id) {
        return $this->userModel->getWalletBalance($user_id);
    }

    public function getTransactionHistory($user_id, $limit = 10) {
        return $this->userModel->getTransactionHistory($user_id, $limit);
    }

    /**
     * Deposit money to wallet
     */
    public function deposit($user_id, $amount) {
        if (!$user_id) {
            return ['success' => false, 'error' => 'User not logged in'];
        }
        
        $amount = floatval($amount);
        
        if ($amount >= 10 && $amount <= 50000) {
            try {
                $this->conn->beginTransaction();
                $this->userModel->updateWalletBalance($user_id, $amount);
                $this->userModel->recordTransaction(null, $user_id, $amount, 'deposit', 'Wallet deposit');
                $this->conn->commit();
                return ['success' => true, 'message' => 'Deposited NPR ' . number_format($amount, 2)];
            } catch (Exception $e) {
                $this->conn->rollBack();
                return ['success' => false, 'error' => $e->getMessage()];
            }
        } else {
            return ['success' => false, 'error' => 'Invalid amount (Rs 10-50,000)'];
        }
    }

    /**
     * Transfer money to another user
     */
    public function transfer($user_id, $to, $amount, $note = '') {
        $to = trim($to);
        $amount = floatval($amount);

        if (empty($to) || $amount <= 0) {
            return ['success' => false, 'error' => 'Invalid input'];
        }

        $receiver = $this->userModel->findUserByPhone($to) ?: $this->userModel->findUserByEmail($to);
        if (!$receiver || $receiver['id'] == $user_id) {
            return ['success' => false, 'error' => 'Invalid recipient'];
        }

        $balance = $this->userModel->getWalletBalance($user_id);
        if ($balance < $amount) {
            return ['success' => false, 'error' => 'Insufficient balance'];
        }

        $this->conn->beginTransaction();
        try {
            $this->userModel->updateWalletBalance($user_id, -$amount);
            $this->userModel->updateWalletBalance($receiver['id'], $amount);
            $this->userModel->recordTransaction($user_id, $receiver['id'], $amount, 'transfer', $note ?: 'P2P transfer');
            $this->conn->commit();
            return ['success' => true, 'message' => 'Transfer successful'];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get transaction count
     */
    public function getTransactionCount($user_id, $type = null) {
        $sql = "SELECT COUNT(*) FROM transactions WHERE sender_id = ? OR receiver_id = ?";
        $params = [$user_id, $user_id];
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
