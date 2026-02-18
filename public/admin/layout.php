<?php
// Admin Panel Common Layout
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

// Check if admin is logged in (for now, check user_id)
// In production, you'd have a separate admin session check
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Get admin data (in real app, get from admin_users table)
$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

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

// Define current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay Admin - <?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></title>
    
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
            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large icon"></i> Dashboard
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">User Management</div>
            </div>
            
            <a href="users.php" class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users icon"></i> Users
            </a>
            <a href="kyc-verification.php" class="<?php echo $currentPage === 'kyc-verification' ? 'active' : ''; ?>">
                <i class="fas fa-id-card icon"></i> KYC Verification
                <span class="badge">5</span>
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Financial</div>
            </div>
            
            <a href="transactions.php" class="<?php echo $currentPage === 'transactions' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt icon"></i> Transactions
            </a>
            <a href="wallet-management.php" class="<?php echo $currentPage === 'wallet-management' ? 'active' : ''; ?>">
                <i class="fas fa-wallet icon"></i> Wallet Management
            </a>
            <a href="deposits.php" class="<?php echo $currentPage === 'deposits' ? 'active' : ''; ?>">
                <i class="fas fa-arrow-down icon"></i> Deposits
                <span class="badge">3</span>
            </a>
            <a href="withdrawals.php" class="<?php echo $currentPage === 'withdrawals' ? 'active' : ''; ?>">
                <i class="fas fa-arrow-up icon"></i> Withdrawals
                <span class="badge">2</span>
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">System</div>
            </div>
            
            <a href="reports.php" class="<?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar icon"></i> Reports & Analytics
            </a>
            <a href="settings.php" class="<?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog icon"></i> System Settings
            </a>
            
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php" class="<?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle icon"></i> Admin Profile
                </a>
                <a href="../logout.php" class="">
                    <i class="fas fa-sign-out-alt icon"></i> Logout
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Admin Topbar -->
    <header class="admin-topbar">
        <div class="admin-topbar-left">
            <h2 class="admin-topbar-title"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h2>
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
        <?php echo $content; ?>
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
