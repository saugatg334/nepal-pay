<?php
/**
 * Admin Dashboard View
 */
$pageTitle = 'Admin Dashboard';
$currentPage = 'admin/dashboard';

ob_start();
?>

<h1 class="text-2xl font-bold mb-6 text-gray-800">Dashboard</h1>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stats-card">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-users text-blue-600 text-xl"></i>
        </div>
        <div>
            <div class="text-sm text-gray-500">Total Users</div>
            <div class="text-xl font-bold"><?php echo number_format($totalUsers ?? 0); ?></div>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-wallet text-green-600 text-xl"></i>
        </div>
        <div>
            <div class="text-sm text-gray-500">Total Balance</div>
            <div class="text-xl font-bold">Rs <?php echo number_format($totalBalance ?? 0); ?></div>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-exchange-alt text-yellow-600 text-xl"></i>
        </div>
        <div>
            <div class="text-sm text-gray-500">Transactions</div>
            <div class="text-xl font-bold"><?php echo number_format($totalTransactions ?? 0); ?></div>
        </div>
    </div>
    
    <div class="stats-card">
        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-id-card text-red-600 text-xl"></i>
        </div>
        <div>
            <div class="text-sm text-gray-500">Pending KYC</div>
            <div class="text-xl font-bold"><?php echo $pendingKYC ?? 0; ?></div>
        </div>
    </div>
</div>

<!-- Tables -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="font-semibold mb-4 text-gray-800">Recent Users</h3>
        <?php if (empty($recentUsers)): ?>
            <p class="text-gray-500 text-center py-4">No users yet</p>
        <?php else: ?>
            <table class="w-full">
                <thead>
                    <tr class="text-left text-sm text-gray-500 border-b">
                        <th class="pb-2">User</th>
                        <th class="pb-2">Status</th>
                        <th class="pb-2">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="py-2">
                                <div class="font-medium"><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></div>
                            </td>
                            <td class="py-2">
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                                    <?php echo $user['status'] ?? 'active'; ?>
                                </span>
                            </td>
                            <td class="py-2 text-gray-500 text-sm">
                                <?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-4">
        <h3 class="font-semibold mb-4 text-gray-800">Recent Transactions</h3>
        <?php if (empty($recentTransactions)): ?>
            <p class="text-gray-500 text-center py-4">No transactions yet</p>
        <?php else: ?>
            <table class="w-full">
                <thead>
                    <tr class="text-left text-sm text-gray-500 border-b">
                        <th class="pb-2">Transaction</th>
                        <th class="pb-2">User</th>
                        <th class="pb-2">Amount</th>
                        <th class="pb-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $txn): ?>
                        <tr class="border-b border-gray-100 last:border-0">
                            <td class="py-2 text-sm font-mono"><?php echo htmlspecialchars($txn['txn_id'] ?? ''); ?></td>
                            <td class="py-2 text-sm"><?php echo htmlspecialchars($txn['sender_name'] ?? 'N/A'); ?></td>
                            <td class="py-2">Rs <?php echo number_format($txn['amount'] ?? 0); ?></td>
                            <td class="py-2">
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">
                                    <?php echo $txn['status'] ?? 'completed'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layouts/app.php';
