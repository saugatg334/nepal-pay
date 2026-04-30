<?php
/**
 * NepalPay API Entry Point
 * Handles all /api/* requests with token-based authentication
 * 
 * Routes:
 * POST   /api/auth/login      - User login, returns access + refresh tokens
 * POST   /api/auth/refresh    - Refresh access token
 * POST   /api/auth/logout     - Revoke token
 * GET    /api/wallet/balance  - Get wallet balance
 * POST   /api/wallet/send     - Send money
 * GET    /api/transactions    - List user transactions
 */

define('BASE_PATH', dirname(__DIR__) . '/..');

// Composer autoload
$autoload = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server not configured']);
    exit;
}
require_once $autoload;

// Bootstrap
use NepalPay\Core\App;
use NepalPay\Core\Logger;
use NepalPay\Helpers\Production\Input;

App::boot(BASE_PATH);

// Fallback for legacy classes (until classmap is regenerated)
if (!class_exists('Config')) {
    require_once BASE_PATH . '/app/config/config.php';
}
if (!class_exists('Database')) {
    require_once BASE_PATH . '/app/config/database.php';
}
if (!class_exists('Session')) {
    require_once BASE_PATH . '/app/helpers/session.php';
}
if (!class_exists('CSRF')) {
    require_once BASE_PATH . '/app/helpers/csrf.php';
}
if (!function_exists('escape')) {
    require_once BASE_PATH . '/app/helpers/helpers.php';
}
if (!function_exists('lang')) {
    require_once BASE_PATH . '/app/helpers/lang.php';
}

// Set JSON header by default
header('Content-Type: application/json; charset=utf-8');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Parse URI to determine endpoint
$uri = parse_url($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/wallet/public/api/', '', $uri);
$uri = str_replace('/wallet/api/', '', $uri);
$parts = explode('/', trim($uri, '/'));
$endpoint = $parts[0] ?? '';
$action = $parts[1] ?? '';

// Simple router
try {
    switch ($endpoint) {
        case 'auth':
            require_once BASE_PATH . '/app/controller/ApiAuthController.php';
            $controller = new ApiAuthController();
            break;
            
        case 'wallet':
            require_once BASE_PATH . '/app/controller/ApiWalletController.php';
            $controller = new ApiWalletController();
            break;
            
        case 'transactions':
            require_once BASE_PATH . '/app/controller/ApiTransactionController.php';
            $controller = new ApiTransactionController();
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'API endpoint not found'
            ]);
            exit;
    }
    
    // Call action method (e.g., 'login', 'balance', 'send')
    if (method_exists($controller, $action)) {
        $controller->$action();
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Action not found'
        ]);
    }
    
} catch (Exception $e) {
    Logger::error('API Error', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'endpoint' => $endpoint . '/' . $action
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => APP_DEBUG ? $e->getMessage() : 'Internal server error'
    ]);
}
