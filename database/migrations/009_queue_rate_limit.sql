-- Production Queue & Rate Limiting Tables
-- Run this to add async queue, rate limiting, and backup tables

-- 1. Job Queue - Async job processing
CREATE TABLE IF NOT EXISTS job_queue (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Rate Limit Events
CREATE TABLE IF NOT EXISTS rate_limit_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    blocked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier, event_type),
    INDEX idx_created (created_at),
    INDEX idx_blocked (blocked, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. System Alerts - Critical failures and monitoring
CREATE TABLE IF NOT EXISTS system_alerts (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Backup Log
CREATE TABLE IF NOT EXISTS backup_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('full', 'incremental', 'transaction_log') DEFAULT 'full',
    file_path VARCHAR(500),
    file_size BIGINT,
    status ENUM('in_progress', 'completed', 'failed') DEFAULT 'in_progress',
    error_message TEXT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    created_by INT,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Transaction Export Log
CREATE TABLE IF NOT EXISTS transaction_export (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    format ENUM('csv', 'json', 'pdf') DEFAULT 'csv',
    file_path VARCHAR(500),
    record_count INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    requested_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Failed Transaction Retry Log
CREATE TABLE IF NOT EXISTS failed_transaction_retry (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Health Check Log
CREATE TABLE IF NOT EXISTS health_check_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_type VARCHAR(50) NOT NULL,
    status ENUM('healthy', 'warning', 'critical') DEFAULT 'healthy',
    response_time_ms INT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_status (check_type, status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. API Request Log
CREATE TABLE IF NOT EXISTS api_request_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_hash VARCHAR(255),
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10),
    ip_address VARCHAR(45),
    response_code INT,
    response_time_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint (endpoint),
    INDEX idx_response (response_code),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Daily Summary (for reporting)
CREATE TABLE IF NOT EXISTS daily_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    summary_date DATE NOT NULL UNIQUE,
    total_transactions INT DEFAULT 0,
    total_volume DECIMAL(15,2) DEFAULT 0,
    total_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    failed_transactions INT DEFAULT 0,
    kyc_submitted INT DEFAULT 0,
    kyc_approved INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (summary_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Session Extensions (for tracking extended sessions)
CREATE TABLE IF NOT EXISTS session_extensions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    extended_by INT,
    reason VARCHAR(255),
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Queue & Rate Limiting Tables Created Successfully!' as result;
