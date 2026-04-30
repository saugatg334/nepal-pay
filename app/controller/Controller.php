<?php
/**
 * Base Controller
 * 
 * All controllers extend this. Provides common utilities:
 * - view rendering with layouts
 * - JSON responses
 * - redirect helpers
 * - CSRF validation
 * - auth checks (requireLogin, requireAdmin)
 */

use NepalPay\Helpers\Production\RBAC;
use NepalPay\Helpers\Production\Input;

class Controller {
    /**
     * Verify user is logged in. Redirects to login if not.
     */
    protected function requireLogin() {
        if (!Session::has('user_id')) {
            flash('error', 'You must be logged in to access this page.');
            redirect(APP_URL . '/index.php?page=login');
            exit;
        }
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regenerate']) || (time() - $_SESSION['last_regenerate']) > 1800) {
            Session::regenerate();
            $_SESSION['last_regenerate'] = time();
        }
    }

    /**
     * Verify user has admin role
     */
    protected function requireAdmin() {
        $this->requireLogin();
        if (Session::get('user_role') !== 'admin') {
            flash('error', 'Unauthorized access.');
            redirect(APP_URL . '/index.php?page=dashboard');
            exit;
        }
    }

    protected function view($template, $data = []) {
        extract($data);
        
        $templateFile = BASE_PATH . '/app/views/' . $template . '.php';
        
        if (!file_exists($templateFile)) {
            http_response_code(404);
            echo "<h1>404 - View not found</h1>";
            echo "<p>Template: " . htmlspecialchars($template) . "</p>";
            if (APP_DEBUG) {
                echo "<p>Path: " . htmlspecialchars($templateFile) . "</p>";
            }
            error_log("View not found: " . $templateFile);
            exit;
        }
        
        try {
            require $templateFile;
        } catch (Exception $e) {
            http_response_code(500);
            echo "<h1>Error rendering view</h1>";
            if (APP_DEBUG) {
                echo "<p>" . escape($e->getMessage()) . "</p>";
                echo "<pre>" . escape($e->getTraceAsString()) . "</pre>";
            } else {
                echo "<p>An error occurred. Please try again.</p>";
            }
            error_log("View error in {$template}: " . $e->getMessage());
            exit;
        }
    }

    protected function render($template, $data = []) {
        $data['page'] = $_GET['page'] ?? 'dashboard';
        extract($data);
        
        $templateFile = BASE_PATH . '/app/views/' . $template . '.php';
        
        if (!file_exists($templateFile)) {
            http_response_code(404);
            echo "<h1>404 - View not found</h1>";
            echo "<p>Template: " . htmlspecialchars($template) . "</p>";
            if (APP_DEBUG) {
                echo "<p>Path: " . htmlspecialchars($templateFile) . "</p>";
            }
            error_log("View not found: " . $templateFile);
            exit;
        }
        
        try {
            ob_start();
            require $templateFile;
            $content = ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            http_response_code(500);
            echo "<h1>Error rendering view</h1>";
            if (APP_DEBUG) {
                echo "<p>" . escape($e->getMessage()) . "</p>";
                echo "<pre>" . escape($e->getTraceAsString()) . "</pre>";
            }
            error_log("View error in {$template}: " . $e->getMessage());
            exit;
        }
        
        if (empty($content)) {
            if (APP_DEBUG) {
                echo "<h1>Warning: View produced empty output</h1>";
                echo "<p>Template: " . htmlspecialchars($template) . "</p>";
            }
        }
        
        $layoutFile = BASE_PATH . '/app/views/layouts/main.php';
        if (file_exists($layoutFile)) {
            try {
                require $layoutFile;
            } catch (Exception $e) {
                http_response_code(500);
                echo "<h1>Error rendering layout</h1>";
                if (APP_DEBUG) {
                    echo "<p>" . escape($e->getMessage()) . "</p>";
                    echo "<pre>" . escape($e->getTraceAsString()) . "</pre>";
                } else {
                    echo "<p>An error occurred while loading the page.</p>";
                }
                error_log("Layout error: " . $e->getMessage());
                exit;
            }
        } else {
            echo $content;
        }
    }

    protected function json($data, $status = 200) {
        jsonResponse($data, $status);
    }

    protected function redirect($url) {
        redirect($url);
    }

    protected function back() {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if (empty($referer)) {
            $referer = APP_URL . '/index.php?page=login';
        }
        redirect($referer);
    }

    protected function unauthorized() {
        http_response_code(401);
        if ($_SERVER['REQUEST_METHOD'] === 'API' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        flash('error', 'You must be logged in to access this page');
        redirect(APP_URL . '/index.php?page=login');
    }

    protected function forbidden() {
        http_response_code(403);
        if ($_SERVER['REQUEST_METHOD'] === 'API' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            jsonResponse(['error' => 'Forbidden'], 403);
        }
        
        flash('error', 'You do not have permission to access this page');
        redirect(APP_URL . '/index.php?page=dashboard');
    }

    protected function error404() {
        http_response_code(404);
        echo '<h1>404 - Page Not Found</h1>';
        exit;
    }

    protected function error500($message = 'Internal Server Error') {
        http_response_code(500);
        if (APP_DEBUG) {
            echo '<h1>500 - Internal Server Error</h1>';
            echo '<p>' . escape($message) . '</p>';
        } else {
            echo '<h1>500 - Something went wrong</h1>';
        }
        exit;
    }

    protected function validateCSRF() {
        if (!CSRF::validateRequest()) {
            http_response_code(403);
            flash('error', 'Invalid security token');
            $this->back();
        }
    }

    protected function getInput() {
        return $_POST;
    }

    protected function getBody() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? $_POST;
    }

    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
               strtolower($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
    }
}
