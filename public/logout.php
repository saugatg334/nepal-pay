<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';

// Clear all session variables
$_SESSION = [];

// Destroy the session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Set flash message
$_SESSION['success'] = 'Logged out successfully.';

// Redirect to login page
header("Location: login.php");
exit;
?>
