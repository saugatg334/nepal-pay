<?php
/**
 * User Controller - Handles all user operations
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../controllers/WalletController.php';

class UserController {
    private $userModel;
    private $walletCtrl;
    
    public function __construct() {
        requireLogin();
        
        $this->userModel = new User();
        $this->walletCtrl = new WalletController();
    }
    
    /**
     * Show user dashboard
     */
    public function dashboard() {
        $user_id = $_SESSION['user_id'];
        
        $user = $this->userModel->getUserById($user_id);
        $balance = $this->userModel->getWalletBalance($user_id);
        $recentTxns = $this->userModel->getTransactionHistory($user_id, 10);
        
        $pageTitle = 'Dashboard';
        $currentPage = 'dashboard';
        $userModel = $this->userModel;
        
        include __DIR__ . '/../views/user/dashboard.php';
    }
    
    /**
     * Show wallet page
     */
    public function wallet() {
        $user_id = $_SESSION['user_id'];
        
        $user = $this->userModel->getUserById($user_id);
        $balance = $this->userModel->getWalletBalance($user_id);
        $recentTxns = $this->userModel->getTransactionHistory($user_id, 10);
        
        $pageTitle = 'Wallet';
        $currentPage = 'wallet';
        
        include __DIR__ . '/../views/user/wallet.php';
    }
    
    /**
     * Handle deposit
     */
    public function deposit() {
        $user_id = $_SESSION['user_id'];
        $amount = floatval($_POST['amount'] ?? 0);
        
        if ($amount < 10 || $amount > 50000) {
            flash('error', 'Amount must be between Rs 10 and Rs 50,000');
            redirect('/wallet');
            return;
        }
        
        $result = $this->walletCtrl->deposit($user_id, $amount);
        
        if ($result['success'] ?? false) {
            flash('success', $result['message'] ?? 'Deposit successful');
        } else {
            flash('error', $result['error'] ?? 'Deposit failed');
        }
        
        redirect('/wallet');
    }
    
    /**
     * Show send money page
     */
    public function showSendMoney() {
        $user_id = $_SESSION['user_id'];
        $balance = $this->userModel->getWalletBalance($user_id);
        
        include __DIR__ . '/../views/user/send-money.php';
    }
    
    /**
     * Handle send money
     */
    public function sendMoney() {
        $user_id = $_SESSION['user_id'];
        $to = $_POST['to'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $note = $_POST['note'] ?? '';
        
        if (empty($to) || $amount <= 0) {
            flash('error', 'Invalid input');
            redirect('/send-money');
            return;
        }
        
        $result = $this->walletCtrl->transfer($user_id, $to, $amount, $note);
        
        if (($result['success'] ?? false) === true) {
            flash('success', $result['message'] ?? 'Transfer successful');
        } else {
            flash('error', $result['error'] ?? 'Transfer failed');
        }
        
        redirect('/wallet');
    }
    
    /**
     * Show request money page
     */
    public function showRequestMoney() {
        include __DIR__ . '/../views/user/request-money.php';
    }
    
    /**
     * Handle request money
     */
    public function requestMoney() {
        flash('success', 'Money request sent!');
        redirect('/dashboard');
    }
    
    /**
     * Show transactions
     */
    public function transactions() {
        $user_id = $_SESSION['user_id'];
        $type = $_GET['type'] ?? null;
        $status = $_GET['status'] ?? null;
        
        $txns = $this->userModel->getTransactionHistory($user_id, 50, 0, $type);
        
        $pageTitle = 'Transactions';
        $currentPage = 'transactions';
        
        include __DIR__ . '/../views/user/transactions.php';
    }
    
    /**
     * Show profile
     */
    public function profile() {
        $user_id = $_SESSION['user_id'];
        $user = $this->userModel->getUserById($user_id);
        
        include __DIR__ . '/../views/user/profile.php';
    }
    
    /**
     * Update profile
     */
    public function updateProfile() {
        $user_id = $_SESSION['user_id'];
        
        try {
            $this->userModel->updateProfile($user_id, [
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $_POST['email'] ?? ''
            ]);
            flash('success', 'Profile updated');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
        }
        
        redirect('/profile');
    }
    
    /**
     * Show KYC
     */
    public function kyc() {
        $user_id = $_SESSION['user_id'];
        $user = $this->userModel->getUserById($user_id);
        
        include __DIR__ . '/../views/user/kyc.php';
    }
    
    /**
     * Submit KYC
     */
    public function submitKYC() {
        $user_id = $_SESSION['user_id'];
        
        try {
            $this->userModel->updateKYCStatus($user_id, 'pending', json_encode([
                'document_type' => $_POST['document_type'] ?? '',
                'document_number' => $_POST['document_number'] ?? '',
                'submitted_at' => date('Y-m-d H:i:s')
            ]));
            flash('success', 'KYC submitted for review');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
        }
        
        redirect('/kyc');
    }
    
    /**
     * Show change password
     */
    public function showChangePassword() {
        include __DIR__ . '/../views/user/change-password.php';
    }
    
    /**
     * Change password
     */
    public function changePassword() {
        $user_id = $_SESSION['user_id'];
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if ($new !== $confirm) {
            flash('error', 'Passwords do not match');
            redirect('/change-password');
            return;
        }
        
        try {
            $this->userModel->changePassword($user_id, $current, $new);
            flash('success', 'Password changed successfully');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
        }
        
        redirect('/profile');
    }
    
    /**
     * Show notifications
     */
    public function notifications() {
        $user_id = $_SESSION['user_id'];
        
        include __DIR__ . '/../views/user/notifications.php';
    }
    
    /**
     * Show set PIN
     */
    public function showSetPin() {
        include __DIR__ . '/../views/user/set-pin.php';
    }
    
    /**
     * Set PIN
     */
    public function setPin() {
        $user_id = $_SESSION['user_id'];
        $pin = $_POST['pin'] ?? '';
        
        if (strlen($pin) < 4 || strlen($pin) > 6) {
            flash('error', 'PIN must be 4-6 digits');
            redirect('/set-pin');
            return;
        }
        
        try {
            $this->userModel->setPIN($user_id, $pin);
            flash('success', 'PIN set successfully');
            redirect('/dashboard');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
            redirect('/set-pin');
        }
    }
}
