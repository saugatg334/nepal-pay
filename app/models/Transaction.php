<?php
require_once __DIR__ . '/Model.php';

class Transaction extends Model {
    protected $table = 'transactions';
    protected $fillable = ['transaction_id', 'sender_id', 'receiver_id', 'amount', 'fee', 'type', 'status', 'description', 'reference_id'];
    protected $primaryKey = 'id';

    public static function findByTransactionId($transactionId) {
        return self::findBy('transaction_id', $transactionId);
    }

    public static function createTransaction($data) {
        $data['transaction_id'] = $data['transaction_id'] ?? generateTransactionId();
        $data['status'] = $data['status'] ?? 'pending';
        
        return self::create($data);
    }

    public static function completeTransaction($transactionId) {
        $sql = "UPDATE transactions SET status = 'completed', updated_at = NOW() WHERE transaction_id = ?";
        return Database::query($sql, [$transactionId]);
    }

    public static function failTransaction($transactionId, $reason = '') {
        $sql = "UPDATE transactions SET status = 'failed', description = ? WHERE transaction_id = ?";
        return Database::query($sql, [$reason, $transactionId]);
    }

    public static function cancelTransaction($transactionId) {
        $sql = "UPDATE transactions SET status = 'cancelled', updated_at = NOW() WHERE transaction_id = ?";
        return Database::query($sql, [$transactionId]);
    }

    public static function getHistory($userId, $filter = 'all', $page = 1, $perPage = 20) {
        // Ensure page is at least 1
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        
        $conditions = [];
        $params = [];
        
        $today = date('Y-m-d 00:00:00');
        $weekAgo = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $monthAgo = date('Y-m-01 00:00:00');
        
        switch ($filter) {
            case 'today':
                $conditions[] = "(sender_id = ? OR receiver_id = ?) AND created_at >= ?";
                $params = [$userId, $userId, $today];
                break;
            case 'week':
                $conditions[] = "(sender_id = ? OR receiver_id = ?) AND created_at >= ?";
                $params = [$userId, $userId, $weekAgo];
                break;
            case 'month':
                $conditions[] = "(sender_id = ? OR receiver_id = ?) AND created_at >= ?";
                $params = [$userId, $userId, $monthAgo];
                break;
            default:
                $conditions[] = "(sender_id = ? OR receiver_id = ?)";
                $params = [$userId, $userId];
        }
        
        return self::paginate($page, $perPage, $conditions, $params);
    }

    public static function getUserTransactions($userId, $limit = 50) {
        $sql = "SELECT * FROM transactions 
                WHERE sender_id = ? OR receiver_id = ?
                ORDER BY created_at DESC 
                LIMIT ?";
        return Database::fetchAll($sql, [$userId, $userId, $limit]);
    }

    public static function getSentTransactions($userId, $limit = 50) {
        $sql = "SELECT * FROM transactions 
                WHERE sender_id = ?
                ORDER BY created_at DESC 
                LIMIT ?";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public static function getReceivedTransactions($userId, $limit = 50) {
        $sql = "SELECT * FROM transactions 
                WHERE receiver_id = ?
                ORDER BY created_at DESC 
                LIMIT ?";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public static function getStats($userId, $period = 'all') {
        $conditions = [];
        $params = [];
        
        if ($period !== 'all') {
            $today = date('Y-m-d 00:00:00');
            
            switch ($period) {
                case 'today':
                    $conditions[] = "created_at >= ?";
                    $params[] = $today;
                    break;
                case 'week':
                    $conditions[] = "created_at >= ?";
                    $params[] = date('Y-m-d 00:00:00', strtotime('-7 days'));
                    break;
                case 'month':
                    $conditions[] = "created_at >= ?";
                    $params[] = date('Y-m-01 00:00:00');
                    break;
            }
        }
        
        $conditions[] = "(sender_id = ? OR receiver_id = ?)";
        $params[] = $userId;
        $params[] = $userId;
        
        $conditions[] = "status = 'completed'";
        
        $where = ' WHERE ' . implode(' AND ', $conditions);
        
        $sql = "SELECT 
                   COUNT(*) as total_transactions,
                   SUM(CASE WHEN sender_id = ? THEN amount ELSE 0 END) as total_sent,
                   SUM(CASE WHEN receiver_id = ? THEN amount ELSE 0 END) as total_received,
                   SUM(CASE WHEN type = 'send' THEN amount ELSE 0 END) as send_count,
                   SUM(CASE WHEN type = 'receive' THEN amount ELSE 0 END) as receive_count
                FROM transactions" . $where;
        
        return Database::fetch($sql, $params);
    }

    public static function getAllTransactions($page = 1, $perPage = 20) {
        return self::paginate($page, $perPage);
    }

    public static function getPendingTransactions() {
        $sql = "SELECT * FROM transactions WHERE status = 'pending' ORDER BY created_at ASC";
        return Database::fetchAll($sql);
    }

    public static function getDailyStats($days = 30) {
        $sql = "SELECT 
                   DATE(created_at) as date,
                   COUNT(*) as count,
                   SUM(CASE WHEN type = 'send' THEN amount ELSE 0 END) as sent,
                   SUM(CASE WHEN type = 'receive' THEN amount ELSE 0 END) as received,
                   SUM(CASE WHEN type = 'add_money' THEN amount ELSE 0 END) as added
                FROM transactions 
                WHERE created_at >= ? 
                AND status = 'completed'
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        return Database::fetchAll($sql, [date('Y-m-d', strtotime("-{$days} days"))]);
    }

    public static function getMonthlyStats($year = null) {
        $year = $year ?? date('Y');
        
        $sql = "SELECT 
                   MONTH(created_at) as month,
                   COUNT(*) as count,
                   SUM(amount) as total
                FROM transactions 
                WHERE YEAR(created_at) = ?
                AND status = 'completed'
                GROUP BY MONTH(created_at)
                ORDER BY month ASC";
        
        return Database::fetchAll($sql, [$year]);
    }
}
