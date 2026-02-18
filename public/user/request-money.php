<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

$user_id = $_SESSION['user_id'] ?? 1;
$error_msg = null;
$success_msg = null;

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay - Request Money</title>
    
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
            <a href="request-money.php" class="active">
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
            <a href="notifications.php">
                <i class="fas fa-bell mr-2"></i> Notifications
            </a>
        </div>
        
        <!-- Right Side Actions -->
        <div class="navbar-actions">
            <!-- Search -->
            <div class="navbar-search">
                <input type="text" placeholder="Search transactions, contacts..." class="bg-gray-100">
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
                    <h1 class="page-title">Request Money</h1>
                    <p class="page-subtitle">Create a payment request and share with others</p>
                </div>
            </div>
            
            <div class="grid-2 gap-6">
                <!-- Request Money Form -->
                <div class="card">
                    <h3 class="card-title mb-6">Request Details</h3>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Request Amount (NPR)</label>
                            <input type="number" name="amount" class="form-input" placeholder="Enter amount" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Request From (Phone/Email)</label>
                            <input type="text" name="from" class="form-input" placeholder="Enter phone number or email">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Reason (Optional)</label>
                            <textarea name="reason" class="form-input" rows="3" placeholder="What is this for?"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Expiry</label>
                            <select name="expiry" class="form-select">
                                <option value="24">24 Hours</option>
                                <option value="48">48 Hours</option>
                                <option value="72">72 Hours</option>
                                <option value="168">7 Days</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full btn-lg">
                            <i class="fas fa-hand-holding-usd"></i> Create Request
                        </button>
                    </form>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Your Payment Link -->
                    <div class="card">
                        <h3 class="card-title mb-4">Your Payment Link</h3>
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <p class="text-sm text-gray-600 mb-2">Share this link to receive money:</p>
                            <div class="flex gap-2">
                                <input type="text" class="form-input text-sm" value="https://nepalpay.com/pay/<?php echo $user_id; ?>" readonly>
                                <button class="btn btn-secondary btn-icon" onclick="copyLink()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button class="btn btn-primary flex-1">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </button>
                            <button class="btn btn-secondary flex-1">
                                <i class="fas fa-envelope"></i> Email
                            </button>
                        </div>
                    </div>
                    
                    <!-- Pending Requests -->
                    <div class="card">
                        <h3 class="card-title mb-4">Pending Requests</h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="w-10 h-10 rounded-full bg-yellow-400 text-white flex items-center justify-center">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Rs 5,000</div>
                                    <div class="text-xs text-gray-500">From: 9841******</div>
                                </div>
                                <span class="text-xs text-yellow-700">Pending</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 rounded-full bg-green-400 text-white flex items-center justify-center">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Rs 2,000</div>
                                    <div class="text-xs text-gray-500">From: 9868******</div>
                                </div>
                                <span class="text-xs text-green-700">Received</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Wallet Balance -->
                    <div class="card">
                        <h3 class="card-title mb-4">Wallet Balance</h3>
                        <div class="text-3xl font-bold text-primary">Rs <?php echo number_format($balance, 2); ?></div>
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
        
        // Copy link function
        function copyLink() {
            const input = document.querySelector('input[readonly]');
            input.select();
            document.execCommand('copy');
            alert('Link copied to clipboard!');
        }
    </script>
</body>
</html>
