<?php
/**
 * Pay Bills - Step 1: Select Bill Type & Enter Customer ID
 */
$pageTitle = 'Pay Bills';
$currentPage = 'pay-bills';
$step = 1;
$billTypes = Bill::BILL_TYPES;
$selectedBillType = $_POST['bill_type'] ?? $_GET['type'] ?? null;
$customerId = $_POST['customer_id'] ?? '';

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Step Indicator -->
    <div class="flex items-center justify-center gap-4 mb-8">
        <div class="flex items-center gap-2 <?php echo $step >= 1 ? 'text-blue-600' : 'text-gray-400'; ?>">
            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $step >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">1</div>
            <span class="text-sm font-medium">Select Type</span>
        </div>
        <div class="w-16 h-0.5 <?php echo $step >= 2 ? 'bg-blue-600' : 'bg-gray-200'; ?>"></div>
        <div class="flex items-center gap-2 <?php echo $step >= 2 ? 'text-blue-600' : 'text-gray-400'; ?>">
            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $step >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">2</div>
            <span class="text-sm font-medium">Bill Details</span>
        </div>
        <div class="w-16 h-0.5 <?php echo $step >= 3 ? 'bg-blue-600' : 'bg-gray-200'; ?>"></div>
        <div class="flex items-center gap-2 <?php echo $step >= 3 ? 'text-blue-600' : 'text-gray-400'; ?>">
            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $step >= 3 ? 'bg-blue-600 text-white' : 'bg-gray-200'; ?>">3</div>
            <span class="text-sm font-medium">Confirm</span>
        </div>
        <div class="w-16 h-0.5 <?php echo $step >= 4 ? 'bg-green-500' : 'bg-gray-200'; ?>"></div>
        <div class="flex items-center gap-2 <?php echo $step >= 4 ? 'text-green-600' : 'text-gray-400'; ?>">
            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $step >= 4 ? 'bg-green-500 text-white' : 'bg-gray-200'; ?>">
                <i class="fas fa-check text-sm"></i>
            </div>
            <span class="text-sm font-medium">Done</span>
        </div>
    </div>

    <div class="card">
        <h3 class="text-lg font-semibold mb-6">Select Bill Type & Enter Customer ID</h3>
        
        <form method="POST">
            <!-- Bill Type Selection -->
            <div class="form-group">
                <label class="form-label">Select Bill Type</label>
                <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mb-4">
                    <?php foreach ($billTypes as $key => $bill): ?>
                        <label class="bill-card p-4 border-2 rounded-xl cursor-pointer text-center transition hover:shadow-md <?php echo $selectedBillType === $key ? 'border-blue-500 bg-blue-50' : 'border-gray-200'; ?>"
                               onclick="selectBillType('<?php echo $key; ?>')">
                            <input type="radio" name="bill_type" value="<?php echo $key; ?>" 
                                   <?php echo $selectedBillType === $key ? 'checked' : ''; ?> class="hidden">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-2 <?php echo 'bg-' . $bill['color'] . '-100'; ?>">
                                <i class="fas <?php echo $bill['icon']; ?> text-xl <?php echo 'text-' . $bill['color'] . '-600'; ?>"></i>
                            </div>
                            <div class="text-sm font-medium"><?php echo $bill['name']; ?></div>
                            <div class="text-xs text-gray-500"><?php echo $bill['provider']; ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Customer ID Input -->
            <div class="form-group">
                <label class="form-label">
                    <?php echo $selectedBillType && isset($billTypes[$selectedBillType]) ? $billTypes[$selectedBillType]['idLabel'] : 'Customer ID'; ?>
                </label>
                <input type="text" name="customer_id" class="form-input" 
                       placeholder="<?php echo $selectedBillType && isset($billTypes[$selectedBillType]) ? $billTypes[$selectedBillType]['idLabel'] : 'Enter customer ID'; ?>"
                       value="<?php echo htmlspecialchars($customerId); ?>" required>
                <p class="form-hint">Enter the customer ID from your <?php echo $selectedBillType ? $billTypes[$selectedBillType]['name'] : 'bill'; ?> bill</p>
            </div>
            
            <button type="submit" class="btn btn-primary w-full">
                <i class="fas fa-search"></i> Fetch Bill Details
            </button>
        </form>
    </div>
</div>

<script>
function selectBillType(type) {
    document.querySelectorAll('.bill-card').forEach(card => {
        card.classList.remove('border-blue-500', 'bg-blue-50');
        card.classList.add('border-gray-200');
    });
    event.currentTarget.classList.remove('border-gray-200');
    event.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/app.php';
