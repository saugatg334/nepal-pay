<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use \Database;
use \Exception;

/**
 * Idempotency Service
 * 
 * PROBLEM: Clients retry requests on timeout. Without idempotency,
 *          each retry executes the transaction again → DOUBLE SPENDING.
 * 
 * SOLUTION: Store request + response. On retry with same key, return
 *           cached response instead of re-executing.
 * 
 * HOW IT WORKS:
 * 1. Client sends X-Idempotency-Key header with unique UUID
 * 2. We hash the request (key + endpoint + body)
 * 3. Check if we've seen this before
 * 4. If yes: return cached response immediately (no transaction executed)
 * 5. If no: execute transaction, cache response, return it
 * 
 * GUARANTEES: Exactly-once semantics even with retries
 * 
 * REAL-WORLD EXAMPLE:
 * - User sends transfer (Idempotency-Key: abc123)
 * - Transfer executes, returns success
 * - Network glitch, response lost
 * - User retries with same Idempotency-Key: abc123
 * - We find cached response, return same result
 * - No second transfer executed
 */
class IdempotencyService
{
    private static Database $db;

    public static function init(): void
    {
        self::$db = Database::getInstance();
    }

    /**
     * Check if this request has been processed before
     * 
     * @param int $userId
     * @param string $idempotencyKey - Client-provided unique key
     * @param string $endpoint - API endpoint (e.g., /api/transfers)
     * @param string $requestBody - Serialized request data for integrity check
     * 
     * @return array|null - Cached response if exists, null if new request
     */
    public static function checkCache(
        int $userId,
        string $idempotencyKey,
        string $endpoint,
        string $requestBody
    ): ?array {
        try {
            // Request fingerprint prevents key reuse with different payloads
            $requestHash = hash('sha256', $requestBody);

            $sql = "
                SELECT 
                    response_code,
                    response_body,
                    response_headers,
                    status,
                    error_message
                FROM idempotency_keys
                WHERE user_id = ?
                AND idempotency_key = ?
                AND endpoint = ?
                AND request_hash = ?
                AND expires_at > NOW()
                LIMIT 1
            ";

            $cached = Database::fetch($sql, [
                $userId,
                $idempotencyKey,
                $endpoint,
                $requestHash
            ]);

            if ($cached) {
                Logger::info('Idempotency cache HIT', [
                    'user_id' => $userId,
                    'key' => $idempotencyKey,
                    'endpoint' => $endpoint,
                    'status' => $cached['status']
                ]);

                return $cached;
            }

            return null;
        } catch (Exception $e) {
            Logger::error('Idempotency check failed', [
                'error' => $e->getMessage(),
                'key' => $idempotencyKey
            ]);
            return null;
        }
    }

    /**
     * Mark request as being processed (status: pending)
     * 
     * @return bool - Successfully marked or already exists
     */
    public static function markPending(
        int $userId,
        string $idempotencyKey,
        string $endpoint,
        string $requestBody
    ): bool {
        try {
            $requestHash = hash('sha256', $requestBody);

            $sql = "
                INSERT INTO idempotency_keys
                (user_id, idempotency_key, endpoint, request_hash, status)
                VALUES (?, ?, ?, ?, 'pending')
                ON DUPLICATE KEY UPDATE
                status = 'pending'
            ";

            Database::query($sql, [
                $userId,
                $idempotencyKey,
                $endpoint,
                $requestHash
            ]);

            return true;
        } catch (Exception $e) {
            Logger::error('Failed to mark pending', [
                'error' => $e->getMessage(),
                'key' => $idempotencyKey
            ]);
            return false;
        }
    }

    /**
     * Cache successful response
     * 
     * @param int $userId
     * @param string $idempotencyKey
     * @param string $endpoint
     * @param string $requestBody
     * @param int $responseCode - HTTP status (200, 201, etc)
     * @param string $responseBody - JSON response
     * @param array $responseHeaders - Response headers (optional)
     */
    public static function cacheSuccess(
        int $userId,
        string $idempotencyKey,
        string $endpoint,
        string $requestBody,
        int $responseCode,
        string $responseBody,
        array $responseHeaders = []
    ): void {
        try {
            $requestHash = hash('sha256', $requestBody);

            $sql = "
                UPDATE idempotency_keys
                SET 
                    status = 'completed',
                    response_code = ?,
                    response_body = ?,
                    response_headers = ?,
                    completed_at = NOW()
                WHERE user_id = ?
                AND idempotency_key = ?
                AND endpoint = ?
                AND request_hash = ?
            ";

            Database::query($sql, [
                $responseCode,
                $responseBody,
                json_encode($responseHeaders),
                $userId,
                $idempotencyKey,
                $endpoint,
                $requestHash
            ]);

            Logger::info('Idempotency response cached', [
                'user_id' => $userId,
                'key' => $idempotencyKey,
                'endpoint' => $endpoint,
                'code' => $responseCode
            ]);
        } catch (Exception $e) {
            Logger::error('Failed to cache success', [
                'error' => $e->getMessage(),
                'key' => $idempotencyKey
            ]);
        }
    }

    /**
     * Cache failed response (error)
     */
    public static function cacheError(
        int $userId,
        string $idempotencyKey,
        string $endpoint,
        string $requestBody,
        int $responseCode,
        string $errorMessage
    ): void {
        try {
            $requestHash = hash('sha256', $requestBody);

            $sql = "
                UPDATE idempotency_keys
                SET 
                    status = 'failed',
                    response_code = ?,
                    error_message = ?,
                    completed_at = NOW()
                WHERE user_id = ?
                AND idempotency_key = ?
                AND endpoint = ?
                AND request_hash = ?
            ";

            Database::query($sql, [
                $responseCode,
                $errorMessage,
                $userId,
                $idempotencyKey,
                $endpoint,
                $requestHash
            ]);

            Logger::warning('Idempotency error cached', [
                'user_id' => $userId,
                'key' => $idempotencyKey,
                'endpoint' => $endpoint,
                'error' => $errorMessage
            ]);
        } catch (Exception $e) {
            Logger::error('Failed to cache error', [
                'error' => $e->getMessage(),
                'key' => $idempotencyKey
            ]);
        }
    }

    /**
     * Validate idempotency key format
     * 
     * Must be UUID or similar (32-128 chars, alphanumeric + dashes/underscores)
     */
    public static function validateKey(string $key): bool
    {
        return strlen($key) >= 32 && strlen($key) <= 128 &&
               preg_match('/^[a-zA-Z0-9_-]+$/', $key) === 1;
    }

    /**
     * Cleanup expired idempotency keys (older than 24 hours)
     * 
     * Run via cron: php -r "require 'app/bootstrap.php'; IdempotencyService::cleanup();"
     * Or add to your cron scheduler
     */
    public static function cleanup(): int
    {
        try {
            $sql = "DELETE FROM idempotency_keys WHERE expires_at < NOW()";
            $result = Database::query($sql, []);
            
            $count = Database::connection()->rowCount();
            Logger::info('Idempotency cleanup completed', [
                'records_deleted' => $count
            ]);

            return $count;
        } catch (Exception $e) {
            Logger::error('Idempotency cleanup failed', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
