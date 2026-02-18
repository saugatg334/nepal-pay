<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

$user_id = $_SESSION['user_id'] ?? 1;

// Get user data
$userModel = new User();
$currentUser = $userModel->getUserById($user_id);
$balance = $userModel->getWalletBalance($user_id);

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

// Sample notifications (in real app, fetch from database)
$notifications = [
    [
        'id' => 1,
        'title' => 'Money Received',
        'message' => 'You received Rs 5,000 from Ram Shrestha',
        'time' => '2 minutes ago',
        'icon' => 'fa-arrow-down',
        'color' => 'green',
        'read' => false
    ],
    [
        'id' => 2,
        'title' => 'Transfer Successful',
        'message' => 'You sent Rs 2,000 to Sita Pandey',
        'time' => '1 hour ago',
        'icon' => 'fa-paper-plane',
        'color' => 'blue',
        'read' => false
    ],
    [
        'id' => 3,
        'title' => 'Bill Payment',
        'message' => 'NEA bill payment of Rs 1,500 successful',
        'time' => '3 hours ago',
        'icon' => 'fa-file-invoice-dollar',
        'color' => 'yellow',
        'read' => true
    ],
    [
        'id' => 4,
        'title' => 'KYC Verified',
        'message' => 'Your KYC has been verified successfully',
        'time' => '1 day ago',
        'icon' => 'fa-check-circle',
        'color' => 'green',
        'read' => true
    ],
    [
        'id' => 5,
        'title' => 'Wallet Top-up',
        'message' => 'Rs 10,000 added to your wallet from NMB Bank',
        'time' => '2 days ago',
        'icon' => 'fa-wallet',
        'color' => 'blue',
        'read' => true
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay - Notifications</title>
    
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
            <a href="transactions.php">
                <i class="fas fa-history mr-2"></i> Transactions
            </a>
            <a href="wallet.php">
                <i class="fas fa-wallet mr-2"></i> Wallet
            </a>
            <a href="kyc.php">
                <i class="fas fa-id-card mr-2"></i> KYC
            </a>
            <a href="notifications.php" class="active">
                <i class="fas fa-bell mr-2"></i> Notifications
            </a>
        </div>
        
        <!-- Right Side Actions -->
        <div class="navbar-actions">
            <!-- Search -->
            <div class="navbar-search">
                <input type="text" placeholder="Search..." class="bg-gray-100">
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
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Notifications</h1>
                    <p class="page-subtitle">Stay updated with your account activity</p>
                </div>
                <button class="btn btn-secondary">
                    <i class="fas fa-check-double"></i> Mark all as read
                </button>
            </div>
            
            <!-- Notifications List -->
            <div class="card">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex gap-2">
                        <button class="btn btn-primary btn-sm">All</button>
                        <button class="btn btn-secondary btn-sm">Unread (2)</button>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="flex items-start gap-4 p-4 rounded-lg hover:bg-gray-50 cursor-pointer <?php echo $notification['read'] ? '' : 'bg-blue-50'; ?>">
                            <div class="w-10 h-10 rounded-full bg-<?php echo $notification['color']; ?>-100 flex items-center justify-center text-<?php echo $notification['color']; ?>-600">
                                <i class="fas <?php echo $notification['icon']; ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium"><?php echo htmlspecialchars($notification['title']); ?></span>
                                    <?php if (!$notification['read']): ?>
                                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <span class="text-xs text-gray-400"><?php echo $notification['time']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Notification Settings -->
            <div class="card mt-6">
                <h3 class="card-title mb-4">Notification Settings</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <div class="font-medium">Push Notifications</div>
                            <div class="text-sm text-gray-500">Receive push notifications on your device</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <div class="font-medium">SMS Notifications</div>
                            <div class="text-sm text-gray-500">Receive SMS for transactions</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <div class="font-medium">Email Notifications</div>
                            <div class="text-sm text-gray-500">Receive email for important updates</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
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
