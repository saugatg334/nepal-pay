<?php

namespace App\Core;

class SecureSession {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            session_start();
            session_regenerate_id(true);
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public static function csrf() {
        return $_SESSION['csrf_token'] ?? '';
    }

    public static function checkCsrf($token) {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public static function destroy() {
        session_destroy();
    }

    public static function userId() {
        return self::get('user_id');
    }

    public static function isLoggedIn() {
        return self::userId() !== null;
    }
}

