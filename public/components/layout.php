<?php
/**
 * NepalPay Design System - Master Layout
 * Production-ready reusable layout for User + Admin panels
 */

session_start();

// Security: Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function generateCsrfToken() {
    return $_SESSION['csrf_token'] ?? '';
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setFlash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return '';
}

function hasFlash($key) {
    return isset($_SESSION['flash'][$key]);
}

// Auth helpers
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isUserLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireUser() {
    if (!isUserLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
    session_regenerate_id(true);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
    session_regenerate_id(true);
}

function getCurrentUser() {
    if (!isUserLoggedIn()) return null;
    
    require_once __DIR__ . '/../models/User.php';
    $userModel = new User();
    return $userModel->getUserById($_SESSION['user_id']);
}

function getUserBalance() {
    if (!isUserLoggedIn()) return 0;
    
    require_once __DIR__ . '/../models/User.php';
    $userModel = new User();
    return $userModel->getWalletBalance($_SESSION['user_id']);
}

function getUserInitials($name) {
    if (empty($name)) return 'U';
    
    $parts = explode(' ', $name);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }
    return $initials;
}

// Get page title from filename
function getPageTitle() {
    $page = basename($_SERVER['PHP_SELF'], '.php');
    return ucwords(str_replace('-', ' ', $page));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NepalPay - <?php echo getPageTitle(); ?></title>
    <meta name="description" content="NepalPay - Secure Digital Wallet">
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="../assets/css/design-system.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/custom.css">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Override any conflicting styles */
        body { font-family: 'Inter', sans-serif; }
        
        /* Smooth transitions */
        * { transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-gray-50">
    <?php echo $content ?? ''; ?>
</body>
</html>
