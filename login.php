<?php
/**
 * Standalone Login Page
 * Uses existing AuthController::handleLogin() for consistency
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}
require_once BASE_PATH . '/app/config/config.php';
require_once BASE_PATH . '/app/config/database.php';
require_once BASE_PATH . '/app/helpers/session.php';
require_once BASE_PATH . '/app/helpers/helpers.php';
require_once BASE_PATH . '/app/helpers/csrf.php';
require_once BASE_PATH . '/app/helpers/validation.php';
require_once BASE_PATH . '/app/controller/AuthController.php';

Session::init();

if (Session::has('user_id')) {
    redirect(APP_URL . '/index.php?page=dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $authController = new AuthController();
        $authController->handleLogin();
    } catch (Exception $e) {
        // Memory issue workaround - direct session handling
        error_log('Login error: ' . $e->getMessage());
        flash('error', 'Login failed. Please try again.');
    }
} 

// Render login form
include 'app/views/auth/login.php';
?>

