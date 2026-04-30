<?php
require_once __DIR__ . '/Model.php';

class Notification extends Model {
    protected $table = 'notifications';
    protected $fillable = ['user_id', 'title', 'message', 'type', 'is_read', 'data'];
    protected $primaryKey = 'id';

    public static function findByUser($userId, $limit = 20) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public static function findUnread($userId, $limit = 20) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public static function createNotification($userId, $title, $message, $type = 'system', $data = null) {
        $sql = "INSERT INTO notifications (user_id, title, message, type, data) VALUES (?, ?, ?, ?, ?)";
        
        $dataJson = $data ? json_encode($data) : null;
        
        return Database::query($sql, [$userId, $title, $message, $type, $dataJson]);
    }

    public static function markAsRead($id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
        return Database::query($sql, [$id]);
    }

    public static function markAllAsRead($userId) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        return Database::query($sql, [$userId]);
    }

    public static function deleteNotification($id, $userId) {
        $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
        return Database::query($sql, [$id, $userId]);
    }

    public static function getUnreadCount($userId) {
        $sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0";
        $result = Database::fetch($sql, [$userId]);
        return $result['total'] ?? 0;
    }

    public static function sendMoneyNotification($senderId, $receiverId, $amount, $transactionId) {
        $sender = User::find($senderId);
        $receiver = User::find($receiverId);
        
        $sql = "INSERT INTO notifications (user_id, title, message, type, data) VALUES (?, ?, ?, ?, ?)";
        
        $title = 'Money Sent';
        $message = "You have sent NPR " . number_format($amount, 2) . " to " . $receiver['full_name'];
        $data = json_encode(['transaction_id' => $transactionId, 'amount' => $amount]);
        
        Database::query($sql, [$senderId, $title, $message, 'transaction', $data]);
        
        $title = 'Money Received';
        $message = "You have received NPR " . number_format($amount, 2) . " from " . $sender['full_name'];
        
        Database::query($sql, [$receiverId, $title, $message, 'transaction', $data]);
    }

    public static function sendBillPaymentNotification($userId, $billType, $amount, $accountNo) {
        $billTypes = [
            'ntc' => 'NTC Mobile',
            'ncell' => 'Ncell',
            'nea' => 'NEA Electricity',
            'internet' => 'Internet'
        ];
        
        $billName = $billTypes[$billType] ?? $billType;
        
        $sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        
        $title = 'Bill Paid';
        $message = "Your {$billName} bill ({$accountNo}) of NPR " . number_format($amount, 2) . " has been paid successfully";
        
        Database::query($sql, [$userId, $title, $message, 'transaction']);
    }

    public static function sendSecurityNotification($userId, $message) {
        $sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        
        $title = 'Security Alert';
        Database::query($sql, [$userId, $title, $message, 'security']);
    }

    public static function sendAccountFreezeNotification($userId, $frozen) {
        $sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        
        $title = 'Account Status Changed';
        $message = $frozen 
            ? 'Your account has been frozen. Please contact support.' 
            : 'Your account has been unfrozen. You can now use all features.';
        
        Database::query($sql, [$userId, $title, $message, 'security']);
    }
}