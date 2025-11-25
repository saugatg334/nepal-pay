<?php
require_once __DIR__ . '/../app/controller/AuthController.php';

$auth = new AuthController();
$auth->logout();
?>
