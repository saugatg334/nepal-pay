<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

// Check if admin is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Get admin data
$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

// Get stats (in real app, these would be calculated from database)
$totalUsers = 1250;
$totalBalance = 25000000; // Total wallet balance in system
$totalTransactions = 8500;
$pendingKYC = 5;
$pendingDeposits = 3;
$pendingWithdrawals = 2;

// Get user initials for avatar
$adminInitials = 'A';
if (!empty($currentUser['name'])) {
    $nameParts = explode(' ', $currentUser['name']);
    $adminInitials = strtoupper(substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $adminInitials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
    }
} else {
    $adminInitials = 'AD';
}

// Recent users (sample data)
$recentUsers = [
    ['id' => 1, 'name' => 'Ram Shrestha', 'phone' => '9841000001', 'status' => 'active', 'joined' => '2 hours ago'],
    ['id' => 2, 'name' => 'Sita Pandey', 'phone' => '9841000002', 'status' => 'active', 'joined' => '5 hours ago'],
    ['id' => 3, 'name' => 'Hari Kumar', 'phone' => '9841000003', 'status' => 'pending', 'joined' => '1 day ago'],
    ['id' => 4, 'name' => 'Gita Devi', 'phone' => '9841000004', 'status' => 'active', 'joined' => '2 days ago'],
];

