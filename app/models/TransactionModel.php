<?php
/**
 * Transaction Model
 */

class TransactionModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Create transaction
     */
    public function create($data)
    {
        $txnId = $this->generateTransactionId();
        
        $sql = "INSERT INTO transactions 
                (txn_id, user_id, type, amount, balance_before, balance_after, status, description, recipient_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $values = [
            $txnId,
            $data['user_id'],
            $data['type'],
            $data['amount'],
            $data['balance_before'] ?? 0,
            $data['balance_after'] ?? 0,
            $data['status'] ?? 'completed',
            $data['description'] ?? null,
            $data['recipient_id'] ?? null
        ];

        $this->db->insert($sql, $values);
        return $txnId;
    }

    /**
     * Get transaction by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM transactions WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get transaction by TXN ID
     */
    public function getByTxnId($txnId)
    {
        $sql = "SELECT * FROM transactions WHERE txn_id = ?";
        return $this->db->fetch($sql, [$txnId]);
    }

    /**
     * Get user transactions
     */
    public function getUserTransactions($userId, $limit = 50, $offset = 0, $type = null)
    {
        $sql = "SELECT * FROM transactions WHERE user_id = ?";
        $params = [$userId];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get user transaction count
     */
    public function getUserTransactionCount($userId, $type = null)
    {
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ?";
        $params = [$userId];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        $result = $this->db->fetch($sql, $params);
        return $result['count'] ?? 0;
    }

    /**
     * Get dashboard stats
     */
    public function getDashboardStats($userId)
    {
        // Total sent
        $sent = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
             WHERE user_id = ? AND type = 'send' AND status = 'completed'",
            [$userId]
        );

        // Total received
        $received = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
             WHERE recipient_id = ? AND type = 'send' AND status = 'completed'",
            [$userId]
        );

        // Total bills paid
        $bills = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM bill_payments 
             WHERE user_id = ? AND status = 'completed'",
            [$userId]
        );

        // Recent transactions
        $recent = $this->db->fetchAll(
            "SELECT * FROM transactions WHERE user_id = ? 
             ORDER BY created_at DESC LIMIT 5",
            [$userId]
        );

        return [
            'total_sent' => $sent['total'] ?? 0,
            'total_received' => $received['total'] ?? 0,
            'total_bills' => $bills['total'] ?? 0,
            'recent_transactions' => $recent
        ];
    }

    /**
     * Update transaction status
     */
    public function updateStatus($txnId, $status)
    {
        $sql = "UPDATE transactions SET status = ?, updated_at = NOW() WHERE txn_id = ?";
        return $this->db->update($sql, [$status, $txnId]);
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId()
    {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * Get monthly summary
     */
    public function getMonthlySummary($userId)
    {
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                SUM(CASE WHEN type = 'send' THEN amount ELSE 0 END) as sent,
                SUM(CASE WHEN type = 'receive' THEN amount ELSE 0 END) as received
                FROM transactions
                WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) 
                AND YEAR(created_at) = YEAR(NOW())
                GROUP BY DATE(created_at)
                ORDER BY created_at DESC";

        return $this->db->fetchAll($sql, [$userId]);
    }
}
