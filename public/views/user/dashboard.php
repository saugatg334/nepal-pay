<?php
$page_title = 'Dashboard';
$menu_items = [
    ['href' => '?path=user/dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-home', 'active' => true],
    ['href' => '?path=wallet/send', 'label' => 'Send Money', 'icon' => 'fas fa-paper-plane'],
    ['href' => '?path=wallet/request', 'label' => 'Request Money', 'icon' => 'fas fa-hand-holding-usd'],
    ['href' => '?path=wallet/deposit', 'label' => 'Deposit', 'icon' => 'fas fa-plus-circle'],
    ['href' => '?path=wallet/history', 'label' => 'Transactions', 'icon' => 'fas fa-history'],
    ['href' => '?path=user/kyc', 'label' => 'KYC', 'icon' => 'fas fa-id-card'],
    ['href' => '?path=user/profile', 'label' => 'Profile', 'icon' => 'fas fa-user'],
];
$user_id = $_SESSION['user_id'];
$balance = $walletCtrl->getWalletBalance($user_id) ?? 0;
?>

<div class="space-y-8">
    <!-- Balance Card -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-8 rounded-3xl shadow-2xl">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-blue-200 text-sm uppercase tracking-wide">Wallet Balance</p>
                <p class="text-4xl font-bold mt-2">Rs <?= number_format($balance, 2) ?></p>
            </div>
            <div class="text-right">
                <p class="text-blue-200 text-sm">****1234</p>
                <p class="text-xs text-blue-300">Account ending in</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mt-6">
            <div class="text-center p-3 rounded-2xl bg-white/10 backdrop-blur-sm hover:bg-white/20 transition-all cursor-pointer">
                <i class="fas fa-exchange-alt text-2xl mb-2 block"></i>
                <p class="text-sm font-medium">Transfer</p>
            </div>
            <div class="text-center p-3 rounded-2xl bg-white/10 backdrop-blur-sm hover:bg-white/20 transition-all cursor-pointer">
                <i class="fas fa-credit-card text-2xl mb-2 block"></i>
                <p class="text-sm font-medium">Pay</p>
            </div>
            <div class="text-center p-3 rounded-2xl bg-white/10 backdrop-blur-sm hover:bg-white/20 transition-all cursor-pointer">
                <i class="fas fa-chart-line text-2xl mb-2 block"></i>
                <p class="text-sm font-medium">Stats</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div>
        <h2 class="text-xl font-bold mb-6">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <a href="?path=wallet/send" class="group p-6 bg-white rounded-2xl shadow hover:shadow-xl hover-lift border-2 border-gray-100 hover:border-blue-200 transition-all duration-200 flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-paper-plane text-2xl text-white"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Send Money</h3>
                <p class="text-sm text-gray-500">To phone/email</p>
            </a>

            <a href="?path=wallet/request" class="group p-6 bg-white rounded-2xl shadow hover:shadow-xl hover-lift border-2 border-gray-100 hover:border-green-200 transition-all duration-200 flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-hand-holding-usd text-2xl text-white"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Request</h3>
                <p class="text-sm text-gray-500">Payment link</p>
            </a>

            <a href="?path=wallet/deposit" class="group p-6 bg-white rounded-2xl shadow hover:shadow-xl hover-lift border-2 border-gray-100 hover:border-purple-200 transition-all duration-200 flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-plus-circle text-2xl text-white"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Deposit</h3>
                <p class="text-sm text-gray-500">Add funds</p>
            </a>

            <a href="?path=pay" class="group p-6 bg-white rounded-2xl shadow hover:shadow-xl hover-lift border-2 border-gray-100 hover:border-orange-200 transition-all duration-200 flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-receipt text-2xl text-white"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">Pay Bills</h3>
                <p class="text-sm text-gray-500">Utilities</p>
            </a>

            <a href="?path=wallet/history" class="group p-6 bg-white rounded-2xl shadow hover:shadow-xl hover-lift border-2 border-gray-100 hover:border-indigo-200 transition-all duration-200 flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <i class="fas fa-history text-2xl text-white"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-1">History</h3>
                <p class="text-sm text-gray-500">Transactions</p>
            </a>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-list text-blue-600"></i>
                Recent Transactions
            </h2>
        </div>
        <div class="divide-y divide-gray-100">
            <?php foreach (array_slice($recent_txns ?? [], 0, 5) as $txn): ?>
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-r from-green-500 to-green-600 flex items-center justify-center text-white text-lg font-semibold">
                                +<?= number_format($txn['amount'] ?? 0, 0) ?>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900"><?= htmlspecialchars($txn['description'] ?? 'Transfer') ?></p>
                                <p class="text-sm text-gray-500"><?= date('M d, Y', strtotime($txn['created_at'] ?? 'now')) ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-green-600 text-lg">Rs <?= number_format($txn['amount'] ?? 0, 2) ?></p>
                            <div class="w-20 bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-medium">
                                <?= ucfirst($txn['status'] ?? 'completed') ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($recent_txns)): ?>
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4 opacity-50"></i>
                    <p class="text-lg font-medium">No transactions yet</p>
                    <p class="mt-1">Send or receive your first payment</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

