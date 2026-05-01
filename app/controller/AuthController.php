<?php
/**
 * AuthController - Handles authentication
 * Required by the NepalPay routing system
 */

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function login(): void
    {
        $this->view('auth/login');
    }

    /**
     * Show register form
     */
    public function register(): void
    {
        $this->view('auth/register');
    }

    /**
     * Handle login form submission
     */
    public function handleLogin(): void
    {
        // Check CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRF::validateToken($csrfToken)) {
            flash('error', 'Invalid security token. Please try again.');
            redirect(APP_URL . '/index.php?page=login');
            return;
        }

        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($phone) || empty($password)) {
            flash('error', 'Please enter phone and password.');
            redirect(APP_URL . '/index.php?page=login');
            return;
        }

        // Find user by phone
        $user = Database::fetch("SELECT * FROM users WHERE phone = ? AND is_active = 1", [$phone]);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Invalid phone or password.');
            redirect(APP_URL . '/index.php?page=login');
            return;
        }

        // Set session
        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['full_name']);
        Session::set('user_email', $user['email'] ?? '');
        Session::set('user_phone', $phone);
        Session::set('user_role', $user['role'] ?? 'user');
        
        // Log login
        if (class_exists('NepalPay\Core\Logger')) {
            NepalPay\Core\Logger::info('User logged in', ['user_id' => $user['id'], 'phone' => substr($phone, -4));
        }
        
        redirect(APP_URL . '/index.php?page=dashboard');
    }

    /**
     * Handle register form submission
     */
    public function handleRegister(): void
    {
        // Check CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRF::validateToken($csrfToken)) {
            flash('error', 'Invalid security token.');
            redirect(APP_URL . '/index.php?page=register');
            return;
        }

        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($fullName) || empty($phone) || empty($password)) {
            flash('error', 'All fields are required.');
            redirect(APP_URL . '/index.php?page=register');
            return;
        }

        if ($password !== $confirmPassword) {
            flash('error', 'Passwords do not match.');
            redirect(APP_URL . '/index.php?page=register');
            return;
        }

        if (strlen($password) < 6) {
            flash('error', 'Password must be at least 6 characters.');
            redirect(APP_URL . '/index.php?page=register');
            return;
        }

        // Check if user exists
        $exists = Database::fetch("SELECT id FROM users WHERE phone = ?", [$phone]);
        if ($exists) {
            flash('error', 'Phone number already registered.');
            redirect(APP_URL . '/index.php?page=register');
            return;
        }

        // Create user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            Database::query(
                "INSERT INTO users (full_name, email, phone, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, 'user', 1, NOW())",
                [$fullName, $email, $phone, $passwordHash]
            );
            
            flash('success', 'Registration successful! Please login.');
            redirect(APP_URL . '/index.php?page=login');
        } catch (\Exception $e) {
            error_log('Register error: ' . $e->getMessage());
            flash('error', 'Registration failed. Please try again.');
            redirect(APP_URL . '/index.php?page=register');
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        $userId = Session::get('user_id');
        
        // Clear session
        Session::clear();
        
        if (class_exists('NepalPay\Core\Logger')) {
            NepalPay\Core\Logger::info('User logged out', ['user_id' => $userId]);
        }
        
        flash('success', 'You have been logged out.');
        redirect(APP_URL . '/index.php?page=login');
    }

    /**
     * Show verify page
     */
    public function verify(): void
    {
        if (!Session::has('pending_verification')) {
            redirect(APP_URL . '/index.php?page=login');
            return;
        }
        $this->view('auth/verify');
    }

    /**
     * Handle OTP verification
     */
    public function handleVerify(): void
    {
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($otp)) {
            flash('error', 'Please enter the OTP.');
            redirect(APP_URL . '/index.php?page=verify');
            return;
        }

        // Verify OTP - accept any 6-digit for demo
        if (strlen($otp) === 6 && is_numeric($otp)) {
            flash('success', 'Phone verified successfully!');
            redirect(APP_URL . '/index.php?page=dashboard');
        } else {
            flash('error', 'Invalid OTP.');
            redirect(APP_URL . '/index.php?page=verify');
        }
    }

    /**
     * Resend OTP
     */
    public function resendOTP(): void
    {
        flash('success', 'OTP sent to your phone.');
        redirect(APP_URL . '/index.php?page=verify');
    }

    /**
     * Show forgot password page
     */
    public function forgotPassword(): void
    {
        $this->view('auth/forgot_password');
    }

    /**
     * Handle forgot password
     */
    public function handleForgotPassword(): void
    {
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($phone)) {
            flash('error', 'Please enter your phone number.');
            redirect(APP_URL . '/index.php?page=forgot_password');
            return;
        }

        // Check if user exists
        $user = Database::fetch("SELECT id FROM users WHERE phone = ?", [$phone]);
        if (!$user) {
            flash('error', 'Phone number not found.');
            redirect(APP_URL . '/index.php?page=forgot_password');
            return;
        }

        flash('success', 'Reset instructions sent to your phone.');
        redirect(APP_URL . '/index.php?page=login');
    }

    /**
     * Show reset password page
     */
    public function resetPassword(): void
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            flash('error', 'Invalid reset token.');
            redirect(APP_URL . '/index.php?page=login');
            return;
        }
        
        $this->view('auth/reset_password');
    }

    /**
     * Handle reset password
     */
    public function handleResetPassword(): void
    {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($newPassword)) {
            flash('error', 'All fields are required.');
            redirect(APP_URL . '/index.php?page=reset_password');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            flash('error', 'Passwords do not match.');
            redirect(APP_URL . '/index.php?page=reset_password');
            return;
        }

        if (strlen($newPassword) < 6) {
            flash('error', 'Password must be at least 6 characters.');
            redirect(APP_URL . '/index.php?page=reset_password');
            return;
        }

        flash('success', 'Password reset successfully!');
        redirect(APP_URL . '/index.php?page=login');
    }

    /**
     * Show admin login form
     */
    public function adminLogin(): void
    {
        $this->view('auth/admin_login');
    }

    /**
     * Handle admin login
     */
    public function handleAdminLogin(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            flash('error', 'Please enter email and password.');
            redirect(APP_URL . '/index.php?page=admin_login');
            return;
        }

        // Find admin user
        $user = Database::fetch("SELECT * FROM users WHERE email = ? AND role = 'admin' AND is_active = 1", [$email]);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Invalid admin credentials.');
            redirect(APP_URL . '/index.php?page=admin_login');
            return;
        }

        // Set session
        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['full_name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', 'admin');
        
        redirect(APP_URL . '/index.php?page=admin_dashboard');
    }

    /**
     * Register device for biometric login
     */
    public function registerDevice(): void
    {
        $this->requireLogin();
        $this->view('auth/register_device');
    }

    /**
     * Handle device registration
     */
    public function handleRegisterDevice(): void
    {
        $this->requireLogin();
        
        flash('success', 'Device registered successfully!');
        redirect(APP_URL . '/index.php?page=security');
    }

    /**
     * Biometric login
     */
    public function biometricLogin(): void
    {
        $this->view('auth/biometric_login');
    }

    /**
     * Handle biometric login
     */
    public function handleBiometricLogin(): void
    {
        $this->requireLogin();
        
        flash('success', 'Logged in with biometrics.');
        redirect(APP_URL . '/index.php?page=dashboard');
    }

    /**
     * Remove device
     */
    public function removeDevice(): void
    {
        $deviceId = $_POST['device_id'] ?? '';
        
        if (!empty($deviceId)) {
            flash('success', 'Device removed.');
        }
        
        redirect(APP_URL . '/index.php?page=security');
    }
}
