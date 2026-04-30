<?php
/**
 * Legacy Config Wrapper
 * 
 * WHY: Existing code uses global Config::get(). This wrapper delegates
 * to NepalPay\Core\Config (which uses Dotenv) while preserving
 * backward compatibility. New code should use NepalPay\Core\Config directly.
 */

// Load the new namespaced Config if not already loaded
if (!class_exists('NepalPay\\Core\\Config')) {
    require_once BASE_PATH . '/app/Core/Config.php';
    // Load environment
    \NepalPay\Core\Config::load(BASE_PATH);
}

class Config {
    public static function get($key, $default = null) {
        return \NepalPay\Core\Config::get($key, $default);
    }

    public static function set($key, $value) {
        \NepalPay\Core\Config::set($key, $value);
    }

    public static function all() {
        return \NepalPay\Core\Config::all();
    }

    /**
     * Get string value
     */
    public static function getString($key, $default = '') {
        return \NepalPay\Core\Config::getString($key, $default);
    }

    /**
     * Get integer value
     */
    public static function getInt($key, $default = 0) {
        return \NepalPay\Core\Config::getInt($key, $default);
    }

    /**
     * Get boolean value
     */
    public static function getBool($key, $default = false) {
        return \NepalPay\Core\Config::getBool($key, $default);
    }

    /**
     * Load configuration (legacy method - now no-op)
     * Config is auto-loaded via NepalPay\Core\Config::load()
     */
    public static function load() {
        // Already loaded by Core\Config, no action needed
        // Kept for backward compatibility
    }
}

// Define legacy constants for backward compatibility
// New code should use Config::get() directly
if (!defined('APP_NAME')) {
    define('APP_NAME', Config::get('APP_NAME', 'NepalPay'));
}
if (!defined('APP_URL')) {
    define('APP_URL', Config::get('APP_URL', 'http://localhost/wallet'));
}
if (!defined('APP_ENV')) {
    define('APP_ENV', Config::get('APP_ENV', 'development'));
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', Config::getBool('APP_DEBUG', Config::get('APP_ENV', 'development') !== 'production'));
}
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', Config::get('APP_TIMEZONE', 'Asia/Kathmandu'));
}

// Set timezone
@date_default_timezone_set(APP_TIMEZONE);
