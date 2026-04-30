<?php
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/../models/BankAccount.php';
require_once __DIR__ . '/../models/Wallet.php';

class BankController extends Controller {
    public function __construct() {
        $this->requireLogin();
    }

    public function index() {
        $userId = Session::get('user_id');
        $banks = BankAccount::findByUser($userId);
        
        $this->render('bank/index', ['banks' => $banks]);
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAdd();
        }
        
        $this->render('bank/add');
    }

    private function handleAdd() {
        $this->validateCSRF();
        
        $bankName = $_POST['bank_name'] ?? '';
        $accountNumber = $_POST['account_number'] ?? '';
        $accountHolder = $_POST['account_holder'] ?? '';
        
        if (empty($bankName) || empty($accountNumber) || empty($accountHolder)) {
            flash('error', 'All fields are required');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        BankAccount::add([
            'user_id' => $userId,
            'bank_name' => $bankName,
            'account_number' => $accountNumber,
            'account_holder_name' => $accountHolder
        ]);
        
        flash('success', 'Bank account linked successfully');
        $this->redirect(APP_URL . '/index.php?page=banks');
    }

    public function remove() {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            flash('error', 'Invalid request');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        BankAccount::remove($id, $userId);
        
        flash('success', 'Bank account removed');
        $this->redirect(APP_URL . '/index.php?page=banks');
    }

    public function setDefault() {
        $id = intval($_GET['id'] ?? 0);
        
        if (!$id) {
            flash('error', 'Invalid request');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        BankAccount::setDefault($userId, $id);
        
        flash('success', 'Default bank account updated');
        $this->redirect(APP_URL . '/index.php?page=banks');
    }

    public function withdraw() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleWithdraw();
        }
        
        $userId = Session::get('user_id');
        $banks = BankAccount::findByUser($userId);
        $wallet = Wallet::findByUserId($userId);
        
        $this->render('bank/withdraw', ['banks' => $banks, 'wallet' => $wallet]);
    }

    private function handleWithdraw() {
        $this->validateCSRF();
        
        $bankId = intval($_POST['bank_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $pin = $_POST['transaction_pin'] ?? '';
        
        if (!$bankId || $amount <= 0) {
            flash('error', 'Invalid input');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        $wallet = Wallet::findByUserId($userId);
        
        if ($wallet['balance'] < $amount) {
            flash('error', 'Insufficient balance');
            $this->back();
        }
        
        try {
            // Pass PIN if required
            $pinData = $pin ? ['pin' => $pin] : null;
            $transactionId = Wallet::transferToBank($userId, $bankId, $amount, $pinData);
            
            flash('success', 'NPR ' . number_format($amount, 2) . ' transferred to bank');
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        } catch (Exception $e) {
            flash('error', 'Transfer failed: ' . $e->getMessage());
            $this->back();
        }
    }

    public function loadMoney() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLoadMoney();
        }
        
        $userId = Session::get('user_id');
        $banks = BankAccount::findByUser($userId);
        $wallet = Wallet::findByUserId($userId);
        
        $this->render('bank/load_money', ['banks' => $banks, 'wallet' => $wallet]);
    }

    private function handleLoadMoney() {
        $this->validateCSRF();
        
        $amount = floatval($_POST['amount'] ?? 0);
        
        if ($amount <= 0) {
            flash('error', 'Invalid amount');
            $this->back();
        }
        
        if ($amount < 100 || $amount > 100000) {
            flash('error', 'Amount must be between NPR 100 and 100,000');
            $this->back();
        }
        
        $userId = Session::get('user_id');
        
        $defaultBank = BankAccount::getDefault($userId);
        
        if (!$defaultBank) {
            flash('error', 'Please add a bank account first');
            $this->redirect(APP_URL . '/index.php?page=add_bank');
        }
        
        try {
            $transactionId = Wallet::loadFromBank($userId, $defaultBank['id'], $amount);
            
            flash('success', 'NPR ' . number_format($amount, 2) . ' loaded from bank');
            $this->redirect(APP_URL . '/index.php?page=dashboard');
        } catch (Exception $e) {
            flash('error', 'Failed: ' . $e->getMessage());
            $this->back();
        }
    }

    protected function requireLogin() {
        Session::init();
        
        if (!Session::has('user_id')) {
            $this->unauthorized();
        }
    }
}