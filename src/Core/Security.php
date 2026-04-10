<?php

namespace App\Core;

class Security
{
    public static function csrf()
    {
        if (!Session::get('csrf')) {
            Session::set('csrf', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf');
    }

    public static function verify($token)
    {
        $sessionToken = Session::get('csrf');

        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            die('CSRF validation failed');
        }
    }
}

