<?php
/**
 * NepalPay - Admin Panel Master Layout
 * Consistent UI across all admin pages
 */
require_once dirname(__DIR__, 2) . '/app/helpers/session_helper.php';
require_once dirname(__DIR__, 2) . '/app/models/User.php';

// Security check
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../index.php');
    exit;
}

// Get current admin data
$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$walletBalance = $userModel->getWalletBalance($_SESSION['user_id']);

// Get user initials
$adminInitials = 'A';
if (!empty($currentUser['full_name'])) {
    $nameParts = explode(' ', $currentUser['full_name']);
    $adminInitials = strtoupper(substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $adminInitials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
    }
}

$userName = $currentUser['full_name'] ?? 'Admin';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = ucwords(str_replace('-', ' ', $currentPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NepalPay Admin - <?php echo $pageTitle; ?></title>
    <meta name="description" content="NepalPay Admin Panel">
    
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Admin Sidebar -->
    <aside class="sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <img src="../assets/logo.svg" alt="NepalPay" class="w-10 h-10">
            <div>
                <div class="sidebar-brand-text">NepalPay</div>
                <div class="sidebar-brand-subtitle">Admin Panel</div>
            </div>
        </div>
        
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
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt icon"></i> Logout
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Top Navbar -->
    <header class="admin-topbar">
        <div class="admin-topbar-left">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="admin-topbar-title"><?php echo $pageTitle; ?></h2>
        </div>
        
        <div class="admin-topbar-right">
            <div class="navbar-search">
                <input type="text" placeholder="Search users, transactions..." class="bg-gray-100">
            </div>
            
            <button class="navbar-notification">
                <i class="fas fa-bell text-gray-600"></i>
                <span class="badge">8</span>
            </button>
            
            <div class="dropdown" id="profileDropdown">
                <div class="navbar-profile" onclick="toggleDropdown('profileDropdown')">
                    <div class="avatar"><?php echo htmlspecialchars($adminInitials); ?></div>
                    <div class="navbar-profile-info">
                        <span class="navbar-profile-name"><?php echo htmlspecialchars($userName); ?></span>
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
        <?php 
        // Flash messages
        if (isset($_SESSION['flash'])): 
            foreach ($_SESSION['flash'] as $type => $message): 
        ?>
            <div class="p-4 mb-4 rounded-lg <?php echo $type === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-green-50 border border-green-200 text-green-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php 
            endforeach;
            unset($_SESSION['flash']);
        endif; 
        ?>
        
        <?php echo $content ?? ''; ?>
    </main>
    
    <script src="../assets/js/app.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
        }
        
        function toggleDropdown(id) {
            document.getElementById(id).classList.toggle('open');
        }
        
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('open');
                }
            });
        });
    </script>
</body>
</html>
