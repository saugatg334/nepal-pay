<?php
require_once __DIR__ . '/../app/config/database.php';

$db = new Database();
$conn = $db->connect();

echo "Creating missing tables directly via PDO...\n\n";

$tables = [
    'job_queue' => "
        CREATE TABLE job_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_type VARCHAR(50) NOT NULL,
            payload JSON NOT NULL,
            priority INT DEFAULT 5,
            scheduled_at TIMESTAMP NULL,
            status ENUM('pending', 'processing', 'completed', 'failed', 'retry') DEFAULT 'pending',
            worker_id VARCHAR(50),
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            lock_expires TIMESTAMP NULL,
            retry_count INT DEFAULT 0,
            last_error TEXT,
            result JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status_priority (status, priority, scheduled_at),
            INDEX idx_job_type (job_type),
            INDEX idx_worker (worker_id),
            INDEX idx_scheduled (scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'failed_transaction_retry' => "
        CREATE TABLE failed_transaction_retry (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_transaction_id INT NOT NULL,
            retry_count INT DEFAULT 0,
            max_retries INT DEFAULT 3,
            last_error TEXT,
            status ENUM('pending', 'processing', 'success', 'failed') DEFAULT 'pending',
            next_retry_at TIMESTAMP NULL,
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_original (original_transaction_id),
            INDEX idx_status (status),
            INDEX idx_next_retry (next_retry_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'system_alerts' => "
        CREATE TABLE system_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alert_type VARCHAR(50) NOT NULL,
            severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
            message TEXT NOT NULL,
            data JSON,
            is_resolved TINYINT(1) DEFAULT 0,
            resolved_by INT,
            resolved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_type_severity (alert_type, severity),
            INDEX idx_resolved (is_resolved),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'idempotency_keys' => "
        CREATE TABLE idempotency_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            key_hash VARCHAR(64) NOT NULL UNIQUE,
            user_id INT,
            endpoint VARCHAR(255),
            original_request JSON,
            response JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            INDEX idx_key_hash (key_hash),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

foreach ($tables as $name => $sql) {
    try {
        $conn->exec($sql);
        echo "Created: $name\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "Exists: $name\n";
        } else {
            echo "Error creating $name: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nDone.\n";
