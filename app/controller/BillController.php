<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/BillPayment.php';
require_once __DIR__ . '/../models/Wallet.php';

class BillController extends Controller {
    public function __construct() {
        $this->requireLogin();
    }

    public function index() {
        $billTypes = BillPayment::getBillTypes();
        
        $this->render('bill/index', ['bill_types' => $billTypes]);
    }

    public function pay() {
        try {
            $type = $_GET['type'] ?? 'ntc';
            
            $billTypes = BillPayment::getBillTypes();
            
            if (!isset($billTypes[$type])) {
                flash('error', 'Invalid bill type');
                $this->redirect(APP_URL . '/index.php?page=bills');
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handlePay($type);
            }
            
            $userId = Session::get('user_id');
            if (!$userId) {
                flash('error', 'User session not found');
                $this->redirect(APP_URL . '/index.php?page=login');
            }
            
            $wallet = Wallet::findByUserId($userId);
            if (!$wallet) {
                flash('error', 'Wallet not found');
                $this->redirect(APP_URL . '/index.php?page=dashboard');
            }
            
            // Ensure CSRF token is set in session
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            
            $isPinRequired = TransactionPinService::isRequired($userId);
            
            $this->render('bill/pay', [
                'bill_type' => $type,
                'bill_info' => $billTypes[$type],
                'wallet' => $wallet,
                'is_pin_required' => $isPinRequired
            ]);
        } catch (Exception $e) {
            error_log("Error in BillController::pay: " . $e->getMessage());
            flash('error', 'An unexpected error occurred');
            $this->back();
        }
    }

    private function handlePay($type) {
        $this->validateCSRF();
        
        $accountNo = $_POST['account_no'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $pin = $_POST['transaction_pin'] ?? '';
        
        if (empty($accountNo) || $amount <= 0) {
            flash('error', 'All fields are required');
            $this->back();
        }
        
        $billTypes = BillPayment::getBillTypes();
        $billInfo = $billTypes[$type];
        
        if ($amount < $billInfo['min'] || $amount > $billInfo['max']) {
            flash('error.php', 'Amount must be between NPR ' . $billInfo['min'] . ' and NPR ' . $billInfo['max']);
            $this->back();
        }
        
        if (!preg_match('/' . $billInfo['pattern'] . '/', $accountNo)) {
            flash('error', 'Invalid account number format');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        // Verify transaction PIN if required
        if (class_exists('\NepalPay\Services\TransactionPinService') && 
            \NepalPay\Services\TransactionPinService::isRequired($userId)) {
            if (empty($pin)) {
                flash('error', 'Transaction PIN required');
                $this->back();
            }
            if (!\NepalPay\Services\TransactionPinService::verify($userId, $pin)) {
                $remaining = \NepalPay\Services\TransactionPinService::remainingAttempts($userId);
                flash('error', 'Invalid PIN. ' . $remaining . ' attempts remaining.');
                $this->back();
            }
        }
        
        $result = BillPayment::processPayment($userId, $type, $accountNo, $amount);
        
        if ($result['success']) {
            flash('success', 'Payment successful! Transaction: ' . $result['transaction_id']);
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        } else {
            flash('error', $result['message']);
            $this->back();
        }
    }

    public function history() {
        $userId = Session::get('user_id');
        
        $history = BillPayment::getHistory($userId);
        
        $this->render('bill/history', ['history' => $history]);
    }

    protected function requireLogin() {
        Session::init();
        
        if (!Session::has('user_id')) {
            $this->unauthorized();
        }
    }
}