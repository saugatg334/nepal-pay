<?php
require_once __DIR__ . '/../config/config.php';

class CSRF {
    private static $tokenName = 'csrf_token';

    public static function generateToken() {
        Session::init();
        
        if (!Session::has(self::$tokenName)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::$tokenName, $token);
        }
        
        return Session::get(self::$tokenName);
    }

    public static function getToken() {
        return Session::get(self::$tokenName);
    }

    public static function validateToken($token) {
        Session::init();
        
        $sessionToken = Session::get(self::$tokenName);
        
        if (!$sessionToken || !$token) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }

    public static function validateRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method === 'GET') {
            return true;
        }
        
        $token = $_POST[self::$tokenName] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        return self::validateToken($token);
    }

    public static function getField() {
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . self::generateToken() . '">';
    }

    public static function getHeader() {
        return self::generateToken();
    }
}