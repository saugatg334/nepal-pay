<?php
/**
 * Job Queue System
 * Database-based async job processing for emails, notifications, alerts
 */
require_once __DIR__ . '/../config/database.php';

class JobQueue {
    private $conn;
    
    const PRIORITY_HIGH = 1;
    const PRIORITY_NORMAL = 5;
    const PRIORITY_LOW = 10;
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRY = 'retry';
    
    const MAX_RETRIES = 3;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Add a job to the queue
     */
    public function enqueue($jobType, $payload, $options = []) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO job_queue 
                (job_type, payload, priority, scheduled_at, retry_count, status, created_at)
                VALUES (?, ?, ?, ?, 0, 'pending', NOW())
            ");
            
            $priority = $options['priority'] ?? self::PRIORITY_NORMAL;
            $scheduledAt = $options['scheduled_at'] ?? null;
            $payloadJson = json_encode($payload);
            
            $stmt->execute([
                $jobType,
                $payloadJson,
                $priority,
                $scheduledAt
            ]);
            
            return [
                'success' => true,
                'job_id' => $this->conn->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("JobQueue enqueue error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Schedule a job for later
     */
    public function schedule($jobType, $payload, $delaySeconds, $options = []) {
        $options['scheduled_at'] = date('Y-m-d H:i:s', time() + $delaySeconds);
        return $this->enqueue($jobType, $payload, $options);
    }
    
    /**
     * Get next pending job
     */
    public function dequeue($workerId, $lockTimeout = 300) {
        try {
            $this->conn->beginTransaction();
            
            // Get next pending job
            $stmt = $this->conn->prepare("
                SELECT * FROM job_queue 
                WHERE status = 'pending' 
                AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                ORDER BY priority ASC, created_at ASC
                FOR UPDATE SKIP LOCKED
                LIMIT 1
            ");
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$job) {
                $this->conn->rollBack();
                return null;
            }
            
            // Mark as processing
            $stmt = $this->conn->prepare("
                UPDATE job_queue 
                SET status = 'processing', worker_id = ?, started_at = NOW(), lock_expires = DATE_ADD(NOW(), INTERVAL ? SECOND)
                WHERE id = ?
            ");
            $stmt->execute([$workerId, $lockTimeout, $job['id']]);
            
            $this->conn->commit();
            
            $job['payload'] = json_decode($job['payload'], true);
            return $job;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("JobQueue dequeue error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mark job as completed
     */
    public function complete($jobId, $result = null) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE job_queue 
                SET status = 'completed', completed_at = NOW(), result = ?
                WHERE id = ?
            ");
            $stmt->execute([json_encode($result), $jobId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Mark job as failed with retry logic
     */
    public function fail($jobId, $error, $shouldRetry = true) {
        try {
            $stmt = $this->conn->prepare("
                SELECT retry_count FROM job_queue WHERE id = ?
            ");
            $stmt->execute([$jobId]);
            $retryCount = (int) $stmt->fetchColumn();
            
            if ($shouldRetry && $retryCount < self::MAX_RETRIES) {
                // Schedule retry with exponential backoff
                $delay = pow(2, $retryCount) * 30; // 30s, 60s, 120s
                $scheduledAt = date('Y-m-d H:i:s', time() + $delay);
                
                $stmt = $this->conn->prepare("
                    UPDATE job_queue 
                    SET status = 'pending', 
                        retry_count = retry_count + 1, 
                        last_error = ?,
                        scheduled_at = ?,
                        worker_id = NULL,
                        started_at = NULL,
                        lock_expires = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$error, $scheduledAt, $jobId]);
            } else {
                // Mark as permanently failed
                $stmt = $this->conn->prepare("
                    UPDATE job_queue 
                    SET status = 'failed', last_error = ?, completed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$error, $jobId]);
                
                // Log critical failure
                $this->logCriticalFailure($jobId, $error);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("JobQueue fail error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get queue statistics
     */
    public function getStats() {
        try {
            $stats = [];
            
            $stmt = $this->conn->query("
                SELECT status, COUNT(*) as cnt 
                FROM job_queue 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY status
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats[$row['status']] = $row['cnt'];
            }
            
            $stmt = $this->conn->query("SELECT COUNT(*) as cnt FROM job_queue WHERE status = 'pending'");
            $stats['pending_now'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Clean up old completed/failed jobs
     */
    public function cleanup($days = 7) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM job_queue 
                WHERE status IN ('completed', 'failed') 
                AND completed_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Log critical job failure for admin alert
     */
    private function logCriticalFailure($jobId, $error) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_alerts 
                (alert_type, severity, message, data, created_at)
                VALUES ('job_failure', 'critical', ?, ?, NOW())
            ");
            $stmt->execute([
                "Job {$jobId} failed after max retries",
                json_encode(['job_id' => $jobId, 'error' => $error])
            ]);
        } catch (PDOException $e) {
            error_log("Critical failure log error: " . $e->getMessage());
        }
    }
}

/**
 * Job Types - Define handlers for each job type
 */
class JobHandlers {
    
    /**
     * Send Email Job
     */
    public static function sendEmail($payload) {
        $to = $payload['to'] ?? '';
        $subject = $payload['subject'] ?? '';
        $body = $payload['body'] ?? '';
        $template = $payload['template'] ?? null;
        
        if (!$to || !$subject) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }
        
        // In production, integrate with email provider (SendGrid, Mailgun, etc.)
        // For now, log and simulate success
        error_log("EMAIL to: {$to}, subject: {$subject}");
        
        return [
            'success' => true,
            'sent_to' => $to,
            'sent_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Send SMS Job
     */
    public static function sendSMS($payload) {
        $to = $payload['to'] ?? '';
        $message = $payload['message'] ?? '';
        
        if (!$to || !$message) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }
        
        // In production, integrate with SMS provider (Twilio, etc.)
        error_log("SMS to: {$to}, message: {$message}");
        
        return [
            'success' => true,
            'sent_to' => $to,
            'sent_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Send Push Notification Job
     */
    public static function sendPushNotification($payload) {
        $userId = $payload['user_id'] ?? null;
        $title = $payload['title'] ?? '';
        $body = $payload['body'] ?? '';
        
        if (!$userId || !$title) {
            return ['success' => false, 'error' => 'Missing required fields'];
        }
        
        // In production, integrate with FCM
        error_log("PUSH to user {$userId}: {$title}");
        
        return [
            'success' => true,
            'sent_to' => $userId,
            'sent_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Fraud Alert Job
     */
    public static function processFraudAlert($payload) {
        $userId = $payload['user_id'] ?? null;
        $flags = $payload['flags'] ?? [];
        $amount = $payload['amount'] ?? 0;
        
        if (!$userId) {
            return ['success' => false, 'error' => 'Missing user_id'];
        }
        
        // Create fraud alert record
        error_log("FRAUD ALERT for user {$userId}, flags: " . implode(', ', $flags));
        
        // Notify security team
        $database = new Database();
        $conn = $database->connect();
        
        $stmt = $conn->prepare("
            INSERT INTO fraud_alerts 
            (user_id, amount, type, flags, status, created_at)
            VALUES (?, ?, 'transaction', ?, 'open', NOW())
        ");
        $stmt->execute([$userId, $amount, json_encode($flags)]);
        
        return [
            'success' => true,
            'alert_id' => $conn->lastInsertId()
        ];
    }
    
    /**
     * Transaction Export Job
     */
    public static function exportTransactions($payload) {
        $userId = $payload['user_id'] ?? null;
        $startDate = $payload['start_date'] ?? date('Y-m-01');
        $endDate = $payload['end_date'] ?? date('Y-m-d');
        $format = $payload['format'] ?? 'csv';
        
        if (!$userId) {
            return ['success' => false, 'error' => 'Missing user_id'];
        }
        
        $database = new Database();
        $conn = $database->connect();
        
        $stmt = $conn->prepare("
            SELECT * FROM transactions 
            WHERE sender_id = ? OR receiver_id = ?
            AND created_at BETWEEN ? AND ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId, $userId, $startDate, $endDate]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate file
        $filename = "transactions_{$userId}_{$startDate}_{$endDate}.{$format}";
        $filepath = __DIR__ . '/../../storage/exports/' . $filename;
        
        // Create directory if not exists
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        // Simple CSV export
        if ($format === 'csv' && !empty($transactions)) {
            $fp = fopen($filepath, 'w');
            fputcsv($fp, array_keys($transactions[0]));
            foreach ($transactions as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'record_count' => count($transactions)
        ];
    }
    
    /**
     * Reconciliation Job
     */
    public static function runReconciliation($payload) {
        $userId = $payload['user_id'] ?? null;
        
        require_once __DIR__ . '/ReconciliationService.php';
        $reconciliation = new ReconciliationService();
        
        if ($userId) {
            $result = $reconciliation->reconcileUser($userId);
        } else {
            $result = $reconciliation->runFullReconciliation();
        }
        
        return $result;
    }
    
    /**
     * Dispatch job to appropriate handler
     */
    public static function dispatch($jobType, $payload) {
        $handlers = [
            'send_email' => 'sendEmail',
            'send_sms' => 'sendSMS',
            'send_push' => 'sendPushNotification',
            'fraud_alert' => 'processFraudAlert',
            'export_transactions' => 'exportTransactions',
            'reconciliation' => 'runReconciliation'
        ];
        
        if (!isset($handlers[$jobType])) {
            return ['success' => false, 'error' => 'Unknown job type: ' . $jobType];
        }
        
        return call_user_func([self::class, $handlers[$jobType]], $payload);
    }
}
