<?php
require_once __DIR__ . '/../config/database.php';

class WebAuthn {
    private $rpId;
    private $rpName;
    private $origin;
    
    public function __construct() {
        $this->rpId = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->rpName = APP_NAME ?? 'NepalPay';
        $this->origin = APP_URL ?? 'http://localhost';
    }
    
    public function generateRegisterOptions($userId) {
        $challenge = $this->generateChallenge();
        
        Session::set('webauthn_challenge', $challenge);
        Session::set('webauthn_user_id', $userId);
        
        $user = User::find($userId);
        
        $publicKey = [
            'challenge' => base64_encode($challenge),
            'rp' => [
                'name' => $this->rpName,
                'id' => $this->rpId
            ],
            'user' => [
                'id' => base64_encode(pack('N', $userId)),
                'name' => $user['email'] ?? $user['phone'],
                'displayName' => $user['full_name']
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257]
            ],
            'timeout' => 60000,
            'attestation' => 'none'
        ];
        
        return base64_encode(json_encode($publicKey));
    }
    
    public function generateLoginOptions() {
        $challenge = $this->generateChallenge();
        
        Session::set('webauthn_login_challenge', $challenge);
        
        $publicKey = [
            'challenge' => base64_encode($challenge),
            'rpId' => $this->rpId,
            'timeout' => 60000,
            'userVerification' => 'preferred'
        ];
        
        return base64_encode(json_encode($publicKey));
    }
    
    public function verifyRegistration($data, $userId) {
        $challenge = Session::get('webauthn_challenge');
        $storedUserId = Session::get('webauthn_user_id');
        
        if (!$challenge || $storedUserId != $userId) {
            return ['success' => false, 'error' => 'Invalid challenge'];
        }
        
        $attestationObject = json_decode(base64_decode($data), true);
        
        if (!$attestationObject) {
            return ['success' => false, 'error' => 'Invalid data'];
        }
        
        $credentialId = base64_decode($attestationObject['credentialId']);
        $publicKey = base64_encode($attestationObject['attestationObject']['attStmt']['sig']);
        
        $sql = "INSERT INTO user_devices (user_id, credential_id, public_key, device_name, device_type) 
                VALUES (?, ?, ?, ?, ?)";
        
        $deviceName = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
        $deviceType = $this->detectDeviceType();
        
        Database::query($sql, [$userId, base64_encode($credentialId), $publicKey, $deviceName, $deviceType]);
        
        Session::remove('webauthn_challenge');
        Session::remove('webauthn_user_id');
        
        return ['success' => true];
    }
    
    public function verifyAuthentication($data, $userId) {
        $challenge = Session::get('webauthn_login_challenge');
        
        if (!$challenge) {
            return ['success' => false, 'error' => 'Invalid challenge'];
        }
        
        $assertion = json_decode(base64_decode($data), true);
        
        if (!$assertion) {
            return ['success' => false, 'error' => 'Invalid data'];
        }
        
        $credentialId = base64_encode(base64_decode($assertion['credentialId']));
        
        $sql = "SELECT * FROM user_devices WHERE user_id = ? AND credential_id = ? AND is_active = 1";
        $device = Database::fetch($sql, [$userId, $credentialId]);
        
        if (!$device) {
            return ['success' => false, 'error' => 'Device not registered'];
        }
        
        $sql = "UPDATE user_devices SET last_used = NOW() WHERE id = ?";
        Database::query($sql, [$device['id']]);
        
        Session::remove('webauthn_login_challenge');
        
        return ['success' => true, 'device' => $device];
    }
    
    public function getUserDevices($userId) {
        $sql = "SELECT id, device_name, device_type, is_active, last_used, created_at 
                FROM user_devices WHERE user_id = ? ORDER BY created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }
    
    public function removeDevice($deviceId, $userId) {
        $sql = "UPDATE user_devices SET is_active = 0 WHERE id = ? AND user_id = ?";
        return Database::query($sql, [$deviceId, $userId]);
    }
    
    public function hasDevices($userId) {
        $sql = "SELECT COUNT(*) as count FROM user_devices WHERE user_id = ? AND is_active = 1";
        $result = Database::fetch($sql, [$userId]);
        return $result['count'] > 0;
    }
    
    private function generateChallenge() {
        return random_bytes(32);
    }
    
    private function detectDeviceType() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/mobile/i', $userAgent)) {
            if (preg_match('/android/i', $userAgent)) {
                return 'Android';
            }
            if (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
                return 'iOS';
            }
            return 'Mobile';
        }
        
        if (preg_match('/windows/i', $userAgent)) {
            return 'Windows PC';
        }
        if (preg_match('/mac/i', $userAgent)) {
            return 'Mac';
        }
        if (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        }
        
        return 'Desktop';
    }
}

function isBiometricSupported() {
    return isset($_SERVER['HTTP_SEC_WEBAUTHN']) || 
           (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/chrome|firefox|edge|safari/i', $_SERVER['HTTP_USER_AGENT']));
}