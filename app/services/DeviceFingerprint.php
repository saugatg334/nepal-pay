<?php
/**
 * Device Fingerprint Service
 * Tracks trusted devices for login and transaction verification
 */
require_once __DIR__ . '/../config/database.php';

class DeviceFingerprint {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Generate device fingerprint hash
     */
    public function generateHash() {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
            screenWidth() . 'x' . screenHeight(),
            $_SERVER['HTTP_SEC_CH_UA'] ?? '',
            $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? ''
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Detect device info from user agent
     */
    public function getDeviceInfo() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $deviceType = 'desktop';
        if (preg_match('/mobile|android|iphone/ip', $ua)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/tablet|ipad/ip', $ua)) {
            $deviceType = 'tablet';
        }

        $browser = 'Unknown';
        if (preg_match('/Chrome\/(\d+)/', $ua, $m)) {
            $browser = 'Chrome ' . $m[1];
        } elseif (preg_match('/Firefox\/(\d+)/', $ua, $m)) {
            $browser = 'Firefox ' . $m[1];
        } elseif (preg_match('/Safari\/(\d+)/', $ua, $m)) {
            $browser = 'Safari ' . $m[1];
        } elseif (preg_match('/Edg\/(\d+)/', $ua, $m)) {
            $browser = 'Edge ' . $m[1];
        }

        $os = 'Unknown';
        if (preg_match('/Windows NT 10\.0/', $ua)) {
            $os = 'Windows 10';
        } elseif (preg_match('/Mac OS X (\d+[._]\d+)/', $ua, $m)) {
            $os = 'macOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Android (\d+)/', $ua, $m)) {
            $os = 'Android ' . $m[1];
        } elseif (preg_match('/iPhone OS (\d+)/', $ua, $m)) {
            $os = 'iOS ' . $m[1];
        } elseif (preg_match('/Linux/', $ua)) {
            $os = 'Linux';
        }

        return [
            'type' => $deviceType,
            'browser' => $browser,
            'os' => $os
        ];
    }

    /**
     * Register or update device
     */
    public function registerDevice($userId, $deviceHash = null, $deviceName = null) {
        if (!$deviceHash) {
            $deviceHash = $this->generateHash();
        }

        $deviceInfo = $this->getDeviceInfo();
        $ipAddress = $this->getClientIP();

        try {
            $query = "SELECT * FROM devices WHERE user_id = ? AND device_hash = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId, $deviceHash]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $query = "UPDATE devices SET last_login = NOW(), ip_address = ?, device_type = ?, browser = ?, os = ? 
                        WHERE user_id = ? AND device_hash = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$ipAddress, $deviceInfo['type'], $deviceInfo['browser'], $deviceInfo['os'], $userId, $deviceHash]);
            } else {
                $query = "INSERT INTO devices (user_id, device_hash, device_name, device_type, browser, os, ip_address, last_login, is_trusted) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$userId, $deviceHash, $deviceName ?? 'Unknown Device', $deviceInfo['type'], $deviceInfo['browser'], $deviceInfo['os'], $ipAddress]);
            }

            return $deviceHash;
        } catch (PDOException $e) {
            error_log("Device register error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trust a device after OTP verification
     */
    public function trustDevice($userId, $deviceHash) {
        try {
            $query = "UPDATE devices SET is_trusted = 1, trust_level = 1 WHERE user_id = ? AND device_hash = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$userId, $deviceHash]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check if device is trusted
     */
    public function isTrusted($userId, $deviceHash = null) {
        if (!$deviceHash) {
            $deviceHash = $this->generateHash();
        }

        try {
            $query = "SELECT is_trusted FROM devices WHERE user_id = ? AND device_hash = ? AND is_trusted = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId, $deviceHash]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get user's trusted devices
     */
    public function getUserDevices($userId) {
        try {
            $query = "SELECT * FROM devices WHERE user_id = ? ORDER BY last_login DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Remove a device
     */
    public function removeDevice($userId, $deviceId) {
        try {
            $query = "DELETE FROM devices WHERE id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$deviceId, $userId]);
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

function screenWidth() {
    return $_COOKIE['sw'] ?? '1920';
}

function screenHeight() {
    return $_COOKIE['sh'] ?? '1080';
}