<?php
// Redirect to main entry point (MVC routing)
$basePath = '/nepal-pay/public';
header('Location: ' . $basePath . '/wallet/deposit');
exit;