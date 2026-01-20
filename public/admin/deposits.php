<?php
require_once __DIR__ . '/../../app/helpers/session_helper.php';
require_once __DIR__ . '/../../app/controller/AdminController.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit();
}

$adminCtrl = new AdminController();
$deposits = $adminCtrl->listDeposits('pending');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin - Pending Deposits</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="p-6 bg-gray-50">
  <h1>Pending Deposits</h1>
  <p><a href="index.php">← Admin dashboard</a></p>
  <?php if (empty($deposits)): ?>
    <p>No pending deposit requests.</p>
  <?php else: ?>
    <table class="table-auto w-full border-collapse border">
      <thead>
        <tr>
          <th class="border px-2 py-1">Txn</th>
          <th class="border px-2 py-1">User</th>
          <th class="border px-2 py-1">Amount</th>
          <th class="border px-2 py-1">Requested</th>
          <th class="border px-2 py-1">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($deposits as $d): ?>
        <tr>
          <td class="border px-2 py-1"><?=htmlspecialchars($d['txn_id'])?></td>
          <td class="border px-2 py-1"><?=htmlspecialchars($d['receiver_name'] ?? $d['receiver_phone'] ?? $d['receiver_id'])?></td>
          <td class="border px-2 py-1"><?=number_format($d['amount'],2)?></td>
          <td class="border px-2 py-1"><?=htmlspecialchars($d['created_at'])?></td>
          <td class="border px-2 py-1">
            <form method="post" action="admin_action.php" style="display:inline">
              <input type="hidden" name="txn_id" value="<?=htmlspecialchars($d['txn_id'])?>">
              <button name="action" value="approve" class="px-2 py-1 bg-green-600 text-white rounded">Approve</button>
            </form>
            <form method="post" action="admin_action.php" style="display:inline; margin-left:6px;">
              <input type="hidden" name="txn_id" value="<?=htmlspecialchars($d['txn_id'])?>">
              <button name="action" value="reject" class="px-2 py-1 bg-red-600 text-white rounded">Reject</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
