(UPDATE existing file: set is_admin in session after successful login)
<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/session_helper.php';

class AuthController {
    private $userModel;

    public function __construct() {
        try {
            $this->userModel = new User();
        } catch (Exception $e) {
            setFlash('error', $e->getMessage());
            return false;
        }
    }

    // ... existing register() ... unchanged ...

    public function login($phone, $password, $pin = null) {
        try {
            // Validate phone number format
            if (!preg_match('/^[0-9]{10}$/', $phone)) {
                setFlash('error', 'Please enter a valid 10-digit phone number.');
                return false;
            }

            // Check if account is locked
            if ($this->userModel->isAccountLocked($phone)) {
                setFlash('error', 'Account is temporarily locked due to multiple failed login attempts. Please try again later.');
                return false;
            }

            $user = $this->userModel->findUserByPhone($phone);
            if ($user && password_verify($password, $user['password'])) {
                // Check KYC status
                if ($user['kyc_status'] !== 'approved') {
                    setFlash('warning', 'Your account is pending KYC verification. Please complete KYC to access all features.');
                    // Allow login but with limited access
                }

                // If PIN is set and provided, verify it
                if (!empty($user['pin']) && $pin !== null) {
                    if (!$this->userModel->verifyPIN($user['id'], $pin)) {
                        $this->userModel->incrementFailedLogin($phone);
                        setFlash('error', 'Invalid PIN.');
                        return false;
                    }
                }

                // Reset failed attempts and update login info
                $this->userModel->resetFailedLoginAttempts($phone);
                $this->userModel->updateLoginInfo($user['id']);

                $_SESSION['user_id'] = $user['id'];
                // New: expose is_admin to session for admin pages
                $_SESSION['is_admin'] = !empty($user['is_admin']) ? 1 : 0;

                setFlash('success', 'Login successful!');
                header("Location: dashboard.php");
                exit;
            } else {
                // Increment failed login attempts
                $this->userModel->incrementFailedLogin($phone);

                // Check if account should be locked (after 5 failed attempts)
                $userData = $this->userModel->findUserByPhone($phone);
                if ($userData && $userData['failed_login_attempts'] >= 5) {
                    $this->userModel->lockAccount($phone, 30); // Lock for 30 minutes
                    setFlash('error', 'Account locked due to multiple failed login attempts. Please try again in 30 minutes.');
                    return false;
                }

                setFlash('error', 'Invalid phone number or password.');
                return false;
            }
        } catch (Exception $e) {
            setFlash('error', 'Login failed: ' . $e->getMessage());
            return false;
        }
    }

    // ... rest unchanged ...
?>