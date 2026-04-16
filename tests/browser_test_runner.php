<?php
/**
 * NepalPay Browser-Based Test Runner
 * No CLI required - runs tests directly in browser
 */
session_start();

$results = [];
$running = false;
$testStartTime = 0;

// Run tests if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_tests'])) {
    $running = true;
    $testStartTime = microtime(true);
    
    require_once __DIR__ . '/../app/config/database.php';
    
    // Test 1: Database Connection
    $results[] = test('Database Connection', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SELECT 1");
            return $stmt->fetchColumn() == 1;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 2: Check Users Table
    $results[] = test('Users Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'users'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 3: Check Transactions Table
    $results[] = test('Transactions Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'transactions'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 4: Check Audit Logs Table
    $results[] = test('Audit Logs Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'audit_logs'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 5: Check Job Queue Table
    $results[] = test('Job Queue Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'job_queue'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 6: Check Ledger Table
    $results[] = test('Ledger Entries Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'ledger_entries'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 7: Check Fraud Alerts Table
    $results[] = test('Fraud Alerts Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'fraud_alerts'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 8: No Negative Balances
    $results[] = test('No Negative Wallet Balances', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE wallet_balance < 0");
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 9: No Invalid Transactions
    $results[] = test('No Invalid Transaction Amounts', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SELECT COUNT(*) FROM transactions WHERE amount <= 0");
            return $stmt->fetchColumn() == 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 10: Input Validator - Phone
    $results[] = test('Input Validator - Phone Validation', function() {
        require_once __DIR__ . '/../app/validators/InputValidator.php';
        $v = new InputValidator();
        return $v->validatePhone('9800000001') && !$v->validatePhone('invalid');
    });
    
    // Test 11: Input Validator - XSS
    $results[] = test('Input Validator - XSS Prevention', function() {
        require_once __DIR__ . '/../app/validators/InputValidator.php';
        $v = new InputValidator();
        $clean = $v->sanitize('<script>alert(1)</script>');
        return strpos($clean, '<script>') === false;
    });
    
    // Test 12: Input Validator - SQL Injection
    $results[] = test('Input Validator - SQL Injection Blocked', function() {
        require_once __DIR__ . '/../app/validators/InputValidator.php';
        $v = new InputValidator();
        return !$v->validatePhone("'; DROP TABLE users; --");
    });
    
    // Test 13: User Model
    $results[] = test('User Model Load', function() {
        try {
            require_once __DIR__ . '/../app/models/User.php';
            $user = new User();
            return $user !== null;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 14: Wallet Model
    $results[] = test('Wallet Model Load', function() {
        try {
            require_once __DIR__ . '/../app/models/Wallet.php';
            $wallet = new Wallet();
            return $wallet !== null;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 15: Transaction Model
    $results[] = test('Transaction Model Load', function() {
        try {
            require_once __DIR__ . '/../app/models/Transaction.php';
            $txn = new Transaction();
            return $txn !== null;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 16: Ledger Model
    $results[] = test('Ledger Model Load', function() {
        try {
            require_once __DIR__ . '/../app/models/Ledger.php';
            $ledger = new Ledger();
            return $ledger !== null;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 17: FraudDetection Service
    $results[] = test('Fraud Detection Service Load', function() {
        try {
            require_once __DIR__ . '/../app/services/FraudDetection.php';
            $fraud = new FraudDetection();
            return $fraud !== null;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 18: RateLimiter Service
    $results[] = test('Rate Limiter Service Load', function() {
        try {
            require_once __DIR__ . '/../app/services/RateLimiter.php';
            $limiter = new RateLimiter();
            return $limiter !== null;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 19: Reconciliation Service
    $results[] = test('Reconciliation Service Load', function() {
        try {
            require_once __DIR__ . '/../app/services/ReconciliationService.php';
            $recon = new ReconciliationService();
            return $recon !== null;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 20: Notifications Table
    $results[] = test('Notifications Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'notifications'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 21: Admin Users Exist
    $results[] = test('Admin User Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 22: System Alerts Table
    $results[] = test('System Alerts Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'system_alerts'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 23: Failed Transaction Retry Table
    $results[] = test('Failed Transaction Retry Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'failed_transaction_retry'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 24: Health Check Log Table
    $results[] = test('Health Check Log Table Exists', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SHOW TABLES LIKE 'health_check_log'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    });
    
    // Test 25: Get User Count
    $results[] = test('User Count >= 1', function() {
        try {
            $db = new Database();
            $conn = $db->connect();
            $stmt = $conn->query("SELECT COUNT(*) FROM users");
            return $stmt->fetchColumn() >= 1;
        } catch (Exception $e) {
            return false;
        }
    });
    
    $testEndTime = microtime(true);
    $totalTime = round($testEndTime - $testStartTime, 2);
}

function test($name, $callback) {
    $start = microtime(true);
    try {
        $result = $callback();
        $passed = $result === true;
    } catch (Exception $e) {
        $passed = false;
        $error = $e->getMessage();
    } catch (Error $e) {
        $passed = false;
        $error = $e->getMessage();
    }
    $duration = round((microtime(true) - $start) * 1000);
    
    return [
        'name' => $name,
        'passed' => $passed ?? false,
        'duration' => $duration,
        'error' => $error ?? null
    ];
}

$passed = array_filter($results, function($r) { return $r['passed']; });
$failed = array_filter($results, function($r) { return !$r['passed']; });
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NepalPay Test Runner</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f1f5f9; }
        .pass { background: #dcfce7; }
        .fail { background: #fee2e2; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-800">NepalPay Test Runner</h1>
            <p class="text-gray-600">Browser-based validation tests</p>
        </div>
        
        <form method="POST" class="mb-6">
            <button type="submit" name="run_tests" value="1" 
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 shadow">
                <?php if ($running): ?>
                    Running Tests...
                <?php else: ?>
                    Run All Tests
                <?php endif; ?>
            </button>
        </form>
        
        <?php if ($running && !empty($results)): ?>
        
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex gap-4 mb-4">
                <div class="bg-green-100 text-green-800 px-4 py-2 rounded-lg">
                    <span class="font-bold"><?php echo count($passed); ?></span> Passed
                </div>
                <div class="bg-red-100 text-red-800 px-4 py-2 rounded-lg">
                    <span class="font-bold"><?php echo count($failed); ?></span> Failed
                </div>
                <div class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg">
                    <span class="font-bold"><?php echo $totalTime; ?></span> ms
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Test</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Duration</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold">Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="<?php echo $r['passed'] ? 'pass' : 'fail'; ?> border-b">
                        <td class="px-4 py-3">
                            <?php if ($r['passed']): ?>
                            <span class="text-green-600 font-bold">✓ PASS</span>
                            <?php else: ?>
                            <span class="text-red-600 font-bold">✗ FAIL</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($r['name']); ?></td>
                        <td class="px-4 py-3 text-gray-500"><?php echo $r['duration']; ?> ms</td>
                        <td class="px-4 py-3 text-red-500 text-sm"><?php echo htmlspecialchars($r['error'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($failed) > 0): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-6">
            <h3 class="font-bold text-red-800 mb-2">Failed Tests Need Attention:</h3>
            <ul class="list-disc list-inside text-red-700">
                <?php foreach ($failed as $f): ?>
                <li><?php echo htmlspecialchars($f['name']); ?>: <?php echo htmlspecialchars($f['error'] ?? 'Unknown error'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        
        <!-- Manual Test Checklist -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h2 class="text-xl font-bold mb-4">Manual Test Checklist</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold mb-2">Core Features</h3>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> User Registration
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> User Login
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> Admin Login
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> View Wallet Balance
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> Add Money (Deposit)
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> Send Money
                    </label>
                </div>
                <div>
                    <h3 class="font-semibold mb-2">Services</h3>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> Pay Bills Flow
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> Mobile Topup
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> Transaction History
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> KYC Submission
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> Admin Dashboard
                    </label>
                    <label class="flex items-center gap-2 mb-1">
                        <input type="checkbox" class="w-4 h-4"> User Management
                    </label>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
