<?php
/**
 * Base Eloquent-like Model
 * 
 * Security: Prevents SQL injection via dynamic field names by whitelisting
 * allowed columns. NEVER pass user input as $field to findBy/where.
 * 
 * All models should define $fillable (allowed mass-assignment fields).
 * findBy() only permits fields in $fillable (or primary key).
 */

require_once __DIR__ . '/../config/database.php';

class Model {
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $dates = ['created_at', 'updated_at'];

    public function __construct() {
        if (empty($this->table)) {
            $this->table = strtolower(get_class($this)) . 's';
        }
    }

    /**
     * Validate field name against whitelist
     */
    protected static function validateField(string $field, $model = null): bool {
        if ($model === null) {
            $model = new static();
        }
        // Allow primary key always
        if ($field === $model->primaryKey) {
            return true;
        }
        // If fillable array defined, only those fields allowed
        if (!empty($model->fillable) && is_array($model->fillable)) {
            return in_array($field, $model->fillable, true);
        }
        // No fillable defined -> allow all (not recommended but backward compatible)
        return true;
    }

    public static function query() {
        $model = new static();
        return Database::getConnection()->prepare("SELECT * FROM {$model->table}");
    }

    public static function create($data) {
        $model = new static();
        $fillable = $model->fillable;
        
        $fields = [];
        $placeholders = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (empty($fillable) || in_array($key, $fillable, true)) {
                $fields[] = $key;
                $placeholders[] = '?';
                $values[] = $value;
            }
        }
        
        $sql = "INSERT INTO {$model->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        Database::query($sql, $values);
        return Database::lastInsertId();
    }

    public static function find($id) {
        $model = new static();
        $sql = "SELECT * FROM {$model->table} WHERE {$model->primaryKey} = ?";
        return Database::fetch($sql, [$id]);
    }

    public static function findBy($field, $value) {
        $model = new static();
        // SECURITY: Ensure field is whitelisted
        if (!self::validateField($field, $model)) {
            throw new InvalidArgumentException("Invalid field name: {$field}");
        }
        $sql = "SELECT * FROM {$model->table} WHERE {$field} = ?";
        return Database::fetch($sql, [$value]);
    }

    public static function all() {
        $model = new static();
        $sql = "SELECT * FROM {$model->table}";
        return Database::fetchAll($sql);
    }

    public static function where($conditions, $params = []) {
        $model = new static();
        
        $where = [];
        foreach ($conditions as $key => $value) {
            // Validate field
            if (!self::validateField($key, $model)) {
                throw new InvalidArgumentException("Invalid field in where: {$key}");
            }
            $where[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $sql = "SELECT * FROM {$model->table} WHERE " . implode(' AND ', $where);
        return Database::fetchAll($sql, $params);
    }

    public static function whereFirst($conditions, $params = []) {
        $model = new static();
        
        $where = [];
        foreach ($conditions as $key => $value) {
            if (!self::validateField($key, $model)) {
                throw new InvalidArgumentException("Invalid field in where: {$key}");
            }
            $where[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $sql = "SELECT * FROM {$model->table} WHERE " . implode(' AND ', $where) . " LIMIT 1";
        return Database::fetch($sql, $params);
    }

    public function update($id, $data) {
        $fillable = $this->fillable;
        
        $updates = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (empty($fillable) || in_array($key, $fillable, true)) {
                $updates[] = "{$key} = ?";
                $values[] = $value;
            }
        }
        
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE {$this->primaryKey} = ?";
        
        return Database::query($sql, $values);
    }

    public static function delete($id) {
        $model = new static();
        $sql = "DELETE FROM {$model->table} WHERE {$model->primaryKey} = ?";
        return Database::query($sql, [$id]);
    }

    public static function count($conditions = [], $params = []) {
        $model = new static();
        
        if (empty($conditions)) {
            $sql = "SELECT COUNT(*) as total FROM {$model->table}";
            $result = Database::fetch($sql);
        } else {
            $where = [];
            foreach ($conditions as $key => $value) {
                if (!self::validateField($key, $model)) {
                    throw new InvalidArgumentException("Invalid field in count: {$key}");
                }
                $where[] = "{$key} = ?";
                $params[] = $value;
            }
            $sql = "SELECT COUNT(*) as total FROM {$model->table} WHERE " . implode(' AND ', $where);
            $result = Database::fetch($sql, $params);
        }
        
        return $result['total'] ?? 0;
    }

    public static function paginate($page = 1, $perPage = 20, $conditions = [], $params = []) {
        $model = new static();
        $offset = ($page - 1) * $perPage;
        
        $where = [];
        $paramsArray = $params;
        
        foreach ($conditions as $key => $value) {
            if (!self::validateField($key, $model)) {
                throw new InvalidArgumentException("Invalid field in paginate: {$key}");
            }
            $where[] = "{$key} = ?";
            $paramsArray[] = $value;
        }
        
        if (!empty($where)) {
            $sql = "SELECT * FROM {$model->table} WHERE " . implode(' AND ', $where) . " LIMIT ? OFFSET ?";
        } else {
            $sql = "SELECT * FROM {$model->table} LIMIT ? OFFSET ?";
        }
        $paramsArray[] = $perPage;
        $paramsArray[] = $offset;
        
        $data = Database::fetchAll($sql, $paramsArray);
        $total = self::count($conditions, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }
}