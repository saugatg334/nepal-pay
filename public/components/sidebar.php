<?php
/**
 * User Panel Sidebar Component
 * Consistent navigation across all user pages
 */

$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$userInitials = $userInitials ?? 'U';
$userName = $userName ?? 'User';
$balance = $balance ?? 0;
?>

<aside class="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="../assets/logo.svg" alt="NepalPay" class="w-10 h-10">
        <div>
            <div class="sidebar-brand-text">NepalPay</div>
            <div class="sidebar-brand-subtitle">Digital Wallet</div>
        </div>
    </div>
    
    <!-- Navigation -->
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
