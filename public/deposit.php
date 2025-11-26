<?php
// use session helper which starts session and provides flash utilities
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/controller/WalletController.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
try {
    $walletController = new WalletController();
} catch (Exception $e) {
    setFlash('error', 'Server error: ' . $e->getMessage());
    header('Location: dashboard.php');
    exit();
}




// Log deposits for debugging
function logDeposit($user_id, $amount, $status, $msg = '') {
    $file = __DIR__ . '/../../logs/deposits.log';
    @mkdir(dirname($file), 0755, true);
    $line = date('Y-m-d H:i:s') . " | User: $user_id | Amount: $amount | Status: $status";
    if ($msg) $line .= " | Message: $msg";
    @file_put_contents($file, $line . PHP_EOL, FILE_APPEND);
}

// Handle wallet operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['deposit_amount']) ? trim($_POST['deposit_amount']) : '';
    if ($user_id === null) {
        logDeposit(0, 0, 'failed', 'User not logged in');
        setFlash('error', 'You must be logged in to deposit.');
        header('Location: login.php');
        exit();
    }

    if ($amount === '' || !is_numeric($amount) || floatval($amount) <= 0) {
        logDeposit($user_id, $amount, 'failed', 'Invalid amount');
        setFlash('error', 'Please enter a valid deposit amount.');
        header('Location: dashboard.php');
        exit();
    }

    $amount = floatval($amount);
    $ok = $walletController->deposit($user_id, $amount);
    if ($ok) {
        logDeposit($user_id, $amount, 'success');
        header('Location: dashboard.php');
        exit();
    } else {
        logDeposit($user_id, $amount, 'failed', 'Deposit operation returned false');
        header('Location: dashboard.php');
        exit();
    }
}

// If reached directly, show deposit form matching dashboard UI
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Deposit - NepalPay Wallet</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/style.css" />
</head>
<body class="min-h-screen bg-gradient-to-tr from-gray-50 to-gray-100 p-6 font-sans text-gray-800">
  <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg p-8">
    <div class="flex items-center justify-center mb-6">
      <div class="w-12 h-12 rounded-full bg-gradient-to-b from-red-600 to-red-700 text-white flex items-center justify-center text-xl font-bold">NP</div>
    </div>
    <h1 class="text-center text-2xl font-bold mb-2">Add Money</h1>
    <p class="text-center text-gray-500 text-sm mb-6">Top up your NepalPay wallet instantly</p>

    <?php if ($msg = getFlash('error')): ?>
      <div class="p-3 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($msg = getFlash('success')): ?>
      <div class="p-3 mb-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <form method="post" action="" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (NPR)</label>
        <input type="number" step="0.01" name="deposit_amount" placeholder="Enter amount" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" />
      </div>
      <button type="submit" class="w-full px-4 py-3 rounded-lg bg-gradient-to-r from-red-600 to-red-700 text-white font-semibold hover:shadow-lg transition">Deposit Now</button>
    </form>

    <p class="text-center text-sm text-gray-500 mt-4"><a href="dashboard.php" class="text-red-600 hover:underline">← Back to dashboard</a></p>
  </div>
</body>
</html>