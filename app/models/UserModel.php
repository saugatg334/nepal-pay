<?php
/**
 * User Model
 */

class UserModel
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Find user by email or username
     */
    public function findByEmailOrUsername($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? OR username = ?";
        return $this->db->fetch($sql, [$email, $email]);
    }

    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }

    /**
     * Find user by ID
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Find user by username
     */
    public function findByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE username = ?";
        return $this->db->fetch($sql, [$username]);
    }

    /**
     * Find user by phone
     */
    public function findByPhone($phone)
    {
        $sql = "SELECT * FROM users WHERE phone = ?";
        return $this->db->fetch($sql, [$phone]);
    }

    /**
     * Create new user
     */
    public function create($data)
    {
        $sql = "INSERT INTO users (name, email, phone, username, password, kyc_status, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $values = [
            $data['name'],
            $data['email'],
            $data['phone'],
            $data['username'],
            $data['password'],
            $data['kyc_status'] ?? 'pending',
            $data['is_active'] ?? true
        ];

        return $this->db->insert($sql, $values);
    }

    /**
     * Update user
     */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        return $this->db->update($sql, $values);
    }

    /**
     * Update last login
     */
    public function updateLastLogin($id)
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        return $this->db->update($sql, [$id]);
    }

    /**
     * Get all users (admin)
     */
    public function getAllUsers($limit = 50, $offset = 0)
    {
        $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }
}
