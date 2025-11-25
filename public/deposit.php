php_check_syntax<?php
session_start();
require_once '../controllers/WalletController.php';
$walletController = new WalletController();




// Handle wallet operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['deposit'])) {
        $amount = $_POST['deposit_amount'];
        $walletController->deposit($_SESSION['user_id'], $amount);
        header("Location: dashboard.php");
        exit();
    }
}