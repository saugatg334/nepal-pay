<?php
/**
 * Auth Controller - Handles all authentication
 * Production-level with audit logging, rate limiting, error handling
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../services/RateLimitService.php';

class AuthController {
    private $rateLimitService;
    private $auth;
    private $userModel;
    private $auditLog;
    private $rateLimiter;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->initSecuritySession();
        $this->auth = new Auth();
        $this->userModel = new User();
        $this->auditLog = new AuditLog();
$this->rateLimitService = new RateLimitService();
    }

    private function initSecuritySession() {
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
            $_SESSION['created_at'] = time();
            $_SESSION['ip_address'] = $this->getClientIP();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
        if ($_SESSION['ip_address'] !== $this->getClientIP()) {
            $this->clearSessionAndRedirect();
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

    private function clearSessionAndRedirect() {
        $_SESSION = [];
        session_destroy();
        redirect('/login');
    }

    private function isSecureSessionValid() {
        if (!isset($_SESSION['initiated']) || !isset($_SESSION['created_at'])) {
            return false;
        }
        $sessionAge = time() - $_SESSION['created_at'];
        if ($sessionAge > 86400) {
            return false;
        }
        return true;
    }
    
    /**
     * Show login page
     */
    public function showLogin() {
        if (isUserLoggedIn()) {
            redirect('/dashboard');
            return;
        }
        
        $csrf = csrf_token();
        include __DIR__ . '/../views/auth/login.php';
    }
    
    /**
     * Handle login
     */
    public function login() {
        try {
$rateLimit = $this->rateLimitService->checkLoginAttempt($_POST['identifier'] ?? 'unknown');
            
            if (!$rateLimit['allowed']) {
                $retryAfter = $rateLimit['retry_after'] ?? 60;
                flash('error', "Too many login attempts. Please try again in {$retryAfter} seconds.");
                redirect('/login');
                return;
            }

            $identifier = trim($_POST['identifier'] ?? '');
            $password = $_POST['password'] ?? '';
            $csrf = $_POST['csrf_token'] ?? '';

            if (!verifyCsrf($csrf)) {
                flash('error', 'Invalid security token. Please try again.');
                redirect('/login');
                return;
            }

            if (empty($identifier) || empty($password)) {
                flash('error', 'Please enter your credentials');
                redirect('/login');
                return;
            }

            if ($this->userModel->isAccountLocked($identifier)) {
                $this->auditLog->log(
                    AuditLog::ACTION_LOGIN_FAILED,
                    null,
                    "Account locked: {$identifier}",
                    ['identifier' => $identifier, 'reason' => 'account_locked'],
                    null,
                    null
                );
                flash('error', 'Account is temporarily locked. Please try again later.');
                redirect('/login');
                return;
            }

            $user = $this->auth->login($identifier, $password);

            if ($user) {
                $this->userModel->resetFailedLoginAttempts($identifier);
                $this->userModel->updateLoginInfo($user['id']);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'] ?? $user['name'] ?? 'User';
                $_SESSION['user_phone'] = $user['phone'] ?? '';
                $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();

                session_regenerate_id(true);

                $this->auditLog->log(
                    AuditLog::ACTION_LOGIN,
                    $user['id'],
                    "User logged in",
                    [
                        'identifier' => $identifier,
                        'is_admin' => $user['is_admin'] ?? 0,
                        'ip' => $this->getClientIP()
                    ],
                    'user',
                    $user['id']
                );

                flash('success', 'Welcome back!');

                if ($user['is_admin'] ?? 0) {
                    redirect('/admin/dashboard');
                } else {
                    redirect('/dashboard');
                }
            } else {
                $this->userModel->incrementFailedLogin($identifier);

                if ($this->userModel->findUserByPhone($identifier)) {
                    $userData = $this->userModel->findUserByPhone($identifier);
                    $attempts = ($userData['failed_login_attempts'] ?? 0) + 1;
                    
                    if ($attempts >= 5) {
                        $this->userModel->lockAccount($identifier, 30);
                        $this->auditLog->log(
                            AuditLog::ACTION_LOGIN_FAILED,
                            null,
                            "Account locked due to failed attempts",
                            ['identifier' => $identifier, 'attempts' => $attempts]
                        );
                        flash('error', 'Too many failed attempts. Account locked for 30 minutes.');
                    } else {
                        $this->auditLog->log(
                            AuditLog::ACTION_LOGIN_FAILED,
                            null,
                            "Invalid password",
                            ['identifier' => $identifier, 'attempts' => $attempts],
                            'user',
                            $userData['id'] ?? null
                        );
                        flash('error', 'Invalid credentials. ' . (5 - $attempts) . ' attempts remaining.');
                    }
                } else {
                    $this->auditLog->log(
                        AuditLog::ACTION_LOGIN_FAILED,
                        null,
                        "User not found",
                        ['identifier' => $identifier]
                    );
                    flash('error', 'Invalid credentials');
                }

                redirect('/login');
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $this->auditLog->log(
                AuditLog::ACTION_LOGIN_FAILED,
                null,
                "Login exception",
                ['identifier' => $_POST['identifier'] ?? 'unknown', 'error' => $e->getMessage()]
            );
            flash('error', 'An error occurred. Please try again later.');
            redirect('/login');
        }
    }
    
    /**
     * Show register page
     */
    public function showRegister() {
        if (isUserLoggedIn()) {
            redirect('/dashboard');
            return;
        }
        
        $csrf = csrf_token();
        include __DIR__ . '/../views/auth/register.php';
    }
    
    /**
     * Handle registration
     */
    public function register() {
        $data = [
            'full_name' => $_POST['name'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => ($_POST['phone'] ?? '') . '@nepalpay.local',
            'password' => $_POST['password'] ?? ''
        ];
        
        $csrf = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrf($csrf)) {
            flash('error', 'Invalid request');
            redirect('/register');
            return;
        }
        
        $result = $this->auth->register($data);
        
        if ($result['success']) {
            flash('success', 'Registration successful! Please login.');
            redirect('/login');
        } else {
            flash('error', $result['error'] ?? 'Registration failed');
            redirect('/register');
        }
    }
    
    /**
     * Handle logout
     */
    public function logout() {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $userName = $_SESSION['user_name'] ?? 'Unknown';

            $this->auditLog->log(
                AuditLog::ACTION_LOGOUT,
                $userId,
                "User logged out",
                ['user_name' => $userName],
                'user',
                $userId
            );
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }

        $_SESSION = [];
        session_destroy();
        redirect('/login');
    }
    
    /**
     * Show admin login
     */
    public function showAdminLogin() {
        if (isAdminLoggedIn()) {
            redirect('/admin/dashboard');
            return;
        }
        
        $csrf = csrf_token();
        include __DIR__ . '/../views/admin/login.php';
    }
    
    /**
     * Handle admin login
     */
    public function adminLogin() {
        $identifier = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';
        $csrf = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrf($csrf)) {
            flash('error', 'Invalid request');
            redirect('/admin/login');
            return;
        }
        
        $user = $this->auth->login($identifier, $password);
        
        if ($user && ($user['is_admin'] ?? 0)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'] ?? 'Admin';
            $_SESSION['user_phone'] = $user['phone'] ?? '';
            $_SESSION['is_admin'] = 1;
            
            session_regenerate_id(true);
            
            flash('success', 'Welcome Admin!');
            redirect('/admin/dashboard');
        } else {
            flash('error', 'Invalid admin credentials');
            redirect('/admin/login');
        }
    }
    
    /**
     * Show forgot password
     */
    public function showForgotPassword() {
        $csrf = csrf_token();
        include __DIR__ . '/../views/auth/forgot-password.php';
    }
    
    /**
     * Send reset link
     */
    public function sendResetLink() {
        $identifier = $_POST['identifier'] ?? '';
        
        if ($this->auth->sendResetOTP($identifier)) {
            flash('success', 'Reset link sent! Check console for OTP.');
        } else {
            flash('error', 'User not found');
        }
        
        redirect('/forgot-password');
    }
}

// Helper functions
if (!function_exists('isUserLoggedIn')) {
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
}

if (!function_exists('isAdminLoggedIn')) {
function isAdminLoggedIn() {
    return isUserLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}
}

if (!function_exists('requireLogin')) {
function requireLogin() {
    if (!isUserLoggedIn()) {
redirect('/login');
        exit;
    }
}

}

if (!function_exists('requireAdmin')) {
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect('/admin/login');
        exit;
    }
}
}

if (!function_exists('csrf_token')) {
if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
}

if (!function_exists('verifyCsrf')) {
if (!function_exists('verifyCsrf')) {
    function verifyCsrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
}

if (!function_exists('flash')) {
if (!function_exists('flash')) {
    function flash($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }
}
}

if (!function_exists('getFlash')) {
    function getFlash($key) {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}
