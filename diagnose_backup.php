<?php
/**
 * Diagnostic Script - Tests all critical paths after restructuring
 */

echo "=== NepalPay Wallet - Production Diagnostic ===\n\n";

// Test 1: Base paths
echo "[1/10] BASE_PATH: ";
define('BASE_PATH', dirname(__DIR__) . '/wallet');
$basePath = dirname(__DIR__) . '\\wallet';
echo ($basePath ? "OK ($basePath)\n" : "FAIL\n");

// Test 2: App structure
echo "[2/10] App directories: ";
$dirs = ['app', 'app/config', 'app/controller', 'app/models', 'app/views', 'app/helpers', 'public', 'database/migrations', 'logs', 'uploads'];
$allOk = true;
foreach ($dirs as $d) {
    $path = __DIR__ . '/' . $d;
    if (!is_dir($path)) {
        echo "FAIL (missing $d)\n";
        $allOk = false;
        break;
    }
}
if ($allOk) echo "OK\n";

// Test 3: Config
echo "[3/10] Config (Legacy): ";
try {
    require_once __DIR__ . '/app/config/config.php';
    echo "OK (APP_NAME=" . APP_NAME . ")\n";
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
}

// Test 4: Database
echo "[4/10] Database: ";
try {
    require_once __DIR__ . '/app/config/database.php';
    $conn = Database::getConnection();
    echo "OK (connected to " . Config::get('DB_NAME', 'unknown') . ")\n";
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
}

// Test 5: Session
echo "[5/10] Session: ";
try {
    require_once __DIR__ . '/app/helpers/session.php';
    Session::init();
    echo "OK (id=" . session_id() . ")\n";
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
}

// Test 6: Models
echo "[6/10] Models: ";
try {
    require_once __DIR__ . '/app/models/Model.php';
    require_once __DIR__ . '/app/models/User.php';
    require_once __DIR__ . '/app/models/Wallet.php';
    echo "OK (User, Wallet, Model)\n";
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
}

// Test 7: Controller
echo "[7/10] Controller base: ";
try {
    require_once __DIR__ . '/app/controller/Controller.php';
    echo "OK\n";
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
}

// Test 8: AuthController
echo "[8/10] AuthController: ";
try {
    require_once __DIR__ . '/app/controller/AuthController.php';
    echo "OK\n";
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
}

// Test 9: WalletController (checks requireLogin exists)
echo "[9/10] WalletController: ";
try {
    require_once __DIR__ . '/app/controller/WalletController.php';
    // Verify requireLogin exists via reflection check
    $rc = new ReflectionClass('Controller');
    if ($rc->hasMethod('requireLogin') && $rc->hasMethod('requireAdmin')) {
        echo "OK (requireLogin, requireAdmin present)\n";
    } else {
        echo "FAIL (missing requireLogin/requireAdmin)\n";
    }
} catch (Exception $e) {
    echo "FAIL (" . $e->getMessage() . ")\n";
}

// Test 10: Views
echo "[10/10] Views: ";
$views = ['app/views/auth/login.php', 'app/views/layouts/main.php'];
$ok = true;
foreach ($views as $v) {
    if (!file_exists(__DIR__ . '/' . $v)) {
        echo "FAIL (missing $v)\n";
        $ok = false;
        break;
    }
}
if ($ok) echo "OK\n";

echo "\n=== Production Infrastructure Files ===\n";
$prodFiles = [
    'app/Core/Config.php',
    'app/Core/App.php',
    'app/Core/Logger.php',
    'app/Core/Container.php',
    'app/Services/SecurityService.php',
    'app/Helpers/Production/RBAC.php',
    'app/Helpers/Production/Input.php',
    'app/Helpers/Production/RateLimiter.php',
    'app/controller/ApiController.php',
];
foreach ($prodFiles as $f) {
    $exists = file_exists(__DIR__ . '/' . $f);
    echo "  " . ($exists ? "✅" : "❌") . " $f\n";
}

echo "\n=== Diagnostic Complete ===\n";

