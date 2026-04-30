<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Wallet.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Merchant.php';

use NepalPay\Helpers\Production\RBAC;

/**
 * Admin Controller with RBAC and CSRF Protection
 * 
 * WHY: Simple role string checks ('admin') are not granular enough
 * for production. RBAC allows permission-based authorization.
 * 
 * SECURITY: All state-changing operations require CSRF tokens
 * and are logged for audit purposes.
 */
class AdminController extends Controller {
    
    // Map actions to required permissions
    private const PERMISSIONS = [
        'index' => 'admin.dashboard',
        'users' => 'admin.users.view',
        'userDetail' => 'admin.users.view',
        'freezeUser' => 'admin.users.freeze',
        'unfreezeUser' => 'admin.users.unfreeze',
        'transactions' => 'admin.transactions.view',
        'merchants' => 'admin.merchants.view',
        'toggleMerchant' => 'admin.merchants.approve',
        'analytics' => 'admin.analytics.view',
    ];
    
    public function __construct() {
        $this->requireAdmin();
    }
    
    private function requirePermission(string $permission): void {
        // CSRF protection for state-changing operations
        if (in_array($permission, [
            'admin.users.freeze',
            'admin.users.unfreeze', 
            'admin.merchants.approve'
        ])) {
            $this->validateCSRF();
        }
        
        $role = Session::get('user_role');
        
        if (!RBAC::can($role, $permission)) {
            // Log unauthorized attempt
            if (class_exists('NepalPay\\Core\\Logger')) {
                NepalPay\Core\Logger::security('Permission denied', [
                    'user_id' => Session::get('user_id'),
                    'role' => $role,
                    'required' => $permission,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            
            $this->forbidden();
        }
    }
    
    /**
     * Validate CSRF token for state-changing operations
     */
    private function validateCSRF(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!\NepalPay\Helpers\CSRF::validateToken($token)) {
            if (class_exists('NepalPay\\Core\\Logger')) {
                NepalPay\Core\Logger::security('CSRF validation failed', [
                    'user_id' => Session::get('user_id'),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
            }
            $this->unauthorized();
        }
    }

    public function index() {
        $this->requirePermission(self::PERMISSIONS['index']);
        
        $page = intval($_GET['page'] ?? 1);
        
        $users = User::getAll($page, 20);
        
        $totalUsers = User::getActiveCount();
        $frozenUsers = User::getFrozenCount();
        $totalBalance = Wallet::getTotalBalance();
        $dailyStats = Transaction::getDailyStats(7);
        
        $this->render('admin/index', [
            'users' => $users,
            'total_users' => $totalUsers,
            'frozen_users' => $frozenUsers,
            'total_balance' => $totalBalance,
            'daily_stats' => $dailyStats
        ]);
    }

    public function users() {
        $this->requirePermission(self::PERMISSIONS['users']);
        
        $page = intval($_GET['page'] ?? 1);
        $search = $_GET['search'] ?? '';
        
        if ($search) {
            $sql = "SELECT * FROM users WHERE full_name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY created_at DESC LIMIT 20";
            $users = Database::fetchAll($sql, ["%{$search}%", "%{$search}%", "%{$search}%"]);
        } else {
            $users = User::getAll($page, 20);
        }
        
        $this->render('admin/users', ['users' => $users, 'search' => $search]);
    }

    public function userDetail() {
        $this->requirePermission(self::PERMISSIONS['userDetail']);
        
        $userId = intval($_GET['id'] ?? 0);
        
        if (!$userId) {
            flash('error', 'User not found');
            $this->redirect(APP_URL . '/index.php?page=admin_users');
        }
        
        $user = User::find($userId);
        $wallet = Wallet::findByUserId($userId);
        $transactions = Transaction::getUserTransactions($userId, 50);
        
        if (!$user) {
            $this->error404();
        }
        
        $this->render('admin/user_detail', [
            'user' => $user,
            'wallet' => $wallet,
            'transactions' => $transactions
        ]);
    }

    public function freezeUser() {
        $this->requirePermission(self::PERMISSIONS['freezeUser']);
        
        // Check request method - freeze should be POST with CSRF protection
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // For GET requests, require confirmation form with CSRF token
            $userId = intval($_GET['id'] ?? 0);
            if (!$userId) {
                flash('error', 'Invalid request');
                $this->back();
            }
            
            // Render confirmation page with CSRF token
            $user = User::find($userId);
            $this->render('admin/freeze_confirm', [
                'user' => $user,
                'csrf_token' => \NepalPay\Helpers\CSRF::getToken()
            ]);
            return;
        }
        
        // POST request - perform action with CSRF validation
        // CSRF validation handled by requirePermission for this permission
        
        $userId = intval($_POST['id'] ?? 0);
        
        if (!$userId) {
            flash('error', 'Invalid request');
            $this->back();
        }
        
        $adminId = Session::get('user_id');
        
        User::freezeAccount($userId, true);
        
        Notification::sendAccountFreezeNotification($userId, true);
        
        $sql = "INSERT INTO admin_actions (admin_id, target_user_id, action_type, notes) VALUES (?, ?, 'freeze', 'Account frozen by admin')";
        Database::query($sql, [$adminId, $userId]);
        
        // Log via SecurityService if available
        if (class_exists('NepalPay\\Services\\SecurityService')) {
            NepalPay\Services\SecurityService::logAdminAction(
                $adminId,
                'user.freeze',
                $userId,
                null,
                'frozen',
                'Account frozen by admin'
            );
        }
        
        // Audit log via SecurityService
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sql = "INSERT INTO security_logs (user_id, action_type, description, ip_address, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        Database::query($sql, [$adminId, 'admin.freeze', "Account frozen: user_id={$userId}", $ip, 'success']);
        
        flash('success', 'User frozen successfully');
        $this->redirect(APP_URL . '/index.php?page=admin_users');
    }

    public function unfreezeUser() {
        $this->requirePermission(self::PERMISSIONS['unfreezeUser']);
        
        // CSRF validation handled by requirePermission for this permission
        
        $userId = intval($_POST['id'] ?? 0);
        
        if (!$userId) {
            flash('error', 'Invalid request');
            $this->back();
        }
        
        $adminId = Session::get('user_id');
        
        User::freezeAccount($userId, false);
        
        Notification::sendAccountFreezeNotification($userId, false);
        
        $sql = "INSERT INTO admin_actions (admin_id, target_user_id, action_type, notes) VALUES (?, ?, 'unfreeze', 'Account unfrozen by admin')";
        Database::query($sql, [$adminId, $userId]);
        
        // Log via SecurityService
        if (class_exists('NepalPay\\Services\\SecurityService')) {
            NepalPay\Services\SecurityService::logAdminAction(
                $adminId,
                'user.unfreeze',
                $userId,
                'frozen',
                'active',
                'Account unfrozen by admin'
            );
        }
        
        // Audit log via SecurityService
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sql = "INSERT INTO security_logs (user_id, action_type, description, ip_address, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        Database::query($sql, [$adminId, 'admin.unfreeze', "Account unfrozen: user_id={$userId}", $ip, 'success']);
        
        flash('success', 'User unfrozen successfully');
        $this->redirect(APP_URL . '/index.php?page=admin_users');
    }

    public function transactions() {
        $this->requirePermission(self::PERMISSIONS['transactions']);
        
        $page = intval($_GET['page'] ?? 1);
        $filter = $_GET['filter'] ?? 'all';
        
        $result = Transaction::getAllTransactions($page, 20);
        
        $this->render('admin/transactions', [
            'transactions' => $result['data'],
            'pagination' => $result
        ]);
    }

    public function merchants() {
        $this->requirePermission(self::PERMISSIONS['merchants']);
        
        $page = intval($_GET['page'] ?? 1);
        
        $merchants = Merchant::getAll($page, 20);
        
        $this->render('admin/merchants', ['merchants' => $merchants]);
    }

    public function toggleMerchant() {
        $this->requirePermission(self::PERMISSIONS['toggleMerchant']);
        
        // CSRF validation handled by requirePermission for this permission
        // toggleMerchant should be POST to prevent CSRF
        
        $id = intval($_POST['id'] ?? 0);
        
        if (!$id) {
            flash('error', 'Invalid request');
            $this->back();
        }
        
        $adminId = Session::get('user_id');
        $merchant = Merchant::find($id);
        
        Merchant::toggleActive($id);
        
        // Audit log
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $action = $merchant['is_active'] ? 'merchant.unapprove' : 'merchant.approve';
        $sql = "INSERT INTO security_logs (user_id, action_type, description, ip_address, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        Database::query($sql, [$adminId, $action, "Merchant toggled: merchant_id={$id}, was_active=" . $merchant['is_active'], $ip, 'success']);
        
        flash('success', 'Merchant status updated');
        $this->redirect(APP_URL . '/index.php?page=admin_merchants');
    }
    
    public function analytics() {
        $this->requirePermission(self::PERMISSIONS['analytics']);
        
        $dailyStats = Transaction::getDailyStats(30);
        $monthlyStats = Transaction::getMonthlyStats();
        
        $totalUsers = User::getActiveCount();
        $totalBalance = Wallet::getTotalBalance();
        
        $this->render('admin/analytics', [
            'daily_stats' => $dailyStats,
            'monthly_stats' => $monthlyStats,
            'total_users' => $totalUsers,
            'total_balance' => $totalBalance
        ]);
    }
    
    protected function requireAdmin() {
        Session::init();
        
        if (!Session::has('user_id')) {
            $this->unauthorized();
        }
        
        $role = Session::get('user_role');
        
        // Use RBAC to check if user has ANY admin-level permission
        // This is a broad check; individual actions call requirePermission()
        $hasAdminAccess = RBAC::canAny($role, [
            'admin.dashboard',
            'admin.users.view',
            'admin.transactions.view',
            'admin.merchants.view',
            'admin.analytics.view'
        ]);
        
        if (!$hasAdminAccess) {
            $this->forbidden();
        }
    }
}
