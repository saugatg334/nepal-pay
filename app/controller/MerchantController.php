<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/Merchant.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Wallet.php';

class MerchantController extends Controller {
    public function __construct() {
        $this->requireLogin();
    }

    public function index() {
        $userId = Session::get('user_id');
        $merchant = Merchant::findByUser($userId);
        
        if (!$merchant) {
            $this->redirect(APP_URL . '/index.php?page=register_merchant');
        }
        
        $stats = Merchant::getStats($merchant['id']);
        $payments = Merchant::getPayments($merchant['id'], null, 20);
        
        $this->render('merchant/index', [
            'merchant' => $merchant,
            'stats' => $stats,
            'payments' => $payments
        ]);
    }

    public function register() {
        $userId = Session::get('user_id');
        $existing = Merchant::findByUser($userId);
        
        if ($existing) {
            $this->redirect(APP_URL . '/index.php?page=merchant');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegister();
        }
        
        $this->render('merchant/register');
    }

    private function handleRegister() {
        $this->validateCSRF();
        
        $businessName = $_POST['business_name'] ?? '';
        $businessType = $_POST['business_type'] ?? '';
        $businessAddress = $_POST['business_address'] ?? '';
        
        if (empty($businessName)) {
            flash('error', 'Business name is required');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        Merchant::createMerchant([
            'user_id' => $userId,
            'business_name' => $businessName,
            'business_type' => $businessType,
            'business_address' => $businessAddress
        ]);
        
        flash('success', 'Merchant account created successfully');
        $this->redirect(APP_URL . '/index.php?page=merchant');
    }

    public function qr() {
        $userId = Session::get('user_id');
        $merchant = Merchant::findByUser($userId);
        
        if (!$merchant) {
            $this->redirect(APP_URL . '/index.php?page=register_merchant');
        }
        
        $qrString = Merchant::generateQRString($merchant['id']);
        
        $this->render('merchant/qr', [
            'merchant' => $merchant,
            'qr_string' => $qrString
        ]);
    }

    public function pay() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->back();
        }
        
        // CRITICAL: CSRF protection for payment endpoint
        $this->validateCSRF();
        
        $merchantCode = $_POST['merchant_code'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $pin = $_POST['transaction_pin'] ?? '';
        
        if (empty($merchantCode) || $amount <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid input']);
        }
        
        // Require transaction PIN if user has set one
        $userId = Session::get('user_id');
        if (class_exists('\NepalPay\Services\TransactionPinService') && 
            \NepalPay\Services\TransactionPinService::isRequired($userId)) {
            if (empty($pin)) {
                jsonResponse(['success' => false, 'message' => 'Transaction PIN required']);
            }
            if (!\NepalPay\Services\TransactionPinService::verify($userId, $pin)) {
                $remaining = \NepalPay\Services\TransactionPinService::remainingAttempts($userId);
                jsonResponse(['success' => false, 'message' => 'Invalid PIN. ' . $remaining . ' attempts remaining.']);
            }
        }
        
        $merchant = Merchant::findByCode($merchantCode);
        
        if (!$merchant) {
            jsonResponse(['success' => false, 'message' => 'Invalid merchant']);
        }
        
        if (!$merchant['is_active']) {
            jsonResponse(['success' => false, 'message' => 'Merchant is inactive']);
        }
        
        $wallet = Wallet::findByUserId($userId);
        
        if ($wallet['balance'] < $amount) {
            jsonResponse(['success' => false, 'message' => 'Insufficient balance']);
        }
        
        try {
            // Pass PIN data (empty if not required, but already verified)
            $pinData = $pin ? ['pin' => $pin] : null;
            $transactionId = Wallet::sendMoney($userId, $merchant['user_id'], $amount, 0, $pinData);
            
            Merchant::recordPayment($merchant['id'], $transactionId, null, $amount);
            
            Notification::createNotification(
                $userId,
                'Payment Successful',
                'Payment of NPR ' . number_format($amount, 2) . ' to ' . $merchant['business_name'] . ' completed',
                'transaction'
            );
            
            jsonResponse(['success' => true, 'message' => 'Payment successful', 'transaction_id' => $transactionId]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    protected function requireLogin() {
        Session::init();
        
        if (!Session::has('user_id')) {
            $this->unauthorized();
        }
    }
}