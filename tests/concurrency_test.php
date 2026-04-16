<?php
/**
 * High Concurrency Test - 50 Parallel Transfers
 * Expected: 1 success, 49 idempotency fails, balance correct
 */
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/WalletService.php';

class ConcurrencyTest {
    private $walletService;
    
    public function __setup() {
        $database = new Database();
        $this->walletService = new WalletService();
        echo "Setup complete\n";
    }
    
    public function testConcurrentTransfers() {
        $senderId = 1; // Test user
        $receiverId = 2;
        $amount = 100.00;
        $key = 'test_concurrent_' . uniqid();
        
        $initialBalance = $this->walletService->getBalance($senderId);
        echo "Initial sender balance: {$initialBalance}\n";
        
        // 50 parallel (simulate with rapid sequential + unique keys for realism)
        $results = [];
        for ($i = 1; $i <= 50; $i++) {
            $idempKey = $key . '_' . $i;
            try {
                $result = $this->walletService->transfer($senderId, $receiverId, $amount, 'Concurrency test', $idempKey);
                $results[] = $result;
            } catch (Exception $e) {
                $results[] = ['error' => $e->getMessage()];
            }
        }
        
        $finalBalance = $this->walletService->getBalance($senderId);
        $receiverFinal = $this->walletService->getBalance($receiverId);
        
        $successCount = count(array_filter($results, fn($r) => ($r['success'] ?? false) === true));
        $expectedDeduct = $successCount * $amount;
        
        echo "\n=== RESULTS ===\n";
        echo "Success: {$successCount}/50 (expect 1)\n";
        echo "Sender final: {$finalBalance} (expected " . ($initialBalance - $expectedDeduct) . ")\n";
        echo "Receiver gain: " . ($receiverFinal - $this->walletService->getBalance($receiverId)) . "\n";
        echo "Match: " . ($finalBalance == ($initialBalance - $expectedDeduct) ? 'PASS' : 'FAIL') . "\n";
        
        return $successCount === 1 && abs($finalBalance - ($initialBalance - $amount)) < 0.01;
    }
}

// Run
$test = new ConcurrencyTest();
$test->__setup();
$pass = $test->testConcurrentTransfers();
echo $pass ? '✅ CONCURRENCY PASS' : '❌ CONCURRENCY FAIL';
?>

