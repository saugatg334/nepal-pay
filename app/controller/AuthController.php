<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../helpers/recaptcha.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/validation.php';

class AuthController extends Controller {
    
    public function login() {
        if (Session::has('user_id')) {
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        }
        
        $this->view('auth/login');
    }
    
    public function adminLogin() {
        if (Session::has('user_id') && Session::get('user_role') === 'admin') {
            $this->redirect(APP_URL . '/index.php?page=admin_dashboard');
        }
        
        $this->render('auth/admin_login');
    }
    
    /**
     * Handle admin login with safe error handling
     */
    public function handleAdminLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($phone) || empty($password)) {
            flash('error', 'All fields are required');
            $this->redirect(APP_URL . '/index.php?page=admin_login');
        }

        $existingUser = User::findByPhoneOrEmail($phone);
        if ($existingUser) {
            if (User::isLocked($existingUser)) {
                $lockedUntil = $existingUser['locked_until'] ? date('Y-m-d H:i:s', strtotime($existingUser['locked_until'])) : 'later';
                flash('error', 'Account locked until ' . $lockedUntil);
                $this->redirect(APP_URL . '/index.php?page=admin_login');
            }

            if (!isset($existingUser['is_active']) || !$existingUser['is_active']) {
                flash('error', 'Account is inactive. Contact support.');
                $this->redirect(APP_URL . '/index.php?page=admin_login');
            }

            if (!empty($existingUser['is_frozen'])) {
                flash('error', 'Account is frozen');
                $this->redirect(APP_URL . '/index.php?page=admin_login');
            }
        }
        
        try {
            $user = \NepalPay\Services\AuthService::authenticate($phone, $password);
        } catch (\Exception $e) {
            flash('error', 'Authentication error: ' . $e->getMessage());
            $this->redirect(APP_URL . '/index.php?page=admin_login');
        }
        
        if (!$user) {
            flash('error', \NepalPay\Services\AuthService::getLastError() ?: 'Invalid admin credentials');
            $this->redirect(APP_URL . '/index.php?page=admin_login');
        }

        if ($user['role'] !== 'admin') {
            flash('error', 'Invalid admin credentials');
            $this->redirect(APP_URL . '/index.php?page=admin_login');
        }
        
        $this->loginUser($user);
        
        flash('success', 'Welcome Admin!');
        $this->redirect(APP_URL . '/index.php?page=admin_dashboard');
    }
    
    /**
     * Handle user login with safe error handling
     */
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        $identifier = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $validation = new Validation($_POST);
        $validation->required(['identifier', 'password']);
        
        if (!$validation->isValid()) {
            foreach ($validation->getAllMessages() as $message) {
                flash('error', $message);
            }
            $this->back();
        }

        $existingUser = User::findByPhoneOrEmail($identifier);
        if ($existingUser) {
            if (User::isLocked($existingUser)) {
                $lockedUntil = $existingUser['locked_until'] ? date('Y-m-d H:i:s', strtotime($existingUser['locked_until'])) : 'later';
                flash('error', 'Account locked until ' . $lockedUntil);
                $this->back();
            }

            if (!isset($existingUser['is_active']) || !$existingUser['is_active']) {
                flash('error', 'Account is inactive. Contact support.');
                $this->back();
            }

            if (!empty($existingUser['is_frozen'])) {
                flash('error', 'Account is frozen');
                $this->back();
            }
        }
        
        try {
            $user = \NepalPay\Services\AuthService::authenticate($identifier, $password);
        } catch (\Exception $e) {
            flash('error', 'Authentication error: ' . $e->getMessage());
            $this->back();
        }
        
        if (!$user) {
            flash('error', \NepalPay\Services\AuthService::getLastError() ?: 'Invalid credentials');
            $this->back();
        }
        
        $this->loginUser($user);
        
        flash('success', 'Welcome back!');
        $this->redirect(APP_URL . '/index.php?page=dashboard');
    }
    
    /**
     * Store login session data - does NOT close session
     */
    private function loginUser($user) {
        Session::init();
        Session::regenerate();
        
        $token = bin2hex(random_bytes(32));
        
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role']);
        Session::set('user_name', $user['full_name']);
        Session::set('login_token', $token);
        
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        
        try {
            $sql = "INSERT INTO sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                    VALUES (?, ?, ?, ?, ?)";
            Database::query($sql, [$user['id'], $token, getClientIP(), getUserAgent(), $expiresAt]);
            
            $sql = "UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?";
            Database::query($sql, [$user['id']]);
        } catch (\Exception $e) {
            error_log("loginUser DB error: " . $e->getMessage());
        }
        
        // Do NOT call Session::save() here - it closes the session!
        // flash() and redirect() need the session to be open
    }
    
    public function register() {
        if (Session::has('user_id')) {
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        }
        
        $this->view('auth/register');
    }
    
    public function handleRegister() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        $validation = new Validation($_POST);
        $validation->required(['full_name', 'email', 'phone', 'password', 'password_confirm'])
                   ->email('email')
                   ->min('password', 8)
                   ->matches('password', 'password_confirm');
        
        if (!$validation->isValid()) {
            foreach ($validation->getAllMessages() as $message) {
                flash('error', $message);
            }
            $this->back();
        }
        
        // Check if email or phone already exists
        $existing = Database::query(
            "SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1",
            [$email, $phone]
        )->fetch();
        
        if ($existing) {
            flash('error', 'Email or phone number already registered');
            $this->back();
        }
        
        $userId = User::createUser([
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ]);
        
        if (!$userId) {
            flash('error', 'Registration failed. Please try again.');
            $this->back();
        }
        
        flash('success', 'Registration successful! Please log in.');
        $this->redirect(APP_URL . '/index.php?page=login');
    }
    
    public function verify() {
        if (!Session::has('temp_user_id')) {
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $this->view('auth/verify');
    }
    
    public function handleVerify() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        $temp_user_id = Session::get('temp_user_id');
        if (!$temp_user_id) {
            flash('error', 'Session expired. Please log in again.');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $otp = $_POST['otp'] ?? '';
        
        if (empty($otp)) {
            flash('error', 'OTP is required');
            $this->back();
        }
        
        $verified = $this->verifyOTP($temp_user_id, $otp);
        
        if (!$verified) {
            flash('error', 'Invalid OTP');
            $this->back();
        }
        
        $user = Database::query(
            "SELECT * FROM users WHERE id = ? LIMIT 1",
            [$temp_user_id]
        )->fetch();
        
        if ($user) {
            Database::query("UPDATE users SET is_verified = 1 WHERE id = ?", [$temp_user_id]);
            $this->loginUser($user);
            flash('success', 'Verification successful!');
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        } else {
            flash('error', 'User not found');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
    }
    
    public function resendOTP() {
        if (!Session::has('temp_user_id')) {
            flash('error', 'Session expired');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $temp_user_id = Session::get('temp_user_id');
        $user = Database::query(
            "SELECT * FROM users WHERE id = ? LIMIT 1",
            [$temp_user_id]
        )->fetch();
        
        if (!$user) {
            flash('error', 'User not found');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $otp = self::generateOTP();
        $expires_at = date('Y-m-d H:i:s', time() + 600);
        
        Database::query(
            "INSERT INTO otp_tokens (user_id, token, type, expires_at) VALUES (?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)",
            [$temp_user_id, $otp, 'verification', $expires_at]
        );
        
        flash('success', 'OTP resent to your email');
        $this->redirect(APP_URL . '/index.php?page=verify');
    }
    
    public function logout() {
        Session::init();
        $user_id = Session::get('user_id');
        
        if ($user_id) {
            Database::query(
                "DELETE FROM sessions WHERE user_id = ?",
                [$user_id]
            );
        }
        
        Session::destroy();
        
        flash('success', 'Logged out successfully');
        $this->redirect(APP_URL . '/index.php?page=login');
    }
    
    public function forgotPassword() {
        if (Session::has('user_id')) {
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        }
        
        $this->view('auth/forgot_password');
    }
    
    public function handleForgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            flash('error', 'Email is required');
            $this->back();
        }
        
        $user = Database::query(
            "SELECT id, email FROM users WHERE email = ? LIMIT 1",
            [$email]
        )->fetch();
        
        if (!$user) {
            flash('error', 'Email not found');
            $this->back();
        }
        
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 3600);
        
        Database::query(
            "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)",
            [$user['id'], $token, $expires_at]
        );
        
        flash('success', 'Password reset link sent to your email');
        $this->redirect(APP_URL . '/index.php?page=login');
    }
    
    public function resetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            flash('error', 'Invalid reset link');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $reset = Database::query(
            "SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1",
            [$token]
        )->fetch();
        
        if (!$reset) {
            flash('error', 'Reset link expired or invalid');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        Session::set('reset_token', $token);
        Session::set('reset_user_id', $reset['user_id']);
        
        $this->view('auth/reset_password');
    }
    
    public function handleResetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        $reset_user_id = Session::get('reset_user_id');
        $reset_token = Session::get('reset_token');
        
        if (!$reset_user_id || !$reset_token) {
            flash('error', 'Session expired');
            $this->redirect(APP_URL . '/index.php?page=forgot_password');
        }
        
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        
        $validation = new Validation($_POST);
        $validation->required(['password', 'password_confirm'])
                   ->min('password', 8)
                   ->matches('password', 'password_confirm');
        
        if (!$validation->isValid()) {
            foreach ($validation->getAllMessages() as $message) {
                flash('error', $message);
            }
            $this->back();
        }
        
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        
        Database::query(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashed, $reset_user_id]
        );
        
        Database::query("DELETE FROM password_resets WHERE token = ?", [$reset_token]);
        
        Session::remove('reset_token');
        Session::remove('reset_user_id');
        
        flash('success', 'Password reset successful! Please log in.');
        $this->redirect(APP_URL . '/index.php?page=login');
    }
    
    public function registerDevice() {
        if (!Session::has('user_id')) {
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $this->view('auth/register_device');
    }
    
    public function handleRegisterDevice() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        if (!Session::has('user_id')) {
            flash('error', 'Please log in first');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $user_id = Session::get('user_id');
        $device_name = $_POST['device_name'] ?? 'Unknown Device';
        
        $device_id = bin2hex(random_bytes(16));
        $public_key = bin2hex(random_bytes(32));
        
        Database::query(
            "INSERT INTO registered_devices (user_id, device_id, device_name, public_key, last_used) 
             VALUES (?, ?, ?, ?, NOW())",
            [$user_id, $device_id, $device_name, $public_key]
        );
        
        flash('success', 'Device registered successfully');
        $this->redirect(APP_URL . '/index.php?page=security');
    }
    
    public function biometricLogin() {
        $this->view('auth/biometric_login');
    }
    
    public function handleBiometricLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $device_id = $_POST['device_id'] ?? '';
        
        if (empty($device_id)) {
            flash('error', 'Device ID is required');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $device = Database::query(
            "SELECT user_id FROM registered_devices WHERE device_id = ? AND deleted_at IS NULL LIMIT 1",
            [$device_id]
        )->fetch();
        
        if (!$device) {
            flash('error', 'Device not registered');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $user = Database::query(
            "SELECT * FROM users WHERE id = ? LIMIT 1",
            [$device['user_id']]
        )->fetch();
        
        if ($user) {
            Database::query("UPDATE registered_devices SET last_used = NOW() WHERE device_id = ?", [$device_id]);
            $this->loginUser($user);
            flash('success', 'Biometric login successful!');
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        }
    }
    
    public function removeDevice() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        if (!Session::has('user_id')) {
            flash('error', 'Please log in first');
            $this->redirect(APP_URL . '/index.php?page=login');
        }
        
        $device_id = $_POST['device_id'] ?? '';
        $user_id = Session::get('user_id');
        
        if (empty($device_id)) {
            flash('error', 'Device ID is required');
            $this->back();
        }
        
        Database::query(
            "UPDATE registered_devices SET deleted_at = NOW() WHERE user_id = ? AND device_id = ?",
            [$user_id, $device_id]
        );
        
        flash('success', 'Device removed successfully');
        $this->redirect(APP_URL . '/index.php?page=security');
    }
    
    private function verifyOTP($user_id, $otp) {
        $result = Database::query(
            "SELECT expires_at FROM otp_tokens WHERE user_id = ? AND token = ? AND type = 'verification' LIMIT 1",
            [$user_id, $otp]
        )->fetch();
        
        if (!$result) {
            return false;
        }
        
        if (strtotime($result['expires_at']) < time()) {
            return false;
        }
        
        Database::query("DELETE FROM otp_tokens WHERE user_id = ? AND type = 'verification'", [$user_id]);
        return true;
    }
    
    private static function generateOTP() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
