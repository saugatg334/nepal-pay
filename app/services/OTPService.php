<?php
/**
 * OTP Service
 * Generates and verifies OTP codes for secure actions
 */
require_once __DIR__ . '/../config/database.php';

class OTPService {
    private $conn;

    const MAX_ATTEMPTS = 3;
    const EXPIRY_MINUTES = 5;

    const TYPE_REGISTER = 'register';
    const TYPE_LOGIN = 'login';
    const TYPE_TRANSACTION = 'transaction';
    const TYPE_PASSWORD_RESET = 'password_reset';
    const TYPE_DEVICE_VERIFY = 'device_verify';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Generate 6-digit OTP
     */
    public function generate() {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Hash OTP for storage (never store plain)
     */
    public function hashOTP($otp) {
        return hash('sha256', $otp);
    }

    /**
     * Create OTP for user
     */
    public function create($userId, $type = self::TYPE_LOGIN) {
        $otp = $this->generate();
        $otpHash = $this->hashOTP($otp);
        $expiresAt = date('Y-m-d H:i:s', time() + (self::EXPIRY_MINUTES * 60));
        $ipAddress = $this->getClientIP();

        try {
            $query = "INSERT INTO otp_codes (user_id, code_hash, code_plain, type, expires_at, ip_address) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId, $otpHash, $otp, $type, $expiresAt, $ipAddress]);

            return [
                'success' => true,
                'otp' => $otp,
                'expires_at' => $expiresAt
            ];
        } catch (PDOException $e) {
            error_log("OTP create error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create OTP'];
        }
    }

    /**
     * Verify OTP
     */
    public function verify($userId, $otp, $type = self::TYPE_LOGIN) {
        $otpHash = $this->hashOTP($otp);

        try {
            $query = "SELECT * FROM otp_codes 
                    WHERE user_id = ? AND code_hash = ? AND type = ? 
                    AND is_used = 0 AND expires_at > NOW() 
                    ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId, $otpHash, $type]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                $this->incrementAttempts($userId, $type);
                return ['success' => false, 'error' => 'Invalid or expired OTP'];
            }

            if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                return ['success' => false, 'error' => 'Too many attempts. Please request new OTP'];
            }

            $query = "UPDATE otp_codes SET is_used = 1 WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$record['id']]);

            return ['success' => true, 'verified' => true];
        } catch (PDOException $e) {
            error_log("OTP verify error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Verification failed'];
        }
    }

    /**
     * Increment failed attempts
     */
    private function incrementAttempts($userId, $type) {
        try {
            $query = "UPDATE otp_codes SET attempts = attempts + 1 
                    WHERE user_id = ? AND type = ? AND expires_at > NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId, $type]);
        } catch (PDOException $e) {
        }
    }

    /**
     * Check if device is trusted (no OTP needed)
     */
    public function isDeviceTrusted($userId) {
        require_once __DIR__ . '/DeviceFingerprint.php';
        $device = new DeviceFingerprint();
        return $device->isTrusted($userId);
    }

    /**
     * Request OTP conditionally (only for untrusted devices)
     */
    public function requestIfNeeded($userId, $type = self::TYPE_LOGIN) {
        if ($this->isDeviceTrusted($userId)) {
            return ['success' => true, 'trusted_device' => true, 'otp_required' => false];
        }

        return $this->create($userId, $type);
    }

    /**
     * Clean up expired OTPs
     */
    public function cleanup() {
        try {
            $stmt = $this->conn->query("DELETE FROM otp_codes WHERE expires_at < NOW()");
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    private function getClientIP() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}