<?php
require_once __DIR__ . '/Model.php';

/**
 * Wallet Model
 * 
 * Financial core of NepalPay. All balance operations use
 * atomic transactions with row-level locking (FOR UPDATE).
 * 
 * SAFETY RULES:
 * 1. Never update balance without a transaction
 * 2. Always use FOR UPDATE when reading balance for modification
 * 3. Always log balance_adjustments for audit
 * 4. Never allow self-transfers
 */
class Wallet extends Model {
    protected $table = 'wallets';
    protected $fillable = ['user_id', 'balance', 'currency', 'wallet_number', 'is_active'];
    protected $primaryKey = 'id';

    public static function findByNumber($walletNumber) {
        return self::findBy('wallet_number', $walletNumber);
    }

    public static function findByUserId($userId) {
        return self::findBy('user_id', $userId);
    }

    public static function getBalance($userId) {
        $sql = "SELECT balance FROM wallets WHERE user_id = ?";
        $result = Database::fetch($sql, [$userId]);
        return $result['balance'] ?? 0;
    }

    public static function addMoney($userId, $amount, $description = 'Add money') {
        Database::beginTransaction();
        
        try {
            $sql = "UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND is_active = 1";
            Database::query($sql, [$amount, $userId]);
            
            $transactionId = generateTransactionId();
            
            $sql = "INSERT INTO transactions (transaction_id, receiver_id, amount, type, status, description) 
                   VALUES (?, ?, ?, 'add_money', 'completed', ?)";
            Database::query($sql, [$transactionId, $userId, $amount, $description]);
            
            Database::commit();
            return $transactionId;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Send money from one user to another
     * Optional $pinData: ['pin' => '1234'] for transaction verification
     */
    public static function sendMoney($senderId, $receiverId, $amount, $fee = 0, $pinData = null) {
        // Prevent self-transfer
        if ((int)$senderId === (int)$receiverId) {
            throw new Exception('Cannot send money to yourself');
        }
        
        // Verify transaction PIN if set
        if (class_exists('\NepalPay\Services\TransactionPinService') && 
            \NepalPay\Services\TransactionPinService::isRequired($senderId)) {
            if (!$pinData || !isset($pinData['pin'])) {
                throw new Exception('Transaction PIN required');
            }
            if (!\NepalPay\Services\TransactionPinService::verify($senderId, $pinData['pin'])) {
                $remaining = \NepalPay\Services\TransactionPinService::remainingAttempts($senderId);
                throw new Exception('Invalid PIN. ' . $remaining . ' attempts remaining.');
            }
        }
        
        Database::beginTransaction();
        
        try {
            $sql = "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE";
            $senderWallet = Database::fetch($sql, [$senderId]);
            
            if (!$senderWallet) {
                throw new Exception('Sender wallet not found or inactive');
            }
            
            if ($senderWallet['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $sql = "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE";
            $receiverWallet = Database::fetch($sql, [$receiverId]);
            
            if (!$receiverWallet) {
                throw new Exception('Receiver wallet not found or inactive');
            }
            
            $sql = "UPDATE wallets SET balance = balance - ? WHERE user_id = ?";
            Database::query($sql, [$amount, $senderId]);
            
            $sql = "UPDATE wallets SET balance = balance + ? WHERE user_id = ?";
            Database::query($sql, [$amount, $receiverId]);
            
            $transactionId = generateTransactionId();
            
            $sql = "INSERT INTO transactions (transaction_id, sender_id, receiver_id, amount, fee, type, status, description) 
                   VALUES (?, ?, ?, ?, ?, 'send', 'completed', 'Money sent')";
            Database::query($sql, [$transactionId, $senderId, $receiverId, $amount, $fee]);
            
            Database::commit();
            return $transactionId;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function withdraw($userId, $amount) {
        Database::beginTransaction();
        
        try {
            $sql = "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE";
            $wallet = Database::fetch($sql, [$userId]);
            
            if (!$wallet) {
                throw new Exception('Wallet not found or inactive');
            }
            
            if ($wallet['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $sql = "UPDATE wallets SET balance = balance - ? WHERE user_id = ?";
            Database::query($sql, [$amount, $userId]);
            
            $transactionId = generateTransactionId();
            
            $sql = "INSERT INTO transactions (transaction_id, sender_id, amount, type, status, description) 
                   VALUES (?, ?, ?, 'withdraw', 'completed', 'Withdrawn to bank')";
            Database::query($sql, [$transactionId, $userId, $amount]);
            
            Database::commit();
            return $transactionId;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function transferToBank($userId, $bankAccountId, $amount, $pinData = null) {
        // Verify transaction PIN if required
        if (class_exists('\NepalPay\Services\TransactionPinService') && 
            \NepalPay\Services\TransactionPinService::isRequired($userId)) {
            if (!$pinData || !isset($pinData['pin'])) {
                throw new Exception('Transaction PIN required');
            }
            if (!\NepalPay\Services\TransactionPinService::verify($userId, $pinData['pin'])) {
                $remaining = \NepalPay\Services\TransactionPinService::remainingAttempts($userId);
                throw new Exception('Invalid PIN. ' . $remaining . ' attempts remaining.');
            }
        }
        
        Database::beginTransaction();
        
        try {
            $sql = "SELECT * FROM wallets WHERE user_id = ? AND is_active = 1 FOR UPDATE";
            $wallet = Database::fetch($sql, [$userId]);
            
            if (!$wallet) {
                throw new Exception('Wallet not found or inactive');
            }
            
            if ($wallet['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $sql = "SELECT * FROM bank_accounts WHERE id = ? AND user_id = ?";
            $bankAccount = Database::fetch($sql, [$bankAccountId, $userId]);
            
            if (!$bankAccount) {
                throw new Exception('Bank account not found');
            }
            
            $sql = "UPDATE wallets SET balance = balance - ? WHERE user_id = ?";
            Database::query($sql, [$amount, $userId]);
            
            $transactionId = generateTransactionId();
            
            $sql = "INSERT INTO transactions (transaction_id, sender_id, amount, type, status, description) 
                   VALUES (?, ?, ?, 'withdraw', 'completed', ?)";
            $description = "Transferred to " . $bankAccount['bank_name'] . " ending " . substr($bankAccount['account_number'], -4);
            Database::query($sql, [$transactionId, $userId, $amount, $description]);
            
            Database::commit();
            return $transactionId;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function loadFromBank($userId, $bankAccountId, $amount) {
        Database::beginTransaction();
        
        try {
            $sql = "SELECT * FROM bank_accounts WHERE id = ? AND user_id = ?";
            $bankAccount = Database::fetch($sql, [$bankAccountId, $userId]);
            
            if (!$bankAccount) {
                throw new Exception('Bank account not found');
            }
            
            $sql = "UPDATE wallets SET balance = balance + ? WHERE user_id = ?";
            Database::query($sql, [$amount, $userId]);
            
            $transactionId = generateTransactionId();
            
            $sql = "INSERT INTO transactions (transaction_id, receiver_id, amount, type, status, description) 
                   VALUES (?, ?, ?, 'add_money', 'completed', ?)";
            $description = "Loaded from " . $bankAccount['bank_name'] . " ending " . substr($bankAccount['account_number'], -4);
            Database::query($sql, [$transactionId, $userId, $amount, $description]);
            
            Database::commit();
            return $transactionId;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function getTotalBalance() {
        $sql = "SELECT SUM(balance) as total FROM wallets WHERE is_active = 1";
        $result = Database::fetch($sql);
        return $result['total'] ?? 0;
    }

    public static function getUserTransactions($userId, $type = null, $limit = 10) {
        if ($type) {
            $sql = "SELECT * FROM transactions 
                    WHERE (sender_id = ? OR receiver_id = ?) AND type = ?
                    ORDER BY created_at DESC LIMIT ?";
            return Database::fetchAll($sql, [$userId, $userId, $type, $limit]);
        } else {
            $sql = "SELECT * FROM transactions 
                    WHERE sender_id = ? OR receiver_id = ?
                    ORDER BY created_at DESC LIMIT ?";
            return Database::fetchAll($sql, [$userId, $userId, $limit]);
        }
    }

    public static function getAnalytics($userId) {
        $sql = "SELECT 
                   SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as total_sent,
                   SUM(CASE WHEN receiver_id = ? THEN amount ELSE 0 END) as total_received
                FROM transactions 
                WHERE (sender_id = ? OR receiver_id = ?) 
                AND status = 'completed'";
        $result = Database::fetch($sql, [$userId, $userId, $userId, $userId]);
        
        $startOfMonth = date('Y-m-01 00:00:00');
        $sql = "SELECT 
                   SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as monthly_sent,
                   SUM(CASE WHEN receiver_id = ? THEN amount ELSE 0 END) as monthly_received
                FROM transactions 
                WHERE (sender_id = ? OR receiver_id = ?) 
                AND created_at >= ?
                AND status = 'completed'";
        $monthly = Database::fetch($sql, [$userId, $userId, $userId, $userId, $startOfMonth]);
        
        return [
            'total_sent' => $result['total_sent'] ?? 0,
            'total_received' => $result['total_received'] ?? 0,
            'monthly_sent' => $monthly['monthly_sent'] ?? 0,
            'monthly_received' => $monthly['monthly_received'] ?? 0
        ];
    }
}
