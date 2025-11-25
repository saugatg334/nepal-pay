<?php
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/controller/WalletController.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\QRCode;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $userModel = new User();
    $walletController = new WalletController();
    // Preload government services for dropdown
    $governmentServices = $walletController->getGovernmentServices();
    $user = $userModel->getUserById($_SESSION['user_id']);

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // Check if PIN is set, if not redirect to set PIN
    if (empty($user['pin'])) {
        header("Location: set-pin.php");
        exit;
    }
} catch (Exception $e) {
    setFlash('error', 'Failed to load user data: ' . $e->getMessage());
    header("Location: login.php");
    exit;
}

// Handle wallet operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['deposit'])) {
        $amount = $_POST['deposit_amount'];
        $walletController->deposit($_SESSION['user_id'], $amount);
        header("Location: dashboard.php");
        exit;
    } elseif (isset($_POST['transfer'])) {
        $receiver_phone = $_POST['receiver_phone'];
        $amount = $_POST['transfer_amount'];
        $walletController->transfer($_SESSION['user_id'], $receiver_phone, $amount);
        header("Location: dashboard.php");
        exit;
    } elseif (isset($_POST['government_payment'])) {
        $service_id = $_POST['service_id'] ?? '';
        $reference_number = $_POST['reference_number'] ?? '';
        $amount = $_POST['payment_amount'] ?? 0;
        $walletController->governmentPayment($_SESSION['user_id'], $amount, $service_id, $reference_number);
        header("Location: dashboard.php");
        exit;
    }
}

// Get updated user data and transaction history
$user = $userModel->getUserById($_SESSION['user_id']);
$transactionHistory = $walletController->getTransactionHistory($_SESSION['user_id']);

// Generate QR code for payment receipt (text-based fallback if GD not available)
$qrData = "Nepal Pay User: " . $user['name'] . " | Phone: " . $user['phone'] . " | Balance: NPR " . number_format($user['wallet_balance'], 2);
if (extension_loaded('gd')) {
    try {
        $qrCode = new QRCode($qrData);
        $qrImage = $qrCode->render();
    } catch (Exception $e) {
        $qrImage = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150"><rect width="150" height="150" fill="white"/><text x="75" y="75" text-anchor="middle" font-family="monospace" font-size="12">QR Code Error</text></svg>');
    }
} else {
    // Fallback to text-based representation if GD is not available
    $qrImage = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150"><rect width="150" height="150" fill="white"/><text x="75" y="75" text-anchor="middle" font-family="monospace" font-size="12">QR Code</text><text x="75" y="90" text-anchor="middle" font-family="monospace" font-size="8">GD Extension Required</text></svg>');
}

