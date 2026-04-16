<?php
/**
 * User Panel Navbar Component
 * Consistent top navigation across all user pages
 */

$userInitials = $userInitials ?? 'U';
$userName = $userName ?? 'User';
$balance = $balance ?? 0;
$currentPage = $currentPage ?? getPageTitle();
?>

<header class="user-topbar">
    <div class="user-topbar-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="user-topbar-title"><?php echo $currentPage; ?></h2>
    </div>
    
    <div class="user-topbar-right">
        <!-- Search -->
        <div class="navbar-search">
            <input type="text" placeholder="Search..." class="bg-gray-100">
        </div>
        
        <!-- Notifications -->
        <button class="navbar-notification" onclick="toggleNotifications()">
            <i class="fas fa-bell text-gray-600"></i>
            <span class="badge" id="notificationBadge">0</span>
        </button>
        
        <!-- Profile Dropdown -->
        <div class="dropdown" id="profileDropdown">
            <div class="navbar-profile" onclick="toggleDropdown('profileDropdown')">
                <div class="avatar"><?php echo htmlspecialchars($userInitials); ?></div>
                <div class="navbar-profile-info">
                    <span class="navbar-profile-name"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="navbar-profile-role">Rs <?php echo number_format($balance, 2); ?></span>
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
