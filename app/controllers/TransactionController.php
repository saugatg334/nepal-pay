<?php
/**
 * TransactionController - Send/Request Money + Balance Logic
 * Production fintech-grade with ACID transactions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/auth_helper.php';

class TransactionController {
    private $userModel;
    private $pdo;

    public function __construct() {
        $database = new Database();
        $this->pdo = $database->connect();
        $this->pdo->beginTransaction(); // ACID wrapper
        $this->userModel = new User();
    }

    public function sendMoney($sender_id, $recipient_identifier, $amount, $description = '') {
        if ($amount <= 0 || $amount > 50000) {
            throw new Exception('Amount must be between Rs 1-50,000');
        }

        // Find recipient by phone/email
        $recipient = $this->findUserByIdentifier($recipient_identifier);
        if (!$recipient) {
            throw new Exception('Recipient not found');
        }

        $sender = $this->userModel->getUserById($sender_id);
        if ($sender['wallet_balance'] < $amount) {
            throw new Exception('Insufficient balance');
        }

        $reference = 'TXN' . time() . rand(100,999);
        
        try {
            // Debit sender
            $this->userModel->updateWalletBalance($sender_id, -$amount);
            
            // Credit receiver  
            $this->userModel->updateWalletBalance($recipient['id'], $amount);
            
            // Record transaction
            $this->userModel->recordTransaction(
                $sender_id, $recipient['id'], $amount, 'transfer', 
                $description, $reference, status: 'completed'
            );
            
            $this->pdo->commit();
            return ['success' => true, 'reference' => $reference];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception('Transfer failed: ' . $e->getMessage());
        }
    }

    public function addMoney($user_id, $amount) {
        if ($amount <= 0 || $amount > 100000) {
            throw new Exception('Invalid add money amount');
        }

        $reference = 'DEP' . time();
        
        $this->userModel->updateWalletBalance($user_id, $amount);
        $this->userModel->recordTransaction($user_id, null, $amount, 'deposit', 'Add money', $reference);
        
        $this->pdo->commit();
        return ['success' => true, 'reference' => $reference];
    }

    private function findUserByIdentifier($identifier) {
        $query = "SELECT * FROM users WHERE phone = ? OR email = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

