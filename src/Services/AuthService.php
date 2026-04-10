<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Core\Session;

class AuthService
{
    private $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

public function login($email, $password)
    {
        if (empty($email) || empty($password)) {
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/^[0-9]{10}$/', $email)) {
            return false;
        }

        $attempts = Session::get('login_attempts') ?? 0;
        if ($attempts >= 5) {
            return false;
        }

        $user = $this->userRepo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            Session::set('login_attempts', $attempts + 1);
            return false;
        }

        Session::set('login_attempts', 0);
        Session::set('user_id', $user['id']);
        Session::set('user_role', $user['role'] ?? 'user');

        Session::regenerate();

        return $user;
    }


    public function logout()
    {
        Session::destroy();
    }

    public function check()
    {
        return Session::get('user_id') !== null;
    }

    public function user()
    {
        return Session::get('user_id');
    }
}

