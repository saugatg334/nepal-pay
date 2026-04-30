<?php
class EmailService {
    public static function sendOTP($user, $otp) {
        $subject = 'Your ' . APP_NAME . ' OTP Code';
        $message = "
<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;'>
    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; text-align: center; color: white;'>
        <h1 style='margin: 0; font-size: 28px;'>" . APP_NAME . "</h1>
        <p style='margin: 10px 0 0 0;'>Digital Wallet</p>
    </div>
    <div style='padding: 40px;'>
        <h2 style='color: #333;'>Your OTP Code</h2>
        <div style='background: #f8f9fa; border-left: 5px solid #667eea; padding: 20px; margin: 20px 0;'>
            <h1 style='font-size: 36px; letter-spacing: 8px; margin: 0; color: #667eea; font-weight: bold;'>{$otp}</h1>
        </div>
        <p>This OTP is valid for <strong>5 minutes</strong> only.</p>
        <p>If you didn't request this, please ignore this email.</p>
        <p style='margin-top: 30px; color: #666; font-size: 14px;'>
            © " . date('Y') . " " . APP_NAME . ". All rights reserved.
        </p>
    </div>
</body>
</html>";

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . Config::get('MAIL_FROM', APP_NAME . ' <noreply@' . parse_url(APP_URL, PHP_URL_HOST) . '>')
        ];

        if (Config::get('MAIL_HOST')) {
            return mail($user['email'], $subject, $message, implode("\r\n", $headers));
        }
        
        // Log for dev
        error_log("[OTP] To: {$user['email']} Code: {$otp}");
        return true;
    }
    
    public static function sendTransactionNotification($user, $type, $amount, $details) {
        $subject = APP_NAME . ' - Transaction Notification';
        $message = "
        <h2>New {$type} Transaction</h2>
        <p>Amount: NPR " . number_format($amount, 2) . "</p>
        <p>Details: " . htmlspecialchars($details) . "</p>";
        
        $headers = ['Content-type: text/html; charset=UTF-8'];
        mail($user['email'], $subject, $message, implode("\r\n", $headers));
    }
}
?>

