<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class WalletService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function transfer($fromUserId, $toUserId, $amount)
    {
        if (!is_numeric($amount) || $amount <= 0 || $amount > 100000) {
            return 'Invalid amount';
        }

        if ($fromUserId == $toUserId) {
            return 'Cannot transfer to yourself';
        }


        try {
            $this->db->beginTransaction();

            // Lock sender
            $stmt = $this->db->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$fromUserId]);
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sender || $sender['wallet_balance'] < $amount) {
                $this->db->rollBack();
                return 'Insufficient balance';
            }

            // Lock receiver
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$toUserId]);
            $receiver = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$receiver) {
                $this->db->rollBack();
                return 'Receiver not found';
            }

            // Transfer
            $this->db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$amount, $fromUserId]);
            $this->db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$amount, $toUserId]);

            // Record txn
            $this->db->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, type, status, created_at) VALUES (?, ?, ?, 'transfer', 'completed', NOW())")->execute([$fromUserId, $toUserId, $amount]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Wallet transfer error: ' . $e->getMessage());
            return 'Transfer failed. Try again.';
        }

    }

    public function getBalance($userId) {
        $stmt = $this->db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
}

