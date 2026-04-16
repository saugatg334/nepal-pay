<!-- Sidebar Component -->
<?php 
$currentPage = $currentPage ?? basename($_SERVER['REQUEST_URI'], '.php');
$isAdmin = $_SESSION['is_admin'] ?? 0;
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="../../assets/logo.svg" alt="NepalPay" class="w-10 h-10">
        <div>
            <div class="sidebar-brand-text">NepalPay</div>
            <div class="sidebar-brand-subtitle"><?php echo $isAdmin ? 'Admin Panel' : 'Digital Wallet'; ?></div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($isAdmin): ?>
            <!-- Admin Navigation -->
            <a href="/admin/dashboard" class="<?php echo $currentPage === 'admin/dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large icon"></i> Dashboard
            </a>
            
            <div class="sidebar-section-title">User Management</div>
            <a href="/admin/users"><i class="fas fa-users icon"></i> Users</a>
            <a href="/admin/kyc-verification"><i class="fas fa-id-card icon"></i> KYC Verification</a>
            
            <div class="sidebar-section-title">Financial</div>
            <a href="/admin/transactions"><i class="fas fa-exchange-alt icon"></i> Transactions</a>
            <a href="/admin/wallet-management"><i class="fas fa-wallet icon"></i> Wallet Management</a>
            <a href="/admin/deposits"><i class="fas fa-arrow-down icon"></i> Deposits</a>
            <a href="/admin/withdrawals"><i class="fas fa-arrow-up icon"></i> Withdrawals</a>
            
            <div class="sidebar-section-title">System</div>
            <a href="/admin/reports"><i class="fas fa-chart-bar icon"></i> Reports</a>
            <a href="/admin/settings"><i class="fas fa-cog icon"></i> Settings</a>
            
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="/admin/profile"><i class="fas fa-user-circle icon"></i> Profile</a>
                <a href="/logout"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
            </div>
            
        <?php else: ?>
            <!-- User Navigation -->
            <a href="/dashboard" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-th-large icon"></i> Dashboard
            </a>
            
            <div class="sidebar-section-title">Wallet</div>
            <a href="/wallet"><i class="fas fa-wallet icon"></i> Wallet</a>
            <a href="/send-money"><i class="fas fa-paper-plane icon"></i> Send Money</a>
            <a href="/request-money"><i class="fas fa-hand-holding-usd icon"></i> Request Money</a>
            <a href="/topup"><i class="fas fa-mobile-alt icon"></i> Mobile Topup</a>
            <a href="/pay-bills"><i class="fas fa-file-invoice-dollar icon"></i> Pay Bills</a>
            
            <div class="sidebar-section-title">History</div>
            <a href="/transactions"><i class="fas fa-history icon"></i> Transactions</a>
            
            <div class="sidebar-section-title">Account</div>
            <a href="/kyc"><i class="fas fa-id-card icon"></i> KYC</a>
            <a href="/notifications"><i class="fas fa-bell icon"></i> Notifications</a>
            <a href="/profile"><i class="fas fa-user-circle icon"></i> Profile</a>
            
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                <a href="/set-pin"><i class="fas fa-lock icon"></i> Set PIN</a>
                <a href="/logout"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
            </div>
        <?php endif; ?>
    </nav>
</aside>
