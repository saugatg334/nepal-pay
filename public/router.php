<?php
/**
 * Nepal Pay Router - Central routing system
 * Fixes duplicate dashboard issues and provides clean navigation
 */

require_once __DIR__ . '/../app/helpers/session_helper.php';

// Get request path
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim(preg_replace('#^/wallet/public/?#', '', $request_uri), '/');

$query = $_GET ?? [];

 // Default route
$target_page = 'index.php';

// Route definitions
$routes = [
    // User routes
    'dashboard' => 'dashboard.php',
    'wallet' => 'wallet.php',
    'profile' => 'profile.php',
    'transactions' => 'user/transactions.php',
    'send-money' => 'user/send-money.php',
    'pay' => 'pay.php',
    'deposit' => 'deposit.php',
    'login' => 'login.php',
    'register' => 'register.php',
    'logout' => function() {
        require_once __DIR__ . '/../app/controller/Authcontroller.php';
        $auth = new AuthController();
        $auth->logout();
    },
    
    // Admin routes (require admin check)
'admin/login' => 'admin/login.php',
    'admin/dashboard' => 'admin/dashboard.php',
    'admin/users' => 'admin/users.php',
    'admin/kyc-verification' => 'admin/kyc-verification.php',
    'admin/deposits' => 'admin/deposits.php',
    'admin/transactions' => 'admin/transactions.php',
    
    // API routes
    'api/bill-lookup' => 'api/bill_lookup.php',
];

// Check if path matches route
if (isset($routes[$path])) {
    $target_page = $routes[$path];
    
    // Handle logout (function)
    if (is_callable($target_page)) {
        $target_page();
        exit;
    }
    
    // Apply middleware after route match, before include
    if (strpos($path, 'admin/') === 0 && $path !== 'admin/login') {
        requireAdmin();
    } elseif (!in_array($path, ['login', 'register', ''])) {
        requireUser();
    }
} else {
    // 404 or index
    $target_page = 'index.php';
}

// Include target page with query params preserved
$_GET['via_router'] = 1; // Marker for debugging
include __DIR__ . '/' . $target_page;
?>

