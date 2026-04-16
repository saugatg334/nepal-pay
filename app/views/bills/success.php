<?php
/**
 * Pay Bills - Step 4: Success
 */
$pageTitle = 'Payment Successful';
$currentPage = 'pay-bills';
$step = 4;
$billTypes = Bill::BILL_TYPES;

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="card text-center">
        <!-- Success Icon -->
        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-green-100 flex items-center justify-center">
            <i class="fas fa-check text-4xl text-green-600"></i>
        </div>
        
        <h3 class="text-2xl font-bold text-green-600 mb-2">Payment Successful!</h3>
        <p class="text-gray-500 mb-6">Your payment has been processed successfully.</p>
        
        <!-- Receipt -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6 max-w-md mx-auto">
            <div class="text-center">
                <div class="text-sm text-gray-500 mb-2">Transaction ID</div>
                <div class="font-mono font-bold text-lg"><?php echo htmlspecialchars($result['txn_id'] ?? 'N/A'); ?></div>
            </div>
            
            <hr class="my-4">
            
            <div class="grid grid-cols-2 gap-3 text-left">
                <div class="text-sm text-gray-500">Bill Type</div>
                <div class="text-right font-medium"><?php echo htmlspecialchars($billTypes[$result['bill_type']]['name'] ?? ''); ?></div>
                
                <div class="text-sm text-gray-500">Customer ID</div>
                <div class="text-right font-medium"><?php echo htmlspecialchars($result['customer_id'] ?? ''); ?></div>
                
                <div class="text-sm text-gray-500">Provider</div>
                <div class="text-right font-medium"><?php echo htmlspecialchars($result['provider'] ?? ''); ?></div>
                
                <div class="text-sm text-gray-500">Amount Paid</div>
                <div class="text-right font-bold text-xl text-green-600">NPR <?php echo number_format($result['amount'], 2); ?></div>
                
                <div class="text-sm text-gray-500">New Balance</div>
                <div class="text-right font-bold text-green-600">NPR <?php echo number_format($result['new_balance'], 2); ?></div>
                
                <div class="text-sm text-gray-500">Date & Time</div>
                <div class="text-right font-medium"><?php echo date('M d, Y h:i A'); ?></div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex gap-3 justify-center">
            <a href="/dashboard" class="btn btn-primary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
            <button onclick="window.print()" class="btn btn-outline">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
        
        <!-- Another Payment -->
        <div class="mt-6 pt-6 border-t">
            <a href="/pay-bills" class="text-blue-600 hover:underline">
                <i class="fas fa-plus"></i> Make Another Payment
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/app.php';
