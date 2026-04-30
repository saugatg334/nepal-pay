<?php
declare(strict_types=1);

namespace NepalPay\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Structured Logging with Monolog
 * 
 * WHY: error_log() is unstructured and hard to parse in production.
 *      Monolog provides log levels, channels, rotation, and formatters.
 *      Critical for debugging production issues and security auditing.
 */
class Logger
{
    private static array $loggers = [];
    private static string $logPath;
    private static string $logLevel;

    public static function initialize(string $basePath): void
    {
        self::$logPath = $basePath . '/logs';
        self::$logLevel = Config::getString('LOG_LEVEL', 'debug');

        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    public static function channel(string $name = 'app'): MonologLogger
    {
        if (!isset(self::$logLevel) || !isset(self::$logPath)) {
            self::initialize(dirname(__DIR__, 2));
        }

        if (isset(self::$loggers[$name])) {
            return self::$loggers[$name];
        }

        $logger = new MonologLogger($name);
        
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );

        $level = self::parseLevel(self::$logLevel);
        
        // Daily rotating handler
        $handler = new RotatingFileHandler(
            self::$logPath . "/{$name}.log",
            Config::getInt('LOG_MAX_FILES', 30),
            $level
        );
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);

        // Also log errors to dedicated error channel
        if ($name !== 'error') {
            $errorHandler = new StreamHandler(
                self::$logPath . '/error.log',
                MonologLogger::ERROR
            );
            $errorHandler->setFormatter($formatter);
            $logger->pushHandler($errorHandler);
        }

        self::$loggers[$name] = $logger;
        return $logger;
    }

    public static function debug(string $message, array $context = [], string $channel = 'app'): void
    {
        self::channel($channel)->debug($message, $context);
    }

    public static function info(string $message, array $context = [], string $channel = 'app'): void
    {
        self::channel($channel)->info($message, $context);
    }

    public static function warning(string $message, array $context = [], string $channel = 'app'): void
    {
        self::channel($channel)->warning($message, $context);
    }

    public static function error(string $message, array $context = [], string $channel = 'app'): void
    {
        self::channel($channel)->error($message, $context);
    }

    public static function critical(string $message, array $context = [], string $channel = 'app'): void
    {
        self::channel($channel)->critical($message, $context);
    }

    public static function alert(string $message, array $context = [], string $channel = 'app'): void
    {
        self::channel($channel)->alert($message, $context);
    }

    public static function emergency(string $message, array $context = [], string $channel = 'app'): void
    {
        self::channel($channel)->emergency($message, $context);
    }

    public static function security(string $message, array $context = []): void
    {
        self::channel('security')->warning($message, $context);
    }

    private static function parseLevel(string $level): int
    {
        return match (strtolower($level)) {
            'debug' => MonologLogger::DEBUG,
            'info' => MonologLogger::INFO,
            'notice' => MonologLogger::NOTICE,
            'warning' => MonologLogger::WARNING,
            'error' => MonologLogger::ERROR,
            'critical' => MonologLogger::CRITICAL,
            'alert' => MonologLogger::ALERT,
            'emergency' => MonologLogger::EMERGENCY,
            default => MonologLogger::DEBUG,
        };
    }
}
