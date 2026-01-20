<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/WalletController.php';
require_once __DIR__ . '/../config/database.php';

class AdminController {
    private $userModel;
    private $walletController;
    private $conn;

    public function __construct() {
        $this->userModel = new User();
        $database = new Database();
        $this->conn = $database->connect();
        $this->walletController = new WalletController();
    }

    // List deposit transactions; $status = null|pending|completed|failed
    public function listDeposits($status = 'pending') {
        return $this->userModel->findTransactionsByType('deposit', $status);
    }

    // Approve a pending deposit: mark transaction completed and credit user's wallet
    public function approveDeposit($txn_id) {
        $this->conn->beginTransaction();
        try {
            $txn = $this->userModel->findTransactionByTxnId($txn_id);
            if (!$txn) throw new Exception('Transaction not found.');
            if ($txn['status'] !== 'pending') throw new Exception('Transaction is not pending.');

            // Credit user wallet
            $this->userModel->updateWalletBalance($txn['receiver_id'], $txn['amount']);

            // Update transaction status & provider_response (audit)
            $this->userModel->updateTransactionStatus($txn['id'], 'completed', 'Approved by admin');

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Reject deposit
    public function rejectDeposit($txn_id, $reason = '') {
        $txn = $this->userModel->findTransactionByTxnId($txn_id);
        if (!$txn) throw new Exception('Transaction not found.');
        $this->userModel->updateTransactionStatus($txn['id'], 'failed', $reason);
        return true;
    }

    // Admin manual deposit (immediate)
    public function manualDeposit($user_id, $amount, $admin_id = null) {
        if (!is_numeric($amount) || $amount <= 0) throw new Exception('Invalid amount');
        $this->conn->beginTransaction();
        try {
            $this->userModel->updateWalletBalance($user_id, $amount);
            $txn_id = 'ADMIN' . time() . rand(1000,9999);
            $this->userModel->recordTransaction($admin_id, $user_id, $amount, 'deposit', 'Admin manual deposit', $txn_id, null, null, 'admin', json_encode(['by'=>$admin_id]), 'completed');
            $this->conn->commit();
            return $txn_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }
}