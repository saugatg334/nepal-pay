<?php
/**
 * User Wallet View
 */
$pageTitle = 'Wallet';
$currentPage = 'wallet';

ob_start();
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Wallet Card -->
    <div>
        <div class="wallet-card" style="max-width: 400px;">
            <div class="text-sm opacity-90">Available Balance</div>
            <div class="text-4xl font-bold mt-2">Rs <?php echo number_format($balance, 2); ?></div>
            <div class="text-sm opacity-90 mt-4"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></div>
        </div>
        
        <!-- Deposit Form -->
        <div class="bg-white rounded-xl shadow-sm p-4 mt-4">
            <h3 class="font-semibold mb-4 text-gray-800">Add Money</h3>
            <form method="POST" action="/wallet/deposit">
                <div class="mb-3">
                    <label class="block text-sm text-gray-600 mb-1">Amount (Rs)</label>
                    <input type="number" name="amount" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter amount" min="10" max="50000" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg hover:bg-blue-700 transition">
                    Add Money
                </button>
            </form>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="font-semibold mb-4 text-gray-800">Recent Transactions</h3>
        <?php if (empty($recentTxns)): ?>
            <p class="text-gray-500 text-center py-4">No transactions yet</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentTxns as $txn): ?>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo ($txn['sender_id'] == $_SESSION['user_id']) ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                                <i class="fas <?php echo ($txn['sender_id'] == $_SESSION['user_id']) ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium"><?php echo htmlspecialchars($txn['description'] ?? 'Transaction'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($txn['created_at'])); ?></div>
                            </div>
                        </div>
                        <div class="font-semibold <?php echo ($txn['sender_id'] == $_SESSION['user_id']) ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo ($txn['sender_id'] == $_SESSION['user_id']) ? '-' : '+'; ?>Rs <?php echo number_format($txn['amount'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layouts/app.php';
