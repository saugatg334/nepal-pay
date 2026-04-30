<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use \Database; // Global Database

/**
 * Security Audit Service
 * 
 * WHY: Every security-sensitive action must be logged for compliance
 *      and forensic analysis. This centralizes audit logging with
 *      structured data that can be queried and alerted on.
 */
class SecurityService
{
    /**
     * Log an authentication event
     */
    public static function logAuth(
        string $action,
        ?int $userId = null,
        string $status = 'success',
        array $metadata = []
    ): void {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $data = [
            'action' => $action,
            'user_id' => $userId,
            'status' => $status,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'timestamp' => date('Y-m-d H:i:s'),
        ] + $metadata;

        // Log to structured log file
        Logger::security("Auth {$action}: {$status}", $data);

        // Log to database for querying
        try {
            $sql = "INSERT INTO security_logs 
                    (user_id, action_type, description, ip_address, user_agent, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            Database::query($sql, [
                $userId,
                $action,
                json_encode($metadata),
                $ip,
                $userAgent,
                $status
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to write security log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log an admin action
     */
    public static function logAdminAction(
        int $adminId,
        string $action,
        ?int $targetUserId = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        string $notes = ''
    ): void {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $data = [
            'admin_id' => $adminId,
            'action' => $action,
            'target_user' => $targetUserId,
            'ip' => $ip,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        Logger::security("Admin action: {$action}", $data);

        try {
            $sql = "INSERT INTO admin_actions 
                    (admin_id, target_user_id, action_type, old_value, new_value, notes, ip_address, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            Database::query($sql, [
                $adminId,
                $targetUserId,
                $action,
                $oldValue,
                $newValue,
                $notes,
                $ip
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to write admin log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if IP is currently rate-limited
     */
    public static function isRateLimited(string $ip, string $type = 'login'): bool
    {
        $window = match ($type) {
            'login' => Config::getInt('RATE_LIMIT_LOGIN_WINDOW', 900),
            'api' => Config::getInt('RATE_LIMIT_API_WINDOW', 60),
            default => 900,
        };

        $maxAttempts = match ($type) {
            'login' => Config::getInt('RATE_LIMIT_LOGIN', 5),
            'api' => Config::getInt('RATE_LIMIT_API', 100),
            default => 5,
        };

        try {
            $sql = "SELECT COUNT(*) as count FROM security_logs 
                    WHERE ip_address = ? 
                    AND action_type = ? 
                    AND status = 'failed'
                    AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)";
            $result = Database::fetch($sql, [$ip, $type, $window]);
            
            return ($result['count'] ?? 0) >= $maxAttempts;
        } catch (\Exception $e) {
            Logger::error('Rate limit check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}

