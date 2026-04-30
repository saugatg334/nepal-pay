<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Wallet.php';
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Beneficiary.php';
require_once __DIR__ . '/../models/Merchant.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/validation.php';

class WalletController extends Controller {
    public function __construct() {
        $this->requireLogin();
    }

    public function index() {
        $this->redirect(APP_URL . '/index.php?page=dashboard');
    }

    public function addMoney() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAddMoney();
        }
        
        $this->render('wallet/add_money');
    }

    private function handleAddMoney() {
        $this->validateCSRF();
        
        $amount = floatval($_POST['amount'] ?? 0);
        
        $validation = new Validation($_POST);
        $validation->required(['amount'])
                   ->numeric('amount')
                   ->positive('amount');
        
        if ($amount < 100 || $amount > 100000) {
            flash('error', 'Amount must be between NPR 100 and 100,000');
            $this->back();
        }
        
        if (!$validation->isValid()) {
            foreach ($validation->getAllMessages() as $message) {
                flash('error', $message);
            }
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        try {
            $transactionId = Wallet::addMoney($userId, $amount, 'Manual add money');
            
            flash('success', 'NPR ' . number_format($amount, 2) . ' added successfully');
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        } catch (Exception $e) {
            flash('error', 'Failed to add money: ' . $e->getMessage());
            $this->back();
        }
    }

    public function sendMoney() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSendMoney();
        }
        
        $beneficiaries = Beneficiary::findByUser(Session::get('user_id'));
        
        $this->render('wallet/send_money', [
            'beneficiaries' => $beneficiaries,
            'csrf_token' => \NepalPay\Helpers\CSRF::getToken()
        ]);
    }

    private function handleSendMoney() {
        $this->validateCSRF();
        
        $receiverIdentifier = $_POST['receiver'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $note = $_POST['note'] ?? '';
        $pin = $_POST['transaction_pin'] ?? '';
        
        $validation = new Validation($_POST);
        $validation->required(['receiver', 'amount', 'transaction_pin'])
                   ->numeric('amount')
                   ->positive('amount');
        
        if (!$validation->isValid()) {
            foreach ($validation->getAllMessages() as $message) {
                flash('error', $message);
            }
            $this->back();
        }
        
        if ($amount < 10) {
            flash('error', 'Minimum amount is NPR 10');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        $receiver = User::findByPhoneOrEmail($receiverIdentifier);
        
        if (!$receiver) {
            flash('error', 'Recipient not found');
            $this->back();
        }
        
        if ($receiver['id'] == $userId) {
            flash('error', 'Cannot send money to yourself');
            $this->back();
        }
        
        $senderWallet = Wallet::findByUserId($userId);
        
        if ($senderWallet['balance'] < $amount) {
            flash('error', 'Insufficient balance');
            $this->back();
        }
        
        try {
            // Pass PIN to Wallet::sendMoney (will be forwarded to service if available)
            $transactionId = Wallet::sendMoney($userId, $receiver['id'], $amount, 0, ['pin' => $pin]);
            
            Notification::sendMoneyNotification($userId, $receiver['id'], $amount, $transactionId);
            
            flash('success', 'NPR ' . number_format($amount, 2) . ' sent to ' . $receiver['full_name']);
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        } catch (Exception $e) {
            flash('error', 'Transfer failed: ' . $e->getMessage());
            $this->back();
        }
    }

    public function requestMoney() {
        $this->render('wallet/request_money');
    }

    private function handleRequestMoney() {
        $this->validateCSRF();
        
        $receiverIdentifier = $_POST['receiver'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $note = $_POST['note'] ?? '';
        
        if (empty($receiverIdentifier) || $amount <= 0) {
            flash('error', 'Invalid input');
            $this->back();
        }
        
        $receiver = User::findByPhoneOrEmail($receiverIdentifier);
        
        if (!$receiver) {
            flash('error', 'User not found');
            $this->back();
        }
        
        $senderId = Session::get('user_id');
        
        $transactionId = generateTransactionId();
        
        $sql = "INSERT INTO transactions (transaction_id, receiver_id, amount, type, status, description) 
                VALUES (?, ?, ?, 'receive', 'pending', ?)";
        Database::query($sql, [$transactionId, $receiver['id'], $amount, 'Money request from ' . Session::get('user_name')]);
        
        Notification::createNotification(
            $receiver['id'],
            'Money Request',
            Session::get('user_name') . ' has requested NPR ' . number_format($amount, 2),
            'transaction',
            ['transaction_id' => $transactionId, 'amount' => $amount]
        );
        
        flash('success', 'Money request sent to ' . $receiver['full_name']);
        $this->redirect(APP_URL . '/index.php?page=dashboard');
    }

    public function scanQR() {
        // CRITICAL: Require login and CSRF protection
        $this->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed', 405);
        }
        
        $this->validateCSRF();
        
        $qrString = $_POST['qr_string'] ?? '';
        
        if (empty($qrString)) {
            $this->json(['success' => false, 'message' => 'Invalid QR code']);
        }
        
        $result = Merchant::resolveQRString($qrString);
        
        if ($result) {
            if (isset($result['user_id'])) {
                $user = User::find($result['user_id']);
                $this->json([
                    'success' => true,
                    'type' => 'user',
                    'name' => $user['full_name'],
                    'wallet_number' => $result['wallet_number']
                ]);
            } else {
                $merchant = Merchant::find($result['id']);
                $this->json([
                    'success' => true,
                    'type' => 'merchant',
                    'business_name' => $merchant['business_name'],
                    'merchant_code' => $merchant['merchant_code']
                ]);
            }
        }
        
        $this->json(['success' => false, 'message' => 'Invalid QR code']);
    }

    public function myQR() {
        $userId = Session::get('user_id');
        $user = User::find($userId);
        $wallet = Wallet::findByUserId($userId);
        
        $qrString = 'NP' . $wallet['wallet_number'];
        
        $this->render('wallet/my_qr', [
            'user' => $user,
            'wallet' => $wallet,
            'qr_string' => $qrString
        ]);
    }
    
    /**
     * JSON error response helper
     */
    private function jsonError($message, $status = 400) {
        http_response_code($status);
        $this->json(['success' => false, 'error' => $message]);
    }
}
