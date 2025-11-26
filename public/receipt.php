<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/models/User.php';

$txn = isset($_GET['txn']) ? trim($_GET['txn']) : null;
$userModel = new User();
$transaction = null;
if ($txn) {
    try {
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare('SELECT t.*, u.name as user_name FROM transactions t LEFT JOIN users u ON t.sender_id = u.id WHERE t.txn_id = :txn LIMIT 1');
        $stmt->bindParam(':txn', $txn);
        $stmt->execute();
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $transaction = null;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Receipt - NepalPay</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container" style="max-width:800px;margin:40px auto;padding:24px;">
    <?php if (!$transaction): ?>
      <h3>Receipt not found</h3>
      <p>Transaction not found. Check the transaction id.</p>
    <?php else: ?>
      <h3>Payment Receipt</h3>
      <div style="background:#fff;padding:16px;border-radius:8px;border:1px solid #eef2f6;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <div>
            <div style="font-weight:700">Transaction ID: <?php echo htmlspecialchars($transaction['txn_id']); ?></div>
            <div style="font-size:13px;color:#6b7280">Status: <?php echo htmlspecialchars($transaction['status']); ?></div>
          </div>
          <div style="text-align:right">
            <div style="font-weight:700;font-size:20px;color:#d32f2f">Rs <?php echo number_format($transaction['amount'],2); ?></div>
            <div style="font-size:12px;color:#6b7280">Date: <?php echo htmlspecialchars($transaction['created_at']); ?></div>
          </div>
        </div>

        <hr style="margin:12px 0">
        <div><strong>Payer:</strong> <?php echo htmlspecialchars($transaction['user_name'] ?: 'Unknown'); ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($transaction['type']); ?></div>
        <div><strong>Provider:</strong> <?php echo htmlspecialchars($transaction['provider'] ?: '-'); ?></div>
        <div><strong>Customer ref (e.g., SC No):</strong> <?php echo htmlspecialchars($transaction['customer_ref'] ?: '-'); ?></div>
        <div><strong>Method:</strong> <?php echo htmlspecialchars($transaction['method'] ?: '-'); ?></div>
        <div style="margin-top:8px"><strong>Description:</strong> <?php echo htmlspecialchars($transaction['description'] ?: '-'); ?></div>

        <?php if (!empty($transaction['provider_response'])): ?>
          <hr>
          <div><strong>Provider Response:</strong>
            <pre style="white-space:pre-wrap;background:#f7fafc;padding:8px;border-radius:6px;overflow:auto;max-height:200px"><?php echo htmlspecialchars($transaction['provider_response']); ?></pre>
          </div>
        <?php endif; ?>

      </div>
    <?php endif; ?>
  </div>
</body>
</html>
