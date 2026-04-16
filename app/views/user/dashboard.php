<?php
/**
 * User Dashboard View
 */
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$balance = $userModel->getWalletBalance($_SESSION['user_id']);

ob_start();
?>

<!-- Wallet Card -->
<div class="wallet-card mb-6" style="max-width: 400px;">
    <div class="text-sm opacity-90">Available Balance</div>
    <div class="text-4xl font-bold mt-2">Rs <?php echo number_format($balance, 2); ?></div>
    <div class="text-sm opacity-90 mt-4"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></div>
    <div class="flex gap-3 mt-4">
        <a href="/wallet" class="px-4 py-2 bg-white/20 rounded-lg text-sm hover:bg-white/30">Add Money</a>
        <a href="/send-money" class="px-4 py-2 bg-white/20 rounded-lg text-sm hover:bg-white/30">Send</a>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <a href="/send-money" class="bg-white p-4 rounded-xl shadow-sm text-center hover:shadow-md transition">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-2">
            <i class="fas fa-paper-plane text-blue-600"></i>
        </div>
        <div class="text-sm font-medium">Send Money</div>
    </a>
    <a href="/request-money" class="bg-white p-4 rounded-xl shadow-sm text-center hover:shadow-md transition">
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-2">
            <i class="fas fa-hand-holding-usd text-green-600"></i>
        </div>
        <div class="text-sm font-medium">Request</div>
    </a>
    <a href="/topup" class="bg-white p-4 rounded-xl shadow-sm text-center hover:shadow-md transition">
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-2">
            <i class="fas fa-mobile-alt text-purple-600"></i>
        </div>
        <div class="text-sm font-medium">Topup</div>
    </a>
    <a href="/pay-bills" class="bg-white p-4 rounded-xl shadow-sm text-center hover:shadow-md transition">
        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-2">
            <i class="fas fa-file-invoice-dollar text-yellow-600"></i>
        </div>
        <div class="text-sm font-medium">Pay Bills</div>
    </a>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-xl shadow-sm p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-gray-800">Recent Transactions</h3>
        <a href="/transactions" class="text-sm text-blue-600 hover:underline">View All</a>
    </div>
    
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


<?php
$content = ob_get_clean();
include __DIR__ . '/../../views/layouts/app.php';

