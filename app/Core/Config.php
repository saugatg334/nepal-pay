<?php
declare(strict_types=1);

namespace NepalPay\Core;

use Dotenv\Dotenv;

/**
 * Production Configuration Manager
 * 
 * WHY: Dotenv handles quotes, comments, multiline values, and escaping
 *      properly. Manual parsing is fragile and a security risk.
 *      Type casting ensures booleans/numerics are correct types.
 */
class Config
{
    private static ?Dotenv $dotenv = null;
    private static bool $loaded = false;
    private static array $cache = [];

    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }

        $envPath = $basePath . '/app/config';
        
        if (file_exists($envPath . '/.env')) {
            self::$dotenv = Dotenv::createImmutable($envPath);
            self::$dotenv->safeLoad();
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            return $default;
        }

        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        
        // Type casting for common patterns
        if (is_string($value)) {
            $value = self::castValue($value);
        }

        self::$cache[$key] = $value;
        return $value;
    }

    public static function getString(string $key, string $default = ''): string
    {
        return (string) self::get($key, $default);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        return (bool) self::get($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        self::$cache[$key] = $value;
    }

    private static function castValue(string $value): mixed
    {
        $lower = strtolower($value);
        
        if ($lower === 'true') {
            return true;
        }
        if ($lower === 'false') {
            return false;
        }
        if ($lower === 'null') {
            return null;
        }
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }
        
        return $value;
    }
}

