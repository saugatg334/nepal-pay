<?php
require_once __DIR__ . '/Model.php';

class BankAccount extends Model {
    protected $table = 'bank_accounts';
    protected $fillable = ['user_id', 'bank_name', 'account_number', 'account_holder_name', 'is_verified', 'is_default'];
    protected $primaryKey = 'id';

    public static function findByUser($userId) {
        $sql = "SELECT * FROM bank_accounts WHERE user_id = ? ORDER BY is_default DESC, created_at DESC";
        return Database::fetchAll($sql, [$userId]);
    }

    public static function findById($id) {
        return self::findBy('id', $id);
    }

    public static function add($data) {
        $data['is_verified'] = 0;
        
        $count = self::count(['user_id' => $data['user_id']]);
        if ($count === 0) {
            $data['is_default'] = 1;
        }
        
        return self::create($data);
    }

    public static function verify($id) {
        $sql = "UPDATE bank_accounts SET is_verified = 1 WHERE id = ?";
        return Database::query($sql, [$id]);
    }

    public static function setDefault($userId, $id) {
        Database::beginTransaction();
        
        try {
            $sql = "UPDATE bank_accounts SET is_default = 0 WHERE user_id = ?";
            Database::query($sql, [$userId]);
            
            $sql = "UPDATE bank_accounts SET is_default = 1 WHERE id = ? AND user_id = ?";
            Database::query($sql, [$id, $userId]);
            
            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function remove($id, $userId) {
        $sql = "DELETE FROM bank_accounts WHERE id = ? AND user_id = ?";
        return Database::query($sql, [$id, $userId]);
    }

    public static function getDefault($userId) {
        $sql = "SELECT * FROM bank_accounts WHERE user_id = ? AND is_default = 1";
        return Database::fetch($sql, [$userId]);
    }
}