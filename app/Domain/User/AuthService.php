<?php
/**
 * AuthService - Business logic for authentication
 */
require_once __DIR__ . '/../../Infrastructure/Database/Repositories/UserRepository.php';

class AuthService {
    private $userRepo;

    public function __construct(UserRepository $userRepo) {
        $this->userRepo = $userRepo;
    }

    public function register($full_name, $phone, $password) {
        // Validation logic here
        if (empty($full_name) || empty($phone) || empty($password)) {
            throw new InvalidArgumentException("All fields required");
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        // Use repo for insert (implement register in repo)
        return true;
    }

    public function verifyLogin($identifier, $password) {
        $user = $this->userRepo->findByPhone($identifier);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
?>

