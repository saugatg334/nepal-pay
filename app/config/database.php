<?php
/**
 * Database Connection (Legacy Wrapper)
 * 
 * This class maintains backward compatibility while using
 * NepalPay\Core\Config for configuration.
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $pdo = null;
    private static $connected = false;

    public static function getConnection() {
        if (self::$connected && self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            // Use Config which now delegates to Core\Config (Dotenv-based)
            $host = Config::get('DB_HOST', 'localhost');
            $port = Config::get('DB_PORT', '3306');
            $dbname = Config::get('DB_NAME', 'wallet');
            $user = Config::get('DB_USER', 'root');
            $pass = Config::get('DB_PASS', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];

            self::$pdo = new PDO($dsn, $user, $pass, $options);
            self::$connected = true;
            
            return self::$pdo;
        } catch (PDOException $e) {
            // Log error via Monolog if available, otherwise error_log
            if (class_exists('NepalPay\\Core\\Logger')) {
                \NepalPay\Core\Logger::error('Database connection failed', [
                    'error' => $e->getMessage()
                ]);
            }
            error_log('Database Connection Error: ' . $e->getMessage());
            
            if (APP_DEBUG) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }

    public static function beginTransaction() {
        return self::getConnection()->beginTransaction();
    }

    public static function commit() {
        return self::getConnection()->commit();
    }

    public static function rollBack() {
        return self::getConnection()->rollBack();
    }

    public static function lastInsertId() {
        return self::getConnection()->lastInsertId();
    }

    public static function query($sql, $params = []) {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function fetch($sql, $params = []) {
        $stmt = self::query($sql, $params);
        return $stmt->fetch();
    }
}