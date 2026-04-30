<?php
/**
 * Main Application Layout
 * This wraps all views with common header, sidebar, and footer
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'NepalPay - Digital Wallet' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="/wallet/public/assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
        }

        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            background-attachment: fixed;
        }

        .container-fluid {
            background: white;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            font-size: 24px;
            font-weight: 700;
            gap: 10px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 15px 0;
        }

        .sidebar-menu a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .sidebar-user {
            position: absolute;
            bottom: 30px;
            left: 20px;
            right: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .sidebar-user-details small {
            display: block;
            opacity: 0.9;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            background: var(--light-bg);
            overflow-y: auto;
        }

        .navbar-top {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 20px;
            font-weight: 600;
            border-radius: 12px 12px 0 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #63378b 100%);
            border: none;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 20px;
                display: none;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .navbar-top {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-wallet"></i>
                NepalPay
            </div>

            <ul class="sidebar-menu">
                <li><a href="/wallet/public/?page=dashboard" class="<?php echo ($currentPage ?? 'dashboard') === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="/wallet/public/?page=send" class="<?php echo ($currentPage ?? '') === 'send' ? 'active' : '' ?>"><i class="fas fa-paper-plane"></i> Send Money</a></li>
                <li><a href="/wallet/public/?page=add" class="<?php echo ($currentPage ?? '') === 'add' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i> Add Money</a></li>
                <li><a href="/wallet/public/?page=withdraw" class="<?php echo ($currentPage ?? '') === 'withdraw' ? 'active' : '' ?>"><i class="fas fa-arrow-down"></i> Withdraw</a></li>
                <li><a href="/wallet/public/?page=bill" class="<?php echo ($currentPage ?? '') === 'bill' ? 'active' : '' ?>"><i class="fas fa-file-invoice-dollar"></i> Bill Payment</a></li>
                <li><a href="/wallet/public/?page=transactions" class="<?php echo ($currentPage ?? '') === 'transactions' ? 'active' : '' ?>"><i class="fas fa-history"></i> Transactions</a></li>
                <li><a href="/wallet/public/?page=profile" class="<?php echo ($currentPage ?? '') === 'profile' ? 'active' : '' ?>"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="/wallet/public/?page=settings" class="<?php echo ($currentPage ?? '') === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>

            <div class="sidebar-user">
                <div class="sidebar-user-info">
                    <div class="sidebar-user-avatar">
                        <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="sidebar-user-details">
                        <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></strong>
                        <small><?php echo htmlspecialchars($_SESSION['user_email'] ?? '') ?></small>
                    </div>
                </div>
                <a href="/wallet/public/?action=logout" class="btn btn-sm btn-outline-light w-100">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Display Flash Messages -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['flash_success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['flash_error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- Page Content -->
            <?php include $viewFile; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JS -->
    <script src="/wallet/public/assets/js/app.js"></script>
</body>
</html>
