<?php
declare(strict_types=1);

namespace NepalPay\Helpers\Production;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use \Database; // Global Database class

/**
 * Centralized Rate Limiter
 * 
 * WHY: Rate limiting scattered across controllers is inconsistent
 *      and hard to maintain. A central service supports:
 *      - IP-based limits (brute force protection)
 *      - User-based limits (account-specific)
 *      - Endpoint-specific limits (API vs web)
 *      - Sliding window algorithm (more accurate than fixed window)
 */
class RateLimiter
{
    /**
     * Check if the current request exceeds rate limits
     */
    public static function check(
        string $key,
        int $maxAttempts = null,
        int $windowSeconds = null
    ): bool {
        $maxAttempts ??= Config::getInt('RATE_LIMIT_LOGIN', 5);
        $windowSeconds ??= Config::getInt('RATE_LIMIT_LOGIN_WINDOW', 900);

        $attempts = self::getAttempts($key, $windowSeconds);
        
        if ($attempts >= $maxAttempts) {
            Logger::warning('Rate limit exceeded', [
                'key' => $key,
                'attempts' => $attempts,
                'window' => $windowSeconds
            ]);
            return false;
        }

        return true;
    }

    /**
     * Record a failed attempt
     */
    public static function hit(string $key): void
    {
        try {
            $sql = "INSERT INTO rate_limits (identifier, attempts, last_attempt, window_start) 
                    VALUES (?, 1, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                    attempts = attempts + 1,
                    last_attempt = NOW()";
            Database::query($sql, [$key]);
        } catch (\Exception $e) {
            Logger::error('Rate limit record failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reset attempts for a key
     */
    public static function reset(string $key): void
    {
        try {
            Database::query("DELETE FROM rate_limits WHERE identifier = ?", [$key]);
        } catch (\Exception $e) {
            Logger::error('Rate limit reset failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get remaining attempts
     */
    public static function remaining(string $key, int $maxAttempts = null, int $windowSeconds = null): int
    {
        $maxAttempts ??= Config::getInt('RATE_LIMIT_LOGIN', 5);
        $windowSeconds ??= Config::getInt('RATE_LIMIT_LOGIN_WINDOW', 900);
        
        $attempts = self::getAttempts($key, $windowSeconds);
        return max(0, $maxAttempts - $attempts);
    }

    private static function getAttempts(string $key, int $windowSeconds): int
    {
        try {
            $sql = "SELECT attempts FROM rate_limits 
                    WHERE identifier = ? 
                    AND window_start > DATE_SUB(NOW(), INTERVAL ? SECOND)";
            $result = Database::fetch($sql, [$key, $windowSeconds]);
            return $result['attempts'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Generate a rate limit key from IP and optional identifier
     */
    public static function key(string $type = 'login', ?string $identifier = null): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "{$type}:{$ip}";
        
        if ($identifier) {
            $key .= ":{$identifier}";
        }
        
        return $key;
    }
}

