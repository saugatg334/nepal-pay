<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/session_helper.php';

class BillController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    // Pay a bill: method must be 'nepalpay' or 'bank'
    public function payBill($user_id, $biller_id, $amount, $method, $reference = '') {
        try {
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception('Invalid payment amount.');
            }

            if (!in_array($method, ['nepalpay', 'bank'])) {
                throw new Exception('Invalid payment method.');
            }

            // Simple business limits
            if ($amount > 100000) {
                throw new Exception('Maximum bill payment amount is NPR 100,000.');
            }

            // If payment via NepalPay (wallet), ensure sufficient balance
            if ($method === 'nepalpay') {
                $balance = $this->userModel->getWalletBalance($user_id);
                if ($balance < $amount) {
                    throw new Exception('Insufficient wallet balance. Available: NPR ' . number_format($balance, 2));
                }

                // Deduct and record transaction
                    $this->userModel->updateWalletBalance($user_id, -$amount);
                    // Generate transaction id
                    $txn_id = 'NP' . time() . rand(100,999);
                    $desc = 'Bill payment to biller #' . $biller_id . ' (NepalPay) Ref:' . $reference;
                    $this->userModel->recordTransaction($user_id, null, $amount, 'bill_payment', $desc, $txn_id, $biller_id, $reference, 'nepalpay', null, 'completed');

                    setFlash('success', 'Paid NPR ' . number_format($amount, 2) . ' via NepalPay.');
                    return $txn_id;
            }

            // If payment via Bank, we simulate creating a bank payout record and deduct (optional)
            if ($method === 'bank') {
                // For bank transfer we still deduct from user's wallet (simulated) and record
                $balance = $this->userModel->getWalletBalance($user_id);
                if ($balance < $amount) {
                    throw new Exception('Insufficient wallet balance for bank payout.');
                }

                $this->userModel->updateWalletBalance($user_id, -$amount);
                // Generate transaction id for bank payout
                $txn_id = 'NPB' . time() . rand(100,999);
                $desc = 'Bill payment to biller #' . $biller_id . ' (Bank transfer) Ref:' . $reference;
                $this->userModel->recordTransaction($user_id, null, $amount, 'bank_payment', $desc, $txn_id, $biller_id, $reference, 'bank', null, 'completed');

                setFlash('success', 'Paid NPR ' . number_format($amount, 2) . ' via Bank transfer (simulated).');
                return $txn_id;
            }

            throw new Exception('Unknown error processing payment.');

        } catch (Exception $e) {
            setFlash('error', $e->getMessage());
            return false;
        }
    }
}
