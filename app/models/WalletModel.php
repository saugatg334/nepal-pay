<?php
/**
 * Wallet Model
 */

class WalletModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Get wallet by user ID
     */
    public function getByUserId($userId)
    {
        $sql = "SELECT * FROM wallets WHERE user_id = ?";
        return $this->db->fetch($sql, [$userId]);
    }

    /**
     * Get wallet by wallet number
     */
    public function getByWalletNumber($walletNumber)
    {
        $sql = "SELECT * FROM wallets WHERE wallet_number = ?";
        return $this->db->fetch($sql, [$walletNumber]);
    }

    /**
     * Create wallet
     */
    public function create($data)
    {
        $sql = "INSERT INTO wallets (user_id, wallet_number, balance, is_active, currency) 
                VALUES (?, ?, ?, ?, ?)";
        
        $values = [
            $data['user_id'],
            $data['wallet_number'],
            $data['balance'] ?? 0,
            $data['is_active'] ?? true,
            $data['currency'] ?? 'NPR'
        ];

        return $this->db->insert($sql, $values);
    }

    /**
     * Update balance
     */
    public function updateBalance($walletId, $newBalance)
    {
        $sql = "UPDATE wallets SET balance = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->update($sql, [$newBalance, $walletId]);
    }

    /**
     * Add money to wallet
     */
    public function addBalance($walletId, $amount)
    {
        $sql = "UPDATE wallets SET balance = balance + ?, updated_at = NOW() WHERE id = ?";
        return $this->db->update($sql, [$amount, $walletId]);
    }

    /**
     * Deduct money from wallet
     */
    public function deductBalance($walletId, $amount)
    {
        $sql = "UPDATE wallets SET balance = balance - ?, updated_at = NOW() WHERE id = ?";
        return $this->db->update($sql, [$amount, $walletId]);
    }

    /**
     * Check if balance is sufficient
     */
    public function hasSufficientBalance($walletId, $amount)
    {
        $wallet = $this->db->fetch("SELECT balance FROM wallets WHERE id = ?", [$walletId]);
        return $wallet && $wallet['balance'] >= $amount;
    }

    /**
     * Get wallet balance
     */
    public function getBalance($walletId)
    {
        $wallet = $this->db->fetch("SELECT balance FROM wallets WHERE id = ?", [$walletId]);
        return $wallet ? $wallet['balance'] : 0;
    }

    /**
     * Update daily/monthly spent
     */
    public function updateDailySpent($walletId, $amount)
    {
        $sql = "UPDATE wallets SET daily_spent = daily_spent + ? WHERE id = ?";
        return $this->db->update($sql, [$amount, $walletId]);
    }

    /**
     * Check daily limit
     */
    public function checkDailyLimit($walletId, $amount)
    {
        $wallet = $this->db->fetch(
            "SELECT daily_spent, daily_limit FROM wallets WHERE id = ?",
            [$walletId]
        );
        
        if (!$wallet) return false;
        
        return ($wallet['daily_spent'] + $amount) <= $wallet['daily_limit'];
    }

    /**
     * Reset daily spent (call daily via cron)
     */
    public function resetDailySpent()
    {
        $sql = "UPDATE wallets SET daily_spent = 0 WHERE DATE(updated_at) < DATE(NOW())";
        return $this->db->update($sql, []);
    }
}
