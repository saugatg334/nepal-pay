<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

// Handle wallet adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_POST['user_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $action = $_POST['action'];
    
    if ($userId > 0 && $amount > 0) {
        try {
            if ($action === 'credit') {
                $userModel->updateWalletBalance($userId, $amount);
                $message = 'Wallet credited successfully';
            } elseif ($action === 'debit') {
                $userModel->updateWalletBalance($userId, -$amount);
                $message = 'Wallet debited successfully';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

// Sample wallet data
$wallets = [
    ['user_id' => 1, 'name' => 'Ram Shrestha', 'phone' => '9841000001', 'balance' => 25000, 'last_transaction' => '2024-01-21'],
    ['user_id' => 2, 'name' => 'Sita Pandey', 'phone' => '9841000002', 'balance' => 15000, 'last_transaction' => '2024-01-20'],
    ['user_id' => 3, 'name' => 'Hari Kumar', 'phone' => '9841000003', 'balance' => 5000, 'last_transaction' => '2024-01-19'],
    ['user_id' => 4, 'name' => 'Gita Devi', 'phone' => '9841000004', 'balance' => 0, 'last_transaction' => '2024-01-18'],
    ['user_id' => 5, 'name' => 'Mohan Rai', 'phone' => '9841000005', 'balance' => 35000, 'last_transaction' => '2024-01-21'],
];

$totalBalance = array_sum(array_column($wallets, 'balance'));

$adminInitials = 'A';
if (!empty($currentUser['full_name'])) {
    $parts = explode(' ', $currentUser['full_name']);
    $adminInitials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $adminInitials .= strtoupper(substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay Admin - Wallet Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="../assets/logo.svg" alt="Nepal Pay" class="w-10 h-10">
            <div><div class="sidebar-brand-text">Nepal Pay</div><div class="sidebar-brand-subtitle">Admin Panel</div></div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-th-large icon"></i> Dashboard</a>
            <div class="sidebar-section"><div class="sidebar-section-title">User Management</div></div>
            <a href="users.php"><i class="fas fa-users icon"></i> Users</a>
            <a href="kyc-verification.php"><i class="fas fa-id-card icon"></i> KYC Verification</a>
            <div class="sidebar-section"><div class="sidebar-section-title">Financial</div></div>
            <a href="transactions.php"><i class="fas fa-exchange-alt icon"></i> Transactions</a>
            <a href="wallet-management.php" class="active"><i class="fas fa-wallet icon"></i> Wallet Management</a>
            <a href="deposits.php"><i class="fas fa-arrow-down icon"></i> Deposits</a>
            <a href="withdrawals.php"><i class="fas fa-arrow-up icon"></i> Withdrawals</a>
            <div class="sidebar-section"><div class="sidebar-section-title">System</div></div>
            <a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports & Analytics</a>
            <a href="settings.php"><i class="fas fa-cog icon"></i> System Settings</a>
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php"><i class="fas fa-user-circle icon"></i> Admin Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
            </div>
        </nav>
    </aside>
    
    <header class="admin-topbar">
        <div class="admin-topbar-left"><h2 class="admin-topbar-title">Wallet Management</h2></div>
        <div class="admin-topbar-right">
            <div class="navbar-search"><input type="text" placeholder="Search..." class="bg-gray-100"></div>
            <button class="navbar-notification"><i class="fas fa-bell text-gray-600"></i><span class="badge">8</span></button>
            <div class="dropdown" id="profileDropdown">
                <div class="navbar-profile" onclick="toggleDropdown('profileDropdown')">
                    <div class="avatar"><?php echo htmlspecialchars($adminInitials); ?></div>
                    <i class="fas fa-chevron-down ml-2 text-gray-400"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                    <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="admin-content">
        <div class="max-w-7xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Wallet Management</h1>
                    <p class="page-subtitle">Manage user wallets and balances</p>
                </div>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="p-4 mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="grid-3 mb-6">
                <div class="stats-card">
                    <div class="stats-card-icon success"><i class="fas fa-wallet"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Total System Balance</div>
                        <div class="stats-card-value">Rs <?php echo number_format($totalBalance); ?></div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon primary"><i class="fas fa-users"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Active Wallets</div>
                        <div class="stats-card-value"><?php echo count(array_filter($wallets, fn($w) => $w['balance'] > 0)); ?></div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon warning"><i class="fas fa-ban"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Zero Balance</div>
                        <div class="stats-card-value"><?php echo count(array_filter($wallets, fn($w) => $w['balance'] == 0)); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Wallets Table -->
            <div class="card">
                <h3 class="card-title mb-4">User Wallets</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Phone</th>
                                <th>Balance</th>
                                <th>Last Transaction</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($wallets as $wallet): ?>
                            <tr>
                                <td>
                                    <div class="table-user">
                                        <div class="avatar"><?php echo strtoupper(substr($wallet['name'], 0, 1)); ?></div>
                                        <div class="table-user-name"><?php echo htmlspecialchars($wallet['name']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($wallet['phone']); ?></td>
                                <td class="font-semibold text-lg">Rs <?php echo number_format($wallet['balance']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($wallet['last_transaction'])); ?></td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="btn btn-sm btn-success" onclick="openAdjustment(<?php echo $wallet['user_id']; ?>, 'credit')">
                                            <i class="fas fa-plus"></i> Credit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="openAdjustment(<?php echo $wallet['user_id']; ?>, 'debit')">
                                            <i class="fas fa-minus"></i> Debit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Adjustment Modal -->
    <div id="adjustmentModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-6 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-lg">
            <h2 class="text-lg font-semibold mb-4">Wallet Adjustment</h2>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="modalUserId">
                <input type="hidden" name="action" id="modalAction">
                <div class="form-group">
                    <label class="form-label">Amount (NPR)</label>
                    <input type="number" name="amount" class="form-input" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" class="form-input" rows="2" placeholder="Reason for adjustment"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn btn-primary flex-1">Submit</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/app.js"></script>
    <script>
        function toggleDropdown(id) { document.getElementById(id).classList.toggle('open'); }
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.dropdown').forEach(d => { if (!d.contains(e.target)) d.classList.remove('open'); });
        });
        function openAdjustment(userId, action) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalAction').value = action;
            document.getElementById('adjustmentModal').classList.remove('hidden');
        }
        function closeModal() { document.getElementById('adjustmentModal').classList.add('hidden'); }
    </script>
</body>
</html>
