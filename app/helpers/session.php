<?php
// BASE_PATH already defined in config

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

class Session {
    private static $timeout = 1800;
    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            $httponly = true;
            $samesite = Config::get('SESSION_SAMESITE', 'Lax');
            
            session_name(Config::get('SESSION_NAME', 'NEPALPAY_SESSION'));
            
            $secure_cookie = (Config::get('APP_ENV', 'development') === 'production') || (Config::get('SESSION_SECURE', 'false') === 'true');
            $session_domain = Config::get('SESSION_DOMAIN', '');
            
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => $session_domain,
                'secure' => $secure_cookie,
                'httponly' => $httponly,
                'samesite' => $samesite
            ]);
            
            session_start();
        }
        
        self::checkTimeout();
    }
    
    private static function checkTimeout() {
        if (isset($_SESSION['last_activity']) && !empty($_SESSION)) {
            $elapsed = time() - $_SESSION['last_activity'];
            if ($elapsed > self::$timeout) {
                self::destroy();
                header("Location: " . APP_URL . "/index.php?page=login");
                exit;
            }
        }
        $_SESSION['last_activity'] = time();
    }

    public static function set($key, $value) {
        self::init();
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        self::init();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    public static function has($key) {
        self::init();
        return isset($_SESSION[$key]);
    }

    public static function remove($key) {
        self::init();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }

    public static function regenerate() {
        self::init();
        session_regenerate_id(true);
        $_SESSION['last_activity'] = time();
    }
    
    public static function save() {
        // Only close if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
    
    public static function setTimeout($seconds) {
        self::$timeout = $seconds;
    }
    
    public static function all() {
        self::init();
        return $_SESSION;
    }
    
    public static function getDeviceInfo() {
        return [
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'device' => self::getDeviceType(),
            'browser' => self::getBrowser()
        ];
    }
    
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
    
    private static function getDeviceType() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/mobile/i', $userAgent)) {
            return 'Mobile';
        }
        if (preg_match('/tablet/i', $userAgent)) {
            return 'Tablet';
        }
        return 'Desktop';
    }
    
    private static function getBrowser() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/chrome/i', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/firefox/i', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/safari/i', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/edge/i', $userAgent)) {
            return 'Edge';
        }
        return 'Unknown';
    }
}
