<!DOCTYPE html>
<html lang="<?php echo getLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? lang('app_name'); ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- QR Code -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    
    <!-- NepalPay Modern CSS -->
    <link href="<?php echo APP_URL; ?>/public/assets/css/nepalpay-modern.css" rel="stylesheet">

</head>
<body>
<?php if (Session::has('user_id')): ?>
<div class="np-app">
    <!-- SIDEBAR -->
    <aside class="np-sidebar" id="npSidebar">
        <div class="np-sidebar-header">
            <i class="fas fa-wallet" style="font-size: 24px; color: var(--primary);"></i>
            <span class="np-sidebar-logo"><?php echo lang('app_name'); ?></span>
        </div>

        <ul class="np-sidebar-menu">
            <li>
                <a href="<?php echo APP_URL; ?>/index.php?page=dashboard" class="<?php echo ($_GET['page'] ?? 'dashboard') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> <?php echo lang('dashboard'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo APP_URL; ?>/index.php?page=send_money" class="<?php echo ($_GET['page'] ?? '') === 'send_money' ? 'active' : ''; ?>">
                    <i class="fas fa-paper-plane"></i> <?php echo lang('send_money'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo APP_URL; ?>/index.php?page=add_money" class="<?php echo ($_GET['page'] ?? '') === 'add_money' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> <?php echo lang('add_money'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo APP_URL; ?>/index.php?page=withdraw" class="<?php echo ($_GET['page'] ?? '') === 'withdraw' ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-down"></i> Withdraw
                </a>
            </li>
            <li>
                <a href="<?php echo APP_URL; ?>/index.php?page=bills" class="<?php echo ($_GET['page'] ?? '') === 'bills' || ($_GET['page'] ?? '') === 'pay_bill' ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice-dollar"></i> <?php echo lang('pay_bills'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo APP_URL; ?>/index.php?page=transactions" class="<?php echo ($_GET['page'] ?? '') === 'transactions' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> <?php echo lang('transactions'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo APP_URL; ?>/index.php?page=profile" class="<?php echo ($_GET['page'] ?? '') === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> <?php echo lang('profile'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo APP_URL; ?>/index.php?page=settings" class="<?php echo ($_GET['page'] ?? '') === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>

        <div class="np-sidebar-footer">
            <div class="np-sidebar-user">
                <div class="np-sidebar-avatar">
                    <?php echo strtoupper(substr(Session::get('user_name') ?? 'U', 0, 1)); ?>
                </div>
                <div class="np-sidebar-user-info">
                    <p><?php echo escape(Session::get('user_name') ?? 'User'); ?></p>
                    <span><?php echo escape(Session::get('user_email') ?? ''); ?></span>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/index.php?page=logout" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> <?php echo lang('logout'); ?>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="np-main">
        <!-- TOP NAVBAR -->
        <nav class="np-topnav">
            <div class="np-topnav-left">
                <button class="np-topnav-icon" onclick="toggleSidebar()" id="sidebarToggle" style="display: none;">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="np-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search transactions...">
                </div>
            </div>
            <div class="np-topnav-right">
                <div class="np-topnav-icon" onclick="showToast('No new notifications')">
                    <i class="fas fa-bell"></i>
                    <div class="np-notification-badge">3</div>
                </div>
                <div class="np-topnav-icon" onclick="toggleProfileMenu()">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </nav>

        <!-- CONTENT AREA -->
        <div class="np-content">
            <!-- Flash Messages -->
            <?php if (hasFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px; border: none; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo escape(getFlash('error')); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (hasFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px; border: none; margin-bottom: 20px;">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo escape(getFlash('success')); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php echo $content; ?>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="npToast" class="np-toast"></div>

<script>
function toggleSidebar() {
    document.getElementById('npSidebar').classList.toggle('show');
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('npToast');
    toast.textContent = message;
    toast.className = 'np-toast show';
    
    if (type === 'success') toast.classList.add('np-toast-success');
    else if (type === 'error') toast.classList.add('np-toast-error');
    else if (type === 'warning') toast.classList.add('np-toast-warning');
    
    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.remove('np-toast-success', 'np-toast-error', 'np-toast-warning');
    }, 3000);
}

function toggleProfileMenu() {
    showToast('Profile menu coming soon!');
}

// Show/hide sidebar toggle on mobile
window.addEventListener('resize', () => {
    const toggleBtn = document.getElementById('sidebarToggle');
    if (window.innerWidth < 1024) {
        toggleBtn.style.display = 'flex';
    } else {
        toggleBtn.style.display = 'none';
        document.getElementById('npSidebar').classList.remove('show');
    }
});

window.addEventListener('load', () => {
    const toggleBtn = document.getElementById('sidebarToggle');
    if (window.innerWidth < 1024) {
        toggleBtn.style.display = 'flex';
    }
});
</script>

<?php else: ?>
    <!-- Not logged in - show content without sidebar -->
    <?php echo $content; ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="<?php echo APP_URL; ?>/public/assets/js/main.js"></script>

<!-- Chatbot CSS -->
<link rel="stylesheet" href="<?php echo APP_URL; ?>/public/assets/css/chatbot.css">

<!-- Chatbot Widget (only for logged-in users) -->
<?php if (Session::has('user_id')): ?>
<?php require_once __DIR__ . '/../chatbot.php'; ?>
<?php endif; ?>

<!-- Chatbot JS -->
<script src="<?php echo APP_URL; ?>/public/assets/js/chatbot.js" defer></script>
</body>
</html>
