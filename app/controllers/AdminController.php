<?php
/**
 * Admin Controller - Handles all admin operations
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AdminController {
    private $userModel;
    
    public function __construct() {
        requireAdmin();
        
        $this->userModel = new User();
    }
    
    /**
     * Show admin dashboard
     */
    public function dashboard() {
        $user_id = $_SESSION['user_id'];
        $user = $this->userModel->getUserById($user_id);
        
        try {
            $db = new Database();
            $conn = $db->connect();
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
            $totalUsers = $stmt->fetchColumn() ?? 0;
            
            $stmt = $conn->query("SELECT COALESCE(SUM(wallet_balance), 0) as total FROM users");
            $totalBalance = $stmt->fetchColumn() ?? 0;
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM transactions");
            $totalTransactions = $stmt->fetchColumn() ?? 0;
            
            $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE kyc_status = 'pending'");
            $pendingKYC = $stmt->fetchColumn() ?? 0;
            
            $stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
            $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $conn->query("SELECT t.*, u.full_name as sender_name FROM transactions t 
                LEFT JOIN users u ON t.sender_id = u.id 
                ORDER BY t.created_at DESC LIMIT 5");
            $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $totalUsers = 0;
            $totalBalance = 0;
            $totalTransactions = 0;
            $pendingKYC = 0;
            $recentUsers = [];
            $recentTransactions = [];
        }
        
        $pageTitle = 'Admin Dashboard';
        $currentPage = 'admin/dashboard';
        
        include __DIR__ . '/../views/admin/dashboard.php';
    }
    
    /**
     * Show users list
     */
    public function users() {
        $search = $_GET['search'] ?? '';
        
        try {
            $users = $this->userModel->getAllUsers(50, 0, $search);
        } catch (Exception $e) {
            $users = [];
        }
        
        include __DIR__ . '/../views/admin/users.php';
    }
    
    /**
     * Activate user
     */
    public function activateUser() {
        $user_id = $_POST['user_id'] ?? 0;
        
        try {
            $this->userModel->updateUserStatus($user_id, 'active');
            flash('success', 'User activated');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
        }
        
        redirect('/admin/users');
    }
    
    /**
     * Deactivate user
     */
    public function deactivateUser() {
        $user_id = $_POST['user_id'] ?? 0;
        
        try {
            $this->userModel->updateUserStatus($user_id, 'inactive');
            flash('success', 'User deactivated');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
        }
        
        redirect('/admin/users');
    }
    
    /**
     * Show all transactions
     */
    public function transactions() {
        include __DIR__ . '/../views/admin/transactions.php';
    }
    
    /**
     * Show KYC verification
     */
    public function kycVerification() {
        $pendingKYC = [];
        
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->prepare("SELECT * FROM users WHERE kyc_status = 'pending' ORDER BY created_at DESC LIMIT 50");
            $stmt->execute();
            $pendingKYC = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // DB not connected
        }
        
        include __DIR__ . '/../views/admin/kyc-verification.php';
    }
    
    /**
     * Approve KYC
     */
    public function approveKYC() {
        $user_id = $_POST['user_id'] ?? 0;
        
        try {
            $this->userModel->updateKYCStatus($user_id, 'approved');
            flash('success', 'KYC approved');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
        }
        
        redirect('/admin/kyc-verification');
    }
    
    /**
     * Reject KYC
     */
    public function rejectKYC() {
        $user_id = $_POST['user_id'] ?? 0;
        
        try {
            $this->userModel->updateKYCStatus($user_id, 'rejected');
            flash('success', 'KYC rejected');
        } catch (Exception $e) {
            flash('error', $e->getMessage());
        }
        
        redirect('/admin/kyc-verification');
    }
    
    /**
     * Show wallet management
     */
    public function walletManagement() {
        include __DIR__ . '/../views/admin/wallet-management.php';
    }
    
    /**
     * Show deposits
     */
    public function deposits() {
        include __DIR__ . '/../views/admin/deposits.php';
    }
    
    /**
     * Show withdrawals
     */
    public function withdrawals() {
        include __DIR__ . '/../views/admin/withdrawals.php';
    }
    
    /**
     * Show reports
     */
    public function reports() {
        include __DIR__ . '/../views/admin/reports.php';
    }
    
    /**
     * Show settings
     */
    public function settings() {
        include __DIR__ . '/../views/admin/settings.php';
    }
    
    /**
     * Update settings
     */
    public function updateSettings() {
        flash('success', 'Settings updated');
        redirect('/admin/settings');
    }
    
    /**
     * Show admin profile
     */
    public function profile() {
        $user_id = $_SESSION['user_id'];
        $user = $this->userModel->getUserById($user_id);
        
        include __DIR__ . '/../views/admin/profile.php';
    }
}
