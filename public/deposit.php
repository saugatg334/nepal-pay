(UPDATE existing file: replace POST handling block to create pending request and require User model)
<?php
// use session helper which starts session and provides flash utilities
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/controller/WalletController.php';
require_once __DIR__ . '/../app/models/User.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
try {
    $walletController = new WalletController();
    $userModel = new User();
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

// Change: create a pending deposit request instead of immediate credit
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

    // record a pending transaction (txn_id for tracking)
    $txn_id = 'REQ' . time() . rand(1000,9999);
    $ok = $userModel->recordTransaction(null, $user_id, $amount, 'deposit', 'User deposit request', $txn_id, null, null, 'bank', null, 'pending');
    if ($ok) {
        logDeposit($user_id, $amount, 'pending', 'Request created: ' . $txn_id);
        setFlash('success', 'Deposit request created. An admin will review and approve it shortly.');
        header('Location: dashboard.php');
        exit();
    } else {
        logDeposit($user_id, $amount, 'failed', 'Failed to create deposit request');
        setFlash('error', 'Failed to create deposit request. Please try again later.');
        header('Location: dashboard.php');
        exit();
    }
}

// If reached directly, show deposit form matching dashboard UI
?>
(keep the rest of the original HTML form intact)