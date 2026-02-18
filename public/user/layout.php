<?php
// User Panel Common Layout
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Get user data
$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

// Get user initials for avatar
$userInitials = '';
if (!empty($currentUser['name'])) {
    $nameParts = explode(' ', $currentUser['name']);
    $userInitials = strtoupper(substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $userInitials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
    }
} else {
    $userInitials = 'U';
}

// Get wallet balance
$walletBalance = $userModel->getWalletBalance($_SESSION['user_id']);

// Define current page for active navigation
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay - <?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></title>
    
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
    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <!-- Logo -->
        <a href="dashboard.php" class="navbar-brand">
            <img src="../assets/logo.svg" alt="Nepal Pay" class="w-10 h-10">
            <span>Nepal Pay</span>
        </a>
        
        <!-- Main Navigation -->
        <div class="navbar-nav">
            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large mr-2"></i> Dashboard
            </a>
            <a href="send-money.php" class="<?php echo $currentPage === 'send-money' ? 'active' : ''; ?>">
                <i class="fas fa-paper-plane mr-2"></i> Send Money
            </a>
            <a href="request-money.php" class="<?php echo $currentPage === 'request-money' ? 'active' : ''; ?>">
                <i class="fas fa-hand-holding-usd mr-2"></i> Request Money
            </a>
            <a href="transactions.php" class="<?php echo $currentPage === 'transactions' ? 'active' : ''; ?>">
                <i class="fas fa-history mr-2"></i> Transactions
            </a>
            <a href="wallet.php" class="<?php echo $currentPage === 'wallet' ? 'active' : ''; ?>">
                <i class="fas fa-wallet mr-2"></i> Wallet
            </a>
            <a href="kyc.php" class="<?php echo $currentPage === 'kyc' ? 'active' : ''; ?>">
                <i class="fas fa-id-card mr-2"></i> KYC
            </a>
            <a href="notifications.php" class="<?php echo $currentPage === 'notifications' ? 'active' : ''; ?>">
                <i class="fas fa-bell mr-2"></i> Notifications
            </a>
        </div>
        
        <!-- Right Side Actions -->
        <div class="navbar-actions">
            <!-- Search -->
            <div class="navbar-search">
                <input type="text" placeholder="Search..." class="bg-gray-100">
            </div>
            
            <!-- Notifications -->
            <button class="navbar-notification">
                <i class="fas fa-bell text-gray-600"></i>
                <span class="badge">3</span>
            </button>
            
            <!-- Profile Dropdown -->
            <div class="dropdown" id="profileDropdown">
                <div class="navbar-profile" onclick="toggleDropdown('profileDropdown')">
                    <div class="avatar"><?php echo htmlspecialchars($userInitials); ?></div>
                    <div class="navbar-profile-info">
                        <span class="navbar-profile-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></span>
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
    </nav>
    
    <!-- Main Content -->
    <main class="page-content">
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
