<?php
/**
 * Auth Middleware - Protects routes
 */
class AuthMiddleware {
    
    /**
     * Require user login
     */
    public static function requireUser() {
        if (!isUserLoggedIn()) {
            redirect('/login');
            exit;
        }
    }
    
    /**
     * Require admin login
     */
    public static function requireAdmin() {
        if (!isAdminLoggedIn()) {
            redirect('/admin/login');
            exit;
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isUser() {
        return isUserLoggedIn() && empty($_SESSION['is_admin']);
    }
    
    /**
     * Check if admin is logged in
     */
    public static function isAdmin() {
        return isUserLoggedIn() && $_SESSION['is_admin'] == 1;
    }
}

/**
 * Check if user is logged in
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isUserLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Generate CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function flash($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

/**
 * Get and clear flash message
 */
function getFlash($key) {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/**
 * Redirect to URL
 */
function redirect($url) {
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    header('Location: ' . $base . $url);
    exit;
}

/**
 * Get current user ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 */
function getUserName() {
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Get user initials
 */
function getUserInitials() {
    $name = getUserName();
    $parts = explode(' ', $name);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }
    return $initials;
}
