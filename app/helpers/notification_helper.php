<?php
class NotificationHelper {
    public static function sendTransactionNotification($user_id, $type, $amount, $description) {
        // Mock email/SMS - replace with PHPMailer/Twilio in production
        $log = "Email/SMS to user $user_id: $type $amount - $description";
        error_log($log);
        return true;
    }
    
    public static function sendApprovalNotification($user_id, $type, $status) {
        $message = "Your $type request has been $status";
        self::sendTransactionNotification($user_id, $type, 0, $message);
    }
}
?>

