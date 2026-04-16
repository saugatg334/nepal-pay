<?php
/**
 * Auth Middleware for NepalPay
 * Helper functions only - include in pages for auth checks
 */

// Rate limiting constants
define('BRUTE_FORCE_LOCK_MINUTES', 5);
define('MAX_LOGIN_ATTEMPTS', 5);

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
 * Require user login - redirects if not logged in
 */
function requireUser() {
    if (!isUserLoggedIn()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            echo json_encode(['error' => 'Please login']);
            exit;
        }
        header('Location: ../index.php');
        exit;
    }
    session_regenerate_id(true);
}

/**
 * Require admin login - redirects if not admin
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }
        header('Location: ../index.php');
        exit;
    }
    session_regenerate_id(true);
}

/**
 * Redirect if user is already logged in (for guest pages)
 */
function requireGuest() {
    if (isUserLoggedIn()) {
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Check for brute force attack
 */
function checkBruteForce($user) {
    if ($user && isset($user['failed_login_attempts']) && $user['failed_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        return true;
    }
    return false;
}

/**
 * Generate CSRF token
 */
function csrfToken() {
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


