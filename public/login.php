<?php
require_once __DIR__ . '/../app/controller/AuthController.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $auth->login($phone, $password);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nepal Pay</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container auth-container">
        <div class="header">
            <h1>Nepal Pay</h1>
            <p>Digital Wallet Login</p>
        </div>

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

        <h2>Login to your account</h2>
        <form method="POST" class="login-form">
            <input type="text" name="phone" placeholder="Phone number (10 digits)" required pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" maxlength="10" inputmode="numeric" autocomplete="username">
            <input type="password" name="password" placeholder="Password" required minlength="6" autocomplete="current-password">
            <button type="submit">Login</button>
        </form>
        <a href="register.php">Don't have an account? Register here</a>
    </div>
</body>
</html>
