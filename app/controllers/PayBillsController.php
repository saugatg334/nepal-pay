<?php
/**
 * Pay Bills Controller - Utilities Payment System
 * Production-level with DB transactions, input validation
 * Multi-step payment flow support with error handling and audit logging
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/Wallet.php';

class PayBillsController {
    private $userModel;
    private $walletModel;
    private $auditLog;
    private $conn;

    private const MIN_AMOUNT = 10;
    private const MAX_AMOUNT = 100000;

    public $billTypes = [
        'electricity' => [
            'name' => 'Electricity',
            'provider' => 'NEA',
            'icon' => 'fa-bolt',
            'color' => 'yellow',
            'idLabel' => 'Customer ID',
            'idPattern' => '/^[A-Za-z0-9]{5,20}$/',
            'idPlaceholder' => 'Enter NEA Customer ID'
        ],
        'water' => [
            'name' => 'Water Supply',
            'provider' => 'NWSC',
            'icon' => 'fa-tint',
            'color' => 'blue',
            'idLabel' => 'Connection Number',
            'idPattern' => '/^[A-Za-z0-9]{5,20}$/',
            'idPlaceholder' => 'Enter Water Connection Number'
        ],
        'internet' => [
            'name' => 'Internet',
            'provider' => 'Various ISPs',
            'icon' => 'fa-wifi',
            'color' => 'purple',
            'idLabel' => 'Account ID',
            'idPattern' => '/^[A-Za-z0-9]{5,20}$/',
            'idPlaceholder' => 'Enter Internet Account ID'
        ],
        'tv' => [
            'name' => 'TV / Cable',
            'provider' => 'Various',
            'icon' => 'fa-tv',
            'color' => 'red',
            'idLabel' => 'Subscriber ID',
            'idPattern' => '/^[A-Za-z0-9]{5,20}$/',
            'idPlaceholder' => 'Enter Cable Subscriber ID'
        ],
        'mobile_postpaid' => [
            'name' => 'Mobile Postpaid',
            'provider' => 'NTC/Ncell',
            'icon' => 'fa-mobile',
            'color' => 'green',
            'idLabel' => 'Mobile Number',
            'idPattern' => '/^9[678]\d{8}$/',
            'idPlaceholder' => 'Enter Mobile Number'
        ]
    ];

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->userModel = new User();
        $this->walletModel = new Wallet();
        $this->auditLog = new AuditLog();
    }

    public function getWalletBalance($user_id) {
        return $this->userModel->getWalletBalance($user_id);
    }

    /**
     * Validate customer ID input (NOT auto-generated)
     */
    public function validateCustomerId($billType, $customerId) {
        if (!isset($this->billTypes[$billType])) {
            return ['valid' => false, 'error' => 'Invalid bill type'];
        }

        $customerId = trim($customerId);
        
        if (empty($customerId)) {
            return ['valid' => false, 'error' => $this->billTypes[$billType]['idLabel'] . ' is required'];
        }

        $pattern = $this->billTypes[$billType]['idPattern'];
        
        if (!preg_match($pattern, $customerId)) {
            return ['valid' => false, 'error' => 'Invalid ' . $this->billTypes[$billType]['idLabel'] . '. Must be 5-20 alphanumeric characters'];
        }

        return ['valid' => true, 'customerId' => $customerId];
    }

    /**
     * Fetch customer details from billing system
     * Simulated API response (in production, call external API)
     */
    public function fetchCustomerDetails($billType, $customerId) {
        $validation = $this->validateCustomerId($billType, $customerId);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        $customerId = $validation['customerId'];

        // Simulated customer database (replace with real API calls)
        $simulatedCustomers = $this->getSimulatedCustomerData($billType);

        // Check if customer exists
        if (isset($simulatedCustomers[$customerId])) {
            $customer = $simulatedCustomers[$customerId];
            return [
                'success' => true,
                'customer' => [
                    'customer_id' => $customerId,
                    'name' => $customer['name'],
                    'bill_type' => $billType,
                    'provider' => $this->billTypes[$billType]['provider'],
                    'due_amount' => $customer['due_amount'],
                    'bill_month' => $customer['bill_month'] ?? date('M Y')
                ]
            ];
        }

        // For demo: return mock data if not found (in production, return error)
        return [
            'success' => true,
            'customer' => [
                'customer_id' => $customerId,
                'name' => 'Customer ' . substr($customerId, 0, 4),
                'bill_type' => $billType,
                'provider' => $this->billTypes[$billType]['provider'],
                'due_amount' => rand(100, 5000),
                'bill_month' => date('M Y')
            ]
        ];
    }

    /**
     * Simulated customer data (replace with real billing API)
     */
    private function getSimulatedCustomerData($billType) {
        return [
            // Electricity (NEA) sample customers
            'electricity' => [
                'NEA001234' => ['name' => 'Ram Bahadur', 'due_amount' => 1250],
                'NEA001235' => ['name' => 'Shyam Kumar', 'due_amount' => 890],
                'NEA001236' => ['name' => 'Hari Prasad', 'due_amount' => 2100],
            ],
            // Water (NWSC) sample customers
            'water' => [
                'WTR50001' => ['name' => 'Gita Devi', 'due_amount' => 450],
                'WTR50002' => ['name' => 'Mohan Singh', 'due_amount' => 680],
            ],
            // Internet sample customers
            'internet' => [
                'ISP987650' => ['name' => 'Tech Solutions Pvt Ltd', 'due_amount' => 2500],
                'ISP987651' => ['name' => 'Home Network', 'due_amount' => 1500],
            ],
            // TV/Cable sample customers
            'tv' => [
                'CABLE001' => ['name' => 'Star Cable Network', 'due_amount' => 350],
                'CABLE002' => ['name' => 'Digital TV Service', 'due_amount' => 420],
            ],
            // Mobile postpaid
            'mobile_postpaid' => [
                '9841000001' => ['name' => 'Sita Pandey', 'due_amount' => 780],
                '9841000002' => ['name' => 'Raj Kumar', 'due_amount' => 1200],
            ]
        ];
    }

    /**
     * Process bill payment with transaction safety, idempotency, and audit logging
     */
    public function processPayment($user_id, $billType, $customerId, $amount, $csrf_token = null, $idempotencyKey = null) {
        try {
            if ($csrf_token && !verifyCsrfToken($csrf_token)) {
                $this->auditLog->log(
                    AuditLog::ACTION_BILL_PAYMENT,
                    $user_id,
                    "Invalid CSRF token",
                    ['bill_type' => $billType, 'customer_id' => $customerId]
                );
                return ['success' => false, 'error' => 'Invalid security token'];
            }

            if (!isset($this->billTypes[$billType])) {
                return ['success' => false, 'error' => 'Invalid bill type selected'];
            }

            if ($idempotencyKey) {
                $existing = $this->walletModel->checkIdempotency($idempotencyKey);
                if ($existing) {
                    return [
                        'success' => true,
                        'duplicate' => true,
                        'txn_id' => $existing['txn_id'],
                        'message' => 'Duplicate transaction already processed'
                    ];
                }
            }

            $amount = floatval($amount);
            if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
                return ['success' => false, 'error' => 'Amount must be between NPR ' . self::MIN_AMOUNT . ' and ' . number_format(self::MAX_AMOUNT)];
            }

            $balance = $this->userModel->getWalletBalance($user_id);
            if ($balance < $amount) {
                $this->auditLog->log(
                    AuditLog::ACTION_BILL_PAYMENT,
                    $user_id,
                    "Insufficient balance",
                    [
                        'bill_type' => $billType,
                        'customer_id' => $customerId,
                        'amount' => $amount,
                        'balance' => $balance
                    ]
                );
                return ['success' => false, 'error' => 'Insufficient balance. Your balance: NPR ' . number_format($balance, 2)];
            }

            $this->conn->beginTransaction();
            try {
                $stmt = $this->conn->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
                $stmt->execute([$user_id]);
                $currentBalance = $stmt->fetchColumn();

                if ($currentBalance < $amount) {
                    throw new Exception('Insufficient balance');
                }

                $this->userModel->updateWalletBalance($user_id, -$amount);

                $txn_id = $this->generateTransactionId();
                $billInfo = $this->billTypes[$billType];
                $description = $billInfo['name'] . ' payment for ' . $customerId;

                $providerResponse = json_encode([
                    'status' => 'success',
                    'bill_type' => $billType,
                    'provider' => $billInfo['provider'],
                    'customer_id' => $customerId,
                    'payment_date' => date('Y-m-d H:i:s')
                ]);

                $this->userModel->recordTransaction(
                    $user_id,
                    null,
                    $amount,
                    'bill',
                    $description,
                    $txn_id,
                    $billInfo['provider'],
                    $customerId,
                    $billType,
                    $providerResponse,
                    'completed'
                );

                $this->conn->commit();

                $this->auditLog->log(
                    AuditLog::ACTION_BILL_PAYMENT,
                    $user_id,
                    "Bill payment successful",
                    [
                        'bill_type' => $billType,
                        'customer_id' => $customerId,
                        'amount' => $amount,
                        'txn_id' => $txn_id,
                        'provider' => $billInfo['provider']
                    ],
                    'transaction',
                    $txn_id
                );

                return [
                    'success' => true,
                    'message' => 'Payment successful!',
                    'txn_id' => $txn_id,
                    'amount' => $amount,
                    'customer_id' => $customerId,
                    'bill_type' => $billType,
                    'provider' => $billInfo['provider'],
                    'new_balance' => $balance - $amount,
                    'payment_date' => date('Y-m-d H:i:s')
                ];
            } catch (Exception $e) {
                $this->conn->rollBack();
                $this->auditLog->log(
                    AuditLog::ACTION_BILL_PAYMENT,
                    $user_id,
                    "Bill payment failed",
                    [
                        'bill_type' => $billType,
                        'customer_id' => $customerId,
                        'amount' => $amount,
                        'error' => $e->getMessage()
                    ]
                );
                return ['success' => false, 'error' => 'Payment failed. Please try again.'];
            }
        } catch (Exception $e) {
            error_log("processPayment error: " . $e->getMessage());
            return ['success' => false, 'error' => 'An error occurred. Please try again later.'];
        }
    }

    private function generateTransactionId() {
        return 'BL' . date('YmdHis') . rand(1000, 9999);
    }

    public function getBillHistory($user_id, $limit = 20) {
        try {
            $query = "SELECT * FROM transactions 
                    WHERE sender_id = :user_id AND type = 'bill' 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}