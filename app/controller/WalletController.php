<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/session_helper.php';
require_once __DIR__ . '/../helper/APIClient.php';

class WalletController {
    private $userModel;
    private $conn;
    private $apiClient;

    public function __construct() {
        $this->userModel = new User();
        // Get database connection for transactions
        $database = new Database();
        $this->conn = $database->connect();
        $this->apiClient = new APIClient();
    }

    public function getGovernmentServices() {
        try {
            return $this->apiClient->getGovernmentServices();
        } catch (Exception $e) {
            setFlash('error', 'Failed to load government services.');
            return [];
        }
    }

    public function deposit($user_id, $amount) {
        try {
            // Validate amount
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception("Invalid deposit amount.");
            }

            if ($amount > 50000) {
                throw new Exception("Maximum deposit amount is NPR 50,000.");
            }

            // Update wallet balance
            $this->userModel->updateWalletBalance($user_id, $amount);

            // Record transaction
            $this->userModel->recordTransaction(null, $user_id, $amount, 'deposit', 'Wallet deposit');

            setFlash('success', "Successfully deposited NPR " . number_format($amount, 2) . " to your wallet.");
            return true;

        } catch (Exception $e) {
            setFlash('error', $e->getMessage());
            return false;
        }
    }

    public function transfer($sender_id, $receiver_phone, $amount) {
        try {
            // Validate amount
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception("Invalid transfer amount.");
            }

            if ($amount > 25000) {
                throw new Exception("Maximum transfer amount is NPR 25,000 per transaction.");
            }

            // Check sender's balance
            $sender_balance = $this->userModel->getWalletBalance($sender_id);
            if ($sender_balance < $amount) {
                throw new Exception("Insufficient wallet balance. Available: NPR " . number_format($sender_balance, 2));
            }

            // Find receiver
            $receiver = $this->userModel->findUserByPhone($receiver_phone);
            if (!$receiver) {
                throw new Exception("Receiver with phone number " . $receiver_phone . " not found.");
            }

            if ($receiver['id'] == $sender_id) {
                throw new Exception("Cannot transfer money to yourself.");
            }

            // Perform transfer using database transaction
            $this->conn->beginTransaction();

            try {
                // Deduct from sender
                $this->userModel->updateWalletBalance($sender_id, -$amount);

                // Add to receiver
                $this->userModel->updateWalletBalance($receiver['id'], $amount);

                // Record transaction
                $this->userModel->recordTransaction($sender_id, $receiver['id'], $amount, 'transfer', 'Money transfer to ' . $receiver['name']);

                $this->conn->commit();

                setFlash('success', "Successfully transferred NPR " . number_format($amount, 2) . " to " . $receiver['name'] . ".");
                return true;

            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            setFlash('error', $e->getMessage());
            return false;
        }
    }

    public function getTransactionHistory($user_id) {
        try {
            return $this->userModel->getTransactionHistory($user_id);
        } catch (Exception $e) {
            setFlash('error', 'Failed to load transaction history.');
            return [];
        }
    }

    public function getWalletBalance($user_id) {
        try {
            return $this->userModel->getWalletBalance($user_id);
        } catch (Exception $e) {
            return 0.00;
        }
    }

    public function governmentPayment($user_id, $amount, $service_id, $reference_number) {
        try {
            // Validate amount
            if (!is_numeric($amount) || $amount <= 0) {
                throw new Exception("Invalid payment amount.");
            }

            if ($amount > 100000) {
                throw new Exception("Maximum government payment amount is NPR 100,000.");
            }

            // Check user's balance
            $user_balance = $this->userModel->getWalletBalance($user_id);
            if ($user_balance < $amount) {
                throw new Exception("Insufficient wallet balance. Available: NPR " . number_format($user_balance, 2));
            }

            // Validate service id and reference number
            if (empty($service_id) || empty($reference_number)) {
                throw new Exception("Service and reference number are required.");
            }

            // Fetch service detail from API
            $service_detail = $this->apiClient->getGovernmentServiceDetails($service_id);
            if (!$service_detail || empty($service_detail['name'])) {
                throw new Exception("Invalid government service selected.");
            }
            $service_name = $service_detail['name'];

            // Perform payment using database transaction
            $this->conn->beginTransaction();

            try {
                // Deduct from user
                $this->userModel->updateWalletBalance($user_id, -$amount);

                // Record transaction (receiver_id can be null for government payments)
                $description = "Government payment - " . $service_name . " (Ref: " . $reference_number . ")";
                $this->userModel->recordTransaction($user_id, null, $amount, 'government_payment', $description);

                $this->conn->commit();

                setFlash('success', "Successfully paid NPR " . number_format($amount, 2) . " for " . $service_name . ".");
                return true;

            } catch (Exception $e) {
                $this->conn->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            setFlash('error', $e->getMessage());
            return false;
        }
    }
}
