<?php
/**
 * Database Backup & Recovery System
 * Production-level backup scripts for NepalPay
 */
require_once __DIR__ . '/../config/database.php';

class BackupService {
    private $conn;
    private $backupDir;
    private $maxBackups = 30;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->backupDir = __DIR__ . '/../../backups';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Create full database backup
     */
    public function createFullBackup($options = []) {
        $backupId = null;
        
        try {
            // Log start
            $backupId = $this->logBackupStart('full');
            
            $filename = 'full_backup_' . date('Y-m-d_His') . '.sql';
            $filepath = $this->backupDir . '/' . $filename;
            
            $tables = $this->getAllTables();
            $sqlContent = "-- NepalPay Database Backup\n";
            $sqlContent .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
            $sqlContent .= "-- Database: nepalpay\n\n";
            
            // Drop and create database
            $sqlContent .= "DROP DATABASE IF EXISTS nepalpay;\n";
            $sqlContent .= "CREATE DATABASE nepalpay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
            $sqlContent .= "USE nepalpay;\n\n";
            
            foreach ($tables as $table) {
                $sqlContent .= $this->getTableStructure($table);
                $sqlContent .= $this->getTableData($table, $options['skip_data'] ?? false);
            }
            
            // Write to file
            file_put_contents($filepath, $sqlContent);
            $filesize = filesize($filepath);
            
            // Gzip compress
            if (function_exists('gzopen')) {
                $gzfilepath = $filepath . '.gz';
                $gz = gzopen($gzfilepath, 'wb');
                gzwrite($gz, $sqlContent);
                gzclose($gz);
                $filesize = filesize($gzfilepath);
                unlink($filepath); // Remove uncompressed
                $filepath = $gzfilepath;
            }
            
            // Log completion
            $this->logBackupComplete($backupId, $filepath, $filesize);
            
            // Clean old backups
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'filename' => basename($filepath),
                'filepath' => $filepath,
                'size' => $filesize,
                'backup_id' => $backupId
            ];
            
        } catch (Exception $e) {
            if ($backupId) {
                $this->logBackupFailed($backupId, $e->getMessage());
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Create incremental backup (last 24 hours)
     */
    public function createIncrementalBackup() {
        $backupId = null;
        
        try {
            $backupId = $this->logBackupStart('incremental');
            
            $filename = 'incremental_' . date('Y-m-d_His') . '.sql';
            $filepath = $this->backupDir . '/' . $filename;
            
            $tables = ['transactions', 'users', 'wallet_ledger', 'audit_logs'];
            $sqlContent = "-- NepalPay Incremental Backup\n";
            $sqlContent .= "-- Period: Last 24 hours\n";
            $sqlContent .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                $sqlContent .= $this->getTableData($table, false, 1);
            }
            
            file_put_contents($filepath, $sqlContent);
            $filesize = filesize($filepath);
            
            $this->logBackupComplete($backupId, $filepath, $filesize);
            
            return [
                'success' => true,
                'filename' => basename($filepath),
                'size' => $filesize
            ];
            
        } catch (Exception $e) {
            if ($backupId) {
                $this->logBackupFailed($backupId, $e->getMessage());
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Export transactions to CSV/JSON
     */
    public function exportTransactions($userId, $startDate, $endDate, $format = 'csv') {
        try {
            $query = "
                SELECT t.*, 
                       s.full_name as sender_name, s.phone as sender_phone,
                       r.full_name as receiver_name, r.phone as receiver_phone
                FROM transactions t
                LEFT JOIN users s ON t.sender_id = s.id
                LEFT JOIN users r ON t.receiver_id = r.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($userId) {
                $query .= " AND (t.sender_id = ? OR t.receiver_id = ?)";
                $params = [$userId, $userId];
            }
            
            if ($startDate) {
                $query .= " AND t.created_at >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $query .= " AND t.created_at <= ?";
                $params[] = $endDate . ' 23:59:59';
            }
            
            $query .= " ORDER BY t.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $filename = 'transactions_' . ($userId ? 'user_' . $userId . '_' : '') . date('Y-m-d') . '.' . $format;
            $filepath = $this->backupDir . '/exports/' . $filename;
            
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }
            
            if ($format === 'csv') {
                $fp = fopen($filepath, 'w');
                if (!empty($transactions)) {
                    fputcsv($fp, array_keys($transactions[0]));
                    foreach ($transactions as $row) {
                        fputcsv($fp, $row);
                    }
                }
                fclose($fp);
            } else {
                file_put_contents($filepath, json_encode($transactions, JSON_PRETTY_PRINT));
            }
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'record_count' => count($transactions),
                'size' => filesize($filepath)
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Restore from backup file
     */
    public function restoreFromBackup($filepath) {
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }
        
        try {
            $sql = file_get_contents($filepath);
            
            // Split into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            $this->conn->beginTransaction();
            
            foreach ($statements as $statement) {
                if (strlen($statement) > 10) {
                    $this->conn->exec($statement);
                }
            }
            
            $this->conn->commit();
            
            // Log restore
            $this->createSystemAlert('backup_restored', 'info', 'Database restored from: ' . basename($filepath));
            
            return ['success' => true, 'message' => 'Database restored successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get list of available backups
     */
    public function getBackupList($limit = 10) {
        try {
            $stmt = $this->conn->query("
                SELECT * FROM backup_log 
                ORDER BY created_at DESC 
                LIMIT $limit
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get table structure
     */
    private function getTableStructure($table) {
        $stmt = $this->conn->query("SHOW CREATE TABLE $table");
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return "\n\n" . $row[1] . ";\n\n";
    }
    
    /**
     * Get table data
     */
    private function getTableData($table, $skipData = false, $daysBack = null) {
        if ($skipData) {
            return '';
        }
        
        $query = "SELECT * FROM $table";
        if ($daysBack) {
            $query .= " WHERE created_at >= DATE_SUB(NOW(), INTERVAL $daysBack DAY)";
        }
        
        $stmt = $this->conn->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            return '';
        }
        
        $sql = "INSERT INTO $table VALUES ";
        $values = [];
        
        foreach ($rows as $row) {
            $vals = array_map(function($v) {
                if (is_null($v)) return 'NULL';
                return "'" . addslashes($v) . "'";
            }, $row);
            $values[] = '(' . implode(', ', $vals) . ')';
        }
        
        return $sql . implode(",\n", $values) . ";\n\n";
    }
    
    /**
     * Get all tables
     */
    private function getAllTables() {
        $stmt = $this->conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        return $tables;
    }
    
    /**
     * Log backup start
     */
    private function logBackupStart($type) {
        $stmt = $this->conn->prepare("
            INSERT INTO backup_log (backup_type, status, started_at) VALUES (?, 'in_progress', NOW())
        ");
        $stmt->execute([$type]);
        return $this->conn->lastInsertId();
    }
    
    /**
     * Log backup completion
     */
    private function logBackupComplete($id, $filepath, $filesize) {
        $stmt = $this->conn->prepare("
            UPDATE backup_log SET status = 'completed', file_path = ?, file_size = ?, completed_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$filepath, $filesize, $id]);
    }
    
    /**
     * Log backup failure
     */
    private function logBackupFailed($id, $error) {
        $stmt = $this->conn->prepare("
            UPDATE backup_log SET status = 'failed', error_message = ?, completed_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$error, $id]);
    }
    
    /**
     * Clean old backups
     */
    private function cleanOldBackups() {
        $files = glob($this->backupDir . '/*.sql.gz');
        if (count($files) > $this->maxBackups) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $toDelete = array_slice($files, 0, count($files) - $this->maxBackups);
            foreach ($toDelete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Create system alert
     */
    private function createSystemAlert($type, $severity, $message) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_alerts (alert_type, severity, message, created_at) VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$type, $severity, $message]);
        } catch (PDOException $e) {
            error_log("System alert error: " . $e->getMessage());
        }
    }
}

// CLI mode
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $options = getopt('', ['type:', 'user:', 'start:', 'end:', 'format:', 'restore:']);
    
    $backup = new BackupService();
    
    if (isset($options['restore'])) {
        echo "Restoring from: " . $options['restore'] . "\n";
        $result = $backup->restoreFromBackup($options['restore']);
        print_r($result);
        exit($result['success'] ? 0 : 1);
    }
    
    $type = $options['type'] ?? 'full';
    
    switch ($type) {
        case 'full':
            echo "Creating full backup...\n";
            $result = $backup->createFullBackup();
            break;
            
        case 'incremental':
            echo "Creating incremental backup...\n";
            $result = $backup->createIncrementalBackup();
            break;
            
        case 'export':
            $userId = $options['user'] ?? null;
            $startDate = $options['start'] ?? date('Y-m-01');
            $endDate = $options['end'] ?? date('Y-m-d');
            $format = $options['format'] ?? 'csv';
            
            echo "Exporting transactions...\n";
            $result = $backup->exportTransactions($userId, $startDate, $endDate, $format);
            break;
            
        default:
            echo "Unknown backup type\n";
            exit(1);
    }
    
    print_r($result);
    exit($result['success'] ? 0 : 1);
}
