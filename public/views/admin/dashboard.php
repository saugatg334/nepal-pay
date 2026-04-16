<?php
$page_title = 'Admin Dashboard';
$is_admin = true;
$menu_items = [
    ['href' => '?path=admin/dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'active' => true],
    ['href' => '?path=admin/users', 'label' => 'Users', 'icon' => 'fas fa-users'],
    ['href' => '?path=admin/transactions', 'label' => 'Transactions', 'icon' => 'fas fa-exchange-alt'],
    ['href' => '?path=admin/kyc', 'label' => 'KYC Verification', 'icon' => 'fas fa-id-card'],
    ['href' => '?path=admin/reports', 'label' => 'Reports', 'icon' => 'fas fa-chart-bar'],
    ['href' => '?path=admin/settings', 'label' => 'Settings', 'icon' => 'fas fa-cog'],
    ['href' => '?path=profile', 'label' => 'Profile', 'icon' => 'fas fa-user'],
];
?>

<div class="space-y-8">
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="glass p-6 rounded-3xl shadow-lg border border-blue-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 uppercase tracking-wide">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900">12,456</p>
                </div>
            </div>
            <div class="mt-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <i class="fas fa-arrow-up mr-1"></i> +2.3%
                </span>
            </div>
        </div>

        <div class="glass p-6 rounded-3xl shadow-lg border border-blue-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-exchange-alt text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 uppercase tracking-wide">Transactions</p>
                    <p class="text-3xl font-bold text-gray-900">45,892</p>
                </div>
            </div>
            <div class="mt-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-arrow-up mr-1"></i> +12.1%
                </span>
            </div>
        </div>

        <div class="glass p-6 rounded-3xl shadow-lg border border-blue-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-rupee-sign text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 uppercase tracking-wide">Revenue</p>
                    <p class="text-3xl font-bold text-gray-900">Rs 2.3 Cr</p>
                </div>
            </div>
            <div class="mt-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    <i class="fas fa-arrow-up mr-1"></i> +8.7%
                </span>
            </div>
        </div>

        <div class="glass p-6 rounded-3xl shadow-lg border border-blue-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-red-600 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600 uppercase tracking-wide">Pending KYC</p>
                    <p class="text-3xl font-bold text-gray-900">187</p>
                </div>
            </div>
            <div class="mt-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <i class="fas fa-clock mr-1"></i> 24h
                </span>
            </div>
        </div>
    </div>

    <!-- Charts & Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Revenue Chart Card -->
        <div class="glass rounded-3xl shadow-lg p-6 border border-blue-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Revenue Trend</h3>
                <div class="flex gap-2">
                    <button class="px-3 py-1 text-xs font-medium rounded-xl bg-blue-100 text-blue-700">7D</button>
                    <button class="px-3 py-1 text-xs font-medium rounded-xl text-gray-500">30D</button>
                </div>
            </div>
            <div class="h-64 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl flex items-center justify-center">
                <div class="text-center text-gray-500">
                    <i class="fas fa-chart-line text-6xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">Chart placeholder</p>
                    <p class="text-sm mt-1">Chart.js integration ready</p>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="glass rounded-3xl shadow-lg p-6 border border-blue-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-900">Recent Users</h3>
                <button class="px-4 py-2 text-sm font-medium rounded-xl border border-gray-200 hover:bg-gray-50">View All</button>
            </div>
            <div class="space-y-3">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-2xl cursor-pointer transition-colors">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white font-semibold text-sm">U<?= $i+1 ?></div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate">User Name <?= $i+1 ?></p>
                            <p class="text-sm text-gray-500">98xxxxxxxx<?= rand(0,9) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-green-600">Rs <?= rand(1000,50000)/100 ?></p>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 font-medium">
                                <i class="fas fa-check mr-1"></i> Active
                            </span>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Pending Actions -->
    <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-tasks text-orange-600"></i>
                Pending Actions
            </h3>
        </div>
        <div class="divide-y divide-gray-100">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-orange-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-id-card text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">187 KYC Pending</p>
                            <p class="text-sm text-gray-500">Verify user documents</p>
                        </div>
                    </div>
                    <a href="?path=admin/kyc" class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-xl hover:bg-orange-700">Review</a>
                </div>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-red-100 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">23 Suspicious</p>
                            <p class="text-sm text-gray-500">Flag transactions</p>
                        </div>
                    </div>
                    <a href="?path=admin/transactions" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700">Review</a>
                </div>
            </div>
        </div>
    </div>
</div>

