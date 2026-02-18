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

// Handle KYC approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_POST['user_id'] ?? 0;
    $action = $_POST['action'];
    
    if ($userId > 0) {
        try {
            if ($action === 'approve') {
                $userModel->updateKYCStatus($userId, 'verified');
                $message = 'KYC approved successfully';
            } elseif ($action === 'reject') {
                $userModel->updateKYCStatus($userId, 'rejected');
                $message = 'KYC rejected';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
        }
    }
}

// Sample pending KYC users
$kycUsers = [
    ['id' => 3, 'name' => 'Hari Kumar', 'phone' => '9841000003', 'email' => 'hari@example.com', 
     'doc_type' => 'Citizenship', 'doc_number' => '12-345-678', 'submitted_at' => '2024-01-20'],
    ['id' => 6, 'name' => 'Laxmi Basnet', 'phone' => '9841000006', 'email' => 'laxmi@example.com',
     'doc_type' => 'Passport', 'doc_number' => 'P1234567', 'submitted_at' => '2024-01-19'],
    ['id' => 9, 'name' => 'Niraj Shrestha', 'phone' => '9841000009', 'email' => 'niraj@example.com',
     'doc_type' => 'Driving License', 'doc_number' => 'DL-123456', 'submitted_at' => '2024-01-18'],
    ['id' => 10, 'name' => 'Punam Acharya', 'phone' => '9841000010', 'email' => 'punam@example.com',
     'doc_type' => 'Voter ID', 'doc_number' => 'VI-987654', 'submitted_at' => '2024-01-17'],
    ['id' => 11, 'name' => 'Rajesh Hamal', 'phone' => '9841000011', 'email' => 'rajesh@example.com',
     'doc_type' => 'Citizenship', 'doc_number' => '11-222-333', 'submitted_at' => '2024-01-16'],
];

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
    <title>Nepal Pay Admin - KYC Verification</title>
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
            <div>
                <div class="sidebar-brand-text">Nepal Pay</div>
                <div class="sidebar-brand-subtitle">Admin Panel</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fas fa-th-large icon"></i> Dashboard</a>
            <div class="sidebar-section"><div class="sidebar-section-title">User Management</div></div>
            <a href="users.php"><i class="fas fa-users icon"></i> Users</a>
            <a href="kyc-verification.php" class="active"><i class="fas fa-id-card icon"></i> KYC Verification</a>
            <div class="sidebar-section"><div class="sidebar-section-title">Financial</div></div>
            <a href="transactions.php"><i class="fas fa-exchange-alt icon"></i> Transactions</a>
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
        <div class="admin-topbar-left"><h2 class="admin-topbar-title">KYC Verification</h2></div>
        <div class="admin-topbar-right">
            <div class="navbar-search">
                <input type="text" placeholder="Search..." class="bg-gray-100">
            </div>
            <button class="navbar-notification">
                <i class="fas fa-bell text-gray-600"></i>
                <span class="badge"><?php echo count($kycUsers); ?></span>
            </button>
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
                    <h1 class="page-title">KYC Verification</h1>
                    <p class="page-subtitle">Review and verify user identity documents</p>
                </div>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="p-4 mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Pending KYC List -->
            <div class="card">
                <h3 class="card-title mb-4">Pending Verifications (<?php echo count($kycUsers); ?>)</h3>
                <div class="space-y-4">
                    <?php foreach ($kycUsers as $user): ?>
                    <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="font-semibold"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone']); ?> | <?php echo htmlspecialchars($user['email']); ?></div>
                                <div class="text-xs text-gray-400 mt-1">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($user['doc_type']); ?></span>
                                    <span class="ml-2"><?php echo htmlspecialchars($user['doc_number']); ?></span>
                                    <span class="ml-2">Submitted: <?php echo date('M d, Y', strtotime($user['submitted_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button class="btn btn-sm btn-outline" onclick="viewDocuments(<?php echo $user['id']; ?>)">
                                <i class="fas fa-eye"></i> View Docs
                            </button>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this KYC?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="grid-3 gap-6 mt-6">
                <div class="stats-card">
                    <div class="stats-card-icon warning"><i class="fas fa-clock"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Pending</div>
                        <div class="stats-card-value"><?php echo count($kycUsers); ?></div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon success"><i class="fas fa-check-circle"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Approved (This Month)</div>
                        <div class="stats-card-value">45</div>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="stats-card-icon danger"><i class="fas fa-times-circle"></i></div>
                    <div class="stats-card-content">
                        <div class="stats-card-label">Rejected (This Month)</div>
                        <div class="stats-card-value">3</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/app.js"></script>
    <script>
        function toggleDropdown(id) {
            document.getElementById(id).classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            document.querySelectorAll('.dropdown').forEach(d => {
                if (!d.contains(e.target)) d.classList.remove('open');
            });
        });
        function viewDocuments(id) {
            alert('Document viewer would open for user ID: ' + id);
        }
    </script>
</body>
</html>
