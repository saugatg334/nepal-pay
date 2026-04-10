<?php
/**
 * Send Money - Core Wallet Feature
 * Phone lookup → Transfer with confirmation
 */

require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/helpers/auth_helper.php';
require_once __DIR__ . '/../app/controllers/TransactionController.php';

requireUser();
$user_id = $_SESSION['user_id'];

$error = $success = '';
$recipient = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $txnCtrl = new TransactionController();
        $result = $txnCtrl->sendMoney(
            $user_id, 
            $_POST['recipient'], 
            floatval($_POST['amount']),
            $_POST['description'] ?? ''
        );
        $success = "Money sent! Reference: " . $result['reference'];
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} elseif (isset($_GET['lookup'])) {
    // AJAX lookup (simple)
    $identifier = $_GET['lookup'];
    require_once __DIR__ . '/../app/models/User.php';
    $userModel = new User();
    $recipient = $userModel->findUserByPhone($identifier);
    if (!$recipient) $recipient = $userModel->findUserByEmail($identifier);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Send Money • Nepal Pay</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="max-w-md mx-auto mt-8 p-6 bg-white rounded-xl shadow-lg">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Send Money</h1>
        <p class="text-gray-600 mt-2">Transfer instantly to any Nepal Pay user</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg mb-6">
            <?= htmlspecialchars($success) ?>
            <a href="index.php?path=dashboard" class="block mt-2 text-green-600 hover:underline">← Back to Dashboard</a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4" id="sendForm">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">To (Phone or Email)</label>
            <input type="text" name="recipient" required placeholder="9841234567 or user@test.com" 
                   value="<?= htmlspecialchars($_GET['lookup'] ?? '') ?>"
                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <button type="button" onclick="lookupRecipient()" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded text-sm">Lookup</button>
        </div>

        <?php if ($recipient): ?>
        <div class="p-3 bg-blue-50 rounded-lg">
            <p><strong><?= htmlspecialchars($recipient['name']) ?></strong></p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($recipient['phone'] ?? $recipient['email']) ?></p>
        </div>
        <?php endif; ?>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount (Rs)</label>
            <input type="number" name="amount" step="0.01" min="10" max="50000" required
                   class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Note (optional)</label>
            <textarea name="description" rows="2" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="For birthday..."></textarea>
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 text-white font-bold py-3 px-6 rounded-lg shadow-lg">
            Send Now
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-gray-500">
        <a href="index.php?path=dashboard" class="hover:underline">← Dashboard</a>
    </div>
</div>

<script>
function lookupRecipient() {
    const phone = document.querySelector('[name="recipient"]').value;
    if (phone) {
        window.location.href = `send.php?lookup=${encodeURIComponent(phone)}`;
    }
}
</script>
</body>
</html>

