<?php
/**
 * Email Service
 * Sends OTP and transaction notifications via email
 * Uses PHP mail() - can be upgraded to SMTP/PHPMailer for production
 */
require_once __DIR__ . '/../config/env.php';

class EmailService {
    private $fromEmail = 'noreply@nepalpay.local';
    private $fromName = 'NepalPay';

    /**
     * Send OTP email
     */
    public function sendOTP($email, $otp, $type = 'login') {
        $subject = 'NepalPay - Your Verification Code';
        
        if ($type === 'register') {
            $subject = 'NepalPay - Complete Your Registration';
        } elseif ($type === 'transaction') {
            $subject = 'NepalPay - Transaction Verification';
        } elseif ($type === 'device_verify') {
            $subject = 'NepalPay - New Device Verification';
        }

        $body = $this->getOTPTemplate($otp, $type);

        return $this->send($email, $subject, $body);
    }

    /**
     * Send transaction confirmation
     */
    public function sendTransactionConfirmation($email, $txnDetails) {
        $subject = 'NepalPay - Transaction Confirmed';
        $body = $this->getTransactionTemplate($txnDetails);

        return $this->send($email, $subject, $body);
    }

    /**
     * Send notification email
     */
    public function sendNotification($email, $title, $message) {
        $subject = 'NepalPay - ' . $title;
        $body = $this->getNotificationTemplate($title, $message);

        return $this->send($email, $subject, $body);
    }

    /**
     * Send email
     */
    private function send($to, $subject, $body) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: NepalPay <" . $this->fromEmail . ">\r\n";
        $headers .= "X-Mailer: NepalPay/1.0\r\n";

        $to = filter_var($to, FILTER_VALIDATE_EMAIL);
        if (!$to) {
            error_log("Invalid email: {$to}");
            return false;
        }

        $result = mail($to, $subject, $body, $headers);
        
        if (!$result) {
            error_log("Email send failed to: {$to}");
        }
        
        return $result;
    }

    /**
     * OTP Email Template
     */
    private function getOTPTemplate($otp, $type) {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;">
    <div style="max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="color: #2563eb; margin: 0;">NepalPay</h1>
        </div>
        
        <p style="color: #333333; font-size: 16px;">Your verification code is:</p>
        
        <div style="background: #2563eb; color: #ffffff; font-size: 32px; font-weight: bold; text-align: center; padding: 15px; border-radius: 8px; letter-spacing: 8px; margin: 20px 0;">
            {$otp}
        </div>
        
        <p style="color: #666666; font-size: 14px;">This code expires in 5 minutes.</p>
        
        <hr style="border: none; border-top: 1px solid #e5e5e5; margin: 20px 0;">
        
        <p style="color: #999999; font-size: 12px;">
            If you didn't request this code, please ignore this email or contact support if suspicious.
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Transaction Confirmation Template
     */
    private function getTransactionTemplate($details) {
        $type = isset($details['type']) ? htmlspecialchars($details['type']) : 'Transaction';
        $amount = number_format(isset($details['amount']) ? $details['amount'] : 0, 2);
        $txnId = isset($details['txn_id']) ? htmlspecialchars($details['txn_id']) : '';
        $date = date('F d, Y h:i A');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;">
    <div style="max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="color: #2563eb; margin: 0;">NepalPay</h1>
        </div>
        
        <div style="background: #dcfce7; color: #166534; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
            <strong style="font-size: 18px;">Transaction Successful</strong>
        </div>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e5e5; color: #666666;">Type</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e5e5; text-align: right;">{$type}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e5e5; color: #666666;">Amount</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e5e5; text-align: right; font-weight: bold;">Rs {$amount}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e5e5; color: #666666;">Transaction ID</td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e5e5; text-align: right;">{$txnId}</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #666666;">Date</td>
                <td style="padding: 10px 0; text-align: right;">{$date}</td>
            </tr>
        </table>
        
        <p style="margin-top: 20px; color: #666666; font-size: 14px;">
            Thank you for using NepalPay!
        </p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Notification Template
     */
    private function getNotificationTemplate($title, $message) {
        $title = htmlspecialchars($title);
        $message = htmlspecialchars($message);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;">
    <div style="max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="color: #333333; margin: 0 0 15px 0;">{$title}</h2>
        <p style="color: #666666;">{$message}</p>
    </div>
</body>
</html>
HTML;
    }
}