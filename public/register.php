<?php
require_once __DIR__ . '/../app/controller/AuthController.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $auth->register($name, $phone, $password, $confirmPassword);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Nepal Pay</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container auth-container">
        <div class="header">
            <h1>Nepal Pay</h1>
            <p>Create Your Digital Wallet</p>
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

        <h2>Create your account</h2>
        <form method="POST" class="register-form">
            <input type="text" name="name" placeholder="Full Name" required minlength="2" maxlength="100" autocomplete="name">
            <input type="text" name="phone" placeholder="Phone Number (10 digits)" required pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" maxlength="10" inputmode="numeric" autocomplete="username">
            <input type="password" name="password" placeholder="Password (min 6 characters)" required minlength="6" autocomplete="new-password">
            <input type="password" name="confirm_password" placeholder="Confirm Password" required autocomplete="new-password">
            <button type="submit">Register</button>
        </form>
        <a href="login.php">Already have an account? Login here</a>
    </div>
</body>
</html>
