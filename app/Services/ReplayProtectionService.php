<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Logger;
use \Database;
use Exception;

/**
 * Replay Attack Protection Service
 * 
 * WHY: Even with encrypted tokens, API requests can be intercepted
 * and replayed. This service prevents request replay attacks.
 * 
 * Protection Mechanisms:
 * - Request ID tracking (nonce)
 * - Timestamp validation
 * - HMAC request signing
 * - Duplicate detection
 */
class ReplayProtectionService
{
    /**
     * @var int Maximum request age in seconds
     */
    private const MAX_REQUEST_AGE = 30;
    
    /**
     * @var int Cleanup probability (1 in N)
     */
    private const CLEANUP_PROBABILITY = 100;
    
    /**
     * Validate a request to prevent replay attacks
     * 
     * @param string $requestId Unique request identifier
     * @param int $timestamp Request timestamp
     * @param string|null $signature HMAC signature (optional)
     * @param string|null $secret Signing secret (optional)
     * @return bool True if request is valid
     */
    public static function validateRequest(
        string $requestId,
        int $timestamp,
        ?string $signature = null,
        ?string $secret = null
    ): bool {
        try {
            // Check timestamp validity
            if (!self::isValidTimestamp($timestamp)) {
                Logger::warning('Replay protection: Invalid timestamp', [
                    'request_id' => $requestId,
                    'timestamp' => $timestamp
                ]);
                return false;
            }
            
            // Check for duplicate request
            if (self::isDuplicateRequest($requestId)) {
                Logger::warning('Replay protection: Duplicate request detected', [
                    'request_id' => $requestId
                ]);
                return false;
            }
            
            // Validate signature if provided
            if ($signature !== null && $secret !== null) {
                if (!self::isValidSignature($requestId, $timestamp, $signature, $secret)) {
                    Logger::warning('Replay protection: Invalid signature', [
                        'request_id' => $requestId
                    ]);
                    return false;
                }
            }
            
            // Store request ID to prevent replay
            self::storeRequestId($requestId);
            
            // Periodic cleanup
            if (rand(1, self::CLEANUP_PROBABILITY) === 1) {
                self::cleanupOldRequests();
            }
            
            return true;
            
        } catch (Exception $e) {
            Logger::error('Replay protection error', [
                'error' => $e->getMessage(),
                'request_id' => $requestId
            ]);
            // Fail safe - reject request on error
            return false;
        }
    }
    
    /**
     * Generate a unique request ID
     * 
     * @return string Request ID
     */
    public static function generateRequestId(): string
    {
        return bin2hex(random_bytes(16)) . '-' . microtime(true);
    }
    
    /**
     * Sign request data for integrity verification
     * 
     * @param string $requestId Request ID
     * @param int $timestamp Request timestamp
     * @param array $data Request data
     * @param string $secret Signing secret
     * @return string HMAC signature
     */
    public static function signRequest(
        string $requestId,
        int $timestamp,
        array $data,
        string $secret
    ): string {
        // Create canonical representation of request
        ksort($data);
        $canonicalData = json_encode($data, JSON_UNESCAPED_SLASHES);
        
        $message = $requestId . '|' . $timestamp . '|' . $canonicalData;
        
        return hash_hmac('sha256', $message, $secret);
    }
    
    /**
     * Validate timestamp is within acceptable range
     * 
     * @param int $timestamp Request timestamp
     * @return bool True if valid
     */
    private static function isValidTimestamp(int $timestamp): bool
    {
        $now = time();
        $age = abs($now - $timestamp);
        
        return $age <= self::MAX_REQUEST_AGE;
    }
    
    /**
     * Check if request ID has been seen before
     * 
     * @param string $requestId Request ID to check
     * @return bool True if duplicate
     */
    private static function isDuplicateRequest(string $requestId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM request_nonces WHERE nonce = ? AND expires_at > NOW()";
            $result = Database::fetch($sql, [$requestId]);
            
            return ($result['count'] ?? 0) > 0;
        } catch (Exception $e) {
            Logger::error('Failed to check duplicate request', [
                'error' => $e->getMessage()
            ]);
            return true; // Fail safe
        }
    }
    
    /**
     * Store request ID to prevent replay
     * 
     * @param string $requestId Request ID
     */
    private static function storeRequestId(string $requestId): void
    {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + self::MAX_REQUEST_AGE + 60);
            
            $sql = "INSERT IGNORE INTO request_nonces (nonce, expires_at, created_at) 
                    VALUES (?, ?, NOW())";
            Database::query($sql, [$requestId, $expiresAt]);
        } catch (Exception $e) {
            Logger::error('Failed to store request ID', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Validate HMAC signature
     * 
     * @param string $requestId Request ID
     * @param int $timestamp Request timestamp
     * @param string $signature Provided signature
     * @param string $secret Signing secret
     * @return bool True if valid
     */
    private static function isValidSignature(
        string $requestId,
        int $timestamp,
        string $signature,
        string $secret
    ): bool {
        try {
            // For GET requests, data might be empty
            $data = $_POST ?? $_GET ?? [];
            
            $computedSignature = self::signRequest($requestId, $timestamp, $data, $secret);
            
            return hash_equals($computedSignature, $signature);
        } catch (Exception $e) {
            Logger::error('Signature validation error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Clean up expired request nonces
     */
    private static function cleanupOldRequests(): void
    {
        try {
            $sql = "DELETE FROM request_nonces WHERE expires_at <= NOW()";
            Database::query($sql);
        } catch (Exception $e) {
            Logger::error('Failed to cleanup old requests', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generate a secret for request signing
     * 
     * @return string Signing secret
     */
    public static function generateSigningSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Get request replay statistics
     * 
     * @return array Statistics
     */
    public static function getStatistics(): array
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM request_nonces";
            $total = Database::fetch($sql);
            
            $sql = "SELECT COUNT(*) as active FROM request_nonces WHERE expires_at > NOW()";
            $active = Database::fetch($sql);
            
            return [
                'total_nonces' => $total['total'] ?? 0,
                'active_nonces' => $active['active'] ?? 0,
                'max_age_seconds' => self::MAX_REQUEST_AGE
            ];
        } catch (Exception $e) {
            Logger::error('Failed to get replay protection stats', [
                'error' => $e->getMessage()
            ]);
            return [
                'total_nonces' => 0,
                'active_nonces' => 0,
                'max_age_seconds' => self::MAX_REQUEST_AGE
            ];
        }
    }
}
