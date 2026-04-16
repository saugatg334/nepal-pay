<?php
/**
 * NepalPay - User Panel Master Layout
 * Consistent UI across all user pages
 */
require_once dirname(__DIR__, 2) . '/app/helpers/session_helper.php';
require_once dirname(__DIR__, 2) . '/app/models/User.php';

// Security check
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Get current user data
$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$walletBalance = $userModel->getWalletBalance($_SESSION['user_id']);

// Get user initials
$userInitials = 'U';
if (!empty($currentUser['full_name'])) {
    $nameParts = explode(' ', $currentUser['full_name']);
    $userInitials = strtoupper(substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $userInitials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
    }
}

$userName = $currentUser['full_name'] ?? 'User';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = ucwords(str_replace('-', ' ', $currentPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NepalPay - <?php echo $pageTitle; ?></title>
    <meta name="description" content="NepalPay - Secure Digital Wallet">
    
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
    <!-- User Sidebar -->
    <aside class="user-sidebar" id="userSidebar">
        <div class="sidebar-brand">
            <img src="../assets/logo.svg" alt="NepalPay" class="w-10 h-10">
            <div>
                <div class="sidebar-brand-text">NepalPay</div>
                <div class="sidebar-brand-subtitle">Digital Wallet</div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large icon"></i> Dashboard
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Wallet</div>
            </div>
            
            <a href="send-money.php" class="<?php echo $currentPage === 'send-money' ? 'active' : ''; ?>">
                <i class="fas fa-paper-plane icon"></i> Send Money
            </a>
            <a href="request-money.php" class="<?php echo $currentPage === 'request-money' ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-usd icon"></i> Request Money
            </a>
            <a href="topup.php" class="<?php echo $currentPage === 'topup' ? 'active' : ''; ?>">
                <i class="fas fa-mobile-alt icon"></i> Mobile Topup
            </a>
            <a href="pay-bills.php" class="<?php echo $currentPage === 'pay-bills' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar icon"></i> Pay Bills
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">History</div>
            </div>
            
            <a href="transactions.php" class="<?php echo $currentPage === 'transactions' ? 'active' : ''; ?>">
                <i class="fas fa-history icon"></i> Transactions
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Account</div>
            </div>
            
            <a href="wallet.php" class="<?php echo $currentPage === 'wallet' ? 'active' : ''; ?>">
                <i class="fas fa-wallet icon"></i> Wallet
            </a>
            <a href="kyc.php" class="<?php echo $currentPage === 'kyc' ? 'active' : ''; ?>">
                <i class="fas fa-id-card icon"></i> KYC
            </a>
            <a href="notifications.php" class="<?php echo $currentPage === 'notifications' ? 'active' : ''; ?>">
                <i class="fas fa-bell icon"></i> Notifications
            </a>
            
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php" class="<?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle icon"></i> My Profile
                </a>
                <a href="change-password.php" class="<?php echo $currentPage === 'change-password' ? 'active' : ''; ?>">
                    <i class="fas fa-lock icon"></i> Change Password
                </a>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt icon"></i> Logout
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Top Navbar -->
    <header class="user-topbar">
        <div class="user-topbar-left">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="user-topbar-title"><?php echo $pageTitle; ?></h2>
        </div>
        
        <div class="user-topbar-right">
            <div class="navbar-search">
                <input type="text" placeholder="Search..." class="bg-gray-100">
            </div>
            
            <button class="navbar-notification">
                <i class="fas fa-bell text-gray-600"></i>
                <span class="badge">0</span>
            </button>
            
            <div class="dropdown" id="profileDropdown">
                <div class="navbar-profile" onclick="toggleDropdown('profileDropdown')">
                    <div class="avatar"><?php echo htmlspecialchars($userInitials); ?></div>
                    <div class="navbar-profile-info">
                        <span class="navbar-profile-name"><?php echo htmlspecialchars($userName); ?></span>
                        <span class="navbar-profile-role">Rs <?php echo number_format($walletBalance, 2); ?></span>
                    </div>
                    <i class="fas fa-chevron-down ml-2 text-gray-400"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="wallet.php" class="dropdown-item">
                        <i class="fas fa-wallet"></i> Wallet
                    </a>
                    <a href="change-password.php" class="dropdown-item">
                        <i class="fas fa-lock"></i> Change Password
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
    <main class="user-content">
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
            document.getElementById('userSidebar').classList.toggle('open');
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
