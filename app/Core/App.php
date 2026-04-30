<?php
declare(strict_types=1);

namespace NepalPay\Core;

/**
 * Application Bootstrapper
 * 
 * WHY: Centralizes initialization logic. Instead of scattered
 *      require_once and config calls in index.php, everything
 *      boots from one place. Makes testing and maintenance easier.
 */
class App
{
    private static bool $booted = false;
    private static string $basePath;

    public static function boot(string $basePath): void
    {
        if (self::$booted) {
            return;
        }

        self::$basePath = $basePath;

        // Load environment configuration
        Config::load($basePath);

        // Initialize structured logging
        Logger::initialize($basePath);

        // Set timezone
        date_default_timezone_set(Config::getString('APP_TIMEZONE', 'Asia/Kathmandu'));

        // Configure error reporting based on environment
        $isProduction = Config::getString('APP_ENV', 'development') === 'production';
        
        if ($isProduction) {
            error_reporting(E_ALL);
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
        }

        ini_set('log_errors', '1');
        ini_set('error_log', $basePath . '/logs/php_errors.log');

        Logger::info('Application booted', ['env' => Config::getString('APP_ENV')]);

        self::$booted = true;
    }

    public static function basePath(string $path = ''): string
    {
        return self::$basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    public static function isProduction(): bool
    {
        return Config::getString('APP_ENV', 'development') === 'production';
    }

    public static function isDebug(): bool
    {
        return Config::getBool('APP_DEBUG', false);
    }
}

