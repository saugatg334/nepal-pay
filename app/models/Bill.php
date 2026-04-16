<?php
/**
 * Bill Model - Handles bill payment operations
 */
require_once __DIR__ . '/../config/database.php';

class Bill {
    private $conn;
    
    // Bill types configuration
    const BILL_TYPES = [
        'electricity' => [
            'name' => 'Electricity',
            'provider' => 'NEA',
            'icon' => 'fa-bolt',
            'color' => 'yellow',
            'idLabel' => 'Customer ID',
            'idPattern' => '/^[A-Za-z0-9]{5,20}$/'
        ],
        'water' => [
            'name' => 'Water Supply',
            'provider' => 'NWSC',
            'icon' => 'fa-tint',
            'color' => 'blue',
            'idLabel' => 'Connection Number',
            'idPattern' => '/^[A-Za-z0-9]{5,20}$/'
        ],
        'internet' => [
            'name' => 'Internet',
            'provider' => 'Various ISPs',
            'icon' => 'fa-wifi',
            'color' => 'purple',
            'idLabel' => 'Account ID',
            'idPattern' => '/^[A-Za-z0-9]{5,20}$/'
        ],
        'tv' => [
            'name' => 'TV / Cable',
            'provider' => 'Various',
            'icon' => 'fa-tv',
            'color' => 'red',
            'idLabel' => 'Subscriber ID',
            'idPattern' => '/^[A-Za-z0-9]{5,20}$/'
        ],
        'mobile_postpaid' => [
            'name' => 'Mobile Postpaid',
            'provider' => 'NTC/Ncell',
            'icon' => 'fa-mobile',
            'color' => 'green',
            'idLabel' => 'Mobile Number',
            'idPattern' => '/^9[678]\d{8}$/'
        ]
    ];
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Get bill types
     */
    public function getBillTypes() {
        return self::BILL_TYPES;
    }
    
    /**
     * Validate customer ID
     */
    public function validateCustomerId($billType, $customerId) {
        if (!isset(self::BILL_TYPES[$billType])) {
            return ['valid' => false, 'error' => 'Invalid bill type'];
        }
        
        $customerId = trim($customerId);
        
        if (empty($customerId)) {
            return ['valid' => false, 'error' => 'Customer ID is required'];
        }
        
        $pattern = self::BILL_TYPES[$billType]['idPattern'];
        
        if (!preg_match($pattern, $customerId)) {
            return ['valid' => false, 'error' => 'Invalid customer ID format'];
        }
        
        return ['valid' => true, 'customerId' => $customerId];
    }
    
    /**
     * Fetch customer details (simulated - replace with real API)
     */
    public function fetchCustomerDetails($billType, $customerId) {
        $validation = $this->validateCustomerId($billType, $customerId);
        
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        // Simulated customer data - in production, call external API
        $customerId = $validation['customerId'];
        
        // Sample data lookup
        $mockCustomers = [
            'electricity' => [
                'NEA001234' => ['name' => 'Ram Bahadur', 'due_amount' => 1250],
                'NEA001235' => ['name' => 'Shyam Kumar', 'due_amount' => 890],
            ],
            'water' => [
                'WTR50001' => ['name' => 'Gita Devi', 'due_amount' => 450],
            ],
            'internet' => [
                'ISP987650' => ['name' => 'Tech Solutions', 'due_amount' => 2500],
            ]
        ];
        
        // Return mock data or generate random
        $billInfo = self::BILL_TYPES[$billType];
        
        if (isset($mockCustomers[$billType][$customerId])) {
            $customer = $mockCustomers[$billType][$customerId];
        } else {
            // Generate random for demo
            $customer = [
                'name' => 'Customer ' . substr($customerId, 0, 4),
                'due_amount' => rand(100, 5000)
            ];
        }
        
        return [
            'success' => true,
            'customer' => [
                'customer_id' => $customerId,
                'name' => $customer['name'],
                'bill_type' => $billType,
                'provider' => $billInfo['provider'],
                'due_amount' => $customer['due_amount'],
                'bill_month' => date('M Y')
            ]
        ];
    }
    
    /**
     * Process bill payment
     */
    public function processPayment($user_id, $billType, $customerId, $amount) {
        // Validate
        $validation = $this->validateCustomerId($billType, $customerId);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        $amount = floatval($amount);
        if ($amount < 10 || $amount > 100000) {
            return ['success' => false, 'error' => 'Invalid amount'];
        }
        
        // Get user balance
        $userModel = new User();
        $balance = $userModel->getWalletBalance($user_id);
        
        if ($balance < $amount) {
            return ['success' => false, 'error' => 'Insufficient balance'];
        }
        
        // Process with transaction
        $this->conn->beginTransaction();
        try {
            // Lock wallet
            $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$user_id]);
            $currentBalance = $stmt->fetchColumn();
            
            if ($currentBalance < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            // Deduct balance
            $userModel->updateWalletBalance($user_id, -$amount);
            
            // Generate transaction ID
            $txn_id = 'BLL' . date('YmdHis') . rand(1000, 9999);
            $billInfo = self::BILL_TYPES[$billType];
            $description = $billInfo['name'] . ' payment for ' . $customerId;
            
            // Record transaction
            $userModel->recordTransaction(
                $user_id,
                null,
                $amount,
                'bill',
                $description,
                $txn_id,
                $billInfo['provider'],
                $customerId,
                $billType,
                json_encode(['status' => 'success']),
                'completed'
            );
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'txn_id' => $txn_id,
                'amount' => $amount,
                'customer_id' => $customerId,
                'bill_type' => $billType,
                'provider' => $billInfo['provider'],
                'new_balance' => $balance - $amount
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get bill transactions
     */
    public function getBillHistory($user_id, $limit = 20) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM transactions 
                    WHERE sender_id = ? AND type = 'bill' 
                    ORDER BY created_at DESC 
                    LIMIT ?");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}

// Load User model for this model
require_once __DIR__ . '/User.php';
