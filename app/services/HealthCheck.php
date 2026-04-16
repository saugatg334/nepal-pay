<?php
/**
 * Health Check System
 * Monitors system health and alerts on issues
 */
require_once __DIR__ . '/../config/database.php';

class HealthCheck {
    private $conn;
    
    const STATUS_HEALTHY = 'healthy';
    const STATUS_WARNING = 'warning';
    const STATUS_CRITICAL = 'critical';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Run all health checks
     */
    public function runAllChecks() {
        $results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_status' => self::STATUS_HEALTHY,
            'checks' => []
        ];
        
        $checks = [
            'database' => 'checkDatabase',
            'disk_space' => 'checkDiskSpace',
            'wallet_balance' => 'checkWalletBalance',
            'failed_transactions' => 'checkFailedTransactions',
            'pending_retries' => 'checkPendingRetries',
            'queue_backlog' => 'checkQueueBacklog',
            'auth_security' => 'checkAuthSecurity'
        ];
        
        foreach ($checks as $name => $method) {
            $checkResult = $this->$method();
            $results['checks'][$name] = $checkResult;
            
            if ($checkResult['status'] === self::STATUS_CRITICAL) {
                $results['overall_status'] = self::STATUS_CRITICAL;
            } elseif ($checkResult['status'] === self::STATUS_WARNING && $results['overall_status'] !== self::STATUS_CRITICAL) {
                $results['overall_status'] = self::STATUS_WARNING;
            }
        }
        
        // Log health check
        $this->logHealthCheck($results);
        
        // Alert if critical
        if ($results['overall_status'] === self::STATUS_CRITICAL) {
            $this->alertOnCritical($results);
        }
        
