<?php
require_once __DIR__ . '/Model.php';

class Beneficiary extends Model {
    protected $table = 'beneficiaries';
    protected $fillable = ['user_id', 'beneficiary_user_id', 'nickname', 'is_favorite'];
    protected $primaryKey = 'id';

    public static function findByUser($userId) {
        $sql = "SELECT b.*, u.full_name, u.phone, u.email 
                FROM beneficiaries b 
                JOIN users u ON b.beneficiary_user_id = u.id 
                WHERE b.user_id = ? 
                ORDER BY b.is_favorite DESC, b.created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    public static function add($userId, $beneficiaryUserId, $nickname = null) {
        $sql = "INSERT INTO beneficiaries (user_id, beneficiary_user_id, nickname) VALUES (?, ?, ?)";
        return Database::query($sql, [$userId, $beneficiaryUserId, $nickname]);
    }

    public static function remove($userId, $beneficiaryUserId) {
        $sql = "DELETE FROM beneficiaries WHERE user_id = ? AND beneficiary_user_id = ?";
        return Database::query($sql, [$userId, $beneficiaryUserId]);
    }

    public static function updateNickname($id, $nickname) {
        $sql = "UPDATE beneficiaries SET nickname = ? WHERE id = ?";
        return Database::query($sql, [$nickname, $id]);
    }

    public static function toggleFavorite($id) {
        $sql = "UPDATE beneficiaries SET is_favorite = NOT is_favorite WHERE id = ?";
        return Database::query($sql, [$id]);
    }

    public static function isBeneficiary($userId, $beneficiaryUserId) {
        $sql = "SELECT COUNT(*) as total FROM beneficiaries WHERE user_id = ? AND beneficiary_user_id = ?";
        $result = Database::fetch($sql, [$userId, $beneficiaryUserId]);
        return $result['total'] > 0;
    }

    public static function getFavorites($userId) {
        $sql = "SELECT b.*, u.full_name, u.phone, u.email 
                FROM beneficiaries b 
                JOIN users u ON b.beneficiary_user_id = u.id 
                WHERE b.user_id = ? AND b.is_favorite = 1
                ORDER BY b.created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    public static function searchByPhone($userId, $phone) {
        $sql = "SELECT id, full_name, phone FROM users 
                WHERE phone LIKE ? AND id != ? 
                LIMIT 10";
        return Database::fetchAll($sql, ["%{$phone}%", $userId]);
    }

    public static function searchByEmail($userId, $email) {
        $sql = "SELECT id, full_name, email FROM users 
                WHERE email LIKE ? AND id != ? 
                LIMIT 10";
        return Database::fetchAll($sql, ["%{$email}%", $userId]);
    }

    public static function getWithBanks($userId) {
        $sql = "SELECT b.*, u.full_name, u.phone, u.email, u2.bank_name, u2.account_number, u2.account_holder_name, u2.is_default as bank_default
                FROM beneficiaries b 
                JOIN users u ON b.beneficiary_user_id = u.id
                LEFT JOIN bank_accounts u2 ON u.id = u2.user_id AND u2.is_default = 1
                WHERE b.user_id = ? 
                ORDER BY b.is_favorite DESC, b.created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }
}
