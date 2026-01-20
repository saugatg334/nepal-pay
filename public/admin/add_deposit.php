<?php
require_once __DIR__ . '/../../app/helpers/session_helper.php';
require_once __DIR__ . '/../../app/controller/AdminController.php';
require_once __DIR__ . '/../../app/models/User.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit();
}

$userModel = new User();
$admin = new AdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_user = $_POST['user_id'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    try {
        $txn = $admin->manualDeposit($target_user, $amount, $_SESSION['user_id']);
        setFlash('success', 'Manual deposit done. Txn: ' . $txn);
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
    header('Location: add_deposit.php');
    exit();
}

// Simple manual deposit form
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin - Add Deposit</title></head>
<body>
  <h1>Manual Deposit</h1>
  <?php if ($msg = getFlash('error')): ?><div style="color:red"><?=$msg?></div><?php endif; ?>
  <?php if ($msg = getFlash('success')): ?><div style="color:green"><?=$msg?></div><?php endif; ?>
  <form method="post">
    <div>
      <label>User ID</label>
      <input name="user_id" required>
    </div>
    <div>
      <label>Amount</label>
      <input name="amount" type="number" step="0.01" required>
    </div>
    <div><button type="submit">Deposit</button></div>
  </form>
  <p><a href="deposits.php">Back to deposits</a></p>
</body>
</html>