<?php
/**
 * NepalPay Auth Helper - Production Robust Version
 * FIXED: DB-tolerant requireUser(), no session destruction on DB errors
 */

require_once __DIR__ . '/session_helper.php';

function requireUser($dbRefresh = true) {
    // Basic session check FIRST (fast, no DB)
    if (empty($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
        setFlash('error', 'Please login to continue.');
        header('Location: ../index.php?path=login');
        exit;
    }
    
    // Optional DB refresh (graceful fallback)
    if ($dbRefresh) {
        try {
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User();
            $user = $userModel->getUserById($_SESSION['user_id']);
            
            if ($user) {
                // Refresh session data safely
                $_SESSION['user_name'] = $user['name'] ?? $_SESSION['user_name'] ?? 'User';
                $_SESSION['phone'] = $user['phone'] ?? $_SESSION['phone'];
                $_SESSION['kyc_status'] = $user['kyc_status'] ?? 'pending';
                $_SESSION['is_admin'] = !empty($user['is_admin']);
            } 
            // Silently continue if DB refresh fails (stale session OK)
            
        } catch (Exception $e) {
            // Log but DON'T destroy session
            error_log("Auth refresh failed: " . $e->getMessage());
        }
    }
    
    // Regenerate occasionally (anti-fixation)
    if (!isset($_SESSION['last_regen']) || (time() - $_SESSION['last_regen']) > 1800) {
        regenerateSession();
        $_SESSION['last_regen'] = time();
    }
}

function requireAdmin() {
    requireUser();
    if (empty($_SESSION['is_admin'])) {
        setFlash('error', 'Admin access required.');
        header('Location: ../index.php?path=admin-login');
        exit;
    }
}

function isAdmin() {
    return !empty($_SESSION['is_admin']);
}

function isKYCVerified() {
    return ($_SESSION['kyc_status'] ?? '') === 'approved';
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Logout with cleanup
 */
function secureLogout() {
    $_SESSION = [];  // Clear all data
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    clearAllFlashes();
}
?>


