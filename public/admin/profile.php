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
} elseif (!empty($currentUser['name'])) {
    $parts = explode(' ', $currentUser['name']);
    $adminInitials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $adminInitials .= strtoupper(substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay Admin - Admin Profile</title>
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
            <a href="settings.php"><i class="fas fa-cog icon"></i> System Settings</a>
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php" class="active"><i class="fas fa-user-circle icon"></i> Admin Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
            </div>
        </nav>
    </aside>
    
    <header class="admin-topbar">
        <div class="admin-topbar-left"><h2 class="admin-topbar-title">Admin Profile</h2></div>
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
                    <h1 class="page-title">Admin Profile</h1>
                    <p class="page-subtitle">Manage your admin account</p>
                </div>
            </div>
            
            <div class="grid-2 gap-6">
                <!-- Profile Card -->
                <div class="card">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 rounded-full bg-primary text-white flex items-center justify-center text-4xl font-bold mx-auto mb-4">
                            <?php echo htmlspecialchars($adminInitials); ?>
                        </div>
                        <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['name'] ?? 'Admin User'); ?></h3>
                        <p class="text-gray-500">Administrator</p>
                        <button class="btn btn-outline mt-4"><i class="fas fa-camera"></i> Change Photo</button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600"><i class="fas fa-envelope mr-2"></i>Email</span>
                            <span class="font-medium"><?php echo htmlspecialchars($currentUser['email'] ?? 'admin@nepalpay.com'); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600"><i class="fas fa-phone mr-2"></i>Phone</span>
                            <span class="font-medium"><?php echo htmlspecialchars($currentUser['phone'] ?? '9841******'); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600"><i class="fas fa-calendar mr-2"></i>Joined</span>
                            <span class="font-medium"><?php echo date('M Y', strtotime($currentUser['created_at'] ?? 'now')); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Form -->
                <div class="card">
                    <h3 class="card-title mb-6">Edit Profile</h3>
                    <form>
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-input" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" disabled>
                            <p class="form-hint">Phone cannot be changed</p>
                        </div>
                        
                        <h4 class="font-semibold mt-6 mb-4">Change Password</h4>
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-input" placeholder="Enter current password">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-input" placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-input" placeholder="Confirm new password">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full mt-4">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
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
