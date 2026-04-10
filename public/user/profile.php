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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    
    if (empty($name)) {
        $error_msg = "Name is required.";
    } else {
        try {
            $userModel->updateProfile($user_id, [
                'email' => $email,
                'address' => $address,
                'date_of_birth' => $date_of_birth,
                'gender' => $gender
            ]);
            $success_msg = "Profile updated successfully!";
            $currentUser = $userModel->getUserById($user_id);
        } catch (Exception $e) {
            $error_msg = "Failed to update profile. Please try again.";
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
    <title>Nepal Pay - My Profile</title>
    
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
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">My Profile</h1>
                    <p class="page-subtitle">Manage your account settings</p>
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
                <!-- Profile Form -->
                <div class="card">
                    <h3 class="card-title mb-6">Personal Information</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <!-- Profile Picture -->
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-20 h-20 rounded-full bg-primary text-white flex items-center justify-center text-3xl font-bold">
                                <?php echo htmlspecialchars($userInitials); ?>
                            </div>
                            <div>
                                <button type="button" class="btn btn-outline btn-sm">
                                    <i class="fas fa-camera"></i> Change Photo
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-input" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" disabled>
                            <p class="form-hint">Phone number cannot be changed</p>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-input" value="<?php echo htmlspecialchars($currentUser['date_of_birth'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select gender...</option>
                                <option value="male" <?php echo ($currentUser['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($currentUser['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($currentUser['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-input" rows="3"><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Account Status -->
                    <div class="card">
                        <h3 class="card-title mb-4">Account Status</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">KYC Status</span>
                                <span class="table-badge <?php echo $kycStatus === 'verified' ? 'success' : 'warning'; ?>">
                                    <?php echo strtoupper($kycStatus); ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Account Type</span>
                                <span class="font-medium">Personal</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Member Since</span>
                                <span class="font-medium"><?php echo date('M Y', strtotime($currentUser['created_at'] ?? 'now')); ?></span>
                            </div>
                        </div>
                        <a href="kyc.php" class="btn btn-outline w-full mt-4">
                            <i class="fas fa-id-card"></i> Complete KYC
                        </a>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="card">
                        <h3 class="card-title mb-4">Quick Links</h3>
                        <div class="space-y-2">
                            <a href="change-password.php" class="dropdown-item">
                                <i class="fas fa-lock"></i> Change Password
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-shield-alt"></i> Security Settings
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-question-circle"></i> Help Center
                            </a>
                            <a href="#" class="dropdown-item">
                                <i class="fas fa-file-alt"></i> Terms & Conditions
                            </a>
                        </div>
                    </div>
                    
                    <!-- Danger Zone -->
                    <div class="card border border-red-200">
                        <h3 class="card-title mb-4 text-red-600">Danger Zone</h3>
                        <p class="text-sm text-gray-500 mb-4">Once you delete your account, there is no going back.</p>
                        <button class="btn btn-danger w-full">
                            <i class="fas fa-trash"></i> Delete Account
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
