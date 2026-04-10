<!DOCTYPE html>
<html>
<head>
    <title>Transactions - Nepal Pay</title>
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
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Transaction History</h2>
            <?php if (empty($transactions)): ?>
                <p>No transactions yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Sender</th>
                            <th>Receiver</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['created_at']) ?></td>
                                <td>Rs. <?= htmlspecialchars($t['amount']) ?></td>
                                <td><?= htmlspecialchars($t['sender_id'] == $userId ? 'Sent' : 'Received') ?></td>
                                <td><?= htmlspecialchars($t['sender_id']) ?></td>
                                <td><?= htmlspecialchars($t['receiver_id']) ?></td>
                                <td><?= htmlspecialchars($t['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