// Get KYC status class for styling
$kycClass = 'kyc-' . strtolower($user['kyc_status']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Nepal Pay</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <h1>Nepal Pay</h1>
            <p>Digital Wallet Dashboard</p>
        </div>

        <div class="container">
            <?php
            $flashMessage = getFlash('success');
            if ($flashMessage) {
                echo '<div class="flash-message flash-success">' . htmlspecialchars($flashMessage) . '</div>';
            }

            $flashError = getFlash('error');
            if ($flashError) {
                echo '<div class="flash-message flash-error">' . htmlspecialchars($flashError) . '</div>';
            }
            ?>

            <h2>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h2>

            <div class="user-info">
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                <p><strong>Wallet Balance:</strong> <span class="balance">NPR <?php echo number_format($user['wallet_balance'], 2); ?></span></p>
                <p><strong>KYC Status:</strong> <span class="kyc-status <?php echo $kycClass; ?>"><?php echo htmlspecialchars($user['kyc_status']); ?></span></p>
                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            </div>

            <!-- Wallet Operations Section -->
            <div class="wallet-operations">
                <h3>Wallet Operations</h3>

                <!-- Deposit Form -->
                <div class="operation-card">
                    <h4>Add Money to Wallet</h4>
                    <form method="POST" class="operation-form">
                        <input type="number" name="deposit_amount" placeholder="Amount (NPR)" min="1" max="50000" step="0.01" required>
                        <button type="submit" name="deposit" class="btn-deposit">Deposit</button>
                    </form>
                    <p class="operation-note">Maximum deposit: NPR 50,000</p>
                </div>

                <!-- Transfer Form -->
                <div class="operation-card">
                    <h4>Send Money</h4>
                    <form method="POST" class="operation-form">
                        <input type="text" name="receiver_phone" placeholder="Receiver Phone (10 digits)" pattern="[0-9]{10}" maxlength="10" required>
                        <input type="number" name="transfer_amount" placeholder="Amount (NPR)" min="1" max="25000" step="0.01" required>
                        <button type="submit" name="transfer" class="btn-transfer">Send Money</button>
                    </form>
                    <p class="operation-note">Maximum transfer: NPR 25,000 per transaction</p>
                </div>

                <!-- Government Payment Form -->
                <div class="operation-card">
                    <h4>Government Payment</h4>
                    <form method="POST" class="operation-form">
                        <select name="service_id" required>
                            <option value="">Select Government Service</option>
                            <?php
                                try {
                                    $services = $walletController->getGovernmentServices();
                                    if (!empty($services) && is_array($services)) {
                                        foreach ($services as $service) {
                                            $id = htmlspecialchars($service['id']);
                                            $name = htmlspecialchars($service['name']);
                                            echo "<option value=\"$id\">$name</option>";
                                        }
                                    }
                                } catch (Exception $e) {
                                    echo "<option value=\"\">Failed to load services</option>";
                                }
                            ?>
                        </select>
                        <input type="text" name="reference_number" placeholder="Reference Number" required>
                        <input type="number" name="payment_amount" placeholder="Amount (NPR)" min="1" max="100000" step="0.01" required>
                        <button type="submit" name="government_payment" class="btn-payment">Pay Government</button>
                    </form>
                    <p class="operation-note">Maximum payment: NPR 100,000 per transaction</p>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="qr-section">
                <div class="qr-card">
                    <h3>Payment QR Code</h3>
                    <p>Scan this QR code to receive payments</p>
                    <div class="qr-code-display">
                        <img src="<?php echo $qrImage; ?>" alt="Payment QR Code">
                    </div>
                    <p class="qr-info">User: <?php echo htmlspecialchars($user['name']); ?><br>Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="transaction-history">
                <h3>Recent Transactions</h3>
                <?php if (empty($transactionHistory)): ?>
                    <p class="no-transactions">No transactions yet. Start by adding money to your wallet!</p>
                <?php else: ?>
                    <div class="transaction-table">
                        <!-- <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactionHistory as $transaction): ?>
                                    <tr class="transaction-<?php echo $transaction['type']; ?>">
                                        <td><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td><?php echo ucfirst($transaction['type']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['description'] ?: 'N/A'); ?></td>
                                        <td class="amount <?php echo $transaction['type'] === 'deposit' ? 'positive' : 'negative'; ?>">
                                            <?php echo ($transaction['type'] === 'deposit' ? '+' : '-') . 'NPR ' . number_format($transaction['amount'], 2); ?>
                                        </td>
                                        <td><span class="status-<?php echo $transaction['status']; ?>"><?php echo ucfirst($transaction['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div> -->
                <?php endif; ?>
            </div>

            <!-- Support Section -->
            <div class="support-section">
                <h3>Support & Help</h3>
                <div class="support-options">
                    <div class="support-item">
                        <div class="support-icon">📞</div>
                        <h4>Call Support</h4>
                        <p>24/7 customer support</p>
                        <a href="tel:+977-1234567890" class="support-link">Call Now</a>
                    </div>
                    <div class="support-item">
                        <div class="support-icon">💬</div>
                        <h4>Live Chat</h4>
                        <p>Chat with our agents</p>
                        <a href="#" class="support-link">Start Chat</a>
                    </div>
                    <div class="support-item">
                        <div class="support-icon">📧</div>
                        <h4>Email Support</h4>
                        <p>Get help via email</p>
                        <a href="mailto:support@nepalpay.com" class="support-link">Send Email</a>
                    </div>
                    <div class="support-item">
                        <div class="support-icon">❓</div>
                        <h4>FAQ</h4>
                        <p>Frequently asked questions</p>
                        <a href="#" class="support-link">View FAQ</a>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <a href="compare.php" class="compare-btn" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-right: 10px;">Compare Wallets</a>
                <a href="kyc-update.php" class="kyc-btn" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-right: 10px;">Update KYC</a>
                <a href="profile.php" class="profile-btn" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-right: 10px;">My Profile</a>
                <a href="logout.php" class="logout-btn" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>
