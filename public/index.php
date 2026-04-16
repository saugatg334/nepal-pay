<?php
/**
 * NepalPay - Main Entry Point
 * Routes all requests to appropriate controllers
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base path
require_once __DIR__ . '/../app/config/env.php';
if (APP_ENV !== 'local') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}
function base_url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}


define('APP_START', microtime(true));

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = array(
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/controllers/',
        __DIR__ . '/../app/core/',
        __DIR__ . '/../app/config/',
        __DIR__ . '/../app/services/'
    );
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// CSRF token helper
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCsrf($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Verify CSRF token (alias)
function verifyCsrfToken($token) {
    return verifyCsrf($token);
}

// Check if user is logged in
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isUserLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Require login
function requireLogin() {
    if (!isUserLoggedIn()) {
header('Location: ' . BASE_URL . '/login');

        exit;
    }
}

// Require admin
function requireAdmin() {
    if (!isAdminLoggedIn()) {
header('Location: ' . BASE_URL . '/admin/login');

        exit;
    }
}

// Set flash message
function flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

// Get flash message
function getFlash($key) {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// Redirect helper
function redirect($url) {
header('Location: ' . BASE_URL . $url);

    exit;
}

// Load routes
$routes = require __DIR__ . '/../routes/web.php';

// Get request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Parse URI
$parsedUri = parse_url($requestUri, PHP_URL_PATH);
$uri = $parsedUri ?: '/';

// Remove BASE_PATH from URI
$uri = str_replace('/nepal-pay/public', '', $uri);
$uri = str_replace(BASE_PATH, '', $uri);

// Ensure leading slash
if (!empty($uri) && strpos($uri, '/') !== 0) {
    $uri = '/' . $uri;
}

// Trim trailing slash except root
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Build route key - format from routes: 'GET /login'
$routeKey = $method . ' ' . $uri;

// Find handler
$handler = null;

// Try exact match first
if (isset($routes[$routeKey])) {
    $handler = $routes[$routeKey];
}

// If no match, try pattern matching for dynamic routes
if (!$handler) {
    foreach ($routes as $route => $h) {
        // Skip if no dynamic params
        if (strpos($route, '{') === false) {
            continue;
        }
        
        // Convert route to regex
        $pattern = str_replace('/', '\\/', $route);
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $routeKey)) {
            $handler = $h;
            break;
        }
    }
}

// 404 if not found
if (!$handler) {
    http_response_code(404);
    echo '<!DOCTYPE html>';
    echo '<html><head><title>404 - Not Found</title>';
    echo '<style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5}.container{max-width:600px;margin:0 auto;background:white;padding:30px;border-radius:8px}ul{columns:2}</style></head>';
    echo '<body><div class="container">';
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>Requested: ' . htmlspecialchars($method . ' ' . $uri) . '</p>';
    echo '<h3>Available Routes:</h3><ul>';
    if (app_env() === 'local') {
        foreach (array_keys($routes) as $r) {
            echo '<li>' . htmlspecialchars($r) . '</li>';
        }
    }
    echo '</ul>';
echo '<p><a href="' . BASE_URL . '/login">Go to Login</a></p>';

    echo '</div></body></html>';
    exit;
}

// Extract controller and method
$controllerName = $handler[0];
$controllerMethod = $handler[1];

// Load controller file
$controllerFile = __DIR__ . '/../app/controllers/' . $controllerName . '.php';

if (!file_exists($controllerFile)) {
    die('Controller not found: ' . $controllerName);
}

require_once $controllerFile;

// Create controller instance
$controller = new $controllerName();

// Call method
if (method_exists($controller, $controllerMethod)) {
    $controller->$controllerMethod();
} else {
    die('Method not found: ' . $controllerMethod);
}