<?php
/**
 * Auth Model - Complete authentication system
 */
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function register($data) {
        // Validation
        $errors = [];
        if (empty($data['full_name']) || empty($data['phone']) || empty($data['email']) || empty($data['password'])) {
            $errors[] = 'All fields required';
        }
        if (!preg_match('/^[0-9]{10}$/', $data['phone'])) {
            $errors[] = 'Phone must be 10 digits';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email';
        }
        if (strlen($data['password']) < 6) {
            $errors[] = 'Password must be 6+ characters';
        }
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        try {
            // Check duplicates
            $check = $this->findByPhoneOrEmail($data['phone'], $data['email']);
            if ($check) {
                return ['success' => false, 'error' => 'Phone or email already registered'];
            }

            // Hash password
            $hashed = password_hash($data['password'], PASSWORD_DEFAULT);

            $query = "INSERT INTO {$this->table} (full_name, phone, email, password_hash) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $success = $stmt->execute([$data['full_name'], $data['phone'], $data['email'], $hashed]);

            return $success ? ['success' => true] : ['success' => false, 'error' => 'Registration failed'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function login($identifier, $password) {
        $user = $this->findByPhoneOrEmail($identifier, $identifier);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        return $user;
    }

    public function isAdmin($user_id) {
        $query = "SELECT is_admin FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ? $user['is_admin'] : 0;
    }

    public function findByPhoneOrEmail($phone, $email) {
        $query = "SELECT * FROM {$this->table} WHERE phone = ? OR email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$phone, $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function sendResetOTP($identifier) {
        $user = $this->findByPhoneOrEmail($identifier, $identifier);
        if (!$user) return false;

        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $query = "UPDATE {$this->table} SET otp_code = ?, otp_expires = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $success = $stmt->execute([$otp, $expires, $user['id']]);

        // Simulate SMS
        error_log("OTP for {$identifier}: {$otp}");

        return $success;
    }

    public function verifyOTP($identifier, $otp) {
        $user = $this->findByPhoneOrEmail($identifier, $identifier);
        if (!$user) return false;

        $now = date('Y-m-d H:i:s');
        $query = "SELECT * FROM {$this->table} WHERE (phone = ? OR email = ?) AND otp_code = ? AND otp_expires > ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$identifier, $identifier, $otp, $now]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function resetPassword($user_id, $new_password) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE {$this->table} SET password_hash = ?, otp_code = NULL, otp_expires = NULL WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$hashed, $user_id]);
    }

    public function addNotification($user_id, $title, $message, $type = 'info') {
        $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$user_id, $title, $message, $type]);
    }

    public function getNotifications($user_id, $limit = 10) {
        $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