// Recent transactions (sample data)
$recentTransactions = [
    ['id' => 'TXN001', 'user' => 'Ram Shrestha', 'type' => 'Transfer', 'amount' => 5000, 'status' => 'completed', 'date' => '2 min ago'],
    ['id' => 'TXN002', 'user' => 'Sita Pandey', 'type' => 'Deposit', 'amount' => 10000, 'status' => 'completed', 'date' => '15 min ago'],
    ['id' => 'TXN003', 'user' => 'Hari Kumar', 'type' => 'Withdrawal', 'amount' => 3000, 'status' => 'pending', 'date' => '30 min ago'],
    ['id' => 'TXN004', 'user' => 'Gita Devi', 'type' => 'Bill Payment', 'amount' => 1500, 'status' => 'completed', 'date' => '1 hour ago'],
    ['id' => 'TXN005', 'user' => 'Mohan Rai', 'type' => 'Transfer', 'amount' => 7500, 'status' => 'completed', 'date' => '2 hours ago'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay Admin - Dashboard</title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="../assets/css/design-system.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/custom.css">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Admin Sidebar -->
    <aside class="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <img src="../assets/logo.svg" alt="Nepal Pay" class="w-10 h-10">
            <div>
                <div class="sidebar-brand-text">Nepal Pay</div>
                <div class="sidebar-brand-subtitle">Admin Panel</div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active">
                <i class="fas fa-th-large icon"></i> Dashboard
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">User Management</div>
            </div>
            
            <a href="users.php">
                <i class="fas fa-users icon"></i> Users
            </a>
            <a href="kyc-verification.php">
                <i class="fas fa-id-card icon"></i> KYC Verification
                <span class="badge">5</span>
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Financial</div>
            </div>
            
            <a href="transactions.php">
                <i class="fas fa-exchange-alt icon"></i> Transactions
            </a>
            <a href="wallet-management.php">
                <i class="fas fa-wallet icon"></i> Wallet Management
            </a>
            <a href="deposits.php">
                <i class="fas fa-arrow-down icon"></i> Deposits
                <span class="badge">3</span>
            </a>
            <a href="withdrawals.php">
                <i class="fas fa-arrow-up icon"></i> Withdrawals
                <span class="badge">2</span>
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">System</div>
            </div>
            
            <a href="reports.php">
                <i class="fas fa-chart-bar icon"></i> Reports & Analytics
            </a>
            <a href="settings.php">
                <i class="fas fa-cog icon"></i> System Settings
            </a>
            
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php">
                    <i class="fas fa-user-circle icon"></i> Admin Profile
                </a>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt icon"></i> Logout
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Admin Topbar -->
    <header class="admin-topbar">
        <div class="admin-topbar-left">
            <h2 class="admin-topbar-title">Dashboard</h2>
        </div>
        <div class="admin-topbar-right">
            <!-- Search -->
            <div class="navbar-search">
                <input type="text" placeholder="Search users, transactions..." class="bg-gray-100">
            </div>
            
            <!-- Notifications -->
            <button class="navbar-notification">
                <i class="fas fa-bell text-gray-600"></i>
                <span class="badge">8</span>
            </button>
            
            <!-- Profile Dropdown -->
            <div class="dropdown" id="profileDropdown">
                <div class="navbar-profile" onclick="toggleDropdown('profileDropdown')">
                    <div class="avatar"><?php echo htmlspecialchars($adminInitials); ?></div>
                    <div class="navbar-profile-info">
                        <span class="navbar-profile-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></span>
                        <span class="navbar-profile-role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down ml-2 text-gray-400"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="admin-content">
        <div class="max-w-7xl mx-auto">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="page-subtitle">Welcome back! Here's what's happening with your platform.</p>
                </div>
                <div class="flex gap-3">
                    <button class="btn btn-secondary">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid-4 mb-6">
                <!-- Total Users -->
                <div class="stats-card">
                    <div class="stats-card-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Total Users</div>
                        <div class="stats-card-value"><?php echo number_format($totalUsers); ?></div>
                        <div class="stats-card-change positive">
                            <i class="fas fa-arrow-up"></i> +12% from last month
                        </div>
                    </div>
                </div>
                
                <!-- Total Balance -->
                <div class="stats-card">
                    <div class="stats-card-icon success">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Total Wallet Balance</div>
                        <div class="stats-card-value">Rs <?php echo number_format($totalBalance); ?></div>
                        <div class="stats-card-change positive">
                            <i class="fas fa-arrow-up"></i> +8% from last month
                        </div>
                    </div>
                </div>
                
                <!-- Total Transactions -->
                <div class="stats-card">
                    <div class="stats-card-icon warning">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Total Transactions</div>
                        <div class="stats-card-value"><?php echo number_format($totalTransactions); ?></div>
                        <div class="stats-card-change positive">
                            <i class="fas fa-arrow-up"></i> +15% from last month
                        </div>
                    </div>
                </div>
                
                <!-- Pending KYC -->
                <div class="stats-card">
                    <div class="stats-card-icon danger">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Pending KYC</div>
                        <div class="stats-card-value"><?php echo $pendingKYC; ?></div>
                        <div class="stats-card-change negative">
                            Needs attention
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Actions -->
            <div class="grid-2 gap-6 mb-6">
                <!-- Pending Deposits -->
                <div class="card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="card-title">Pending Deposits</h3>
                        <a href="deposits.php" class="text-sm text-primary font-medium">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm">Rs <?php echo number_format(rand(5000, 20000)); ?></div>
                                        <div class="text-xs text-gray-500">User #<?php echo $i + 100; ?></div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button class="btn btn-sm btn-success">Approve</button>
                                    <button class="btn btn-sm btn-danger">Reject</button>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Pending Withdrawals -->
                <div class="card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="card-title">Pending Withdrawals</h3>
                        <a href="withdrawals.php" class="text-sm text-primary font-medium">View All</a>
                    </div>
                    <div class="space-y-3">
                        <?php for ($i = 1; $i <= 2; $i++): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-red-600">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm">Rs <?php echo number_format(rand(3000, 15000)); ?></div>
                                        <div class="text-xs text-gray-500">User #<?php echo $i + 200; ?></div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button class="btn btn-sm btn-success">Approve</button>
                                    <button class="btn btn-sm btn-danger">Reject</button>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tables Grid -->
            <div class="grid-2 gap-6">
                <!-- Recent Users -->
                <div class="card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="card-title">Recent Users</h3>
                        <a href="users.php" class="text-sm text-primary font-medium">View All</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="table-user">
                                                <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                                                <div>
                                                    <div class="table-user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                                    <div class="table-user-phone"><?php echo htmlspecialchars($user['phone']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="table-badge <?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $user['joined']; ?></td>
                                        <td>
                                            <a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="card-title">Recent Transactions</h3>
                        <a href="transactions.php" class="text-sm text-primary font-medium">View All</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Transaction</th>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $tx): ?>
                                    <tr>
                                        <td>
                                            <div class="font-medium text-sm"><?php echo htmlspecialchars($tx['id']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo $tx['type']; ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($tx['user']); ?></td>
                                        <td class="font-medium">Rs <?php echo number_format($tx['amount']); ?></td>
                                        <td>
                                            <span class="table-badge <?php echo $tx['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($tx['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- System Activity Chart Placeholder -->
            <div class="card mt-6">
                <h3 class="card-title mb-4">System Activity</h3>
                <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                    <div class="text-center">
                        <i class="fas fa-chart-line text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Transaction volume chart would appear here</p>
                        <p class="text-sm text-gray-400">Last 30 days activity</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- JavaScript -->
    <script src="../assets/js/app.js"></script>
    <script>
        // Dropdown toggle function
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('open');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('open');
                }
            });
        });
    </script>
</body>
</html>
