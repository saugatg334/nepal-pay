<?php
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userModel = new User();
$user = $userModel->getUserById($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle KYC document update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kyc_documents = trim($_POST['kyc_documents'] ?? '');

    if (empty($kyc_documents)) {
        setFlash('error', 'Please provide KYC documents information.');
    } else {
        $updateSuccess = $userModel->updateKycDocuments($_SESSION['user_id'], $kyc_documents);
        if ($updateSuccess) {
            setFlash('success', 'KYC documents updated successfully.');
            header("Location: kyc-update.php");
            exit;
        } else {
            setFlash('error', 'Failed to update KYC documents.');
        }
    }
}

$flashSuccess = getFlash('success');
$flashError = getFlash('error');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Update - Nepal Pay</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="kyc-update-container">
        <h1>KYC Update</h1>

        <?php if ($flashSuccess): ?>
            <div class="flash-message flash-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
        <?php endif; ?>
        <?php if ($flashError): ?>
            <div class="flash-message flash-error"><?php echo htmlspecialchars($flashError); ?></div>
        <?php endif; ?>

        <form method="POST" class="kyc-update-form">
            <label for="kyc_documents">KYC Documents Information:</label><br>
            <textarea id="kyc_documents" name="kyc_documents" rows="6" cols="50" placeholder="Enter your KYC document details"><?php echo htmlspecialchars($user['kyc_documents'] ?? ''); ?></textarea><br><br>
            <button type="submit" class="btn-update">Update KYC</button>
        </form>

        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
