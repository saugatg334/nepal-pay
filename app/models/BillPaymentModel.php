<?php
/**
 * Bill Payment Model
 */

class BillPaymentModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Create bill payment
     */
    public function create($data)
    {
        $txnId = $this->generateTransactionId();
        
        $sql = "INSERT INTO bill_payments 
                (txn_id, user_id, provider, category, customer_id, customer_name, amount, status, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $values = [
            $txnId,
            $data['user_id'],
            $data['provider'],
            $data['category'],
            $data['customer_id'],
            $data['customer_name'] ?? null,
            $data['amount'],
            $data['status'] ?? 'pending',
            $data['description'] ?? null
        ];

        $this->db->insert($sql, $values);
        return $txnId;
    }

    /**
     * Get bill by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM bill_payments WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Get bill by transaction ID
     */
    public function getByTxnId($txnId)
    {
        $sql = "SELECT * FROM bill_payments WHERE txn_id = ?";
        return $this->db->fetch($sql, [$txnId]);
    }

    /**
     * Get user bills
     */
    public function getUserBills($userId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM bill_payments WHERE user_id = ? 
                ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$userId, $limit, $offset]);
    }

    /**
     * Get bills by category
     */
    public function getBillsByCategory($userId, $category)
    {
        $sql = "SELECT * FROM bill_payments WHERE user_id = ? AND category = ? 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$userId, $category]);
    }

    /**
     * Update bill status
     */
    public function updateStatus($txnId, $status)
    {
        $sql = "UPDATE bill_payments SET status = ?, updated_at = NOW() WHERE txn_id = ?";
        return $this->db->update($sql, [$status, $txnId]);
    }

    /**
     * Get bill statistics
     */
    public function getStats($userId)
    {
        $stats = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_bills,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as paid_bills,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_paid
             FROM bill_payments WHERE user_id = ?",
            [$userId]
        );

        return $stats;
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId()
    {
        return 'BILL' . date('YmdHis') . rand(1000, 9999);
    }

    /**
     * Get bill providers (for bill payment page)
     */
    public static function getProviders()
    {
        return [
            'electricity' => [
                'NEPAL_ELECTRICITY' => 'Nepal Electricity Authority',
                'PRIVATE_PROVIDER' => 'Private Provider'
            ],
            'water' => [
                'KATHMANDU_WATER' => 'Kathmandu Water',
                'LOCAL_WATER' => 'Local Water Supply'
            ],
            'internet' => [
                'WORLDLINK' => 'Worldlink',
                'VIANET' => 'Vianet',
                'SUBISU' => 'Subisu',
                'FIBERNET' => 'Fibernet'
            ],
            'mobile' => [
                'NCELL' => 'Ncell',
                'NTC' => 'NTC',
                'SMART' => 'Smart Cell'
            ],
            'tv' => [
                'DISH_HOME' => 'Dish Home',
                'SKY_CABLE' => 'Sky Cable'
            ],
            'education' => [
                'SCHOOL' => 'School Fee',
                'UNIVERSITY' => 'University Fee',
                'COACHING' => 'Coaching Center'
            ]
        ];
    }
}
