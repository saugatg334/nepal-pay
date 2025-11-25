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

    public function register($name, $phone, $password, $confirmPassword = null) {
        try {
            // Check password confirmation if provided
            if ($confirmPassword !== null && $password !== $confirmPassword) {
                setFlash('error', 'Passwords do not match.');
                return false;
            }

            if ($this->userModel->findUserByPhone($phone)) {
                setFlash('error', 'User already exists with this phone number.');
                return false;
            }

            $register = $this->userModel->register($name, $phone, $password);
            if ($register) {
                setFlash('success', 'Registration successful! Please login.');
                return true;
            } else {
                setFlash('error', 'Registration failed. Please try again.');
                return false;
            }
        } catch (Exception $e) {
            setFlash('error', $e->getMessage());
            return false;
        }
    }

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

    public function logout() {
        session_destroy();
        setFlash('success', 'You have been logged out successfully.');
        header("Location: login.php");
        exit;
    }

    public function setPIN($userId, $pin) {
        try {
            if (strlen($pin) < 4 || strlen($pin) > 6) {
                setFlash('error', 'PIN must be 4-6 digits long.');
                return false;
            }

            if (!preg_match('/^[0-9]+$/', $pin)) {
                setFlash('error', 'PIN must contain only numbers.');
                return false;
            }

            if ($this->userModel->setPIN($userId, $pin)) {
                setFlash('success', 'PIN set successfully!');
                return true;
            } else {
                setFlash('error', 'Failed to set PIN. Please try again.');
                return false;
            }
        } catch (Exception $e) {
            setFlash('error', 'PIN setup failed: ' . $e->getMessage());
            return false;
        }
    }

    public function updateKYC($userId, $documents) {
        try {
            // In a real application, you'd validate and store documents
            // For now, we'll just update the status to pending
            if ($this->userModel->updateKYCStatus($userId, 'pending', json_encode($documents))) {
                setFlash('success', 'KYC documents submitted successfully. Please wait for verification.');
                return true;
            } else {
                setFlash('error', 'Failed to submit KYC documents. Please try again.');
                return false;
            }
        } catch (Exception $e) {
            setFlash('error', 'KYC submission failed: ' . $e->getMessage());
            return false;
        }
    }
}
?>
