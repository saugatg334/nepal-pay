<!DOCTYPE html>
<html>
<head>
    <title>Login - Nepal Pay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/wallet/public/assets/css/app.css">
</head>
<body>
    <div class="container" style="max-width: 400px;">
        <div class="card">
            <h2>Login to Nepal Pay</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="/wallet/public/login">
                <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrf() ?>">
                <input type="text" name="phone" placeholder="Phone Number" required>
                <input type="password" name="password" placeholder="Password" required>
                <button class="btn" type="submit">Login</button>
            </form>
            <p style="text-align: center; margin-top: 20px;">
                <a href="/wallet/public/register">Register</a>
            </p>
        </div>
    </div>
</body>
</html>

