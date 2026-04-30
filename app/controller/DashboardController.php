<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Wallet.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Notification.php';

class DashboardController extends Controller {
    public function __construct() {
        $this->requireLogin();
    }

    public function index() {
        $userId = Session::get('user_id');
        $user = User::find($userId);
        $wallet = Wallet::findByUserId($userId);
        $recentTransactions = Wallet::getUserTransactions($userId, null, 10);
        $analytics = Wallet::getAnalytics($userId);
        
        $this->render('dashboard/index', [
            'user' => $user,
            'wallet' => $wallet,
            'recent_transactions' => $recentTransactions,
            'analytics' => $analytics
        ]);
    }

    public function profile() {
        $userId = Session::get('user_id');
        $user = User::find($userId);
        $wallet = Wallet::findByUserId($userId);
        
        $this->render('dashboard/profile', [
            'user' => $user,
            'wallet' => $wallet
        ]);
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        $userId = Session::get('user_id');
        
        $fullName = $_POST['full_name'] ?? '';
        
        if (empty($fullName)) {
            flash('error', 'Name is required');
            $this->back();
        }
        
        $sql = "UPDATE users SET full_name = ?, updated_at = NOW() WHERE id = ?";
        Database::query($sql, [$fullName, $userId]);
        
        Session::set('user_name', $fullName);
        
        flash('success', 'Profile updated successfully');
        $this->redirect(APP_URL . '/index.php?page=profile');
    }

    public function transactions() {
        $filter = $_GET['filter'] ?? 'all';
        $page = intval($_GET['page'] ?? 1);
        
        $userId = Session::get('user_id');
        
        $result = Transaction::getHistory($userId, $filter, $page, 20);
        
        $this->render('dashboard/transactions', [
            'transactions' => $result['data'],
            'filter' => $filter,
            'pagination' => $result
        ]);
    }

    public function analytics() {
        $userId = Session::get('user_id');
        $wallet = Wallet::findByUserId($userId);
        $analytics = Wallet::getAnalytics($userId);
        
        $dailyStats = Transaction::getDailyStats(30);
        
        $this->render('dashboard/analytics', [
            'wallet' => $wallet,
            'analytics' => $analytics,
            'daily_stats' => $dailyStats
        ]);
    }

    public function notifications() {
        $userId = Session::get('user_id');
        
        $notifications = Notification::findByUser($userId, 50);
        
        Notification::markAllAsRead($userId);
        
        $this->render('notification/index', [
            'notifications' => $notifications
        ]);
    }
    
    /**
     * Display set/change transaction PIN form
     */
    public function setPin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSetPin();
        }
        
        $this->render('dashboard/set_pin');
    }
    
    /**
     * Handle PIN creation/update
     */
    public function handleSetPin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        $this->validateCSRF();
        
        $pin = $_POST['pin'] ?? '';
        $confirm = $_POST['pin_confirm'] ?? '';
        
        if (strlen($pin) < 4 || strlen($pin) > 6 || !ctype_digit($pin)) {
            flash('error', 'PIN must be 4-6 digits');
            $this->back();
        }
        
        if ($pin !== $confirm) {
            flash('error', 'PINs do not match');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        try {
            if (class_exists('\NepalPay\Services\TransactionPinService')) {
                \NepalPay\Services\TransactionPinService::setPin($userId, $pin);
                flash('success', 'Transaction PIN set successfully');
            } else {
                // Fallback: store hashed PIN directly (should not happen in production)
                $hashed = password_hash($pin, PASSWORD_BCRYPT);
                Database::query("UPDATE users SET transaction_pin = ? WHERE id = ?", [$hashed, $userId]);
                flash('success', 'Transaction PIN set (fallback)');
            }
        } catch (\Exception $e) {
            flash('error', 'Failed to set PIN: ' . $e->getMessage());
        }
        
        $this->redirect(APP_URL . '/index.php?page=security');
    }
    
    protected function requireLogin() {
        Session::init();
        
        if (!Session::has('user_id')) {
            $this->unauthorized();
        }
        
        $user = User::find(Session::get('user_id'));
        
        if ($user['is_frozen']) {
            flash('error', 'Your account is frozen. Contact support.');
            $this->redirect(APP_URL . '/index.php?page=logout');
        }
    }
}