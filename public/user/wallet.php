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

// Get wallet balance
$balance = $userModel->getWalletBalance($user_id);

// Handle deposit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'deposit') {
    $walletCtrl = new WalletController();
    
    $amount = $_POST['amount'] ?? 0;
    
    if (empty($amount) || $amount <= 0) {
        $error_msg = "Please enter a valid amount.";
    } else {
        $result = $walletCtrl->deposit($user_id, $amount);
        if ($result) {
            $success_msg = "Money added successfully!";
            $balance = $userModel->getWalletBalance($user_id);
        } else {
            $error_msg = $_SESSION['flash']['error'] ?? "Deposit failed. Please try again.";
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
    <title>Nepal Pay - Wallet</title>
    
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
            <a href="wallet.php" class="active">
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
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">My Wallet</h1>
                    <p class="page-subtitle">Manage your wallet and add money</p>
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
            
            <!-- Wallet Cards -->
            <div class="grid-3 gap-6 mb-6">
                <!-- Main Wallet Card -->
                <div class="wallet-card col-span-2">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="wallet-card-label">Available Balance</div>
                            <div class="wallet-card-balance">Rs <?php echo number_format($balance, 2); ?></div>
                            <div class="wallet-card-number">**** **** **** <?php echo substr($currentUser['phone'] ?? '1234', -4); ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-white/80 text-sm">Account Status</div>
                            <div class="text-white font-semibold mt-1"><?php echo strtoupper($kycStatus); ?></div>
                        </div>
                    </div>
                    <div class="wallet-card-footer">
                        <span><i class="fas fa-credit-card mr-1"></i> Nepal Pay Wallet</span>
                        <span><?php echo date('M Y'); ?></span>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="space-y-4">
                    <div class="card">
                        <h3 class="card-title mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="send-money.php" class="btn btn-primary w-full">
                                <i class="fas fa-paper-plane"></i> Send Money
                            </a>
                            <button onclick="openDepositModal()" class="btn btn-success w-full">
                                <i class="fas fa-plus"></i> Add Money
                            </button>
                            <a href="request-money.php" class="btn btn-outline w-full">
                                <i class="fas fa-hand-holding-usd"></i> Request Money
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add Money & Linked Banks -->
            <div class="grid-2 gap-6">
                <!-- Add Money -->
                <div class="card">
                    <h3 class="card-title mb-6">Add Money to Wallet</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="deposit">
                        
                        <div class="form-group">
                            <label class="form-label">Select Bank</label>
                            <select name="bank" class="form-select">
                                <option value="">Choose a bank...</option>
                                <option value="nmb">NMB Bank</option>
                                <option value="nic">NIC Asia</option>
                                <option value="scb">Standard Chartered</option>
                                <option value="hbl">HBL Bank</option>
                                <option value="ncd">Nepal Credit & Commerce Bank</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Amount (NPR)</label>
                            <input type="number" name="amount" class="form-input" placeholder="Enter amount" min="100" max="50000">
                            <p class="form-hint">Minimum: NPR 100 | Maximum: NPR 50,000</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <div class="grid-2 gap-3">
                                <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer">
                                    <input type="radio" name="method" value="bank_transfer" checked>
                                    <span>Bank Transfer</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer">
                                    <input type="radio" name="method" value="card">
                                    <span>Debit Card</span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-full btn-lg">
                            <i class="fas fa-plus"></i> Add Money
                        </button>
                    </form>
                </div>
                
                <!-- Linked Banks -->
                <div class="card">
                    <h3 class="card-title mb-6">Linked Banks & Cards</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                N
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold">NMB Bank</div>
                                <div class="text-sm text-gray-500">**** **** **** 4532</div>
                            </div>
                            <span class="table-badge success">Active</span>
                        </div>
                        
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                E
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold">eSewa</div>
                                <div class="text-sm text-gray-500">Connected</div>
                            </div>
                            <span class="table-badge success">Active</span>
                        </div>
                        
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">
                                K
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold">Khalti</div>
                                <div class="text-sm text-gray-500">Connected</div>
                            </div>
                            <span class="table-badge success">Active</span>
                        </div>
                    </div>
                    
                    <button class="btn btn-outline w-full mt-4">
                        <i class="fas fa-plus"></i> Link New Bank
                    </button>
                </div>
            </div>
            
            <!-- Wallet History -->
            <div class="card mt-6">
                <h3 class="card-title mb-6">Wallet Statement</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo date('M d, Y'); ?></td>
                                <td>Wallet Top-up</td>
                                <td>NMB Bank</td>
                                <td class="text-green-600">+Rs 10,000.00</td>
                                <td><span class="table-badge success">Completed</span></td>
                            </tr>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime('-1 day')); ?></td>
                                <td>Transfer to Ram S.</td>
                                <td>Wallet</td>
                                <td class="text-red-500">-Rs 5,000.00</td>
                                <td><span class="table-badge success">Completed</span></td>
                            </tr>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime('-2 days')); ?></td>
                                <td>Received from Sita P.</td>
                                <td>Wallet</td>
                                <td class="text-green-600">+Rs 3,000.00</td>
                                <td><span class="table-badge success">Completed</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Deposit Modal -->
    <div id="depositModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center p-6 z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-lg">
            <h2 class="text-lg font-semibold mb-4">Add Money</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="deposit">
                <div class="form-group">
                    <label class="form-label">Amount (NPR)</label>
                    <input type="number" name="amount" class="form-input" placeholder="Enter amount" min="100" max="50000" required>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeDepositModal()" class="btn btn-secondary flex-1">Cancel</button>
                    <button type="submit" class="btn btn-success flex-1">Add Money</button>
                </div>
            </form>
        </div>
    </div>
    
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
        
        // Modal functions
        function openDepositModal() {
            document.getElementById('depositModal').classList.remove('hidden');
        }
        
        function closeDepositModal() {
            document.getElementById('depositModal').classList.add('hidden');
        }
    </script>
</body>
</html>
