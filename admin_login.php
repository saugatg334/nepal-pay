<?php
/**
 * Standalone Admin Login Page
 * Uses existing AuthController::handleAdminLogin() for consistency
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

if (Session::has('user_id') && Session::get('user_role') === 'admin') {
    redirect(APP_URL . '/index.php?page=admin');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $authController = new AuthController();
        $authController->handleAdminLogin();
    } catch (Exception $e) {
        error_log('Admin login error: ' . $e->getMessage());
        flash('error', 'Admin login failed. Please try again.');
    }
}

// Render admin login form
include 'app/views/auth/admin_login.php';
?>

