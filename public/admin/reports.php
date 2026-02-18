<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

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
    <title>Nepal Pay Admin - Reports & Analytics</title>
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
            <a href="wallet-management.php"><i class="fas fa-wallet icon"></i> Wallet Management</a>
            <a href="deposits.php"><i class="fas fa-arrow-down icon"></i> Deposits</a>
            <a href="withdrawals.php"><i class="fas fa-arrow-up icon"></i> Withdrawals</a>
            <div class="sidebar-section"><div class="sidebar-section-title">System</div></div>
            <a href="reports.php" class="active"><i class="fas fa-chart-bar icon"></i> Reports & Analytics</a>
            <a href="settings.php"><i class="fas fa-cog icon"></i> System Settings</a>
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php"><i class="fas fa-user-circle icon"></i> Admin Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
            </div>
        </nav>
    </aside>
    
    <header class="admin-topbar">
        <div class="admin-topbar-left"><h2 class="admin-topbar-title">Reports & Analytics</h2></div>
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
                    <h1 class="page-title">Reports & Analytics</h1>
                    <p class="page-subtitle">View platform performance and insights</p>
                </div>
                <div class="flex gap-3">
                    <select class="form-select">
                        <option>Last 7 Days</option>
                        <option selected>Last 30 Days</option>
                        <option>Last 90 Days</option>
                        <option>This Year</option>
                    </select>
                    <button class="btn btn-primary"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid-2 gap-6 mb-6">
                <div class="card">
                    <h3 class="card-title mb-4">Transaction Volume</h3>
                    <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-chart-line text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">Transaction volume chart</p>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <h3 class="card-title mb-4">User Growth</h3>
                    <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-chart-area text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">User growth chart</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid-4 mb-6">
                <div class="stats-card">
                    <div class="stats-card-icon primary"><i class="fas fa-users"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">New Users (30d)</div>
                        <div class="stats-card-value">+125</div>
                        <div class="stats-card-change positive">+15% vs last month</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon success"><i class="fas fa-exchange-alt"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Transactions (30d)</div>
                        <div class="stats-card-value">8,542</div>
                        <div class="stats-card-change positive">+22% vs last month</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon warning"><i class="fas fa-arrow-down"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Total Deposits (30d)</div>
                        <div class="stats-card-value">Rs 12.5M</div>
                        <div class="stats-card-change positive">+18% vs last month</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon danger"><i class="fas fa-arrow-up"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Total Withdrawals (30d)</div>
                        <div class="stats-card-value">Rs 8.2M</div>
                        <div class="stats-card-change positive">+8% vs last month</div>
                    </div>
                </div>
            </div>
            
            <!-- Top Users Table -->
            <div class="card">
                <h3 class="card-title mb-4">Top Users by Transaction Volume</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>User</th>
                                <th>Transactions</th>
                                <th>Volume</th>
                                <th>Avg. Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-yellow-400 text-white">1</span></td>
                                <td><div class="table-user"><div class="avatar">M</div><div class="table-user-name">Mohan Rai</div></div></td>
                                <td>156</td>
                                <td class="font-semibold">Rs 2,450,000</td>
                                <td>Rs 15,705</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-gray-400 text-white">2</span></td>
                                <td><div class="table-user"><div class="avatar">R</div><div class="table-user-name">Ram Shrestha</div></div></td>
                                <td>142</td>
                                <td class="font-semibold">Rs 1,890,000</td>
                                <td>Rs 13,310</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-amber-700 text-white">3</span></td>
                                <td><div class="table-user"><div class="avatar">S</div><div class="table-user-name">Sita Pandey</div></div></td>
                                <td>128</td>
                                <td class="font-semibold">Rs 1,560,000</td>
                                <td>Rs 12,188</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td><div class="table-user"><div class="avatar">H</div><div class="table-user-name">Hari Kumar</div></div></td>
                                <td>98</td>
                                <td class="font-semibold">Rs 980,000</td>
                                <td>Rs 10,000</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td><div class="table-user"><div class="avatar">G</div><div class="table-user-name">Gita Devi</div></div></td>
                                <td>87</td>
                                <td class="font-semibold">Rs 750,000</td>
                                <td>Rs 8,621</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/app.js"></script>
    <script>
        function toggleDropdown(id) { document.getElementById(id).classList.toggle('open'); }
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.dropdown').forEach(d => { if (!d.contains(e.target)) d.classList.remove('open'); });
        });
    </script>
</body>
</html>
