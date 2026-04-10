<?php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class UserRepository {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function findByPhone($phone) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE phone = :phone LIMIT 1");
        $stmt->execute(['phone' => $phone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO users (name, phone, password, created_at) VALUES (:name, :phone, :password, NOW())");
        return $stmt->execute([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateBalance($userId, $amount) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT wallet_balance FROM users WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $userId]);
            $balance = $stmt->fetchColumn() ?: 0;

            $newBalance = $balance + $amount;

            $stmt = $this->db->prepare("UPDATE users SET wallet_balance = :balance WHERE id = :id");
            $stmt->execute(['balance' => $newBalance, 'id' => $userId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}

