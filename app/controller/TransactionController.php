<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../helpers/session_helper.php';

class TransactionController {
    private $userModel;
    private $transactionModel;
    private $activityLog;
    private $conn;

    public function __construct() {
        $this->userModel = new User();
        $this->transactionModel = new Transaction();
        $this->activityLog = new ActivityLog();
        
        $database = new Database();
        $this->conn = $database->connect();
    }

    // ==================== WALLET ENGINE ====================
    
    // Get wallet balance
    public function getBalance($user_id) {
        return $this->userModel->getWalletBalance($user_id);
    }

    // Deposit money (admin only)
    public function deposit($user_id, $amount, $note = 'Deposit', $admin_id = null) {
        try {
            // Validate amount
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception("Invalid amount");
            }

            // Get current balance
            $balance_before = $this->getBalance($user_id);
            
            // Start transaction
            $this->conn->beginTransaction();
            
            // Update wallet balance
            $this->userModel->updateWalletBalance($user_id, $amount);
            
            $balance_after = $balance_before + $amount;
            
            // Create transaction record
            $this->transactionModel->create([
                'sender_id' => $admin_id,
                'receiver_id' => $user_id,
                'amount' => $amount,
                'fee' => 0,
                'type' => 'deposit',
                'status' => 'completed',
                'reference_id' => $this->transactionModel->generateReferenceId(),
                'note' => $note,
                'sender_balance_before' => 0,
                'sender_balance_after' => 0,
                'receiver_balance_before' => $balance_before,
                'receiver_balance_after' => $balance_after,
                'ip_address' => $this->getClientIP()
            ]);
            
            // Commit transaction
            $this->conn->commit();
            
            // Log activity
            if ($admin_id) {
                $this->activityLog->log($admin_id, ActivityLog::ACTION_DEPOSIT, 
                    "Deposited Rs. $amount to user ID $user_id", $this->getClientIP());
            }
            
            return ['success' => true, 'message' => 'Deposit successful'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Send money (user to user)
    public function sendMoney($sender_id, $receiver_identifier, $amount, $note = '') {
        try {
            // Validate amount
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception("Invalid amount");
            }

            // Get settings
            $settings = $this->getSettings();
            $min_amount = $settings['min_transfer_amount'] ?? 10;
            $max_amount = $settings['max_transfer_amount'] ?? 25000;
            
            if ($amount < $min_amount) {
                throw new Exception("Minimum transfer amount is Rs. $min_amount");
            }
            
            if ($amount > $max_amount) {
                throw new Exception("Maximum transfer amount is Rs. $max_amount");
            }

            // Find receiver by phone or email
            $receiver = $this->findReceiver($receiver_identifier);
            if (!$receiver) {
                throw new Exception("User not found");
            }

            // Check not sending to self
            if ($receiver['id'] == $sender_id) {
                throw new Exception("Cannot send money to yourself");
            }

            // Check wallet not frozen
            if ($receiver['wallet_frozen'] ?? false) {
                throw new Exception("Recipient wallet is frozen");
            }

            // Check sufficient balance
            $sender_balance = $this->getBalance($sender_id);
            if ($sender_balance < $amount) {
                throw new Exception("Insufficient balance");
            }

            // Check sender wallet not frozen
            $sender = $this->userModel->getUserById($sender_id);
            if ($sender['wallet_frozen'] ?? false) {
                throw new Exception("Your wallet is frozen");
            }

            // Start atomic transaction
            $this->conn->beginTransaction();
            
            // Deduct from sender
            $sender_balance_before = $sender_balance;
            $this->userModel->updateWalletBalance($sender_id, -$amount);
            $sender_balance_after = $sender_balance - $amount;
            
            // Update total sent
            $this->userModel->updateTotalSent($sender_id, $amount);
            
            // Add to receiver
            $receiver_balance_before = $this->getBalance($receiver['id']);
            $this->userModel->updateWalletBalance($receiver['id'], $amount);
            $receiver_balance_after = $receiver_balance_before + $amount;
            
            // Update total received
            $this->userModel->updateTotalReceived($receiver['id'], $amount);
            
            // Create transaction record
            $txn_id = $this->transactionModel->create([
                'sender_id' => $sender_id,
                'receiver_id' => $receiver['id'],
                'amount' => $amount,
                'fee' => 0,
                'type' => 'send',
                'status' => 'completed',
                'reference_id' => $this->transactionModel->generateReferenceId(),
                'note' => $note,
                'sender_balance_before' => $sender_balance_before,
                'sender_balance_after' => $sender_balance_after,
                'receiver_balance_before' => $receiver_balance_before,
                'receiver_balance_after' => $receiver_balance_after,
                'ip_address' => $this->getClientIP()
            ]);
            
            // Commit
            $this->conn->commit();
            
            // Log activities
            $this->activityLog->log($sender_id, ActivityLog::ACTION_SEND_MONEY, 
                "Sent Rs. $amount to " . $receiver['name'], $this->getClientIP());
            $this->activityLog->log($receiver['id'], ActivityLog::ACTION_RECEIVE_MONEY, 
                "Received Rs. $amount from " . $sender['name'], $this->getClientIP());
            
            return [
                'success' => true, 
                'message' => 'Money sent successfully',
                'transaction_id' => $txn_id
            ];
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Admin adjust balance
    public function adjustBalance($admin_id, $user_id, $amount, $note = '') {
        try {
            if (empty($amount)) {
                throw new Exception("Amount is required");
            }

            $balance_before = $this->getBalance($user_id);
            
            $this->conn->beginTransaction();
            
            // Update balance
            $this->userModel->updateWalletBalance($user_id, $amount);
            
            $balance_after = $balance_before + $amount;
            
            // Create transaction
            $txn_type = $amount >= 0 ? 'admin_adjust' : 'admin_adjust';
            $this->transactionModel->create([
                'sender_id' => $admin_id,
                'receiver_id' => $user_id,
                'amount' => abs($amount),
                'fee' => 0,
                'type' => $txn_type,
                'status' => 'completed',
                'reference_id' => $this->transactionModel->generateReferenceId(),
                'note' => $note ?: ($amount >= 0 ? 'Credit adjustment' : 'Debit adjustment'),
                'sender_balance_before' => 0,
                'sender_balance_after' => 0,
                'receiver_balance_before' => $balance_before,
                'receiver_balance_after' => $balance_after,
                'ip_address' => $this->getClientIP()
            ]);
            
            $this->conn->commit();
            
            // Log activity
            $this->activityLog->log($admin_id, ActivityLog::ACTION_ADMIN_ADJUST, 
                "Adjusted balance for user ID $user_id by Rs. $amount", $this->getClientIP());
            
            return ['success' => true, 'message' => 'Balance adjusted successfully'];
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Freeze wallet
    public function freezeWallet($admin_id, $user_id) {
        try {
            $this->userModel->freezeWallet($user_id);
            
            $this->activityLog->log($admin_id, ActivityLog::ACTION_WALLET_FREEZE, 
                "Froze wallet for user ID $user_id", $this->getClientIP());
            
            return ['success' => true, 'message' => 'Wallet frozen successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Unfreeze wallet
    public function unfreezeWallet($admin_id, $user_id) {
        try {
            $this->userModel->unfreezeWallet($user_id);
            
            $this->activityLog->log($admin_id, ActivityLog::ACTION_WALLET_UNFREEZE, 
                "Unfroze wallet for user ID $user_id", $this->getClientIP());
            
            return ['success' => true, 'message' => 'Wallet unfrozen successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ==================== TRANSACTION HISTORY ====================
    
    public function getTransactionHistory($user_id, $limit = 20, $type = null, $status = null) {
        return $this->transactionModel->getUserTransactions($user_id, $limit, 0, $type, $status);
    }

    public function getAllTransactions($limit = 50, $filters = []) {
        return $this->transactionModel->getAll($limit, 0, $filters);
    }

    public function getTransactionById($id) {
        return $this->transactionModel->getById($id);
    }

    public function getStats($user_id) {
        return $this->transactionModel->getStats($user_id);
    }

    public function getRecentTransactions($user_id, $limit = 5) {
        return $this->transactionModel->getRecentTransactions($user_id, $limit);
    }

    public function getLastTransaction($user_id) {
        return $this->transactionModel->getLastTransaction($user_id);
    }

    // ==================== SECURITY ====================
    
    // Verify PIN
    public function verifyPIN($user_id, $pin) {
        $user = $this->userModel->getUserById($user_id);
        
        // Check if locked
        if ($user['pin_locked_until'] && strtotime($user['pin_locked_until']) > time()) {
            throw new Exception("PIN is locked. Try again later.");
        }
        
        // Verify PIN
        if (!password_verify($pin, $user['pin'])) {
            // Increment attempts
            $this->userModel->incrementPinAttempts($user_id);
            
            // Check if should lock
            $user = $this->userModel->getUserById($user_id);
            if ($user['pin_attempts'] >= 5) {
                $this->userModel->lockPin($user_id);
                throw new Exception("Too many wrong attempts. PIN locked for 30 minutes.");
            }
            
            $attempts_left = 5 - $user['pin_attempts'];
            throw new Exception("Wrong PIN. $attempts_left attempts remaining.");
        }
        
        // Reset attempts on success
        $this->userModel->resetPinAttempts($user_id);
        
        return true;
    }

    // Set PIN
    public function setPIN($user_id, $pin) {
        if (strlen($pin) != 4 || !is_numeric($pin)) {
            throw new Exception("PIN must be 4 digits");
        }
        
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
        $this->userModel->setPin($user_id, $hashed_pin);
        
        $this->activityLog->log($user_id, ActivityLog::ACTION_PIN_CHANGE, 
            "Transaction PIN set/changed", $this->getClientIP());
        
        return ['success' => true, 'message' => 'PIN set successfully'];
    }

    // ==================== HELPER FUNCTIONS ====================
    
    private function findReceiver($identifier) {
        // Try by phone
        $user = $this->userModel->findUserByPhone($identifier);
        if ($user) return $user;
        
        // Try by email
        return $this->userModel->findUserByEmail($identifier);
    }

    private function getSettings() {
        try {
            $query = "SELECT setting_key, setting_value FROM system_settings";
            $stmt = $this->conn->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        } catch (Exception $e) {
            return [];
        }
    }

    private function getClientIP() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return $ip;
    }
}
