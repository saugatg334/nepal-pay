<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use \Database;
use Exception;

/**
 * Alert Service
 * 
 * WHY: Security events require immediate attention.
 * This service provides out-of-band notification for critical events.
 * 
 * Notifications sent via:
 * - Email (PHP mail() with HTML templates)
 * - SMS (via configurable gateway: Twilio, generic REST)
 * - Webhook (HTTP POST to incident management system)
 * - Database logs (persistent audit trail)
 */
class AlertService
{
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    const CATEGORY_FRAUD = 'fraud';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_COMPLIANCE = 'compliance';
    const CATEGORY_OPERATIONAL = 'operational';

    /**
     * Send an alert with multi-channel notification
     */
    public static function send(
        string $severity,
        string $title,
        string $message,
        array $context = [],
        string $category = self::CATEGORY_SECURITY
    ): int {
        $alertId = 0;
        
        try {
            $sql = "INSERT INTO security_events 
                    (event_type, severity, description, request_data, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            Database::query($sql, [
                $category,
                $severity,
                $title . ': ' . $message,
                json_encode($context)
]);
            
            $alertId = (int) Database::lastInsertId();
            Logger::$logMethod('Alert triggered', [
                'alert_id' => $alertId,
                'severity' => $severity,
                'title' => $title,
                'category' => $category
            ]);
            
            if ($severity === self::SEVERITY_CRITICAL) {
                self::sendCriticalNotification($alertId, $title, $message, $context);
            } elseif ($severity === self::SEVERITY_HIGH) {
                self::sendHighSeverityNotification($alertId, $title, $message, $context);
            }
            
            return $alertId;
            
        } catch (Exception $e) {
            Logger::error('Failed to send alert', [
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Critical alerts: email + SMS + webhook
     */
    private static function sendCriticalNotification(
        int $alertId,
        string $title,
        string $message,
        array $context
    ): void {
        self::sendEmailNotification($alertId, $title, $message, $context, true);
        self::sendSmsNotification($alertId, $title, $context);
        self::sendWebhookNotification($alertId, $title, $message, $context);
    }
    
    /**
     * High severity: email only
     */
    private static function sendHighSeverityNotification(
        int $alertId,
        string $title,
        string $message,
        array $context
    ): void {
        self::sendEmailNotification($alertId, $title, $message, $context, false);
    }
    
    /**
     * Send email via PHP mail()
     */
    private static function sendEmailNotification(
        int $alertId,
        string $title,
        string $message,
        array $context,
        bool $critical
    ): void {
        try {
            $enabled = Config::getBool('ALERT_EMAIL_ENABLED', false);
            if (!$enabled) {
                return;
            }
            
            $to = Config::get('ALERT_EMAIL_RECIPIENTS', 'security@nepalpay.com');
            $subject = ($critical ? '[CRITICAL] ' : '[ALERT] ') . $title;
            $body = self::buildEmailTemplate($title, $message, $context, $alertId);
            
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . Config::get('ALERT_EMAIL_FROM', 'NepalPay Security <security@nepalpay.com>'),
                'X-Priority: ' . ($critical ? '1' : '3'),
                'X-Mailer: NepalPay-AlertService/1.0'
            ];
            
            $sent = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($sent) {
                Logger::info('Email alert sent', [
                    'alert_id' => $alertId,
                    'to' => $to,
                    'subject' => $subject
                ]);
            } else {
                Logger::error('Email alert failed (mail() returned false)', [
                    'alert_id' => $alertId,
                    'to' => $to
                ]);
            }
            
        } catch (Exception $e) {
            Logger::error('Failed to send email notification', [
                'alert_id' => $alertId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send SMS via configured gateway
     */
    private static function sendSmsNotification(
        int $alertId,
        string $title,
        array $context
    ): void {
        try {
            $enabled = Config::getBool('ALERT_SMS_ENABLED', false);
            if (!$enabled) {
                return;
            }
            
            $phoneNumbers = Config::get('ALERT_SMS_NUMBERS', '');
            if (empty($phoneNumbers)) {
                return;
            }
            
            $gateway = Config::get('ALERT_SMS_GATEWAY', 'generic');
            $text = substr('[NepalPay] ' . $title . ': ' . ($context['reason'] ?? 'Check dashboard'), 0, 160);
            $numbers = array_map('trim', explode(',', $phoneNumbers));
            
            foreach ($numbers as $number) {
                if (empty($number)) {
                    continue;
                }
                
                $sent = self::sendViaSmsGateway($number, $text, $gateway);
                
                if ($sent) {
                    Logger::info('SMS alert sent', [
                        'alert_id' => $alertId,
                        'to' => $number,
                        'gateway' => $gateway
                    ]);
                } else {
                    Logger::error('SMS alert failed', [
                        'alert_id' => $alertId,
                        'to' => $number
                    ]);
                }
            }
            
        } catch (Exception $e) {
            Logger::error('Failed to send SMS notification', [
                'alert_id' => $alertId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send webhook via HTTP POST
     */
    private static function sendWebhookNotification(
        int $alertId,
        string $title,
        string $message,
        array $context
    ): void {
        try {
            $webhookUrl = Config::get('ALERT_WEBHOOK_URL', '');
            if (empty($webhookUrl)) {
                return;
            }
            
            $payload = [
                'alert_id' => $alertId,
                'title' => $title,
                'message' => $message,
                'context' => $context,
                'timestamp' => time(),
                'source' => 'nepalpay-security',
                'version' => '1.0'
            ];
            
            $jsonPayload = json_encode($payload);
            
            $ch = curl_init($webhookUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonPayload),
                    'X-NepalPay-Alert: ' . $alertId,
                    'User-Agent: NepalPay-AlertService/1.0'
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                Logger::error('Webhook alert cURL error', [
                    'alert_id' => $alertId,
                    'error' => $curlError
                ]);
            } elseif ($httpCode >= 200 && $httpCode < 300) {
                Logger::info('Webhook alert delivered', [
                    'alert_id' => $alertId,
                    'url' => $webhookUrl,
                    'http_code' => $httpCode
                ]);
            } else {
                Logger::error('Webhook alert failed', [
                    'alert_id' => $alertId,
                    'url' => $webhookUrl,
                    'http_code' => $httpCode,
                    'response' => substr((string)$response, 0, 500)
                ]);
            }
            
        } catch (Exception $e) {
            Logger::error('Failed to send webhook notification', [
                'alert_id' => $alertId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Gateway-specific SMS dispatch
     */
    private static function sendViaSmsGateway(string $number, string $text, string $gateway): bool
    {
        $apiKey = Config::get('SMS_API_KEY', '');
        $apiSecret = Config::get('SMS_API_SECRET', '');
        
        switch ($gateway) {
            case 'twilio':
                $sid = Config::get('TWILIO_SID', $apiKey);
                $token = Config::get('TWILIO_TOKEN', $apiSecret);
                $from = Config::get('TWILIO_FROM_NUMBER', '');
                
                if (empty($sid) || empty($token) || empty($from)) {
                    return false;
                }
                
                $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query([
                        'From' => $from,
                        'To' => $number,
                        'Body' => $text
                    ]),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_USERPWD => "{$sid}:{$token}",
                    CURLOPT_SSL_VERIFYPEER => true
                ]);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                return $httpCode === 201;
                
            case 'generic':
            default:
                $genericUrl = Config::get('SMS_GENERIC_URL', '');
                if (empty($genericUrl)) {
                    return false;
                }
                
                $ch = curl_init($genericUrl);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query([
                        'to' => $number,
                        'message' => $text,
                        'api_key' => $apiKey
                    ]),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => true
                ]);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                return $httpCode >= 200 && $httpCode < 300;
        }
    }
    
    /**
     * Build HTML email template
     */
    private static function buildEmailTemplate(
        string $title,
        string $message,
        array $context,
        int $alertId
    ): string {
        $contextHtml = '';
        foreach ($context as $key => $value) {
            $contextHtml .= sprintf(
                '<tr><td><strong>%s</strong></td><td>%s</td></tr>',
                htmlspecialchars((string)$key),
                htmlspecialchars(print_r($value, true))
            );
        }
        
        return sprintf('
            <html>
            <head><title>%s</title></head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; border: 1px solid #ddd;">
                    <div style="background: #d32f2f; color: white; padding: 20px;">
                        <h2 style="margin: 0;">%s</h2>
                    </div>
                    <div style="padding: 20px;">
                        <p>%s</p>
                        <p><strong>Alert ID:</strong> %d</p>
                        <p><strong>Time:</strong> %s</p>
                        %s
                    </div>
                    <div style="background: #f5f5f5; padding: 15px; font-size: 12px; color: #666;">
                        <p>This is an automated security alert from NepalPay.</p>
                        <p>Please do not reply to this email.</p>
                    </div>
            </body>
            </html>
        ',
            htmlspecialchars($title),
            htmlspecialchars($title),
            htmlspecialchars($message),
            $alertId,
            date('Y-m-d H:i:s T'),
            $contextHtml ? '<h3>Context:</h3><table style="border-collapse: collapse; width: 100%;">' . $contextHtml . '</table>' : ''
        );
    }
    
    /**
     * Trigger fraud alert
     */
    public static function triggerFraudAlert(
        int $userId,
        string $transactionId,
        string $reason,
        string $riskLevel,
        float $amount
    ): int {
        $title = sprintf('Fraud Alert - User #%d', $userId);
        $message = sprintf(
            'Suspicious transaction detected for user %d. Transaction: %s, Amount: NPR %.2f, Risk: %s, Reason: %s',
            $userId,
            $transactionId,
            $amount,
            $riskLevel,
            $reason
        );
        
        $severity = ($riskLevel === 'critical') ? self::SEVERITY_CRITICAL : self::SEVERITY_HIGH;
        
        return self::send(
            $severity,
            $title,
            $message,
            [
                'user_id' => $userId,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'risk_level' => $riskLevel,
                'reason' => $reason
            ],
            self::CATEGORY_FRAUD
        );
    }
    
    /**
     * Trigger security alert
     */
    public static function triggerSecurityAlert(
        string $eventType,
        string $description,
        array $context = []
    ): int {
        $severity = $context['severity'] ?? self::SEVERITY_HIGH;
        $title = sprintf('Security Alert - %s', $eventType);
        
        return self::send(
            $severity,
            $title,
            $description,
            $context,
            self::CATEGORY_SECURITY
        );
    }
    
    /**
     * Trigger reconciliation mismatch alert
     */
    public static function triggerReconciliationAlert(
        int $affectedUserId,
        float $expectedBalance,
        float $actualBalance,
        string $details
    ): int {
        $title = sprintf('Reconciliation Mismatch - User #%d', $affectedUserId);
        $message = sprintf(
            'Balance mismatch detected. Expected: NPR %.2f, Actual: NPR %.2f, Difference: NPR %.2f. %s',
            $expectedBalance,
            $actualBalance,
            abs($expectedBalance - $actualBalance),
            $details
        );
        
        return self::send(
            self::SEVERITY_CRITICAL,
            $title,
            $message,
            [
                'user_id' => $affectedUserId,
                'expected_balance' => $expectedBalance,
                'actual_balance' => $actualBalance,
                'difference' => abs($expectedBalance - $actualBalance),
                'details' => $details
            ],
            self::CATEGORY_COMPLIANCE
        );
    }
    
    /**
     * Trigger backup failure alert
     */
    public static function triggerBackupAlert(string $backupType, string $errorMessage): int
    {
        $title = sprintf('Backup Failure - %s', $backupType);
        $message = sprintf('Automated backup failed: %s', $errorMessage);
        
        return self::send(
            self::SEVERITY_HIGH,
            $title,
            $message,
            [
                'backup_type' => $backupType,
                'error' => $errorMessage,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            self::CATEGORY_OPERATIONAL
        );
    }
    
    /**
     * Get alert statistics
     */
    public static function getStatistics(array $filters = []): array
    {
        try {
            $where = [];
            $params = [];
            
            if (!empty($filters['severity'])) {
                $where[] = 'severity = ?';
                $params[] = $filters['severity'];
            }
            
            if (!empty($filters['category'])) {
                $where[] = 'event_type = ?';
                $params[] = $filters['category'];
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "SELECT 
                    severity,
                    COUNT(*) as count,
                    SUM(CASE WHEN handled = 0 THEN 1 ELSE 0 END) as pending
                    FROM security_events {$whereClause}
                    GROUP BY severity";
            
            $result = Database::fetchAll($sql, $params);
            
            $stats = [
                'total' => 0,
                'pending' => 0,
                'by_severity' => [],
                'by_category' => []
            ];
            
            foreach ($result as $row) {
                $stats['total'] += (int)$row['count'];
                $stats['pending'] += (int)$row['pending'];
                $stats['by_severity'][$row['severity']] = [
                    'count' => (int)$row['count'],
                    'pending' => (int)$row['pending']
                ];
            }
            
            $sql = "SELECT COUNT(*) as count, DATEDIFF(NOW(), MIN(created_at)) as days FROM security_events";
            $rate = Database::fetch($sql);
            
            $stats['alerts_per_day'] = ($rate['days'] > 0) ? round($rate['count'] / $rate['days'], 2) : 0;
            
            return $stats;
            
        } catch (Exception $e) {
            Logger::error('Failed to get alert statistics', [
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'pending' => 0,
                'by_severity' => [],
                'by_category' => [],
                'alerts_per_day' => 0
            ];
        }
    }
}
