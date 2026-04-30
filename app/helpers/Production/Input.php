<?php
declare(strict_types=1);

namespace NepalPay\Helpers\Production;

/**
 * Input Sanitization & Validation Helper
 * 
 * WHY: Direct $_GET/$_POST access is dangerous. A wrapper ensures:
 *      - Type casting (getInt, getBool, getString)
 *      - XSS filtering for non-HTML contexts
 *      - Null coalescing with defaults
 *      - Consistent trimming
 */
class Input
{
    /**
     * Get a string value from request (GET/POST)
     */
    public static function string(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Get an integer value
     */
    public static function int(string $key, int $default = 0): int
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $default;
    }

    /**
     * Get a float value
     */
    public static function float(string $key, float $default = 0.0): float
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? (float) $value : $default;
    }

    /**
     * Get a boolean value
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Get an email value (validated)
     */
    public static function email(string $key, string $default = ''): string
    {
        $value = self::string($key, $default);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ?: $default;
    }

    /**
     * Get raw input (for JSON bodies)
     */
    public static function json(string $key = null, mixed $default = null): mixed
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if ($key === null) {
            return $data ?? $default;
        }
        
        return $data[$key] ?? $default;
    }

    /**
     * Check if a key exists in the request
     */
    public static function has(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    /**
     * Get all input as array
     */
    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Escape output for HTML contexts
     */
    public static function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize for database (basic, prepared statements preferred)
     */
    public static function sanitize(string $input): string
    {
        $input = trim($input);
        $input = stripslashes($input);
        return $input;
    }

    /**
     * Get client IP address
     */
    public static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? 'unknown';
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            || ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
    }

    /**
     * Check if request method is POST
     */
    public static function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Get bearer token from Authorization header
     */
    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        
        return null;
    }
}

