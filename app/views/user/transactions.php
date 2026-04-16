<?php
/**
 * User Transactions History View
 */
$pageTitle = 'Transactions';
$currentPage = 'transactions';

$filterType = $_GET['type'] ?? null;
$filterStatus = $_GET['status'] ?? null;

ob_start();
?>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-sm text-gray-600 mb-1">Type</label>
            <select name="type" class="p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Types</option>
                <option value="deposit" <?php echo $filterType === 'deposit' ? 'selected' : ''; ?>>Deposit</option>
                <option value="withdrawal" <?php echo $filterType === 'withdrawal' ? 'selected' : ''; ?>>Withdrawal</option>
                <option value="transfer" <?php echo $filterType === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                <option value="bill" <?php echo $filterType === 'bill' ? 'selected' : ''; ?>>Bill Payment</option>
                <option value="topup" <?php echo $filterType === 'topup' ? 'selected' : ''; ?>>Mobile Topup</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">Status</label>
            <select name="status" class="p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="failed" <?php echo $filterStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-filter mr-1"></i> Filter
        </button>
        <a href="/transactions" class="px-4 py-2 text-gray-600 hover:text-gray-800">Clear</a>
    </form>
</div>

<!-- Transactions List -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($txns)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-history text-4xl mb-3 text-gray-300"></i>
            <p>No transactions found</p>
        </div>
    <?php else: ?>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Date</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Description</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Type</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Amount</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($txns as $txn): ?>
                    <tr class="border-t border-gray-100 hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">
                            <?php echo date('M d, Y h:i A', strtotime($txn['created_at'])); ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium"><?php echo htmlspecialchars($txn['description'] ?? 'Transaction'); ?></div>
                            <?php if (!empty($txn['txn_id'])): ?>
                                <div class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($txn['txn_id']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php 
                            $typeLabels = [
                                'deposit' => ['label' => 'Deposit', 'class' => 'bg-green-100 text-green-700'],
                                'withdrawal' => ['label' => 'Withdrawal', 'class' => 'bg-red-100 text-red-700'],
                                'transfer' => ['label' => 'Transfer', 'class' => 'bg-blue-100 text-blue-700'],
                                'bill' => ['label' => 'Bill', 'class' => 'bg-yellow-100 text-yellow-700'],
                                'topup' => ['label' => 'Topup', 'class' => 'bg-purple-100 text-purple-700']
                            ];
                            $typeInfo = $typeLabels[$txn['type']] ?? ['label' => $txn['type'], 'class' => 'bg-gray-100 text-gray-700'];
                            ?>
                            <span class="px-2 py-1 rounded text-xs <?php echo $typeInfo['class']; ?>">
                                <?php echo $typeInfo['label']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 font-semibold <?php echo ($txn['sender_id'] == $_SESSION['user_id']) ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo ($txn['sender_id'] == $_SESSION['user_id']) ? '-' : '+'; ?>Rs <?php echo number_format($txn['amount'], 2); ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php 
                            $statusLabels = [
                                'completed' => ['label' => 'Completed', 'class' => 'bg-green-100 text-green-700'],
                                'pending' => ['label' => 'Pending', 'class' => 'bg-yellow-100 text-yellow-700'],
                                'failed' => ['label' => 'Failed', 'class' => 'bg-red-100 text-red-700'],
                                'processing' => ['label' => 'Processing', 'class' => 'bg-blue-100 text-blue-700']
                            ];
                            $statusInfo = $statusLabels[$txn['status']] ?? ['label' => $txn['status'], 'class' => 'bg-gray-100 text-gray-700'];
                            ?>
                            <span class="px-2 py-1 rounded text-xs <?php echo $statusInfo['class']; ?>">
                                <?php echo $statusInfo['label']; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layouts/app.php';
