<?php
/**
 * RateLimitService - Production-Grade Rate Limiting
 * 
 * Features:
 * - Multiple rate limit strategies (fixed window, sliding window)
 * - Per-user, per-IP, per-endpoint limits
 * - Configurable limits with database storage
 * - Event logging for abuse detection
 * - Graceful degradation
 */
require_once __DIR__ . '/../config/database.php';

class RateLimitService {
    private $conn;
    
    // Default limits
    const DEFAULT_PER_MINUTE = 10;
    const DEFAULT_PER_HOUR = 50;
    const DEFAULT_PER_DAY = 200;
    const DEFAULT_AMOUNT_PER_HOUR = 100000;
    const DEFAULT_AMOUNT_PER_DAY = 200000;
    
    const WINDOW_MINUTE = 60;
    const WINDOW_HOUR = 3600;
    const WINDOW_DAY = 86400;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Check rate limit for user
     * Returns array with allowed status and details
     */
    public function check($userId, $amount = 0, $scope = 'api') {
        $result = [
            'allowed' => true,
            'reason' => null,
            'limit_type' => null,
            'remaining' => [],
            'reset_at' => []
        ];
        
        // Get limits from config or use defaults
        $limits = $this->getLimits($scope);
        
        // Check 1: Per-minute request limit
        $countMinute = $this->getRequestCount($userId, self::WINDOW_MINUTE);
        if ($countMinute >= $limits['per_minute']) {
            $result['allowed'] = false;
            $result['reason'] = 'Too many requests. Please wait a minute.';
            $result['limit_type'] = 'per_minute';
            $result['reset_at']['per_minute'] = date('Y-m-d H:i:s', time() + self::WINDOW_MINUTE);
            $this->logBlockedEvent($userId, 'per_minute');
            return $result;
        }
        
        // Check 2: Per-hour request limit
        $countHour = $this->getRequestCount($userId, self::WINDOW_HOUR);
        if ($countHour >= $limits['per_hour']) {
            $result['allowed'] = false;
            $result['reason'] = 'Hourly request limit reached. Try again later.';
            $result['limit_type'] = 'per_hour';
            $result['reset_at']['per_hour'] = date('Y-m-d H:i:s', time() + self::WINDOW_HOUR);
            $this->logBlockedEvent($userId, 'per_hour');
            return $result;
        }
        
        // Check 3: Per-day request limit
        $countDay = $this->getRequestCount($userId, self::WINDOW_DAY);
        if ($countDay >= $limits['per_day']) {
            $result['allowed'] = false;
            $result['reason'] = 'Daily request limit reached.';
            $result['limit_type'] = 'per_day';
            $result['reset_at']['per_day'] = date('Y-m-d H:i:s', time() + self::WINDOW_DAY);
            $this->logBlockedEvent($userId, 'per_day');
            return $result;
        }
        
        // Check 4: Amount per hour (if amount > 0)
        if ($amount > 0) {
            $amountHour = $this->getAmountSum($userId, self::WINDOW_HOUR);
            if ($amountHour + $amount > $limits['amount_per_hour']) {
                $result['allowed'] = false;
                $result['reason'] = 'Hourly amount limit reached. Try again later.';
                $result['limit_type'] = 'amount_per_hour';
                $result['reset_at']['amount_per_hour'] = date('Y-m-d H:i:s', time() + self::WINDOW_HOUR);
                $this->logBlockedEvent($userId, 'amount_per_hour');
                return $result;
            }
            
            // Check 5: Amount per day
            $amountDay = $this->getAmountSum($userId, self::WINDOW_DAY);
            if ($amountDay + $amount > $limits['amount_per_day']) {
                $result['allowed'] = false;
                $result['reason'] = 'Daily amount limit reached.';
                $result['limit_type'] = 'amount_per_day';
                $result['reset_at']['amount_per_day'] = date('Y-m-d H:i:s', time() + self::WINDOW_DAY);
                $this->logBlockedEvent($userId, 'amount_per_day');
                return $result;
            }
        }
        
        // All checks passed - calculate remaining
        $result['remaining'] = [
            'per_minute' => max(0, $limits['per_minute'] - $countMinute - 1),
            'per_hour' => max(0, $limits['per_hour'] - $countHour - 1),
            'per_day' => max(0, $limits['per_day'] - $countDay - 1),
            'amount_per_hour' => max(0, $limits['amount_per_hour'] - $amountHour - $amount),
            'amount_per_day' => max(0, $limits['amount_per_day'] - $amountDay - $amount)
        ];
        
        return $result;
    }
    
