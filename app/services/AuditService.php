<?php
/**
 * AuditService - Comprehensive Audit Logging
 */
require_once __DIR__ . '/../config/database.php';

class AuditService {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Log audit event
     */
    public function log($action, $userId = null, $metadata = [], $entityType = null, $entityId = null) {
        try {
            $ipAddress = $this->getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
            
            $description = is_array($metadata) ? ($metadata['description'] ?? $action) : $action;
            
            $stmt = $this->conn->prepare("
                INSERT INTO audit_logs 
                (user_id, action, entity_type, entity_id, description, metadata, 
                 ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $description,
                json_encode($metadata),
                $ipAddress,
                $userAgent
            ]);
            
            return $this->conn->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("AuditService log error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log async (via job queue)
     */
    public function logAsync($action, $userId = null, $metadata = [], $entityType = null, $entityId = null) {
        return $this->log($action, $userId, $metadata, $entityType, $entityId);
    }
    
    /**
     * Get client IP
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
}