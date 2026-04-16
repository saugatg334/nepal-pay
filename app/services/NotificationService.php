<?php
/**
 * Notification Service
 * Production-level notification system for user alerts
 * TRIGGERS ONLY ON: payment success, money received
 */
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    private $conn;
    
    // Notification types
    const TYPE_MONEY_SENT = 'money_sent';
    const TYPE_MONEY_RECEIVED = 'money_received';
    const TYPE_BILL_PAID = 'bill_paid';
    const TYPE_TOPUP = 'topup';
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_KYC = 'kyc';
    const TYPE_SECURITY = 'security';
    const TYPE_SYSTEM = 'system';
    
    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';
    
    // Status
    const STATUS_UNREAD = 'unread';
    const STATUS_READ = 'read';
    const STATUS_ARCHIVED = 'archived';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Create notification for REAL financial actions only
     * DO NOT call for: page visits, failed logins, UI actions
     */
    public function create($userId, $type, $title, $message, $data = null, $priority = self::PRIORITY_NORMAL) {
        try {
            $query = "INSERT INTO notifications 
                    (user_id, type, title, message, data, priority, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'unread', NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $userId, 
                $type, 
                $title, 
                $message, 
                json_encode($data ?? []),
                $priority
            ]);

            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Notification create error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Notify money sent - ONLY for successful transactions
     */
    public function notifyMoneySent($userId, $amount, $recipientName, $txnId) {
        return $this->create(
            $userId, 
            self::TYPE_MONEY_SENT, 
            'Money Sent',
            'Rs ' . number_format($amount, 2) . ' sent to ' . htmlspecialchars($recipientName),
            ['amount' => $amount, 'txn_id' => $txnId, 'recipient' => $recipientName],
            self::PRIORITY_NORMAL
        );
    }
    
    /**
     * Notify money received - ONLY for successful transactions
     */
    public function notifyMoneyReceived($userId, $amount, $senderName, $txnId) {
        return $this->create(
            $userId, 
            self::TYPE_MONEY_RECEIVED,
            'Money Received',
            'Rs ' . number_format($amount, 2) . ' received from ' . htmlspecialchars($senderName),
            ['amount' => $amount, 'txn_id' => $txnId, 'sender' => $senderName],
            self::PRIORITY_NORMAL
        );
    }
    
    /**
     * Notify bill payment - ONLY on success
     */
    public function notifyBillPaid($userId, $amount, $billType, $customerId, $txnId) {
        return $this->create(
            $userId, 
            self::TYPE_BILL_PAID,
            'Bill Payment Successful',
            'Rs ' . number_format($amount, 2) . ' paid for ' . ucfirst($billType) . ' (' . htmlspecialchars($customerId) . ')',
            ['amount' => $amount, 'bill_type' => $billType, 'customer_id' => $customerId, 'txn_id' => $txnId],
            self::PRIORITY_NORMAL
        );
    }
    
    /**
     * Notify topup - ONLY on success
     */
    public function notifyTopup($userId, $amount, $phoneNumber, $txnId) {
        return $this->create(
            $userId, 
            self::TYPE_TOPUP,
            'Top-up Successful',
            'Rs ' . number_format($amount, 2) . ' top-up to ' . htmlspecialchars($phoneNumber),
            ['amount' => $amount, 'phone' => $phoneNumber, 'txn_id' => $txnId],
            self::PRIORITY_NORMAL
        );
    }
    
    /**
     * Notify deposit - ONLY on success
     */
    public function notifyDeposit($userId, $amount, $txnId) {
        return $this->create(
            $userId, 
            self::TYPE_DEPOSIT,
            'Deposit Successful',
            'Rs ' . number_format($amount, 2) . ' deposited to your wallet',
            ['amount' => $amount, 'txn_id' => $txnId],
            self::PRIORITY_NORMAL
        );
    }
    
    /**
     * Notify withdrawal - ONLY on success
     */
    public function notifyWithdrawal($userId, $amount, $txnId) {
        return $this->create(
            $userId, 
            self::TYPE_WITHDRAWAL,
            'Withdrawal Successful',
            'Rs ' . number_format($amount, 2) . ' withdrawn from your wallet',
            ['amount' => $amount, 'txn_id' => $txnId],
            self::PRIORITY_NORMAL
        );
    }
    
    /**
     * Notify KYC status change
     */
    public function notifyKYC($userId, $status, $message) {
        $priority = ($status === 'rejected') ? self::PRIORITY_HIGH : self::PRIORITY_NORMAL;
        
        return $this->create(
            $userId, 
            self::TYPE_KYC,
            'KYC ' . ucfirst($status),
            $message,
            ['status' => $status],
            $priority
        );
    }
    
    /**
     * Notify security events (login from new device, etc)
     */
    public function notifySecurity($userId, $message, $priority = self::PRIORITY_HIGH) {
        return $this->create(
            $userId, 
            self::TYPE_SECURITY,
            'Security Alert',
            $message,
            [],
            $priority
        );
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $limit = 20, $offset = 0, $status = null) {
        try {
            $query = "SELECT * FROM notifications WHERE user_id = ?";
            $params = [$userId];
            
            if ($status) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        try {
            $query = "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Mark as read
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $query = "UPDATE notifications SET status = 'read', read_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Mark all as read
     */
    public function markAllAsRead($userId) {
        try {
            $query = "UPDATE notifications SET status = 'read', read_at = NOW() WHERE user_id = ? AND status = 'unread'";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Archive notification
     */
    public function archive($notificationId, $userId) {
        try {
            $query = "UPDATE notifications SET status = 'archived', archived_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$notificationId, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Delete old archived notifications
     */
    public function cleanupOldNotifications($days = 90) {
        try {
            $query = "DELETE FROM notifications 
                    WHERE status = 'archived' 
                    AND archived_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get notification by ID
     */
    public function getById($notificationId, $userId) {
        try {
            $query = "SELECT * FROM notifications WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$notificationId, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Send notification to multiple channels (SMS, Email) - placeholder
     */
    public function sendToChannel($notificationId, $channel) {
        // TODO: Integrate with SMS/Email providers
        // For now, just log
        error_log("Notification {$notificationId} queued for {$channel} delivery");
        return true;
    }
}
