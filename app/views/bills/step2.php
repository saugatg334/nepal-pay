<?php
/**
 * Pay Bills - Step 2: Show Customer Details
 */
$pageTitle = 'Pay Bills - Bill Details';
$currentPage = 'pay-bills';
$step = 2;
$billTypes = Bill::BILL_TYPES;
$billInfo = $billTypes[$billType] ?? [];

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="card">
        <h3 class="text-lg font-semibold mb-6">Bill Details</h3>
        
        <!-- Customer Info -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500">Customer Name</div>
                    <div class="text-lg font-semibold"><?php echo htmlspecialchars($customer['name']); ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Customer ID</div>
                    <div class="text-lg font-semibold"><?php echo htmlspecialchars($customer['customer_id']); ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Provider</div>
                    <div class="font-semibold"><?php echo htmlspecialchars($customer['provider']); ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Bill Month</div>
                    <div class="font-semibold"><?php echo htmlspecialchars($customer['bill_month']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Amount Form -->
        <form method="POST">
            <input type="hidden" name="bill_type" value="<?php echo htmlspecialchars($billType); ?>">
            <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customerId); ?>">
            <input type="hidden" name="customer_name" value="<?php echo htmlspecialchars($customer['name']); ?>">
            
            <div class="form-group">
                <label class="form-label">Payment Amount (NPR)</label>
                <input type="number" name="amount" class="form-input text-xl" 
                       value="<?php echo $customer['due_amount']; ?>"
                       min="10" max="100000" required>
                <p class="form-hint">You can pay full due amount or any partial amount</p>
            </div>
            
            <div class="flex gap-3">
                <a href="/pay-bills" class="btn btn-secondary flex-1">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button type="submit" class="btn btn-primary flex-1">
                    Proceed to Pay <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/app.php';
