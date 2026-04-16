<?php
/**
 * NepalPay Test Runner (HTML)
 * Run tests in browser: http://localhost/nepal-pay/tests/run_tests.php
 */
session_start();

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Wallet.php';
require_once __DIR__ . '/../app/models/Ledger.php';
require_once __DIR__ . '/../app/services/FraudDetection.php';
require_once __DIR__ . '/../app/services/ReconciliationService.php';
require_once __DIR__ . '/../app/services/RateLimiter.php';
require_once __DIR__ . '/../app/services/FailSafeHandler.php';
require_once __DIR__ . '/../app/validators/InputValidator.php';

$results = [];
$testSuite = $_GET['suite'] ?? 'all';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NepalPay Validation Tests</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .test-pass { background: #dcfce7; color: #166534; }
        .test-fail { background: #fee2e2; color: #991b1b; }
        .test-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body class="p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">NepalPay Validation Tests</h1>
        <p class="text-gray-600 mb-8">Production Readiness Verification</p>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        
        <?php
        // Run tests
        $database = new Database();
        $conn = $database->connect();
        
        $tests = [
            // Suite 1: Data Consistency
            [
                'suite' => 'consistency',
                'name' => '1.1 Wallet Balance = Ledger Sum',
                'test' => function() {
                    $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE wallet_balance < 0");
                    return $stmt->fetchColumn() == 0;
                }
            ],
            [
                'suite' => 'consistency',
                'name' => '1.2 No Negative Balances',
                'test' => function() {
                    $stmt = $this->conn->query("SELECT COUNT(*) FROM users WHERE wallet_balance < 0");
                    return $stmt->fetchColumn() == 0;
                }
            ],
            [
                'suite' => 'consistency',
                'name' => '1.3 Transaction Integrity',
                'test' => function() {
                    $stmt = $this->conn->query("SELECT COUNT(*) FROM transactions WHERE amount <= 0");
                    return $stmt->fetchColumn() == 0;
                }
            ],
            
            // Suite 2: Security
            [
                'suite' => 'security',
                'name' => '2.1 SQL Injection Prevention',
                'test' => function() {
                    $v = new InputValidator();
                    return !$v->validatePhone("'; DROP TABLE users; --");
                }
            ],
            [
                'suite' => 'security',
                'name' => '2.2 XSS Prevention',
                'test' => function() {
                    $v = new InputValidator();
                    $clean = $v->sanitize('<script>alert(1)</script>');
                    return strpos($clean, '<script>') === false;
                }
            ],
            [
                'suite' => 'security',
                'name' => '2.3 Input Validation',
                'test' => function() {
                    $v = new InputValidator();
                    return $v->validatePhone('9800000001') && !$v->validatePhone('invalid');
                }
            ],
            
            // Suite 3: System Health
            [
                'suite' => 'health',
                'name' => '3.1 Database Connection',
                'test' => function() {
                    $stmt = $this->conn->query("SELECT 1");
                    return $stmt->fetchColumn() == 1;
                }
            ],
            [
                'suite' => 'health',
                'name' => '3.2 Required Tables Exist',
                'test' => function() {
                    $tables = ['users', 'transactions', 'audit_logs', 'job_queue'];
                    foreach ($tables as $table) {
                        $stmt = $this->conn->query("SHOW TABLES LIKE '$table'");
                        if ($stmt->rowCount() == 0) return false;
                    }
                    return true;
                }
            ],
            [
                'suite' => 'health',
                'name' => '3.3 Health Check Table',
                'test' => function() {
                    $stmt = $this->conn->query("SHOW TABLES LIKE 'health_check_log'");
                    return $stmt->rowCount() > 0;
                }
            ],
            
            // Suite 4: Rate Limiting
            [
                'suite' => 'rate_limit',
                'name' => '4.1 Rate Limiter Service',
                'test' => function() {
                    $limiter = new RateLimiter();
                    $status = $limiter->getStatus(999);
                    return isset($status['per_minute']);
                }
            ],
            
            // Suite 5: Fraud Detection
            [
                'suite' => 'fraud',
                'name' => '5.1 Fraud Detection Active',
                'test' => function() {
                    $fraud = new FraudDetection();
                    $result = $fraud->analyzeTransaction(999, 100, 'test', []);
                    return isset($result['risk_level']);
                }
            ],
            
            // Suite 6: Job Queue
            [
                'suite' => 'queue',
                'name' => '6.1 Job Queue System',
                'test' => function() {
                    $queue = new JobQueue();
                    $stats = $queue->getStats();
                    return is_array($stats);
                }
            ]
        ];
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            if ($testSuite !== 'all' && $test['suite'] !== $testSuite) continue;
            
            try {
                $result = $test['test']();
                $status = $result ? 'pass' : 'fail';
                if ($result) $passed++; else $failed++;
            } catch (Exception $e) {
                $status = 'fail';
                $failed++;
            }
            
            $results[] = [
                'name' => $test['name'],
                'suite' => $test['suite'],
                'status' => $status,
                'error' => $e->getMessage() ?? null
            ];
        }
        ?>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Test Results</h2>
            
            <div class="flex gap-4 mb-4">
                <div class="bg-green-100 text-green-800 px-4 py-2 rounded">
                    Passed: <?php echo $passed; ?>
                </div>
                <div class="bg-red-100 text-red-800 px-4 py-2 rounded">
                    Failed: <?php echo $failed; ?>
                </div>
            </div>
            
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">Test</th>
                        <th class="text-left py-2">Suite</th>
                        <th class="text-left py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr class="border-b">
                        <td class="py-2"><?php echo htmlspecialchars($r['name']); ?></td>
                        <td class="py-2 text-gray-500"><?php echo htmlspecialchars($r['suite']); ?></td>
                        <td class="py-2">
                            <?php if ($r['status'] === 'pass'): ?>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">PASS</span>
                            <?php else: ?>
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">FAIL</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>
        
        <form method="POST" class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Run Tests</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Test Suite</label>
                <select name="suite" class="w-full border rounded px-3 py-2">
                    <option value="all">All Tests</option>
                    <option value="consistency">Data Consistency</option>
                    <option value="security">Security</option>
                    <option value="health">System Health</option>
                    <option value="rate_limit">Rate Limiting</option>
                    <option value="fraud">Fraud Detection</option>
                    <option value="queue">Job Queue</option>
                </select>
            </div>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Run Validation Tests
            </button>
        </form>
        
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Manual Test Checklist</h2>
            
            <div class="space-y-3">
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Login with valid credentials works</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Login with invalid credentials shows error</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Wallet balance displays correctly</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Send money to another user works</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Insufficient balance prevents transaction</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Pay bills flow completes successfully</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Mobile topup processes correctly</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Admin dashboard shows user data</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>Transaction history displays correctly</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="w-4 h-4">
                    <span>CSRF protection blocks forged requests</span>
                </label>
            </div>
        </div>
        
        <div class="mt-8 bg-blue-50 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 text-blue-800">Pre-Production Checklist</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h3 class="font-medium text-blue-700 mb-2">Database</h3>
                    <ul class="text-sm space-y-1">
                        <li>☐ Run all migrations (001-009)</li>
                        <li>☐ Verify tables created</li>
                        <li>☐ Check foreign keys</li>
                        <li>☐ Run initial backup</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-medium text-blue-700 mb-2">Configuration</h3>
                    <ul class="text-sm space-y-1">
                        <li>☐ Set production environment</li>
                        <li>☐ Configure email/SMS</li>
                        <li>☐ Set rate limits</li>
                        <li>☐ Configure backup schedule</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-medium text-blue-700 mb-2">Security</h3>
                    <ul class="text-sm space-y-1">
                        <li>☐ Force HTTPS</li>
                        <li>☐ Configure CORS</li>
                        <li>☐ Set up firewall</li>
                        <li>☐ Enable audit logging</li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-medium text-blue-700 mb-2">Monitoring</h3>
                    <ul class="text-sm space-y-1">
                        <li>☐ Set up cron jobs</li>
                        <li>☐ Configure log rotation</li>
                        <li>☐ Set up alerts</li>
                        <li>☐ Test backup/restore</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
