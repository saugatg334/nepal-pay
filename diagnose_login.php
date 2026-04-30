<?php
/**
 * NepalPay Login Diagnostic Script
 * Tests every component in the login chain to find the exact failure point
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html>
<head>
    <title>NepalPay Login Diagnostics</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f172a; color: #e2e8f0; }
        .pass { color: #10b981; background: #064e3b; padding: 8px 12px; border-radius: 6px; margin: 4px 0; }
        .fail { color: #ef4444; background: #7f1d1d; padding: 8px 12px; border-radius: 6px; margin: 4px 0; }
        .warn { color: #f59e0b; background: #78350f; padding: 8px 12px; border-radius: 6px; margin: 4px 0; }
        .info { color: #3b82f6; background: #1e3a5f; padding: 8px 12px; border-radius: 6px; margin: 4px 0; }
        h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        pre { background: #1e293b; padding: 10px; border-radius: 6px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #334155; padding: 8px; text-align: left; }
        th { background: #1e293b; }
    </style>
</head>
<body>
    <h1>🔍 NepalPay Login Diagnostic Report</h1>
";

$errors = [];
$warnings = [];

function test($name, $callable) {
    try {
        $result = $callable();
        if ($result === true) {
            echo "<div class='pass'>✅ PASS: $name</div>";
            return true;
        } elseif ($result === false) {
            echo "<div class='fail'>❌ FAIL: $name</div>";
            return false;
        } else {
            echo "<div class='info'>ℹ️ INFO: $name = " . htmlspecialchars((string)$result) . "</div>";
            return $result;
        }
    } catch (Throwable $e) {
        echo "<div class='fail'>❌ EXCEPTION in $name: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        return false;
    }
}

// ===== PHASE 1: Basic Environment =====
echo "<h2>1️⃣ Environment & Paths</h2>";
$basePath = dirname(__DIR__);
test("BASE_PATH exists", fn() => file_exists($basePath) ? "yes ($basePath)" : false);
test("Composer autoload exists", fn() => file_exists("$basePath/vendor/autoload.php"));
test("PHP Version", fn() => PHP_VERSION);
test("Display errors", fn() => ini_get('display_errors'));

// ===== PHASE 2: Core Files =====
echo "<h2>2️⃣ Core Files Load Test</h2>";
test("config.php", fn() => (@require_once "$basePath/app/config/config.php") === null);
test("database.php", fn() => (@require_once "$basePath/app/config/database.php") === null);
test("session.php", fn() => (@require_once "$basePath/app/helpers/session.php") === null);
test("csrf.php", fn() => (@require_once "$basePath/app/helpers/csrf.php") === null);
test("helpers.php", fn() => (@require_once "$basePath/app/helpers/helpers.php") === null);

// ===== PHASE 3: Class Availability =====
echo "<h2>3️⃣ Class/Function Availability</h2>";
test("Session class", fn() => class_exists('Session'));
test("CSRF class", fn() => class_exists('CSRF'));
test("Database class", fn() => class_exists('Database'));
test("Config class (global)", fn() => class_exists('Config'));
test("flash() function", fn() => function_exists('flash'));
test("redirect() function", fn() => function_exists('redirect'));
test("APP_URL defined", fn() => defined('APP_URL') ? APP_URL : false);
test("APP_NAME defined", fn() => defined('APP_NAME') ? APP_NAME : false);

// ===== PHASE 4: Namespaced Classes =====
echo "<h2>4️⃣ Namespaced Class Availability</h2>";
test("\\NepalPay\\Services\\AuthService", fn() => class_exists('\\NepalPay\\Services\\AuthService'));
test("\\NepalPay\\Core\\Logger", fn() => class_exists('\\NepalPay\\Core\\Logger'));
test("\\NepalPay\\Core\\Config", fn() => class_exists('\\NepalPay\\Core\\Config'));
test("\\NepalPay\\Helpers\\Production\\RateLimiter", fn() => class_exists('\\NepalPay\\Helpers\\Production\\RateLimiter'));
test("\\NepalPay\\Helpers\\Production\\Input", fn() => class_exists('\\NepalPay\\Helpers\\Production\\Input'));
test("\\NepalPay\\Helpers\\Production\\RBAC", fn() => class_exists('\\NepalPay\\Helpers\\Production\\RBAC'));

// ===== PHASE 5: Model Classes =====
echo "<h2>5️⃣ Model Classes</h2>";
test("User model", fn() => class_exists('User'));
test("Wallet model", fn() => class_exists('Wallet'));
test("Transaction model", fn() => class_exists('Transaction'));

// ===== PHASE 6: Database Connection =====
echo "<h2>6️⃣ Database Connection</h2>";
test("DB connect", function() {
    try {
        $db = Database::getConnection();
        return $db ? "Connected" : false;
    } catch (Throwable $e) {
        return "Error: " . $e->getMessage();
    }
});

// ===== PHASE 7: Table Existence =====
echo "<h2>7️⃣ Required Database Tables</h2>";
$requiredTables = ['users', 'wallets', 'transactions', 'sessions', 'otp_tokens', 'password_resets', 'login_attempts', 'security_logs', 'admin_actions'];
foreach ($requiredTables as $table) {
    test("Table: $table", function() use ($table) {
        try {
            $result = Database::fetch("SHOW TABLES LIKE ?", [$table]);
            return $result ? "EXISTS" : "MISSING";
        } catch (Throwable $e) {
            return "Error: " . $e->getMessage();
        }
    });
}

// ===== PHASE 8: User Lookup =====
echo "<h2>8️⃣ User Lookup Test
