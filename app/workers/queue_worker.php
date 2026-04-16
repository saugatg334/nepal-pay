<?php
/**
 * Job Queue Worker
 * Processes async jobs from the queue
 * 
 * Usage: php queue_worker.php [--workers=N] [--timeout=300]
 * 
 * Options:
 *   --workers=N    Number of worker processes (default: 1)
 *   --timeout=N   Max time to run in seconds (default: 300)
 *   --once         Run only one job and exit
 */

define('BASE_PATH', dirname(__DIR__));
define('WORKER_START', time());

// Parse command line arguments
$options = getopt('', ['workers::', 'timeout::', 'once', 'help']);
$numWorkers = isset($options['workers']) ? (int)$options['workers'] : 1;
$maxTimeout = isset($options['timeout']) ? (int)$options['timeout'] : 300;
$runOnce = isset($options['once']);

if (isset($options['help'])) {
    echo "Job Queue Worker\n";
    echo "Usage: php queue_worker.php [options]\n";
    echo "Options:\n";
    echo "  --workers=N    Number of parallel workers (default: 1)\n";
    echo "  --timeout=N   Max runtime in seconds (default: 300)\n";
    echo "  --once         Process one job and exit\n";
    echo "  --help         Show this help\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Job Queue Worker starting...\n";
echo "[" . date('Y-m-d H:i:s') . "] Workers: {$numWorkers}, Timeout: {$maxTimeout}s\n";

/**
 * Main worker process
 */
function runWorker($workerId) {
    require_once BASE_PATH . '/app/config/database.php';
    require_once BASE_PATH . '/app/services/JobQueue.php';
    
    $queue = new JobQueue();
    $processed = 0;
    $failed = 0;
    
    echo "[Worker {$workerId}] Started\n";
    
    while (true) {
        // Check timeout
        if (time() - WORKER_START >= $maxTimeout) {
            echo "[Worker {$workerId}] Timeout reached, exiting\n";
            break;
        }
        
        // Get next job
        $job = $queue->dequeue($workerId);
        
        if (!$job) {
            // No jobs, wait a bit
            sleep(1);
            continue;
        }
        
        $jobId = $job['id'];
        $jobType = $job['job_type'];
        $payload = $job['payload'];
        
        echo "[Worker {$workerId}] Processing job {$jobId} ({$jobType})\n";
        
        try {
            $result = JobHandlers::dispatch($jobType, $payload);
            
            if ($result['success'] ?? false) {
                $queue->complete($jobId, $result);
                echo "[Worker {$workerId}] Job {$jobId} completed\n";
                $processed++;
            } else {
                $error = $result['error'] ?? 'Unknown error';
                $queue->fail($jobId, $error, true);
                echo "[Worker {$workerId}] Job {$jobId} failed: {$error}\n";
                $failed++;
            }
        } catch (Exception $e) {
            $queue->fail($jobId, $e->getMessage(), true);
            echo "[Worker {$workerId}] Job {$jobId} exception: " . $e->getMessage() . "\n";
            $failed++;
        }
        
        // If run-once mode, exit after first job
        if ($runOnce) {
            echo "[Worker {$workerId}] Run-once mode, exiting\n";
            break;
        }
    }
    
    echo "[Worker {$workerId}] Finished. Processed: {$processed}, Failed: {$failed}\n";
    
    return ['processed' => $processed, 'failed' => $failed];
}

// Run workers
if ($numWorkers === 1) {
    // Single worker mode
    runWorker('worker-1');
} else {
    // Multi-worker mode (fork processes)
    $pids = [];
    
    for ($i = 1; $i <= $numWorkers; $i++) {
        $pid = pcntl_fork();
        
        if ($pid === -1) {
            die("Could not fork worker process\n");
        } elseif ($pid === 0) {
            // Child process
            runWorker("worker-{$i}");
            exit(0);
        } else {
            // Parent process
            $pids[] = $pid;
            echo "Started worker {$i} with PID {$pid}\n";
        }
    }
    
    // Wait for all workers to complete
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] All workers completed\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Queue worker shutdown\n";
