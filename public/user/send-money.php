<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controller/WalletController.php';

$user_id = $_SESSION['user_id'] ?? 1;
$error_msg = null;
$success_msg = null;

// Get user data
$userModel = new User();
$currentUser = $userModel->getUserById($user_id);
$balance = $userModel->getWalletBalance($user_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $walletCtrl = new WalletController();
    
    $to = $_POST['to'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $note = $_POST['note'] ?? '';
    
    if (empty($to) || empty($amount)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        $result = $walletCtrl->transfer($user_id, $to, $amount);
        if ($result) {
            $success_msg = "Money sent successfully!";
            $balance = $userModel->getWalletBalance($user_id);
        } else {
            $error_msg = $_SESSION['flash']['error'] ?? "Transfer failed. Please try again.";
        }
    }
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
    <title>Nepal Pay - Send Money</title>
    
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
            <a href="send-money.php" class="active">
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
                    <h1 class="page-title">Send Money</h1>
                    <p class="page-subtitle">Transfer money to any Nepal Pay user or bank</p>
                </div>
            </div>
            
            <?php if ($error_msg): ?>
                <div class="p-4 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_msg): ?>
                <div class="p-4 mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            
            <div class="grid-2 gap-6">
                <!-- Send Money Form -->
                <div class="card">
                    <h3 class="card-title mb-6">Send Money Details</h3>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label">Recipient Phone Number / Email</label>
                            <input type="text" name="to" class="form-input" placeholder="Enter phone number or email" required>
                            <p class="form-hint">Enter the recipient's Nepal Pay registered number</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Amount (NPR)</label>
                            <input type="number" name="amount" class="form-input" placeholder="Enter amount" min="1" max="25000" required>
                            <p class="form-hint">Maximum transfer: NPR 25,000 per transaction</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Note (Optional)</label>
                            <textarea name="note" class="form-input" rows="3" placeholder="Add a note..."></textarea>
                        </div>
                        
                        <!-- Transfer Summary -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Amount</span>
                                <span class="font-semibold" id="displayAmount">NPR 0.00</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Fee</span>
                                <span class="font-semibold">NPR 0.00</span>
                            </div>
                            <div class="border-t pt-2 mt-2">
                                <div class="flex justify-between">
                                    <span class="font-semibold">Total</span>
                                    <span class="font-bold text-primary" id="displayTotal">NPR 0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full btn-lg">
                            <i class="fas fa-paper-plane"></i> Send Money
                        </button>
                    </form>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Wallet Balance -->
                    <div class="card">
                        <h3 class="card-title mb-4">Available Balance</h3>
                        <div class="text-3xl font-bold text-primary">Rs <?php echo number_format($balance, 2); ?></div>
                        <a href="wallet.php" class="btn btn-outline w-full mt-4">
                            <i class="fas fa-plus"></i> Add Money
                        </a>
                    </div>
                    
                    <!-- Recent Recipients -->
                    <div class="card">
                        <h3 class="card-title mb-4">Recent Recipients</h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-semibold">R</div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Ram Shrestha</div>
                                    <div class="text-xs text-gray-500">9841******</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-semibold">S</div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Sita Pandey</div>
                                    <div class="text-xs text-gray-500">9868******</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                <div class="w-10 h-10 rounded-full bg-purple-500 text-white flex items-center justify-center font-semibold">S</div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Shop Name</div>
                                    <div class="text-xs text-gray-500">9841******</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transfer Limits -->
                    <div class="card bg-blue-50 border border-blue-100">
                        <h3 class="card-title mb-4 text-blue-800">Transfer Limits</h3>
                        <div class="space-y-2 text-sm text-blue-700">
                            <div class="flex justify-between">
                                <span>Per Transaction</span>
                                <span class="font-semibold">NPR 25,000</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Daily Limit</span>
                                <span class="font-semibold">NPR 50,000</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Monthly Limit</span>
                                <span class="font-semibold">NPR 200,000</span>
                            </div>
                        </div>
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
        
        // Update amount display
        const amountInput = document.querySelector('input[name="amount"]');
        if (amountInput) {
            amountInput.addEventListener('input', function() {
                const amount = parseFloat(this.value) || 0;
                document.getElementById('displayAmount').textContent = 'NPR ' + amount.toFixed(2);
                document.getElementById('displayTotal').textContent = 'NPR ' + amount.toFixed(2);
            });
        }
    </script>
</body>
</html>
