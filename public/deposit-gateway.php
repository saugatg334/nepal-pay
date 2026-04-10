<?php
session_start();
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

if (!isset($_SESSION['pending_deposit'])) {
    header('Location: index.php?path=dashboard');
    exit;
}

$gateway_ref = $_GET['ref'] ?? '';
$amount = $_SESSION['deposit_amount'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if ($_GET['status'] === 'success') {
    // Simulate successful payment
    $userModel = new User();
    $userModel->updateWalletBalance($user_id, $amount);
    
    // Clear session
    unset($_SESSION['pending_deposit'], $_SESSION['deposit_amount']);
    
    setFlash('success', 'Deposit of Rs ' . number_format($amount) . ' successful!');
    header('Location: index.php?path=dashboard');
    exit;
}

if ($_GET['status'] === 'cancel') {
    unset($_SESSION['pending_deposit'], $_SESSION['deposit_amount']);
    setFlash('error', 'Deposit cancelled.');
    header('Location: deposit.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>NepalPay Gateway</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-600 to-purple-600 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl p-8 text-center mx-4">
        <div class="w-20 h-20 bg-green-100 rounded-2xl mx-auto mb-6 flex items-center justify-center">
            <i class="fas fa-credit-card text-3xl text-green-600"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Processing</h1>
        <p class="text-gray-600 mb-8">Depositing Rs <?= number_format($amount) ?> to your NepalPay wallet</p>
        
        <div class="space-y-4 mb-8">
            <div class="flex justify-between text-sm">
                <span>Gateway Ref:</span>
                <span class="font-mono"><?= htmlspecialchars($gateway_ref) ?></span>
            </div>
            <div class="flex justify-between text-lg font-bold text-green-600">
                <span>Amount:</span>
                <span>Rs <?= number_format($amount) ?></span>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
            <a href="?status=cancel&ref=<?= urlencode($gateway_ref) ?>" 
               class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-4 px-6 rounded-xl text-center transition-all">
                Cancel
            </a>
            <a href="?status=success&ref=<?= urlencode($gateway_ref) ?>" 
               class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg hover:shadow-xl transition-all">
                Complete Payment
            </a>
        </div>
        
        <p class="text-xs text-gray-500 mt-6">Demo gateway - simulates real Khalti/eSewa payment</p>
    </div>
</body>
</html>

