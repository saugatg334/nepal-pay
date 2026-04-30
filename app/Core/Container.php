<?php
declare(strict_types=1);

namespace NepalPay\Core;

/**
 * Simple Dependency Injection Container
 * 
 * WHY: Manual instantiation creates tight coupling. A DI container
 *      allows services to be swapped, mocked in tests, and configured
 *      in one place. This is a minimal PSR-11 compatible implementation.
 */
class Container
{
    private static array $bindings = [];
    private static array $singletons = [];
    private static array $instances = [];

    /**
     * Register a binding (new instance each time)
     */
    public static function bind(string $abstract, callable|string $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }

    /**
     * Register a singleton (same instance each time)
     */
    public static function singleton(string $abstract, callable|string $concrete): void
    {
        self::$singletons[$abstract] = $concrete;
    }

    /**
     * Resolve a binding
     */
    public static function make(string $abstract)
    {
        // Return cached singleton
        if (isset(self::$instances[$abstract])) {
            return self::$instances[$abstract];
        }

        // Resolve singleton
        if (isset(self::$singletons[$abstract])) {
            $concrete = self::$singletons[$abstract];
            $instance = is_callable($concrete) ? $concrete(self::class) : new $concrete();
            self::$instances[$abstract] = $instance;
            return $instance;
        }

        // Resolve binding
        if (isset(self::$bindings[$abstract])) {
            $concrete = self::$bindings[$abstract];
            return is_callable($concrete) ? $concrete(self::class) : new $concrete();
        }

        // Auto-resolve class
        if (class_exists($abstract)) {
            return new $abstract();
        }

        throw new \Exception("Cannot resolve {$abstract}");
    }

    public static function has(string $abstract): bool
    {
        return isset(self::$bindings[$abstract]) 
            || isset(self::$singletons[$abstract]) 
            || isset(self::$instances[$abstract])
            || class_exists($abstract);
    }
}

