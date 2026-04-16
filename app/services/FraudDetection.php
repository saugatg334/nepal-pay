<?php
/**
 * Fraud Detection Service
 * Production-level rules engine for detecting suspicious activity
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AuditLog.php';

class FraudDetection {
    private $conn;
    private $auditLog;
    
    // Risk thresholds
    const HIGH_VALUE_THRESHOLD = 50000;
    const RAPID_TRANSACTION_COUNT = 5;
    const RAPID_TRANSACTION_WINDOW = 300; // 5 minutes in seconds
    const NEW_DEVICE_LIMIT = 3;
    const MAX_DAILY_TRANSACTIONS = 50;
    const MAX_DAILY_AMOUNT = 200000;
    
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->auditLog = new AuditLog();
    }
    
    /**
     * Analyze a transaction for fraud risk
     * Returns array with risk_level and flags
     */
    public function analyzeTransaction($user_id, $amount, $type, $metadata = []) {
        $flags = [];
        $riskScore = 0;
        
        // Check 1: High value transaction
        if ($amount >= self::HIGH_VALUE_THRESHOLD) {
            $flags[] = 'high_value';
            $riskScore += 30;
        }
        
        // Check 2: Rapid transactions
        $rapidCount = $this->getRapidTransactionCount($user_id);
        if ($rapidCount >= self::RAPID_TRANSACTION_COUNT) {
            $flags[] = 'rapid_transactions';
            $riskScore += 40;
        }
        
        // Check 3: New device login
        $deviceFingerprint = $metadata['device_fingerprint'] ?? null;
        if ($deviceFingerprint && $this->isNewDevice($user_id, $deviceFingerprint)) {
            $flags[] = 'new_device';
            $riskScore += 25;
            
            // Check if too many new devices
            $newDeviceCount = $this->getNewDeviceCount($user_id);
            if ($newDeviceCount > self::NEW_DEVICE_LIMIT) {
                $flags[] = 'too_many_new_devices';
                $riskScore += 20;
            }
        }
        
        // Check 4: Daily transaction limit
        $dailyCount = $this->getDailyTransactionCount($user_id);
        if ($dailyCount >= self::MAX_DAILY_TRANSACTIONS) {
            $flags[] = 'daily_limit_exceeded';
            $riskScore += 20;
        }
        
        // Check 5: Daily amount limit
        $dailyAmount = $this->getDailyAmount($user_id);
        if ($dailyAmount + $amount > self::MAX_DAILY_AMOUNT) {
            $flags[] = 'daily_amount_exceeded';
            $riskScore += 25;
        }
        
        // Check 6: Unusual hours (late night transactions)
        $hour = date('H');
        if ($hour >= 0 && $hour < 5) {
            $flags[] = 'unusual_hours';
            $riskScore += 10;
        }
        
        // Check 7: First-time large transfer to new recipient
        if ($type === 'transfer' && isset($metadata['receiver_id'])) {
            $isNewRecipient = $this->isNewRecipient($user_id, $metadata['receiver_id']);
            if ($isNewRecipient && $amount > 10000) {
                $flags[] = 'new_recipient_high_value';
                $riskScore += 20;
            }
        }
        
        // Check 8: IP address change
        $ipAddress = $metadata['ip_address'] ?? null;
        if ($ipAddress && $this->isNewIP($user_id, $ipAddress)) {
            $flags[] = 'new_ip_address';
            $riskScore += 15;
        }
        
        // Determine risk level
        $riskLevel = $this->calculateRiskLevel($riskScore, $flags);
        
        $result = [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'flags' => $flags,
            'should_block' => $riskLevel === self::RISK_CRITICAL,
            'requires_verification' => $riskLevel === self::RISK_HIGH || $riskLevel === self::RISK_CRITICAL
        ];
        
        // Log if high risk
        if ($riskLevel === self::RISK_HIGH || $riskLevel === self::RISK_CRITICAL) {
            $this->logSuspiciousActivity($user_id, $amount, $type, $flags, $metadata);
        }
        
        return $result;
    }
    
    /**
     * Calculate risk level from score and flags
     */
    private function calculateRiskLevel($score, $flags) {
        if ($score >= 70 || in_array('daily_limit_exceeded', $flags)) {
            return self::RISK_CRITICAL;
        }
        if ($score >= 40 || in_array('rapid_transactions', $flags)) {
            return self::RISK_HIGH;
        }
        if ($score >= 20 || in_array('high_value', $flags)) {
            return self::RISK_MEDIUM;
        }
        return self::RISK_LOW;
    }
    
    /**
     * Get count of transactions in last X seconds
     */
    private function getRapidTransactionCount($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as cnt
                FROM transactions
                WHERE sender_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$user_id, self::RAPID_TRANSACTION_WINDOW]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Check if device is new for user
     */
    private function isNewDevice($user_id, $device_fingerprint) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as cnt
                FROM trusted_devices
                WHERE user_id = ? AND device_fingerprint = ? AND is_trusted = 1
            ");
            $stmt->execute([$user_id, $device_fingerprint]);
            $trusted = (int) $stmt->fetchColumn();
            return $trusted === 0;
        } catch (PDOException $e) {
            return true; // Default to new device on error
        }
    }
    
    /**
     * Get count of new devices in last 24 hours
     */
    private function getNewDeviceCount($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as cnt
                FROM trusted_devices
                WHERE user_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$user_id]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get today's transaction count
     */
    private function getDailyTransactionCount($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as cnt
                FROM transactions
                WHERE sender_id = ?
                AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$user_id]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get today's total transaction amount
     */
    private function getDailyAmount($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM transactions
                WHERE sender_id = ?
                AND DATE(created_at) = CURDATE()
                AND status = 'completed'
            ");
            $stmt->execute([$user_id]);
            return (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Check if recipient is new
     */
    private function isNewRecipient($user_id, $receiver_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as cnt
                FROM transactions
                WHERE sender_id = ? AND receiver_id = ?
            ");
            $stmt->execute([$user_id, $receiver_id]);
            return (int) $stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            return true;
        }
    }
    
    /**
     * Check if IP is new for user
     */
    private function isNewIP($user_id, $ip_address) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as cnt
                FROM audit_logs
                WHERE user_id = ? AND ip_address = ?
                LIMIT 1
            ");
            $stmt->execute([$user_id, $ip_address]);
            return (int) $stmt->fetchColumn() === 0;
        } catch (PDOException $e) {
            return true;
        }
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity($user_id, $amount, $type, $flags, $metadata) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO fraud_alerts 
                (user_id, amount, type, flags, ip_address, user_agent, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())
            ");
            
            $ipAddress = $metadata['ip_address'] ?? null;
            $userAgent = $metadata['user_agent'] ?? null;
            
            $stmt->execute([
                $user_id,
                $amount,
                $type,
                json_encode($flags),
                $ipAddress,
                $userAgent
            ]);
            
            // Also log to audit
            $this->auditLog->log(
                AuditLog::ACTION_FRAUD_ALERT,
                $user_id,
                "Fraud risk detected: " . implode(', ', $flags),
                [
                    'amount' => $amount,
                    'type' => $type,
                    'flags' => $flags
                ],
                'fraud_alert',
                $this->conn->lastInsertId()
            );
        } catch (PDOException $e) {
            error_log("Fraud alert logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get all open fraud alerts (for admin)
     */
    public function getOpenAlerts($limit = 50) {
        try {
            $stmt = $this->conn->prepare("
                SELECT f.*, u.full_name, u.phone
                FROM fraud_alerts f
                LEFT JOIN users u ON f.user_id = u.id
                WHERE f.status = 'open'
                ORDER BY f.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Resolve a fraud alert
     */
    public function resolveAlert($alert_id, $admin_id, $resolution, $notes = '') {
        try {
            $stmt = $this->conn->prepare("
                UPDATE fraud_alerts 
                SET status = 'resolved', resolved_by = ?, resolution = ?, resolved_notes = ?, resolved_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$admin_id, $resolution, $notes, $alert_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Fraud alert resolution error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user risk score (for display)
     */
    public function getUserRiskScore($user_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as alert_count
                FROM fraud_alerts
                WHERE user_id = ? AND status = 'open'
            ");
            $stmt->execute([$user_id]);
            $openAlerts = (int) $stmt->fetchColumn();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as resolved_count
                FROM fraud_alerts
                WHERE user_id = ? AND status = 'resolved'
                AND resolved_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$user_id]);
            $recentResolved = (int) $stmt->fetchColumn();
            
            // Calculate score (0 = lowest risk, 100 = highest)
            $score = min(100, ($openAlerts * 20) + ($recentResolved * 2));
            
            if ($score >= 70) {
                $level = self::RISK_CRITICAL;
            } elseif ($score >= 40) {
                $level = self::RISK_HIGH;
            } elseif ($score >= 20) {
                $level = self::RISK_MEDIUM;
            } else {
                $level = self::RISK_LOW;
            }
            
            return [
                'score' => $score,
                'level' => $level,
                'open_alerts' => $openAlerts,
                'recent_resolved' => $recentResolved
            ];
        } catch (PDOException $e) {
            return ['score' => 0, 'level' => self::RISK_LOW, 'open_alerts' => 0, 'recent_resolved' => 0];
        }
    }
}
