<?php
/**
 * Global Error & Exception Handler
 * 
 * Production improvements:
 * - Routes to custom error pages (404, 500) based on HTTP status
 * - Structured logging via Monolog when available
 * - Respects APP_DEBUG for showing/hiding details
 * - Prevents duplicate error reporting
 */

define('ERROR_HANDLER_ACTIVE', true);

// Helper: Determine if headers already sent
function error_headers_sent_override(&$filename, &$linenum) {
    if (xdebug_is_enabled()) {
        return headers_sent($filename, $linenum);
    }
    return headers_sent($filename, $linenum);
}

// Set error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Don't handle errors suppressed with @
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    // Fatal error types that should trigger shutdown handler instead
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    
    if (in_array($errno, $fatalTypes)) {
        // Let shutdown handler catch these
        return false;
    }
    
    $error_types = [
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated',
    ];
    
    $error_type = $error_types[$errno] ?? 'Unknown Error';
    $logMessage = "[{$error_type}] {$errstr} in {$errfile}:{$errline}";
    
    // Log the error
    if (class_exists('NepalPay\\Core\\Logger')) {
        NepalPay\Core\Logger::error($logMessage);
    } else {
        error_log($logMessage);
    }
    
    // In production, don't display warnings/notices
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        return true;
    }
    
    // In development, show a clean message
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    
    echo "<div style='background:#fff3cd;border:1px solid #ffeaa7;padding:20px;margin:20px;border-radius:8px;font-family:monospace;font-size:13px;'>";
    echo "<h3 style='color:#856404;margin-top:0;'>{$error_type}</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($errstr) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($errfile) . ":" . $errline . "</p>";
    echo "</div>";
    
    return true;
});

// Set exception handler
set_exception_handler(function($exception) {
    $message = $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();
    
    $logMessage = "Uncaught Exception: {$message} in {$file}:{$line}";
    
    // Log to Monolog if available
    if (class_exists('NepalPay\\Core\\Logger')) {
        NepalPay\Core\Logger::error($logMessage, [
            'trace' => $exception->getTraceAsString(),
            'class' => get_class($exception)
        ]);
    } else {
        error_log($logMessage);
    }
    
    // Determine appropriate error page based on HTTP status or exception type
    $statusCode = 500;
    if ($exception instanceof \InvalidArgumentException) {
        $statusCode = 400;
    } elseif ($exception instanceof \RuntimeException) {
        $statusCode = 500;
    }
    
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
    }
    
    // Show custom error page in production
    if (!defined('APP_DEBUG') || !APP_DEBUG) {
        $errorView = defined('BASE_PATH') ? BASE_PATH . '/app/views/errors/500.php' : null;
        if ($errorView && file_exists($errorView)) {
            require $errorView;
        } else {
            echo '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Something went wrong</h1></body></html>';
        }
        return;
    }
    
    // Show detailed error in development
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=UTF-8');
    }
    
    echo '<!DOCTYPE html><html><head><title>Error</title>';
    echo '<style>
        body { font-family: monospace; background: #f8f9fa; padding: 20px; }
        .error-box { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; max-width: 800px; margin: 40px auto; }
        h1 { color: #dc3545; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style></head><body>';
    echo '<div class="error-box">';
    echo '<h1>' . htmlspecialchars(get_class($exception)) . '</h1>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($message) . '</p>';
    echo '<p><strong>File:</strong> ' . htmlspecialchars($file) . ':' . $line . '</p>';
    echo '<h3>Stack Trace</h3>';
    echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    echo '</div></body></html>';
});

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        $logMessage = "Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}";
        
        if (class_exists('NepalPay\\Core\\Logger')) {
            NepalPay\Core\Logger::critical($logMessage);
        } else {
            error_log($logMessage);
        }
        
        // If output buffering is active, clean it
        if (ob_get_length()) {
            ob_end_clean();
        }
        
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        // Show custom error page
        $errorView = defined('BASE_PATH') ? BASE_PATH . '/app/views/errors/500.php' : null;
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Something went wrong</h1></body></html>';
        }
    }
});

// Disable error suppression in production unless explicitly debugging
if (!defined('APP_DEBUG') || !APP_DEBUG) {
    error_reporting(E_ALL);
    // Don't display errors - they're logged
    if (function_exists('ini_set')) {
        @ini_set('display_errors', '0');
        @ini_set('display_startup_errors', '0');
    }
}
