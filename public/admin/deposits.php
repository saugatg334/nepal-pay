<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userModel = new User();
$currentUser = $userModel->getUserById($_SESSION['user_id']);

// Handle deposit approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $depositId = $_POST['deposit_id'] ?? 0;
    $action = $_POST['action'];
    // In real app, process the deposit
    $message = $action === 'approve' ? 'Deposit approved successfully' : 'Deposit rejected';
}

$deposits = [
    ['id' => 1, 'user' => 'Ram Shrestha', 'phone' => '9841000001', 'amount' => 10000, 'method' => 'Bank Transfer', 'bank' => 'NMB Bank', 'status' => 'pending', 'date' => date('Y-m-d H:i:s', strtotime('-30 min'))],
    ['id' => 2, 'user' => 'Sita Pandey', 'phone' => '9841000002', 'amount' => 5000, 'method' => 'eSewa', 'bank' => 'eSewa', 'status' => 'pending', 'date' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
    ['id' => 3, 'user' => 'Hari Kumar', 'phone' => '9841000003', 'amount' => 15000, 'method' => 'Bank Transfer', 'bank' => 'NIC Asia', 'status' => 'pending', 'date' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ['id' => 4, 'user' => 'Gita Devi', 'phone' => '9841000004', 'amount' => 2000, 'method' => 'Khalti', 'bank' => 'Khalti', 'status' => 'completed', 'date' => date('Y-m-d H:i:s', strtotime('-1 day'))],
    ['id' => 5, 'user' => 'Mohan Rai', 'phone' => '9841000005', 'amount' => 8000, 'method' => 'Bank Transfer', 'bank' => 'Standard Chartered', 'status' => 'completed', 'date' => date('Y-m-d H:i:s', strtotime('-2 days'))],
];

$adminInitials = 'A';
if (!empty($currentUser['full_name'])) {
    $parts = explode(' ', $currentUser['full_name']);
    $adminInitials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $adminInitials .= strtoupper(substr(end($parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay Admin - Deposits</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/design-system.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
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
            <a href="transactions.php"><i class="fas fa-exchange-alt icon"></i> Transactions</a>
            <a href="wallet-management.php"><i class="fas fa-wallet icon"></i> Wallet Management</a>
            <a href="deposits.php" class="active"><i class="fas fa-arrow-down icon"></i> Deposits</a>
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
    
    <header class="admin-topbar">
        <div class="admin-topbar-left"><h2 class="admin-topbar-title">Deposits</h2></div>
        <div class="admin-topbar-right">
            <div class="navbar-search"><input type="text" placeholder="Search..." class="bg-gray-100"></div>
            <button class="navbar-notification"><i class="fas fa-bell text-gray-600"></i><span class="badge">3</span></button>
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
    
    <main class="admin-content">
        <div class="max-w-7xl mx-auto">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Deposits</h1>
                    <p class="page-subtitle">Manage deposit requests</p>
                </div>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="p-4 mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="grid-3 mb-6">
                <div class="stats-card">
                    <div class="stats-card-icon warning"><i class="fas fa-clock"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Pending</div>
                        <div class="stats-card-value"><?php echo count(array_filter($deposits, fn($d) => $d['status'] === 'pending')); ?></div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon success"><i class="fas fa-check"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Completed Today</div>
                        <div class="stats-card-value"><?php echo count(array_filter($deposits, fn($d) => $d['status'] === 'completed')); ?></div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon success"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Total Deposited</div>
                        <div class="stats-card-value">Rs <?php echo number_format(array_sum(array_column($deposits, 'amount'))); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Deposits Table -->
            <div class="card">
                <h3 class="card-title mb-4">Deposit Requests</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Bank</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deposits as $deposit): ?>
                            <tr>
                                <td>
                                    <div class="table-user">
                                        <div class="avatar"><?php echo strtoupper(substr($deposit['user'], 0, 1)); ?></div>
                                        <div>
                                            <div class="table-user-name"><?php echo htmlspecialchars($deposit['user']); ?></div>
                                            <div class="table-user-phone"><?php echo htmlspecialchars($deposit['phone']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-semibold text-lg">Rs <?php echo number_format($deposit['amount']); ?></td>
                                <td><?php echo htmlspecialchars($deposit['method']); ?></td>
                                <td><?php echo htmlspecialchars($deposit['bank']); ?></td>
                                <td>
                                    <span class="table-badge <?php echo $deposit['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($deposit['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, h:i A', strtotime($deposit['date'])); ?></td>
                                <td>
                                    <?php if ($deposit['status'] === 'pending'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="deposit_id" value="<?php echo $deposit['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this deposit?')">Reject</button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-outline" disabled>Processed</button>
                                    <?php endif; ?>
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
