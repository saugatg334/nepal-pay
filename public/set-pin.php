<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/controller/Authcontroller.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$authController = new AuthController();

$message = getFlash('message');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin']);
    $confirmPin = trim($_POST['confirm_pin']);

    if ($pin !== $confirmPin) {
        $message = 'PINs do not match.';
    } else {
        if ($authController->setPIN($_SESSION['user_id'], $pin)) {
            header("Location: dashboard.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set PIN - Nepal Pay</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Nepal Pay</h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <div class="auth-section">
                <h2>Set Your PIN</h2>
                <p>Create a 4-6 digit PIN for enhanced security</p>

                <?php if ($message): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="pin">Enter PIN (4-6 digits)</label>
                        <input type="password" id="pin" name="pin" pattern="[0-9]{4,6}" maxlength="6" required placeholder="Enter 4-6 digit PIN">
                    </div>

                    <div class="form-group">
                        <label for="confirm_pin">Confirm PIN</label>
                        <input type="password" id="confirm_pin" name="confirm_pin" pattern="[0-9]{4,6}" maxlength="6" required placeholder="Confirm your PIN">
                    </div>

                    <button type="submit" class="btn btn-primary">Set PIN</button>
                </form>

                <div class="auth-links">
                    <a href="dashboard.php">Skip for now</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        // PIN validation
        document.getElementById('pin').addEventListener('input', function(e) {
            const pin = e.target.value;
            if (pin.length < 4 || pin.length > 6) {
                e.target.setCustomValidity('PIN must be 4-6 digits');
            } else if (!/^\d+$/.test(pin)) {
                e.target.setCustomValidity('PIN must contain only numbers');
            } else {
                e.target.setCustomValidity('');
            }
        });

        document.getElementById('confirm_pin').addEventListener('input', function(e) {
            const pin = document.getElementById('pin').value;
            const confirmPin = e.target.value;
            if (pin !== confirmPin) {
                e.target.setCustomValidity('PINs do not match');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
