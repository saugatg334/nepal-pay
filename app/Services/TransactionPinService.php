<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use Database;
use \User;
use Exception;

/**
 * Transaction PIN Service
 * 
 * WHY: Adds a second factor for financial transactions (send money, withdraw, bill pay).
 * - PIN stored hashed (password_hash)
 * - Throttled attempts with lockout
 * - Separate from login password
 * - Meets fintech compliance (2FA for payments)
 * 
 * Usage: 
 *   if (TransactionPinService::isRequired($userId)) {
 *       $valid = TransactionPinService::verify($userId, $inputPin);
 *       if (!$valid) throw new Exception('Invalid PIN');
 *   }
 */
class TransactionPinService
{
    /**
     * Check if user has set a transaction PIN
     */
    public static function isPinSet(int $userId): bool
    {
        $user = User::find($userId);
        return $user && !empty($user['transaction_pin']);
    }
    
    /**
     * Determine if PIN is required for this transaction
     * Based on user setting or threshold amount
     */
    public static function isRequired(int $userId, float $amount = 0): bool
    {
        // If user has set a PIN, always required
        if (self::isPinSet($userId)) {
            return true;
        }
        
        // Optional: Require PIN for high-value transactions even if not set?
        // Many apps require setting PIN before first transfer.
        // For now: only if set.
        return false;
    }
    
    /**
     * Verify transaction PIN
     * Returns true on success, false on failure
     * Updates lockout after too many attempts
     */
    public static function verify(int $userId, string $pin): bool
    {
        $user = User::find($userId);
        if (!$user || empty($user['transaction_pin'])) {
            // PIN not set, verification not needed
            return true;
        }
        
        // Check if locked
        if ($user['pin_locked_until'] && strtotime($user['pin_locked_until']) > time()) {
            Logger::warning('Transaction PIN attempt while locked', ['user_id' => $userId]);
            return false;
        }
        
        // Verify
        if (!password_verify($pin, $user['transaction_pin'])) {
            self::recordFailedAttempt($userId);
            return false;
        }
        
        // On success: reset attempts
        self::clearFailedAttempts($userId);
        return true;
    }
    
    /**
     * Set or update transaction PIN (hashed)
     */
    public static function setPin(int $userId, string $pin): bool
    {
        // Validate PIN format (4 or 6 digits)
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            throw new \InvalidArgumentException('PIN must be 4-6 digits');
        }
        
        $hashed = password_hash($pin, PASSWORD_BCRYPT);
        
        $sql = "UPDATE users SET transaction_pin = ?, pin_attempts = 0, pin_locked_until = NULL WHERE id = ?";
        Database::query($sql, [$hashed, $userId]);
        
        Logger::info('Transaction PIN set/updated', ['user_id' => $userId]);
        return true;
    }
    
    /**
     * Remove transaction PIN (disable requirement)
     */
    public static function removePin(int $userId): bool
    {
        $sql = "UPDATE users SET transaction_pin = NULL, pin_attempts = 0, pin_locked_until = NULL WHERE id = ?";
        Database::query($sql, [$userId]);
        
        Logger::info('Transaction PIN removed', ['user_id' => $userId]);
        return true;
    }
    
    /**
     * Record a failed PIN attempt
     */
    private static function recordFailedAttempt(int $userId): void
    {
        try {
            $user = User::find($userId);
            $attempts = ($user['pin_attempts'] ?? 0) + 1;
            $maxAttempts = Config::getInt('PIN_MAX_ATTEMPTS', 3);
            
            if ($attempts >= $maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + Config::getInt('PIN_LOCKOUT_MINUTES', 30) * 60);
                $sql = "UPDATE users SET pin_attempts = ?, pin_locked_until = ? WHERE id = ?";
                Database::query($sql, [$attempts, $lockedUntil, $userId]);
                Logger::security('PIN locked due to failed attempts', ['user_id' => $userId]);
            } else {
                $sql = "UPDATE users SET pin_attempts = ? WHERE id = ?";
                Database::query($sql, [$attempts, $userId]);
            }
        } catch (\Exception $e) {
            Logger::error('Failed to record PIN attempt', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Reset failed attempts after successful verification
     */
    private static function clearFailedAttempts(int $userId): void
    {
        try {
            $sql = "UPDATE users SET pin_attempts = 0, pin_locked_until = NULL WHERE id = ?";
            Database::query($sql, [$userId]);
        } catch (\Exception $e) {
            Logger::error('Failed to clear PIN attempts', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Check if PIN is currently locked
     */
    public static function isLocked(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;
        return $user['pin_locked_until'] && strtotime($user['pin_locked_until']) > time();
    }
    
    /**
     * Get remaining attempts before lockout
     */
    public static function remainingAttempts(int $userId): int
    {
        $user = User::find($userId);
        if (!$user) return 0;
        $max = Config::getInt('PIN_MAX_ATTEMPTS', 3);
        $attempts = $user['pin_attempts'] ?? 0;
        return max(0, $max - $attempts);
    }
    
    /**
     * Check if PIN was verified recently (within last 5 minutes)
     * Useful for avoiding repeated verification prompts
     */
    public static function isVerifiedRecently(int $userId): bool
    {
        // In a production system, we'd track verification timestamps
        // For now, we don't have this feature, so return false
        return false;
    }
}

