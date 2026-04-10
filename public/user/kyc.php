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

// Handle KYC form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentType = $_POST['document_type'] ?? '';
    $documentNumber = $_POST['document_number'] ?? '';
    
    if (empty($documentType) || empty($documentNumber)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        // In a real app, you would handle file uploads here
        // For demo, we'll just update the status
        try {
            $userModel->updateKYCStatus($user_id, 'pending', json_encode([
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'submitted_at' => date('Y-m-d H:i:s')
            ]));
            $success_msg = "KYC documents submitted successfully! Your verification is pending.";
            $currentUser = $userModel->getUserById($user_id);
        } catch (Exception $e) {
            $error_msg = "Failed to submit KYC. Please try again.";
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
    <title>Nepal Pay - KYC Verification</title>
    
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
            <a href="kyc.php" class="active">
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
                    <h1 class="page-title">KYC Verification</h1>
                    <p class="page-subtitle">Verify your identity to unlock all features</p>
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
            
            <!-- KYC Status -->
            <?php if ($kycStatus === 'verified'): ?>
                <div class="card mb-6">
                    <div class="flex items-center gap-4 p-6 bg-green-50 rounded-xl">
                        <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center text-white text-2xl">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-green-800">Account Verified</h3>
                            <p class="text-green-700">Your account is fully verified. You can now send and receive money without limits.</p>
                        </div>
                    </div>
                </div>
            <?php elseif ($kycStatus === 'pending'): ?>
                <div class="card mb-6">
                    <div class="flex items-center gap-4 p-6 bg-yellow-50 rounded-xl">
                        <div class="w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center text-white text-2xl">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-yellow-800">Verification Pending</h3>
                            <p class="text-yellow-700">Your documents are being reviewed. This usually takes 24-48 hours.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- KYC Form -->
                <div class="grid-2 gap-6">
                    <div class="card">
                        <h3 class="card-title mb-6">Submit Documents</h3>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="form-label">Document Type</label>
                                <select name="document_type" class="form-select" required>
                                    <option value="">Select document type...</option>
                                    <option value="citizenship">Citizenship Certificate</option>
                                    <option value="passport">Passport</option>
                                    <option value="driving_license">Driving License</option>
                                    <option value="voter_id">Voter ID</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Document Number</label>
                                <input type="text" name="document_number" class="form-input" placeholder="Enter document number" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Front Photo</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-500">Click to upload or drag and drop</p>
                                    <p class="text-xs text-gray-400">PNG, JPG up to 5MB</p>
                                    <input type="file" name="front_photo" class="hidden" accept="image/*">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Back Photo</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-500">Click to upload or drag and drop</p>
                                    <p class="text-xs text-gray-400">PNG, JPG up to 5MB</p>
                                    <input type="file" name="back_photo" class="hidden" accept="image/*">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Selfie with Document</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                    <i class="fas fa-camera text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-500">Take a selfie holding your document</p>
                                    <p class="text-xs text-gray-400">PNG, JPG up to 5MB</p>
                                    <input type="file" name="selfie" class="hidden" accept="image/*">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-full btn-lg">
                                <i class="fas fa-upload"></i> Submit for Verification
                            </button>
                        </form>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- Requirements -->
                        <div class="card">
                            <h3 class="card-title mb-4">Requirements</h3>
                            <ul class="space-y-3">
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <span class="text-sm">Valid government-issued ID</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <span class="text-sm">Clear, readable photos</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <span class="text-sm">Documents must not be expired</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-check-circle text-green-500 mt-1"></i>
                                    <span class="text-sm">Good lighting conditions</span>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Benefits -->
                        <div class="card">
                            <h3 class="card-title mb-4">Benefits of KYC</h3>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm">Higher Limits</div>
                                        <div class="text-xs text-gray-500">Send up to NPR 100,000</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm">Bank Transfers</div>
                                        <div class="text-xs text-gray-500">Transfer to any bank in Nepal</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm">Enhanced Security</div>
                                        <div class="text-xs text-gray-500">Protected account</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
