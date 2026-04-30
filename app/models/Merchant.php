<?php
require_once __DIR__ . '/Model.php';

class Merchant extends Model {
    protected $table = 'merchants';
    protected $fillable = ['user_id', 'business_name', 'business_type', 'business_address', 'merchant_code', 'qr_prefix', 'is_active'];
    protected $primaryKey = 'id';

    public static function findByUser($userId) {
        return self::findBy('user_id', $userId);
    }

    public static function findByCode($code) {
        return self::findBy('merchant_code', $code);
    }

    public static function createMerchant($data) {
        $data['merchant_code'] = generateMerchantCode();
        
        return self::create($data);
    }

    public static function generateQRString($merchantId) {
        $merchant = self::find($merchantId);
        
        if ($merchant) {
            return $merchant['qr_prefix'] . $merchant['merchant_code'];
        }
        
        return null;
    }

    public static function getQRString($userId) {
        $merchant = self::findByUser($userId);
        
        if ($merchant) {
            return $merchant['qr_prefix'] . $merchant['merchant_code'];
        }
        
        $user = User::find($userId);
        $wallet = Wallet::findByUserId($userId);
        return 'NP' . $wallet['wallet_number'];
    }

    public static function resolveQRString($qrString) {
        if (strpos($qrString, 'MER') === 0) {
            $code = substr($qrString, 3);
            return self::findByCode($code);
        } elseif (strpos($qrString, 'NP') === 0) {
            $walletNumber = substr($qrString, 2);
            return Wallet::findByNumber($walletNumber);
        }
        
        return null;
    }

    public static function getPayments($merchantId, $status = null) {
        $sql = "SELECT mp.*, t.amount, t.created_at, u.full_name as payer_name
                FROM merchant_payments mp
                JOIN transactions t ON mp.transaction_id = t.id
                LEFT JOIN users u ON t.sender_id = u.id
                WHERE mp.merchant_id = ?";
        
        $params = [$merchantId];
        
        if ($status) {
            $sql .= " AND mp.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY mp.created_at DESC";
        
        return Database::fetchAll($sql, $params);
    }

    public static function recordPayment($merchantId, $transactionId, $orderId, $amount) {
        $sql = "INSERT INTO merchant_payments (merchant_id, transaction_id, order_id, order_amount, payment_amount, status) 
               VALUES (?, ?, ?, ?, ?, 'completed')";
        
        return Database::query($sql, [$merchantId, $transactionId, $orderId, $amount, $amount]);
    }

    public static function getStats($merchantId) {
        $sql = "SELECT 
                   COUNT(*) as total_payments,
                   SUM(payment_amount) as total_amount
                FROM merchant_payments 
                WHERE merchant_id = ? AND status = 'completed'";
        
        $result = Database::fetch($sql, [$merchantId]);
        
        $sql = "SELECT 
                   COUNT(*) as today_payments,
                   SUM(payment_amount) as today_amount
                FROM merchant_payments 
                WHERE merchant_id = ? 
                AND status = 'completed'
                AND created_at >= ?";
        
        $today = date('Y-m-d 00:00:00');
        $todayStats = Database::fetch($sql, [$merchantId, $today]);
        
        return [
            'total_payments' => $result['total_payments'] ?? 0,
            'total_amount' => $result['total_amount'] ?? 0,
            'today_payments' => $todayStats['today_payments'] ?? 0,
            'today_amount' => $todayStats['today_amount'] ?? 0
        ];
    }

    public static function getAll($page = 1, $perPage = 20) {
        return self::paginate($page, $perPage);
    }

    public static function toggleActive($id) {
        $sql = "UPDATE merchants SET is_active = NOT is_active WHERE id = ?";
        return Database::query($sql, [$id]);
    }
}