        return $results;
    }
    
    /**
     * Check database connectivity
     */
    private function checkDatabase() {
        $start = microtime(true);
        
        try {
            $this->conn->query("SELECT 1");
            $responseTime = (microtime(true) - $start) * 1000;
            
            if ($responseTime > 1000) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Database response slow',
                    'response_time_ms' => round($responseTime)
                ];
            }
            
            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Database connected',
                'response_time_ms' => round($responseTime)
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_CRITICAL,
                'message' => 'Database connection failed'
            ];
        }
    }
    
    /**
     * Check disk space
     */
    private function checkDiskSpace() {
        $path = dirname(__DIR__, 2);
        $bytes = disk_free_space($path);
        $total = disk_total_space($path);
        $percent = ($bytes / $total) * 100;
        
        if ($percent < 10) {
            return [
                'status' => self::STATUS_CRITICAL,
                'message' => 'Disk space critical',
                'free_percent' => round($percent, 2)
            ];
        } elseif ($percent < 20) {
            return [
                'status' => self::STATUS_WARNING,
                'message' => 'Disk space low',
                'free_percent' => round($percent, 2)
            ];
        }
        
        return [
            'status' => self::STATUS_HEALTHY,
            'message' => 'Disk space OK',
            'free_percent' => round($percent, 2)
        ];
    }
    
    /**
     * Check for negative wallet balances
     */
    private function checkWalletBalance() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as cnt FROM users WHERE wallet_balance < 0
            ");
            $negativeCount = (int) $stmt->fetchColumn();
            
            if ($negativeCount > 0) {
                return [
                    'status' => self::STATUS_CRITICAL,
                    'message' => 'Negative wallet balances found',
                    'negative_count' => $negativeCount
                ];
            }
            
            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'All wallet balances OK'
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_WARNING,
                'message' => 'Could not verify wallet balances'
            ];
        }
    }
    
    /**
     * Check for failed transactions
     */
    private function checkFailedTransactions() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as cnt FROM transactions 
                WHERE status = 'failed' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $failedLastHour = (int) $stmt->fetchColumn();
            
            $stmt = $this->conn->query("
                SELECT COUNT(*) as cnt FROM transactions 
                WHERE status = 'failed' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $failedLastDay = (int) $stmt->fetchColumn();
            
            if ($failedLastHour > 10) {
                return [
                    'status' => self::STATUS_CRITICAL,
                    'message' => 'High failed transaction rate',
                    'failed_last_hour' => $failedLastHour,
                    'failed_last_day' => $failedLastDay
                ];
            } elseif ($failedLastDay > 50) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Elevated failed transactions',
                    'failed_last_hour' => $failedLastHour,
                    'failed_last_day' => $failedLastDay
                ];
            }
            
            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Transaction failure rate OK',
                'failed_last_hour' => $failedLastHour,
                'failed_last_day' => $failedLastDay
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_WARNING,
                'message' => 'Could not check failed transactions'
            ];
        }
    }
    
    /**
     * Check pending transaction retries
     */
    private function checkPendingRetries() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as cnt FROM failed_transaction_retry 
                WHERE status IN ('pending', 'processing')
            ");
            $pending = (int) $stmt->fetchColumn();
            
            $stmt = $this->conn->query("
                SELECT COUNT(*) as cnt FROM failed_transaction_retry 
                WHERE status = 'failed'
            ");
            $failed = (int) $stmt->fetchColumn();
            
            if ($pending > 20 || $failed > 10) {
                return [
                    'status' => self::STATUS_CRITICAL,
                    'message' => 'Many pending/failed retries',
                    'pending_count' => $pending,
                    'failed_count' => $failed
                ];
            } elseif ($pending > 5) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Some pending retries',
                    'pending_count' => $pending,
                    'failed_count' => $failed
                ];
            }
            
            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Retry queue OK',
                'pending_count' => $pending,
                'failed_count' => $failed
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_WARNING,
                'message' => 'Could not check retry queue'
            ];
        }
    }
    
    /**
     * Check job queue backlog
     */
    private function checkQueueBacklog() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) as cnt FROM job_queue 
                WHERE status = 'pending' 
                AND (scheduled_at IS NULL OR scheduled_at <= NOW())
            ");
            $backlog = (int) $stmt->fetchColumn();
            
            if ($backlog > 100) {
                return [
                    'status' => self::STATUS_CRITICAL,
                    'message' => 'Job queue backed up',
                    'backlog_count' => $backlog
                ];
            } elseif ($backlog > 50) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Job queue growing',
                    'backlog_count' => $backlog
                ];
            }
            
            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Job queue OK',
                'backlog_count' => $backlog
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_WARNING,
                'message' => 'Could not check job queue'
            ];
        }
    }
    
    /**
     * Check authentication security
     */
    private function checkAuthSecurity() {
        try {
            // Check for locked accounts
            $stmt = $this->conn->query("
                SELECT COUNT(*) as cnt FROM users 
                WHERE account_locked_until > NOW()
            ");
            $locked = (int) $stmt->fetchColumn();
            
            // Check for many failed login attempts
            $stmt = $this->conn->query("
                SELECT COUNT(*) as cnt FROM users 
                WHERE failed_login_attempts >= 3
            ");
            $failedLogins = (int) $stmt->fetchColumn();
            
            if ($failedLogins > 20) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Many failed login attempts',
                    'locked_accounts' => $locked,
                    'failed_attempts' => $failedLogins
                ];
            }
            
            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Auth security OK',
                'locked_accounts' => $locked,
                'failed_attempts' => $failedLogins
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_WARNING,
                'message' => 'Could not check auth security'
            ];
        }
    }
    
    /**
     * Log health check result
     */
    private function logHealthCheck($results) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO health_check_log 
                (check_type, status, details, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                'system',
                $results['overall_status'],
                json_encode($results)
            ]);
        } catch (PDOException $e) {
            error_log("Health check log error: " . $e->getMessage());
        }
    }
    
    /**
     * Alert on critical status
     */
    private function alertOnCritical($results) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_alerts 
                (alert_type, severity, message, data, created_at)
                VALUES ('health_check', 'critical', ?, ?, NOW())
            ");
            
            $message = "System health check failed: " . $results['overall_status'];
            $details = json_encode([
                'checks' => array_filter($results['checks'], function($c) {
                    return $c['status'] !== self::STATUS_HEALTHY;
                })
            ]);
            
            $stmt->execute([$message, $details]);
            
            // TODO: Send alert to admin
            
        } catch (PDOException $e) {
            error_log("Critical alert error: " . $e->getMessage());
        }
    }
    
    /**
     * Get health history
     */
    public function getHistory($hours = 24) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM health_check_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY created_at DESC
            ");
            $stmt->execute([$hours]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

// CLI mode
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "Running NepalPay Health Check...\n\n";
    
    $health = new HealthCheck();
    $result = $health->runAllChecks();
    
    echo "Overall Status: " . strtoupper($result['overall_status']) . "\n\n";
    
    foreach ($result['checks'] as $name => $check) {
        $icon = $check['status'] === 'healthy' ? '✓' : ($check['status'] === 'warning' ? '⚠' : '✗');
        echo "{$icon} {$name}: {$check['message']}\n";
    }
    
    echo "\n";
    
    exit($result['overall_status'] === 'healthy' ? 0 : 1);
}