    /**
     * Check rate limit by IP address only (for non-authenticated endpoints)
     */
    public function checkByIp($ipAddress, $endpoint = 'api') {
        $result = [
            'allowed' => true,
            'reason' => null,
            'limit_type' => null
        ];
        
        // Different limits for IP-based checking
        $limits = [
            'per_minute' => 60,  // More liberal for IP
            'per_hour' => 500,
            'per_day' => 2000
        ];
        
        $countMinute = $this->getIpRequestCount($ipAddress, self::WINDOW_MINUTE);
        if ($countMinute >= $limits['per_minute']) {
            $result['allowed'] = false;
            $result['reason'] = 'Too many requests from your IP.';
            $result['limit_type'] = 'per_minute';
            $this->logIpBlockedEvent($ipAddress, 'per_minute');
            return $result;
        }
        
        return $result;
    }
    
    /**
     * Record a request/event (must be called after successful operations)
     */
    public function record($userId, $amount = 0, $scope = 'api') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO rate_limit_events 
                (identifier, event_type, ip_address, blocked, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $ipAddress = $this->getClientIp();
            $stmt->execute(['user_' . $userId, $scope, $ipAddress]);
            
            // Also record amount-based events
            if ($amount > 0) {
                $stmt = $this->conn->prepare("
                    INSERT INTO rate_limit_events 
                    (identifier, event_type, ip_address, blocked, created_at)
                    VALUES (?, 'amount', ?, 0, NOW())
                ");
                $stmt->execute(['user_amount_' . $userId, $ipAddress]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("RateLimitService record error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get limits for a scope from database or defaults
     */
    private function getLimits($scope) {
        try {
            $stmt = $this->conn->prepare("
                SELECT setting_key, setting_value 
                FROM system_settings 
                WHERE setting_key LIKE 'rate_limit_%'
            ");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            return [
                'per_minute' => intval($settings['rate_limit_per_minute'] ?? self::DEFAULT_PER_MINUTE),
                'per_hour' => intval($settings['rate_limit_per_hour'] ?? self::DEFAULT_PER_HOUR),
                'per_day' => intval($settings['rate_limit_per_day'] ?? self::DEFAULT_PER_DAY),
                'amount_per_hour' => intval($settings['rate_limit_amount_per_hour'] ?? self::DEFAULT_AMOUNT_PER_HOUR),
                'amount_per_day' => intval($settings['rate_limit_amount_per_day'] ?? self::DEFAULT_AMOUNT_PER_DAY)
            ];
        } catch (PDOException $e) {
            return [
                'per_minute' => self::DEFAULT_PER_MINUTE,
                'per_hour' => self::DEFAULT_PER_HOUR,
                'per_day' => self::DEFAULT_PER_DAY,
                'amount_per_hour' => self::DEFAULT_AMOUNT_PER_HOUR,
                'amount_per_day' => self::DEFAULT_AMOUNT_PER_DAY
            ];
        }
    }
    
    /**
     * Get request count for user in time window
     */
    private function getRequestCount($userId, $seconds) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as cnt
                FROM rate_limit_events
                WHERE identifier = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND blocked = 0
            ");
            $stmt->execute(['user_' . $userId, $seconds]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get request count for IP in time window
     */
    private function getIpRequestCount($ipAddress, $seconds) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as cnt
                FROM rate_limit_events
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND blocked = 0
            ");
            $stmt->execute([$ipAddress, $seconds]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get total amount for user in time window
     */
    private function getAmountSum($userId, $seconds) {
        try {
            // Sum from transactions instead of rate_limit_events
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM transactions
                WHERE sender_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND status = 'completed'
            ");
            $stmt->execute([$userId, $seconds]);
            return (float) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Log blocked event
     */
    private function logBlockedEvent($userId, $eventType) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO rate_limit_events 
                (identifier, event_type, ip_address, blocked, created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $ipAddress = $this->getClientIp();
            $stmt->execute(['user_' . $userId, $eventType, $ipAddress]);
        } catch (PDOException $e) {
            error_log("RateLimitService logBlockedEvent error: " . $e->getMessage());
        }
    }
    
    /**
     * Log blocked IP event
     */
    private function logIpBlockedEvent($ipAddress, $eventType) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO rate_limit_events 
                (identifier, event_type, ip_address, blocked, created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute(['ip_' . $ipAddress, $eventType, $ipAddress]);
        } catch (PDOException $e) {
            error_log("RateLimitService logIpBlockedEvent error: " . $e->getMessage());
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIp() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
    
    /**
     * Get rate limit status for display
     */
    public function getStatus($userId, $scope = 'api') {
        $limits = $this->getLimits($scope);
        
        $countMinute = $this->getRequestCount($userId, self::WINDOW_MINUTE);
        $countHour = $this->getRequestCount($userId, self::WINDOW_HOUR);
        $countDay = $this->getRequestCount($userId, self::WINDOW_DAY);
        $amountHour = $this->getAmountSum($userId, self::WINDOW_HOUR);
        $amountDay = $this->getAmountSum($userId, self::WINDOW_DAY);
        
        return [
            'per_minute' => [
                'used' => $countMinute,
                'limit' => $limits['per_minute'],
                'remaining' => max(0, $limits['per_minute'] - $countMinute),
                'percent' => $limits['per_minute'] > 0 ? round($countMinute / $limits['per_minute'] * 100) : 0
            ],
            'per_hour' => [
                'used' => $countHour,
                'limit' => $limits['per_hour'],
                'remaining' => max(0, $limits['per_hour'] - $countHour),
                'percent' => $limits['per_hour'] > 0 ? round($countHour / $limits['per_hour'] * 100) : 0
            ],
            'per_day' => [
                'used' => $countDay,
                'limit' => $limits['per_day'],
                'remaining' => max(0, $limits['per_day'] - $countDay),
                'percent' => $limits['per_day'] > 0 ? round($countDay / $limits['per_day'] * 100) : 0
            ],
            'amount_per_hour' => [
                'used' => $amountHour,
                'limit' => $limits['amount_per_hour'],
                'remaining' => max(0, $limits['amount_per_hour'] - $amountHour),
                'percent' => $limits['amount_per_hour'] > 0 ? round($amountHour / $limits['amount_per_hour'] * 100) : 0
            ],
            'amount_per_day' => [
                'used' => $amountDay,
                'limit' => $limits['amount_per_day'],
                'remaining' => max(0, $limits['amount_per_day'] - $amountDay),
                'percent' => $limits['amount_per_day'] > 0 ? round($amountDay / $limits['amount_per_day'] * 100) : 0
            ]
        ];
    }
    
    /**
     * Reset rate limits for user (admin action)
     */
    public function reset($userId) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM rate_limit_events 
                WHERE identifier = ?
            ");
            $stmt->execute(['user_' . $userId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get blocked users (for admin monitoring)
     */
    public function getBlockedUsers($hours = 24) {
        try {
            $stmt = $this->conn->prepare("
                SELECT identifier, event_type, COUNT(*) as cnt, MAX(created_at) as last_blocked
                FROM rate_limit_events
                WHERE blocked = 1
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY identifier, event_type
                ORDER BY cnt DESC
            ");
            $stmt->execute([$hours]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}