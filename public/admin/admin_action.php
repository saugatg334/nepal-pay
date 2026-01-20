<?php
require_once __DIR__ . '/../../app/helpers/session_helper.php';
require_once __DIR__ . '/../../app/controller/AdminController.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: deposits.php');
    exit();
}

$action = $_POST['action'] ?? '';
$txn_id = $_POST['txn_id'] ?? '';

$admin = new AdminController();

try {
    if ($action === 'approve') {
        $admin->approveDeposit($txn_id);
        setFlash('success', 'Deposit approved.');
    } elseif ($action === 'reject') {
        $admin->rejectDeposit($txn_id, 'Rejected by admin');
        setFlash('success', 'Deposit rejected.');
    }
} catch (Exception $e) {
    setFlash('error', $e->getMessage());
}

header('Location: deposits.php');
exit();
?>