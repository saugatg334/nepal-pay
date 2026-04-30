<?php
require_once __DIR__ . '/Model.php';

/**
 * User Model
 * 
 * Handles all user-related data operations with security best practices.
 * Passwords are hashed with bcrypt. Account lockout uses locked_until
 * field — NEVER destroys the password.
 */
class User extends Model {
    protected $table = 'users';
    protected $fillable = ['phone', 'email', 'password', 'full_name', 'profile_image', 'role', 'is_active', 'is_verified', 'is_frozen'];
    protected $primaryKey = 'id';

    public static function findByPhone($phone) {
        return self::findBy('phone', $phone);
    }

    public static function findByEmail($email) {
        return self::findBy('email', $email);
    }

    public static function findByPhoneOrEmail($identifier) {
        $sql = "SELECT * FROM users WHERE phone = ? OR email = ? LIMIT 1";
        return Database::fetch($sql, [$identifier, $identifier]);
    }

    public static function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        $userId = self::create($data);
        
        if ($userId) {
            $walletNumber = generateWalletNumber($data['phone']);
            
            $sql = "INSERT INTO wallets (user_id, balance, wallet_number) VALUES (?, ?, ?)";
            Database::query($sql, [$userId, 0.00, $walletNumber]);
        }
        
        return $userId;
    }

    public static function verifyPassword($user, $password) {
        return password_verify($password, $user['password']);
    }

    public static function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        return Database::query($sql, [$hashedPassword, $userId]);
    }

    public static function updateLoginAttempts($userId, $attempts, $lockedUntil = null) {
        $sql = "UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?";
        return Database::query($sql, [$attempts, $lockedUntil, $userId]);
    }

    /**
     * Lock account until specified time.
     * CRITICAL: Never overwrites the password. Uses locked_until field only.
     */
    public static function lockAccount($userId, $lockedUntil) {
        $sql = "UPDATE users SET locked_until = ?, failed_attempts = 0 WHERE id = ?";
        return Database::query($sql, [$lockedUntil, $userId]);
    }

    public static function isLocked($user) {
        if (empty($user['locked_until'])) {
            return false;
        }

        $lockedUntil = strtotime($user['locked_until']);
        if ($lockedUntil <= time()) {
            // Automatically clear expired lockouts so users can log in again.
            self::updateLoginAttempts($user['id'], 0, null);
            return false;
        }

        return true;
    }

    public static function setOTP($userId, $otp) {
        $expiry = date('Y-m-d H:i:s', strtotime('+' . Config::get('OTP_EXPIRY', 300) . ' seconds'));
        
        $sql = "UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?";
        Database::query($sql, [$otp, $expiry, $userId]);
        
        // Send email
        $user = self::find($userId);
        if ($user && $user['email']) {
            EmailService::sendOTP($user, $otp);
        }
        
        return true;
    }

    public static function verifyOTP($userId, $otp) {
        $sql = "SELECT * FROM users WHERE id = ? AND otp_code = ? AND otp_expires > NOW()";
        $user = Database::fetch($sql, [$userId, $otp]);
        
        if ($user) {
            $sql = "UPDATE users SET otp_code = NULL, otp_expires = NULL, is_verified = 1 WHERE id = ?";
            Database::query($sql, [$userId]);
            return true;
        }
        
        return false;
    }

    public static function clearOTP($userId) {
        $sql = "UPDATE users SET otp_code = NULL, otp_expires = NULL WHERE id = ?";
        return Database::query($sql, [$userId]);
    }

    public static function freezeAccount($userId, $freeze = true) {
        $sql = "UPDATE users SET is_frozen = ? WHERE id = ?";
        return Database::query($sql, [$freeze ? 1 : 0, $userId]);
    }

    public static function getWallet($userId) {
        $sql = "SELECT * FROM wallets WHERE user_id = ?";
        return Database::fetch($sql, [$userId]);
    }

    public static function getAll($page = 1, $perPage = 20) {
        return self::paginate($page, $perPage);
    }

    public static function getActiveCount() {
        return self::count(['is_active' => 1]);
    }

    public static function getFrozenCount() {
        return self::count(['is_frozen' => 1]);
    }

    public static function isEmailExists($email, $excludeId = null) {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as total FROM users WHERE email = ? AND id != ?";
            $result = Database::fetch($sql, [$email, $excludeId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM users WHERE email = ?";
            $result = Database::fetch($sql, [$email]);
        }
        return $result['total'] > 0;
    }

    public static function isPhoneExists($phone, $excludeId = null) {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as total FROM users WHERE phone = ? AND id != ?";
            $result = Database::fetch($sql, [$phone, $excludeId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM users WHERE phone = ?";
            $result = Database::fetch($sql, [$phone]);
        }
        return $result['total'] > 0;
    }
}
