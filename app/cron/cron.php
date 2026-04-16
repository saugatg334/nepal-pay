<?php
/**
 * NepalPay Cron Jobs
 * Schedule: 
 *   * * * * * php /path/to/cron.php >> /var/log/nepalpay_cron.log 2>&1
 * 
 * Or use Windows Task Scheduler for Windows
 */

define('BASE_PATH', dirname(__DIR__));

// Parse command line for specific job
$job = $argv[1] ?? 'all';

echo "[" . date('Y-m-d H:i:s') . "] Cron job starting: {$job}\n";

switch ($job) {
    case 'minute':
        runMinuteTasks();
        break;
    case 'hourly':
        runHourlyTasks();
        break;
    case 'daily':
        runDailyTasks();
        break;
    case 'retry':
        runRetryProcessor();
        break;
    case 'cleanup':
        runCleanupTasks();
        break;
    case 'all':
    default:
        runMinuteTasks();
        runHourlyTasks();
        runDailyTasks();
        runCleanupTasks();
        break;
}

echo "[" . date('Y-m-d H:i:s') . "] Cron job completed\n";

/**
 * Run every minute
 */
function runMinuteTasks() {
    echo "Running minute tasks...\n";
    
    // Process pending retries
    require_once BASE_PATH . '/app/services/FailSafeHandler.php';
    $result = processPendingRetries();
    echo "Retries processed: {$result['processed']}, failed: {$result['failed']}\n";
    
    // Process job queue
    require_once BASE_PATH . '/app/services/JobQueue.php';
    $queue = new JobQueue();
    $stats = $queue->getStats();
    echo "Queue stats: " . json_encode($stats) . "\n";
}

/**
 * Run every hour
 */
function runHourlyTasks() {
    echo "Running hourly tasks...\n";
    
    // Export transactions to job queue
    require_once BASE_PATH . '/app/services/JobQueue.php';
    $queue = new JobQueue();
    
    $queue->enqueue('hourly_stats', [
        'timestamp' => date('Y-m-d H:i:s')
    ], ['priority' => JobQueue::PRIORITY_LOW]);
    
    echo "Hourly stats job queued\n";
}

/**
 * Run daily (midnight)
 */
function runDailyTasks() {
    echo "Running daily tasks...\n";
    
    $date = date('Y-m-d');
    
    // Create daily summary
    require_once BASE_PATH . '/app/config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Get today's stats
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(amount), 0) as total_volume,
                COUNT(DISTINCT sender_id) as active_users
            FROM transactions
            WHERE DATE(created_at) = CURDATE()
            AND status = 'completed'
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
        $totalUsers = $stmt->fetchColumn();
        
        $stmt = $conn->query("SELECT COUNT(*) as new_users FROM users WHERE DATE(created_at) = CURDATE()");
        $newUsers = $stmt->fetchColumn();
        
        $stmt = $conn->query("SELECT COUNT(*) as failed FROM transactions WHERE DATE(created_at) = CURDATE() AND status = 'failed'");
        $failed = $stmt->fetchColumn();
        
        // Insert daily summary
        $stmt = $conn->prepare("
            INSERT INTO daily_summary 
            (summary_date, total_transactions, total_volume, total_users, active_users, new_users, failed_transactions, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $date,
            $stats['total_transactions'],
            $stats['total_volume'],
            $totalUsers,
            $stats['active_users'],
            $newUsers,
            $failed
        ]);
        
        echo "Daily summary created\n";
        
    } catch (Exception $e) {
        echo "Daily summary error: " . $e->getMessage() . "\n";
    }
    
    // Queue backup
    require_once BASE_PATH . '/app/services/JobQueue.php';
    $queue = new JobQueue();
    $queue->schedule('backup', [
        'type' => 'incremental',
        'date' => $date
    ], 60, ['priority' => JobQueue::PRIORITY_LOW]);
    
    echo "Backup job queued\n";
}

/**
 * Run cleanup tasks
 */
function runCleanupTasks() {
    echo "Running cleanup tasks...\n";
    
    require_once BASE_PATH . '/app/config/database.php';
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Clean old job queue entries (keep last 7 days)
        $stmt = $conn->prepare("
            DELETE FROM job_queue 
            WHERE status IN ('completed', 'failed') 
            AND completed_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute();
        echo "Cleaned {$stmt->rowCount()} old job queue entries\n";
        
        // Clean old audit logs (keep 90 days)
        $stmt = $conn->prepare("
            DELETE FROM audit_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        $stmt->execute();
        echo "Cleaned {$stmt->rowCount()} old audit logs\n";
        
        // Clean old health check logs (keep 30 days)
        $stmt = $conn->prepare("
            DELETE FROM health_check_log 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        echo "Cleaned {$stmt->rowCount()} old health checks\n";
        
        // Clean resolved alerts (keep 30 days)
        $stmt = $conn->prepare("
            DELETE FROM system_alerts 
            WHERE is_resolved = 1 
            AND resolved_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        echo "Cleaned {$stmt->rowCount()} old system alerts\n";
        
    } catch (Exception $e) {
        echo "Cleanup error: " . $e->getMessage() . "\n";
    }
}

/**
 * Run retry processor manually
 */
function runRetryProcessor() {
    require_once BASE_PATH . '/app/services/FailSafeHandler.php';
    $result = processPendingRetries();
    echo "Retry results: " . json_encode($result) . "\n";
}
