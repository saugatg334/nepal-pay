<?php
declare(strict_types=1);

namespace NepalPay\Services;

use NepalPay\Core\Config;
use NepalPay\Core\Logger;
use \Database; // Global Database class
use \User;     // Global User model
use \Wallet;   // Global Wallet model
use NepalPay\Helpers\Production\Input;
use NepalPay\Helpers\Production\RateLimiter;

class AuthService
{
    private static string $lastError = '';

    /**
     * Authenticate a user by phone/email and password
     * Returns user array on success, false on failure
     * SAFE: Wraps all db operations in try/catch to prevent fatal errors
     */
    public static function authenticate(string $identifier, string $password): array|false
    {
        try {
            // Rate limiting check BEFORE lookup
            $ipKey = RateLimiter::key('login');
            $maxAttempts = Config::getInt('RATE_LIMIT_LOGIN', 5);
            $windowSeconds = Config::getInt('RATE_LIMIT_LOGIN_WINDOW', 900);
            
            if (!RateLimiter::check($ipKey, $maxAttempts, $windowSeconds)) {
                self::$lastError = 'Too many login attempts. Please try again later.';
                Logger::warning('Rate limit exceeded before authentication', [
                    'ip' => Input::ip(),
                ]);
                return false;
            }
            
            // Get user by phone or email
            $user = User::findByPhoneOrEmail($identifier);
            
            if (!$user) {
                self::$lastError = 'Invalid credentials';
                RateLimiter::hit($ipKey);
                return false;
            }
            
            // Check if account is active
            if (!isset($user['is_active']) || !$user['is_active']) {
                self::$lastError = 'Account is inactive. Contact support.';
                Logger::warning('Login attempt on inactive account', ['user_id' => $user['id']]);
                return false;
            }
            
            // Check if frozen
            if (!empty($user['is_frozen'])) {
                self::$lastError = 'Account is frozen';
                Logger::warning('Login attempt on frozen account', ['user_id' => $user['id']]);
                return false;
            }
            
            // Check account lock (per-user lockout)
            if (User::isLocked($user)) {
                self::$lastError = 'Account is temporarily locked due to multiple failed login attempts.';
                Logger::warning('Login attempt on locked account', ['user_id' => $user['id']]);
                return false;
            }
            
            // Verify password
            if (!User::verifyPassword($user, $password)) {
                // Record failed attempt
                $attempts = ($user['failed_attempts'] ?? 0) + 1;
                $maxAttempts = Config::getInt('MAX_LOGIN_ATTEMPTS', 5);
                
                if ($attempts >= $maxAttempts) {
                    $lockedUntil = date('Y-m-d H:i:s', time() + Config::getInt('LOCKOUT_DURATION', 1800));
                    User::updateLoginAttempts($user['id'], $attempts, $lockedUntil);
                    Logger::security('Account locked due to failed attempts', [
                        'user_id' => $user['id'],
                        'attempts' => $attempts
                    ]);
                    self::$lastError = 'Invalid credentials. Account is now locked for ' . Config::getInt('LOCKOUT_DURATION', 1800) . ' seconds.';
                } else {
                    User::updateLoginAttempts($user['id'], $attempts);
                    self::$lastError = 'Invalid credentials';
                }
                
                RateLimiter::hit($ipKey);
                return false;
            }
            
            // Clear failed attempts on successful login
            User::updateLoginAttempts($user['id'], 0, null);
            RateLimiter::reset($ipKey);
            self::$lastError = '';
            
            return $user;
            
        } catch (\Exception $e) {
            self::$lastError = 'Authentication failed. Please try again.';
            error_log("AuthService::authenticate ERROR: " . $e->getMessage());
            Logger::error('Authentication exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function getLastError(): string
    {
        return self::$lastError;
    }

    public static function generateOTP(int $userId): string
    {
        try {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiry = date('Y-m-d H:i:s', time() + Config::getInt('OTP_EXPIRY', 300));
            
            Database::query("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?", [$otp, $expiry, $userId]);
            
            $user = User::find($userId);
            if ($user && !empty($user['email']) && class_exists('EmailService')) {
                try {
                    EmailService::sendOTP($user, $otp);
                } catch (\Exception $e) {
                    Logger::error('Failed to send OTP email', ['error' => $e->getMessage()]);
                }
            }
            
            return $otp;
        } catch (\Exception $e) {
            Logger::error('OTP generation failed', ['error' => $e->getMessage()]);
            return '000000';
        }
    }
    
    public static function verifyOTP(int $userId, string $otp): bool
    {
        try {
            $sql = "SELECT * FROM users WHERE id = ? AND otp_code = ? AND otp_expires > NOW()";
            $user = Database::fetch($sql, [$userId, $otp]);
            
            if ($user) {
                Database::query("UPDATE users SET otp_code = NULL, otp_expires = NULL, is_verified = 1 WHERE id = ?", [$userId]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Logger::error('OTP verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public static function changePassword(int $userId, string $newPassword): bool
    {
        if (strlen($newPassword) < Config::getInt('PASSWORD_MIN_LENGTH', 8)) {
            throw new \InvalidArgumentException('Password too short');
        }
        return User::updatePassword($userId, $newPassword);
    }
    
    public static function createUser(array $data): int|false
    {
        if (empty($data['phone']) || empty($data['password']) || empty($data['full_name'])) {
            throw new \InvalidArgumentException('Missing required fields');
        }
        
        if (User::isPhoneExists($data['phone'])) {
            throw new \InvalidArgumentException('Phone number already registered');
        }
        if (!empty($data['email']) && User::isEmailExists($data['email'])) {
            throw new \InvalidArgumentException('Email already registered');
        }
        
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['role'] = $data['role'] ?? 'user';
        $data['is_active'] = 1;
        $data['is_verified'] = 0;
        
        try {
            User::create($data);
            $userId = Database::lastInsertId();
            
            if ($userId) {
                $walletNumber = 'NP' . substr(preg_replace('/\D/', '', $data['phone']), -10);
                Database::query("INSERT INTO wallets (user_id, balance, wallet_number) VALUES (?, ?, ?)", [$userId, 0.00, $walletNumber]);
                Logger::info('User created with wallet', ['user_id' => $userId]);
            }
            
            return $userId;
        } catch (\Exception $e) {
            Logger::error('User creation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
