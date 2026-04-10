<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Nepal Pay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/wallet/public/assets/css/app.css">
</head>
<body>
    <div class="navbar">
        <div>नेपाल Pay</div>
        <div>
            <a href="/wallet/public/dashboard">Dashboard</a>
            <a href="/wallet/public/transactions">Transactions</a>
            <a href="/wallet/public/logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h1>Balance: Rs. <?= htmlspecialchars($user['wallet_balance'] ?? 0) ?></h1>
            <p>Welcome back!</p>
        </div>

        <div class="card">
            <h2>Send Money</h2>
            <form method="POST" action="/wallet/public/transfer">
                <input type="hidden" name="csrf" value="<?= \App\Core\Security::csrf() ?>">
                <input type="email" name="receiver_email" placeholder="Receiver Email/Phone" required>
                <input type="number" name="amount" placeholder="Amount" min="1" step="0.01" required>
                <button class="btn" type="submit">Send Money</button>
            </form>
        </div>
    </div>
</body>
</html>

