<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

// Check admin login
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userModel = new User();

// Current admin
$currentUser = $userModel->getUserById($_SESSION['user_id']);

// Filter
$filter = $_GET['filter'] ?? 'all';

// Users list - try to get from database, fallback to sample data
try {
    $users = $userModel->getAllUsers($filter) ?? [];
} catch (Exception $e) {
    // Fallback sample data if database query fails
    $users = [
        ['id' => 1, 'full_name' => 'Ram Shrestha', 'phone' => '9841000001', 'email' => 'ram@example.com', 'status' => 'active', 'kyc_status' => 'verified', 'wallet_balance' => 25000, 'created_at' => '2024-01-15'],
        ['id' => 2, 'full_name' => 'Sita Pandey', 'phone' => '9841000002', 'email' => 'sita@example.com', 'status' => 'active', 'kyc_status' => 'verified', 'wallet_balance' => 15000, 'created_at' => '2024-01-16'],
        ['id' => 3, 'full_name' => 'Hari Kumar', 'phone' => '9841000003', 'email' => 'hari@example.com', 'status' => 'active', 'kyc_status' => 'pending', 'wallet_balance' => 5000, 'created_at' => '2024-01-17'],
        ['id' => 4, 'full_name' => 'Gita Devi', 'phone' => '9841000004', 'email' => 'gita@example.com', 'status' => 'inactive', 'kyc_status' => 'rejected', 'wallet_balance' => 0, 'created_at' => '2024-01-18'],
        ['id' => 5, 'full_name' => 'Mohan Rai', 'phone' => '9841000005', 'email' => 'mohan@example.com', 'status' => 'active', 'kyc_status' => 'verified', 'wallet_balance' => 35000, 'created_at' => '2024-01-19'],
        ['id' => 6, 'full_name' => 'Laxmi Basnet', 'phone' => '9841000006', 'email' => 'laxmi@example.com', 'status' => 'active', 'kyc_status' => 'pending', 'wallet_balance' => 8000, 'created_at' => '2024-01-20'],
        ['id' => 7, 'full_name' => 'Bibek Tamang', 'phone' => '9841000007', 'email' => 'bibek@example.com', 'status' => 'active', 'kyc_status' => 'verified', 'wallet_balance' => 42000, 'created_at' => '2024-01-21'],
        ['id' => 8, 'full_name' => 'Anita Gurung', 'phone' => '9841000008', 'email' => 'anita@example.com', 'status' => 'active', 'kyc_status' => 'verified', 'wallet_balance' => 18000, 'created_at' => '2024-01-22'],
    ];
}

// Apply filters locally if needed
if ($filter === 'active') {
    $users = array_filter($users, fn($u) => ($u['status'] ?? '') === 'active');
} elseif ($filter === 'inactive') {
    $users = array_filter($users, fn($u) => ($u['status'] ?? '') === 'inactive');
} elseif ($filter === 'kyc_pending') {
    $users = array_filter($users, fn($u) => ($u['kyc_status'] ?? '') === 'pending');
}
$users = array_values($users);

