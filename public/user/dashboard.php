<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controller/WalletController.php';

$user_id = $_SESSION['user_id'] ?? 1;
$error_msg = null;

try {
    $walletCtrl = new WalletController();
    $balance = $walletCtrl->getWalletBalance($user_id);
    $transactions = $walletCtrl->getTransactionHistory($user_id, 10);
    
    // Get user data
    $userModel = new User();
    $currentUser = $userModel->getUserById($user_id);
} catch (Exception $e) {
    $balance = 0.00;
    $transactions = [];
    $error_msg = $e->getMessage();
    $currentUser = ['name' => 'User', 'kyc_status' => 'pending'];
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

// KYC status
$kycStatus = $currentUser['kyc_status'] ?? 'pending';
$profileCompletion = 50;
if (!empty($currentUser['email'])) $profileCompletion += 15;
if (!empty($currentUser['address'])) $profileCompletion += 10;
if (!empty($currentUser['date_of_birth'])) $profileCompletion += 10;
if ($kycStatus === 'verified') $profileCompletion += 15;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nepal Pay - Dashboard</title>
    
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
            <a href="dashboard.php" class="active">
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
        <div class="max-w-7xl mx-auto">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?>!</h1>
                    <p class="page-subtitle">Here's your wallet overview</p>
                </div>
                <div class="flex gap-3">
                    <a href="send-money.php" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Money
                    </a>
                    <a href="wallet.php" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Add Money
                    </a>
                </div>
            </div>
            
            <!-- Stats & Quick Actions -->
            <div class="grid-4 mb-6">
                <!-- Wallet Card -->
                <div class="wallet-card">
                    <div class="wallet-card-label">Available Balance</div>
                    <div class="wallet-card-balance">Rs <?php echo number_format($balance, 2); ?></div>
                    <div class="wallet-card-number">**** **** **** <?php echo substr($currentUser['phone'] ?? '1234', -4); ?></div>
                    <div class="wallet-card-footer">
                        <span><i class="fas fa-credit-card mr-1"></i> Nepal Pay</span>
                        <span><?php echo strtoupper($currentUser['kyc_status'] ?? 'PENDING'); ?></span>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="col-span-3">
                    <div class="quick-actions">
                        <a href="send-money.php" class="quick-action">
                            <div class="quick-action-icon send">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <span class="quick-action-label">Send</span>
                        </a>
                        <a href="request-money.php" class="quick-action">
                            <div class="quick-action-icon receive">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <span class="quick-action-label">Receive</span>
                        </a>
                        <a href="pay.php" class="quick-action">
                            <div class="quick-action-icon pay">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <span class="quick-action-label">Pay Bills</span>
                        </a>
                        <a href="wallet.php" class="quick-action">
                            <div class="quick-action-icon topup">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <span class="quick-action-label">Top Up</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Grid -->
            <div class="grid-2 gap-6">
                <!-- Recent Transactions -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Recent Transactions</h3>
                            <p class="card-subtitle">Your latest activity</p>
                        </div>
                        <a href="transactions.php" class="text-sm text-primary font-medium">View All</a>
                    </div>
                    
                    <?php if (!empty($error_msg)): ?>
                        <div class="p-3 bg-yellow-50 text-yellow-800 rounded-lg text-sm mb-4">
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="transaction-list">
                        <?php if (empty($transactions)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p>No transactions yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <div class="transaction-item">
                                    <div class="transaction-icon <?php echo $tx['amount'] < 0 ? 'send' : 'receive'; ?>">
                                        <i class="fas <?php echo $tx['amount'] < 0 ? 'fa-paper-plane' : 'fa-arrow-down'; ?>"></i>
                                    </div>
                                    <div class="transaction-details">
                                        <div class="transaction-title"><?php echo htmlspecialchars($tx['description'] ?: ucfirst($tx['type'])); ?></div>
                                        <div class="transaction-meta">
                                            <?php echo date('M d, Y h:i A', strtotime($tx['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="transaction-amount <?php echo $tx['amount'] < 0 ? 'send' : 'receive'; ?>">
                                        <?php echo $tx['amount'] < 0 ? '-' : '+'; ?>Rs <?php echo number_format(abs($tx['amount']), 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- KYC Status -->
                    <div class="card">
                        <h3 class="card-title mb-4">KYC Verification</h3>
                        
                        <?php if ($kycStatus === 'verified'): ?>
                            <div class="kyc-status verified">
                                <div class="kyc-status-icon">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="kyc-status-content">
                                    <div class="kyc-status-title">Verified</div>
                                    <div class="kyc-status-text">Your account is fully verified</div>
                                </div>
                            </div>
                        <?php elseif ($kycStatus === 'pending'): ?>
                            <div class="kyc-status pending">
                                <div class="kyc-status-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="kyc-status-content">
                                    <div class="kyc-status-title">Pending Verification</div>
                                    <div class="kyc-status-text">Submit your documents to verify your account</div>
                                </div>
                            </div>
                            <a href="kyc.php" class="btn btn-primary w-full mt-4">
                                <i class="fas fa-upload"></i> Complete KYC
                            </a>
                        <?php else: ?>
                            <div class="kyc-status rejected">
                                <div class="kyc-status-icon">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="kyc-status-content">
                                    <div class="kyc-status-title">Rejected</div>
                                    <div class="kyc-status-text">Please resubmit your documents</div>
                                </div>
                            </div>
                            <a href="kyc.php" class="btn btn-primary w-full mt-4">
                                <i class="fas fa-redo"></i> Retry KYC
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Profile Completion -->
                    <div class="card">
                        <h3 class="card-title mb-4">Profile Completion</h3>
                        <div class="progress-label">
                            <span>Your profile is <?php echo $profileCompletion; ?>% complete</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo $profileCompletion; ?>%"></div>
                        </div>
                        <div class="mt-4 space-y-2">
                            <?php if (empty($currentUser['email'])): ?>
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <i class="fas fa-times-circle text-danger"></i> Add email address
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-2 text-sm text-success">
                                    <i class="fas fa-check-circle"></i> Email verified
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($currentUser['address'])): ?>
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <i class="fas fa-times-circle text-danger"></i> Add address
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-2 text-sm text-success">
                                    <i class="fas fa-check-circle"></i> Address added
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($kycStatus !== 'verified'): ?>
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <i class="fas fa-times-circle text-danger"></i> Complete KYC
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-2 text-sm text-success">
                                    <i class="fas fa-check-circle"></i> KYC verified
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="profile.php" class="btn btn-outline w-full mt-4">
                            <i class="fas fa-edit"></i> Update Profile
                        </a>
                    </div>
                    
                    <!-- Linked Banks -->
                    <div class="card">
                        <h3 class="card-title mb-4">Linked Banks</h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold">
                                    N
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">NMB Bank</div>
                                    <div class="text-xs text-gray-500">****4532</div>
                                </div>
                                <span class="table-badge success">Active</span>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center text-white font-bold">
                                    E
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">eSewa</div>
                                    <div class="text-xs text-gray-500">Connected</div>
                                </div>
                                <span class="table-badge success">Active</span>
                            </div>
                        </div>
                        <button class="btn btn-outline w-full mt-4">
                            <i class="fas fa-plus"></i> Link Bank
                        </button>
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
