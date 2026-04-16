<?php
/**
 * Pay Bills - Step 3: Confirm Payment
 */
$pageTitle = 'Pay Bills - Confirm';
$currentPage = 'pay-bills';
$step = 3;
$billTypes = Bill::BILL_TYPES;
$billInfo = $billTypes[$billType] ?? [];

ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="card text-center">
        <!-- Icon -->
        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-blue-100 flex items-center justify-center">
            <i class="fas fa-file-invoice-dollar text-3xl text-blue-600"></i>
        </div>
        
        <h3 class="text-xl font-semibold mb-6">Confirm Payment</h3>
        
        <!-- Summary -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6 text-left">
            <table class="w-full">
                <tr class="border-b">
                    <td class="py-3 text-gray-600">Bill Type</td>
                    <td class="py-3 font-semibold text-right"><?php echo htmlspecialchars($billInfo['name']); ?></td>
                </tr>
                <tr class="border-b">
                    <td class="py-3 text-gray-600">Customer Name</td>
                    <td class="py-3 font-semibold text-right"><?php echo htmlspecialchars($customerName); ?></td>
                </tr>
                <tr class="border-b">
                    <td class="py-3 text-gray-600">Customer ID</td>
                    <td class="py-3 font-semibold text-right"><?php echo htmlspecialchars($customerId); ?></td>
                </tr>
                <tr class="border-b">
                    <td class="py-3 text-gray-600">Provider</td>
                    <td class="py-3 font-semibold text-right"><?php echo htmlspecialchars($billInfo['provider']); ?></td>
                </tr>
                <tr>
                    <td class="py-3 text-gray-600">Amount</td>
                    <td class="py-3 font-bold text-xl text-right text-blue-600">NPR <?php echo number_format($amount, 2); ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Balance Warning -->
        <div class="bg-yellow-50 p-3 rounded-lg mb-6 text-left">
            <div class="flex items-center gap-2 text-yellow-700">
                <i class="fas fa-info-circle"></i>
                <span>Your balance: <strong>NPR <?php echo number_format($balance, 2); ?></strong></span>
            </div>
            <?php if ($balance < $amount): ?>
                <div class="text-red-600 text-sm mt-1 ml-6">Insufficient balance!</div>
            <?php endif; ?>
        </div>
        
        <!-- Confirm Form -->
        <form method="POST">
            <input type="hidden" name="bill_type" value="<?php echo htmlspecialchars($billType); ?>">
            <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customerId); ?>">
            <input type="hidden" name="customer_name" value="<?php echo htmlspecialchars($customerName); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
            
            <div class="flex gap-3">
                <a href="/pay-bills?step=2&type=<?php echo $billType; ?>" class="btn btn-secondary flex-1">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button type="submit" class="btn btn-primary flex-1 btn-lg" <?php echo $balance < $amount ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-circle"></i> Confirm Payment
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/app.php';
