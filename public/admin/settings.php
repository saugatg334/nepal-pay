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
    <title>Nepal Pay Admin - System Settings</title>
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
            <a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports & Analytics</a>
            <a href="settings.php" class="active"><i class="fas fa-cog icon"></i> System Settings</a>
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php"><i class="fas fa-user-circle icon"></i> Admin Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
            </div>
        </nav>
    </aside>
    
    <header class="admin-topbar">
        <div class="admin-topbar-left"><h2 class="admin-topbar-title">System Settings</h2></div>
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
        <div class="max-w-4xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title">System Settings</h1>
                    <p class="page-subtitle">Configure platform settings</p>
                </div>
            </div>
            
            <!-- General Settings -->
            <div class="card mb-6">
                <h3 class="card-title mb-4">General Settings</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div>
                            <div class="font-medium">Maintenance Mode</div>
                            <div class="text-sm text-gray-500">Put the platform in maintenance mode</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div>
                            <div class="font-medium">User Registration</div>
                            <div class="text-sm text-gray-500">Allow new users to register</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-4 border rounded-lg">
                        <div>
                            <div class="font-medium">Email Verification</div>
                            <div class="text-sm text-gray-500">Require email verification for new accounts</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Transaction Limits -->
            <div class="card mb-6">
                <h3 class="card-title mb-4">Transaction Limits</h3>
                <div class="grid-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Minimum Transfer (NPR)</label>
                        <input type="number" class="form-input" value="10">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maximum Transfer (NPR)</label>
                        <input type="number" class="form-input" value="25000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Daily Transfer Limit (NPR)</label>
                        <input type="number" class="form-input" value="50000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Monthly Transfer Limit (NPR)</label>
                        <input type="number" class="form-input" value="200000">
                    </div>
                </div>
            </div>
            
            <!-- Fee Settings -->
            <div class="card mb-6">
                <h3 class="card-title mb-4">Fee Settings</h3>
                <div class="grid-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Transfer Fee (%)</label>
                        <input type="number" class="form-input" value="0" step="0.1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Withdrawal Fee (NPR)</label>
                        <input type="number" class="form-input" value="10">
                    </div>
                </div>
            </div>
            
            <!-- API Keys -->
            <div class="card mb-6">
                <h3 class="card-title mb-4">API Configuration</h3>
                <div class="space-y-4">
                    <div class="form-group">
                        <label class="form-label">SMS API Key</label>
                        <input type="password" class="form-input" value="************************">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Gateway API</label>
                        <input type="password" class="form-input" value="************************">
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="flex justify-end gap-3">
                <button class="btn btn-secondary">Cancel</button>
                <button class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
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
