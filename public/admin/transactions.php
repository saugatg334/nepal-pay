<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

// Check admin login
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

// Filter handling
$filter = $_GET['filter'] ?? 'all';
$type = $_GET['type'] ?? 'all';

// Sample transactions data
$transactions = [
    ['id' => 'TXN001', 'sender' => 'Ram Shrestha', 'sender_phone' => '9841000001', 'receiver' => 'Sita Pandey', 'receiver_phone' => '9841000002',
     'type' => 'transfer', 'amount' => 5000, 'fee' => 10, 'status' => 'completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 min'))],
    ['id' => 'TXN002', 'sender' => 'Hari Kumar', 'sender_phone' => '9841000003', 'receiver' => 'System', 'receiver_phone' => '',
     'type' => 'deposit', 'amount' => 10000, 'fee' => 0, 'status' => 'completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-15 min'))],
    ['id' => 'TXN003', 'sender' => 'Gita Devi', 'sender_phone' => '9841000004', 'receiver' => 'System', 'receiver_phone' => '',
     'type' => 'withdrawal', 'amount' => 3000, 'fee' => 15, 'status' => 'pending', 'created_at' => date('Y-m-d H:i:s', strtotime('-30 min'))],
    ['id' => 'TXN004', 'sender' => 'Mohan Rai', 'sender_phone' => '9841000005', 'receiver' => 'NEA', 'receiver_phone' => '',
     'type' => 'bill_payment', 'amount' => 1500, 'fee' => 5, 'status' => 'completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['id' => 'TXN005', 'sender' => 'Laxmi Basnet', 'sender_phone' => '9841000006', 'receiver' => 'Bibek Tamang', 'receiver_phone' => '9841000007',
     'type' => 'transfer', 'amount' => 7500, 'fee' => 15, 'status' => 'completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['id' => 'TXN006', 'sender' => 'Anita Gurung', 'sender_phone' => '9841000008', 'receiver' => 'System', 'receiver_phone' => '',
     'type' => 'deposit', 'amount' => 20000, 'fee' => 0, 'status' => 'completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-3 hours'))],
    ['id' => 'TXN007', 'sender' => 'Niraj Shrestha', 'sender_phone' => '9841000009', 'receiver' => 'NT Mobile', 'receiver_phone' => '',
     'type' => 'recharge', 'amount' => 500, 'fee' => 0, 'status' => 'completed', 'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
    ['id' => 'TXN008', 'sender' => 'Punam Acharya', 'sender_phone' => '9841000010', 'receiver' => 'System', 'receiver_phone' => '',
     'type' => 'withdrawal', 'amount' => 8000, 'fee' => 20, 'status' => 'failed', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
];

// Filter by type
if ($type !== 'all') {
    $transactions = array_filter($transactions, fn($t) => $t['type'] === $type);
}
$transactions = array_values($transactions);

// Filter by status
if ($filter !== 'all') {
    $transactions = array_filter($transactions, fn($t) => $t['status'] === $filter);
}
$transactions = array_values($transactions);

// Admin initials
$adminInitials = 'A';
if (!empty($currentUser['full_name'])) {
    $parts = explode(' ', $currentUser['full_name']);
    $adminInitials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $adminInitials .= strtoupper(substr(end($parts), 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay Admin - Transactions</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Admin Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="../assets/logo.svg" alt="Nepal Pay" class="w-10 h-10">
            <div><div class="sidebar-brand-text">Nepal Pay</div><div class="sidebar-brand-subtitle">Admin Panel</div></div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-th-large icon"></i> Dashboard</a>
            <div class="sidebar-section"><div class="sidebar-section-title">User Management</div></div>
            <a href="users.php"><i class="fas fa-users icon"></i> Users</a>
            <a href="kyc-verification.php"><i class="fas fa-id-card icon"></i> KYC Verification</a>
            <div class="sidebar-section"><div class="sidebar-section-title">Financial</div></div>
            <a href="transactions.php" class="active"><i class="fas fa-exchange-alt icon"></i> Transactions</a>
            <a href="wallet-management.php"><i class="fas fa-wallet icon"></i> Wallet Management</a>
            <a href="deposits.php"><i class="fas fa-arrow-down icon"></i> Deposits</a>
            <a href="withdrawals.php"><i class="fas fa-arrow-up icon"></i> Withdrawals</a>
            <div class="sidebar-section"><div class="sidebar-section-title">System</div></div>
            <a href="reports.php"><i class="fas fa-chart-bar icon"></i> Reports & Analytics</a>
            <a href="settings.php"><i class="fas fa-cog icon"></i> System Settings</a>
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php"><i class="fas fa-user-circle icon"></i> Admin Profile</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt icon"></i> Logout</a>
            </div>
        </nav>
    </aside>
    
    <!-- Admin Topbar -->
    <header class="admin-topbar">
        <div class="admin-topbar-left"><h2 class="admin-topbar-title">Transactions</h2></div>
        <div class="admin-topbar-right">
            <div class="navbar-search">
                <input type="text" placeholder="Search transactions..." class="bg-gray-100">
            </div>
            <button class="navbar-notification"><i class="fas fa-bell text-gray-600"></i><span class="badge">8</span></button>
            <div class="dropdown" id="profileDropdown">
                <div class="navbar-profile" onclick="toggleDropdown('profileDropdown')">
                    <div class="avatar"><?php echo htmlspecialchars($adminInitials); ?></div>
                    <i class="fas fa-chevron-down ml-2 text-gray-400"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
                    <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="admin-content">
        <div class="max-w-7xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Transactions Monitoring</h1>
                    <p class="page-subtitle">View and manage all transactions</p>
                </div>
                <button class="btn btn-primary"><i class="fas fa-download"></i> Export</button>
            </div>
            
            <!-- Stats -->
            <div class="grid-4 mb-6">
                <div class="stats-card">
                    <div class="stats-card-icon primary"><i class="fas fa-exchange-alt"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Total Transactions</div>
                        <div class="stats-card-value">8,500</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon success"><i class="fas fa-arrow-down"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Deposits Today</div>
                        <div class="stats-card-value">Rs 30,000</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon warning"><i class="fas fa-arrow-up"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Withdrawals Today</div>
                        <div class="stats-card-value">Rs 11,000</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon danger"><i class="fas fa-times"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Failed Transactions</div>
                        <div class="stats-card-value">12</div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="flex gap-4 mb-6">
                <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
                <a href="?filter=completed" class="btn <?php echo $filter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">Completed</a>
                <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                <a href="?filter=failed" class="btn <?php echo $filter === 'failed' ? 'btn-primary' : 'btn-secondary'; ?>">Failed</a>
                <span class="border-l mx-2"></span>
                <a href="?type=all" class="btn <?php echo $type === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All Types</a>
                <a href="?type=transfer" class="btn <?php echo $type === 'transfer' ? 'btn-primary' : 'btn-secondary'; ?>">Transfer</a>
                <a href="?type=deposit" class="btn <?php echo $type === 'deposit' ? 'btn-primary' : 'btn-secondary'; ?>">Deposit</a>
                <a href="?type=withdrawal" class="btn <?php echo $type === 'withdrawal' ? 'btn-primary' : 'btn-secondary'; ?>">Withdrawal</a>
                <a href="?type=bill_payment" class="btn <?php echo $type === 'bill_payment' ? 'btn-primary' : 'btn-secondary'; ?>">Bill</a>
            </div>
            
            <!-- Transactions Table -->
            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Sender</th>
                                <th>Receiver</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td class="font-mono text-sm"><?php echo htmlspecialchars($tx['id']); ?></td>
                                <td>
                                    <div class="text-sm font-medium"><?php echo htmlspecialchars($tx['sender']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($tx['sender_phone']); ?></div>
                                </td>
                                <td>
                                    <div class="text-sm font-medium"><?php echo htmlspecialchars($tx['receiver']); ?></div>
                                    <?php if ($tx['receiver_phone']): ?>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($tx['receiver_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $typeIcons = [
                                        'transfer' => 'fa-paper-plane',
                                        'deposit' => 'fa-arrow-down',
                                        'withdrawal' => 'fa-arrow-up',
                                        'bill_payment' => 'fa-file-invoice-dollar',
                                        'recharge' => 'fa-mobile-alt'
                                    ];
                                    $typeColors = [
                                        'transfer' => 'primary',
                                        'deposit' => 'success',
                                        'withdrawal' => 'warning',
                                        'bill_payment' => 'info',
                                        'recharge' => 'primary'
                                    ];
                                    ?>
                                    <span class="table-badge <?php echo $typeColors[$tx['type']] ?? 'primary'; ?>">
                                        <i class="fas <?php echo $typeIcons[$tx['type']] ?? 'fa-exchange'; ?> mr-1"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $tx['type'])); ?>
                                    </span>
                                </td>
                                <td class="font-semibold">Rs <?php echo number_format($tx['amount']); ?></td>
                                <td>Rs <?php echo number_format($tx['fee']); ?></td>
                                <td>
                                    <span class="table-badge <?php 
                                        echo match($tx['status']) {
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            default => 'primary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($tx['status']); ?>
                                    </span>
                                </td>
                                <td class="text-sm"><?php echo date('M d, h:i A', strtotime($tx['created_at'])); ?></td>
                                <td>
                                    <a href="transaction-details.php?id=<?php echo $tx['id']; ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/app.js"></script>
    <script>
        function toggleDropdown(id) { document.getElementById(id).classList.toggle('open'); }
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.dropdown').forEach(d => { if (!d.contains(e.target)) d.classList.remove('open'); });
        });
    </script>
</body>
</html>
