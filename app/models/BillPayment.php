<?php
require_once __DIR__ . '/Model.php';

class BillPayment extends Model {
    protected $table = 'bill_payments';
    protected $fillable = ['user_id', 'bill_type', 'bill_number', 'amount', 'status', 'transaction_id'];
    protected $primaryKey = 'id';

    public static function findByUser($userId, $limit = 20) {
        $sql = "SELECT * FROM bill_payments WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public static function payNTC($userId, $phoneNumber, $amount) {
        return self::processPayment($userId, 'ntc', $phoneNumber, $amount);
    }

    public static function payNcell($userId, $phoneNumber, $amount) {
        return self::processPayment($userId, 'ncell', $phoneNumber, $amount);
    }

    public static function payNEA($userId, $consumerNumber, $amount) {
        return self::processPayment($userId, 'nea', $consumerNumber, $amount);
    }

    public static function payInternet($userId, $accountNumber, $amount, $provider) {
        return self::processPayment($userId, $provider, $accountNumber, $amount);
    }

    private static function processPayment($userId, $billType, $billNumber, $amount) {
        Database::beginTransaction();
        
        try {
            $wallet = Wallet::findByUserId($userId);
            
            if ($wallet['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            $validation = self::validateBill($billType, $billNumber, $amount);
            
            if ($validation['status'] === 'failed') {
                throw new Exception($validation['message']);
            }
            
            $sql = "UPDATE wallets SET balance = balance - ? WHERE user_id = ?";
            Database::query($sql, [$amount, $userId]);
            
            $transactionId = generateTransactionId();
            
            $sql = "INSERT INTO transactions (transaction_id, sender_id, amount, type, status, description) 
                   VALUES (?, ?, ?, 'bill_payment', 'completed', ?)";
            $description = ucfirst($billType) . ' bill payment for ' . $billNumber;
            Database::query($sql, [$transactionId, $userId, $amount, $description]);
            
            $sql = "INSERT INTO bill_payments (user_id, bill_type, bill_number, amount, status, transaction_id) 
                   VALUES (?, ?, ?, ?, 'completed', ?)";
            Database::query($sql, [$userId, $billType, $billNumber, $amount, $transactionId]);
            
            Notification::sendBillPaymentNotification($userId, $billType, $amount, $billNumber);
            
            Database::commit();
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'message' => 'Payment successful'
            ];
        } catch (Exception $e) {
            Database::rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private static function validateBill($billType, $billNumber, $amount) {
        switch ($billType) {
            case 'ntc':
            case 'ncell':
                if (!preg_match('/^9[78]\d{8}$/', $billNumber)) {
                    return ['status' => 'failed', 'message' => 'Invalid phone number'];
                }
                if ($amount < 10 || $amount > 5000) {
                    return ['status' => 'failed', 'message' => 'Amount must be between NPR 10 and 5000'];
                }
                break;
                
            case 'nea':
                if (!preg_match('/^\d{9,11}$/', $billNumber)) {
                    return ['status' => 'failed', 'message' => 'Invalid consumer number'];
                }
                if ($amount < 50 || $amount > 50000) {
                    return ['status' => 'failed', 'message' => 'Amount must be between NPR 50 and 50000'];
                }
                break;
                
            case 'internet':
                if (empty($billNumber)) {
                    return ['status' => 'failed', 'message' => 'Invalid account number'];
                }
                if ($amount < 500 || $amount > 10000) {
                    return ['status' => 'failed', 'message' => 'Amount must be between NPR 500 and 10000'];
                }
                break;
                
            default:
                return ['status' => 'failed', 'message' => 'Invalid bill type'];
        }
        
        return ['status' => 'success'];
    }

    public static function getHistory($userId) {
        $sql = "SELECT * FROM bill_payments WHERE user_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    public static function getBillTypes() {
        return [
            'ntc' => ['name' => 'NTC Mobile', 'min' => 10, 'max' => 5000, 'pattern' => '^9[78]\d{8}$'],
            'ncell' => ['name' => 'Ncell', 'min' => 10, 'max' => 5000, 'pattern' => '^9[78]\d{8}$'],
            'nea' => ['name' => 'NEA Electricity', 'min' => 50, 'max' => 50000, 'pattern' => '^\d{9,11}$'],
            'internet' => ['name' => 'Internet', 'min' => 500, 'max' => 10000, 'pattern' => '.+']
        ];
    }
}