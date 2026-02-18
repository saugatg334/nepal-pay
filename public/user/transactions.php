<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controller/WalletController.php';

$user_id = $_SESSION['user_id'] ?? 1;
$error_msg = null;

// Get user data
$userModel = new User();
$currentUser = $userModel->getUserById($user_id);
$balance = $userModel->getWalletBalance($user_id);

// Get transactions
try {
    $walletCtrl = new WalletController();
    $transactions = $walletCtrl->getTransactionHistory($user_id, 50);
} catch (Exception $e) {
    $transactions = [];
    $error_msg = $e->getMessage();
}

// Filter handling
$filter = $_GET['filter'] ?? 'all';
$filteredTransactions = $transactions;

if ($filter === 'send') {
    $filteredTransactions = array_filter($transactions, function($tx) {
        return $tx['amount'] < 0;
    });
} elseif ($filter === 'receive') {
    $filteredTransactions = array_filter($transactions, function($tx) {
        return $tx['amount'] > 0;
    });
} elseif ($filter === 'bills') {
    $filteredTransactions = array_filter($transactions, function($tx) {
        return in_array($tx['type'], ['bill', 'government_payment', 'recharge']);
    });
}

// Get user initials for avatar
$userInitials = 'U';
if (!empty($currentUser['name'])) {
    $nameParts = explode(' ', $currentUser['name']);
    $userInitials = strtoupper(substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $userInitials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
    }
}

$kycStatus = $currentUser['kyc_status'] ?? 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay - Transactions</title>
    
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
            <a href="dashboard.php">
                <i class="fas fa-th-large mr-2"></i> Dashboard
            </a>
            <a href="send-money.php">
                <i class="fas fa-paper-plane mr-2"></i> Send Money
            </a>
            <a href="request-money.php">
                <i class="fas fa-hand-holding-usd mr-2"></i> Request Money
            </a>
            <a href="transactions.php" class="active">
                <i class="fas fa-history mr-2"></i> Transactions
            </a>
            <a href="wallet.php">
                <i class="fas fa-wallet mr-2"></i> Wallet
            </a>
            <a href="kyc.php">
                <i class="fas fa-id-card mr-2"></i> KYC
            </a>
            <a href="notifications.php">
                <i class="fas fa-bell mr-2"></i> Notifications
            </a>
        </div>
        
        <!-- Right Side Actions -->
        <div class="navbar-actions">
            <!-- Search -->
            <div class="navbar-search">
                <input type="text" placeholder="Search transactions..." class="bg-gray-100">
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
    </nav>
    
    <!-- Main Content -->
    <main class="page-content">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Transaction History</h1>
                    <p class="page-subtitle">View all your past transactions</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="flex gap-4 mb-6">
                <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                    All
                </a>
                <a href="?filter=send" class="btn <?php echo $filter === 'send' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-paper-plane mr-2"></i> Sent
                </a>
                <a href="?filter=receive" class="btn <?php echo $filter === 'receive' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-arrow-down mr-2"></i> Received
                </a>
                <a href="?filter=bills" class="btn <?php echo $filter === 'bills' ? 'btn-primary' : 'btn-secondary'; ?>">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> Bills
                </a>
            </div>
            
            <?php if ($error_msg): ?>
                <div class="p-4 mb-4 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <!-- Transactions List -->
            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filteredTransactions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-3"></i>
                                        <p>No transactions found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filteredTransactions as $tx): ?>
                                    <tr>
                                        <td>
                                            <div class="text-sm"><?php echo date('M d, Y', strtotime($tx['created_at'])); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($tx['created_at'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="font-medium"><?php echo htmlspecialchars($tx['description'] ?: ucfirst($tx['type'])); ?></div>
                                            <?php if (!empty($tx['txn_id'])): ?>
                                                <div class="text-xs text-gray-500">Txn: <?php echo htmlspecialchars(substr($tx['txn_id'], 0, 12)); ?>...</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="table-badge <?php 
                                                echo match($tx['type']) {
                                                    'transfer' => 'primary',
                                                    'deposit' => 'success',
                                                    'withdrawal' => 'warning',
                                                    'bill', 'government_payment' => 'info',
                                                    default => 'primary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($tx['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="table-badge success">
                                                <i class="fas fa-check-circle mr-1"></i> Completed
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-semibold <?php echo $tx['amount'] < 0 ? 'text-red-500' : 'text-green-600'; ?>">
                                                <?php echo $tx['amount'] < 0 ? '-' : '+'; ?>Rs <?php echo number_format(abs($tx['amount']), 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../receipt.php?txn=<?php echo urlencode($tx['txn_id'] ?? ''); ?>" class="btn btn-sm btn-outline">
                                                <i class="fas fa-receipt"></i> Receipt
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
