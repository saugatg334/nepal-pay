<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userModel = new User();
$user = $userModel->getUserById($_SESSION['user_id']);

if (!$user) {
    header("Location: login.php");
    exit;
}

$message = getFlash('profile_message');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];

    // Basic validation
    $errors = [];
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($errors)) {
        $profileData = [
            'email' => $email,
            'address' => $address,
            'date_of_birth' => $date_of_birth,
            'gender' => $gender
        ];

        if ($userModel->updateProfile($_SESSION['user_id'], $profileData)) {
            setFlash('profile_message', 'Profile updated successfully!');
            header("Location: profile.php");
            exit;
        } else {
            $message = 'Failed to update profile.';
        }
    } else {
        $message = implode(' ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Nepal Pay</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Nepal Pay</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php" class="active">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="profile-section">
                <h2>My Profile</h2>

                <?php if ($message): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" placeholder="Enter your address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kyc_status">KYC Status</label>
                        <span class="kyc-status kyc-<?php echo strtolower($user['kyc_status']); ?>">
                            <?php echo ucfirst($user['kyc_status']); ?>
                        </span>
                    </div>

                    <div class="form-group">
                        <label for="wallet_balance">Wallet Balance</label>
                        <input type="text" id="wallet_balance" value="NPR <?php echo number_format($user['wallet_balance'], 2); ?>" readonly>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