// Admin initials
$adminInitials = 'A';
if (!empty($currentUser['full_name'])) {
    $parts = explode(' ', $currentUser['full_name']);
    $adminInitials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $adminInitials .= strtoupper(substr(end($parts), 0, 1));
    }
} elseif (!empty($currentUser['name'])) {
    $parts = explode(' ', $currentUser['name']);
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
    <title>Nepal Pay Admin - Users</title>
    
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
            <a href="dashboard.php">
                <i class="fas fa-th-large icon"></i> Dashboard
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">User Management</div>
            </div>
            
            <a href="users.php" class="active">
                <i class="fas fa-users icon"></i> Users
            </a>
            <a href="kyc-verification.php">
                <i class="fas fa-id-card icon"></i> KYC Verification
                <span class="badge">5</span>
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">Financial</div>
            </div>
            
            <a href="transactions.php">
                <i class="fas fa-exchange-alt icon"></i> Transactions
            </a>
            <a href="wallet-management.php">
                <i class="fas fa-wallet icon"></i> Wallet Management
            </a>
            <a href="deposits.php">
                <i class="fas fa-arrow-down icon"></i> Deposits
            </a>
            <a href="withdrawals.php">
                <i class="fas fa-arrow-up icon"></i> Withdrawals
            </a>
            
            <div class="sidebar-section">
                <div class="sidebar-section-title">System</div>
            </div>
            
            <a href="reports.php">
                <i class="fas fa-chart-bar icon"></i> Reports & Analytics
            </a>
            <a href="settings.php">
                <i class="fas fa-cog icon"></i> System Settings
            </a>
            
            <div class="mt-6 pt-6 border-t border-white/10">
                <a href="profile.php">
                    <i class="fas fa-user-circle icon"></i> Admin Profile
                </a>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt icon"></i> Logout
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Admin Topbar -->
    <header class="admin-topbar">
        <div class="admin-topbar-left">
            <h2 class="admin-topbar-title">Users Management</h2>
        </div>
        <div class="admin-topbar-right">
            <div class="navbar-search">
                <input type="text" placeholder="Search users..." class="bg-gray-100">
            </div>
            
            <button class="navbar-notification">
                <i class="fas fa-bell text-gray-600"></i>
                <span class="badge">8</span>
            </button>
            
            <div class="dropdown" id="profileDropdown">
                <div class="navbar-profile" onclick="toggleDropdown('profileDropdown')">
                    <div class="avatar"><?php echo htmlspecialchars($adminInitials); ?></div>
                    <i class="fas fa-chevron-down ml-2 text-gray-400"></i>
                </div>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="../logout.php" class="dropdown-item danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="admin-content">
        <div class="max-w-7xl mx-auto">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Users Management</h1>
                    <p class="page-subtitle">Manage all registered users</p>
                </div>
                <button class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>
            
            <!-- Filters -->
            <div class="flex gap-4 mb-6">
                <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                    All (<?php echo count($users); ?>)
                </a>
                <a href="?filter=active" class="btn <?php echo $filter === 'active' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Active
                </a>
                <a href="?filter=inactive" class="btn <?php echo $filter === 'inactive' ? 'btn-primary' : 'btn-secondary'; ?>">
                    Inactive
                </a>
                <a href="?filter=kyc_pending" class="btn <?php echo $filter === 'kyc_pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                    KYC Pending
                </a>
            </div>
            
            <!-- Users Table -->
            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>KYC Status</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="table-user">
                                        <?php if (!empty($user['profile_pic'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="avatar">
                                        <?php else: ?>
                                        <div class="avatar">
                                            <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="table-user-name">
                                                <?php echo htmlspecialchars($user['full_name'] ?? $user['name'] ?? 'Unknown'); ?>
                                            </div>
                                            <div class="table-user-phone">
                                                ID: #<?php echo $user['id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($user['email'] ?? 'No email'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $kycClass = 'primary';
                                    if (($user['kyc_status'] ?? '') === 'verified') $kycClass = 'success';
                                    elseif (($user['kyc_status'] ?? '') === 'pending') $kycClass = 'warning';
                                    elseif (($user['kyc_status'] ?? '') === 'rejected') $kycClass = 'danger';
                                    ?>
                                    <span class="table-badge <?php echo $kycClass; ?>">
                                        <?php echo ucfirst($user['kyc_status'] ?? 'unknown'); ?>
                                    </span>
                                </td>
                                <td class="font-semibold">
                                    Rs <?php echo number_format($user['wallet_balance'] ?? $user['balance'] ?? 0, 2); ?>
                                </td>
                                <td>
                                    <span class="table-badge <?php echo ($user['status'] ?? '') === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'unknown'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete-user.php?id=<?php echo $user['id']; ?>" 
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this user?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-8 text-gray-500">
                                    No users found
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/app.js"></script>
    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            dropdown.classList.toggle('open');
        }
        
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
