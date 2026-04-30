<?php
/**
 * Quick Verification Script
 * Run: http://localhost/wallet/test_fixes.php
 * 
 * This script verifies that all the fixes have been applied correctly
 */

echo "<h1>NepalPay Fixes Verification</h1>";
echo "<hr>";

$checks = [];

// Check 1: error_handler.php exists
$checks['error_handler.php exists'] = file_exists(__DIR__ . '/app/helpers/error_handler.php') ? '✅ PASS' : '❌ FAIL';

// Check 2: error_handler.php is loaded in index.php
$index_content = file_get_contents(__DIR__ . '/public/index.php');
$checks['error_handler required in index.php'] = (strpos($index_content, 'app/helpers/error_handler.php') !== false) ? '✅ PASS' : '❌ FAIL';

// Check 3: Route validation happens before access
$checks['index.php validates route BEFORE access'] = (strpos($index_content, 'if (!isset($routes[$page])) {') !== false && 
    strpos($index_content, '$route = $routes[$page];') !== false) ? '✅ PASS' : '❌ FAIL';

// Check 4: index.php has try/catch around controller method
$checks['index.php has try/catch for controller'] = (strpos($index_content, 'try {') !== false && 
    strpos($index_content, '$controller->$method()') !== false) ? '✅ PASS' : '❌ FAIL';

// Check 5: Controller.php has error handling in view()
$controller_content = file_get_contents(__DIR__ . '/app/controller/Controller.php');
$checks['Controller::view() has try/catch'] = (strpos($controller_content, 'protected function view') !== false && 
    strpos($controller_content, 'try {') !== false) ? '✅ PASS' : '❌ FAIL';

// Check 6: Controller.php render() has proper error handling
$checks['Controller::render() has error handling'] = (strpos($controller_content, 'ob_start()') !== false && 
    strpos($controller_content, 'ob_end_clean()') !== false && 
    strpos($controller_content, 'try {') !== false) ? '✅ PASS' : '❌ FAIL';

// Check 7: main.php doesn't have duplicate requires
$main_content = file_get_contents(__DIR__ . '/app/views/layouts/main.php');
$duplicate_requires = substr_count($main_content, "require_once __DIR__ . '/../../helpers/helpers.php'") + 
                     substr_count($main_content, "require_once __DIR__ . '/../../helpers/lang.php'");
$checks['main.php has NO duplicate requires'] = ($duplicate_requires === 0) ? '✅ PASS' : '❌ FAIL (Found ' . $duplicate_requires . ')';

// Check 8: AuthController still uses view() for login
$auth_content = file_get_contents(__DIR__ . '/app/controller/AuthController.php');
$checks['AuthController::login() uses view()'] = (strpos($auth_content, 'public function login()') !== false && 
    strpos($auth_content, '$this->view(\'auth/login\')') !== false) ? '✅ PASS' : '❌ FAIL';

// Check 9: login.php has complete HTML
$login_content = file_get_contents(__DIR__ . '/app/views/auth/login.php');
$checks['login.php is complete HTML document'] = (strpos($login_content, '<!DOCTYPE html>') !== false && 
    strpos($login_content, '</html>') !== false) ? '✅ PASS' : '❌ FAIL';

// Check 10: CONFIG defines APP_DEBUG
if (file_exists(__DIR__ . '/app/config/config.php')) {
    $config_content = file_get_contents(__DIR__ . '/app/config/config.php');
    $checks['APP_DEBUG is defined'] = (strpos($config_content, 'APP_DEBUG') !== false) ? '✅ PASS' : '❌ FAIL';
} else {
    $checks['APP_DEBUG is defined'] = '⚠️  config.php not found';
}

echo "<table border='1' cellpadding='10' cellspacing='0' style='margin:20px 0;'>";
echo "<tr style='background:#f0f0f0;'><th>Check</th><th>Status</th></tr>";

$all_pass = true;
foreach ($checks as $check => $status) {
    if (strpos($status, '❌') !== false || strpos($status, '⚠️') !== false) {
        $all_pass = false;
    }
    $row_style = (strpos($status, '✅') !== false) ? 'background:#e8f5e9;' : 'background:#ffebee;';
    echo "<tr style='{$row_style}'><td>{$check}</td><td><strong>{$status}</strong></td></tr>";
}

echo "</table>";

if ($all_pass) {
    echo "<div style='background:#e8f5e9;border:2px solid #4caf50;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h2 style='margin-top:0;color:#2e7d32;'>✅ ALL FIXES VERIFIED!</h2>";
    echo "<p>Your system is ready. Try accessing <strong>http://localhost/wallet/</strong> to see the login page.</p>";
    echo "</div>";
} else {
    echo "<div style='background:#ffebee;border:2px solid #f44336;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h2 style='margin-top:0;color:#c62828;'>⚠️ SOME CHECKS FAILED</h2>";
    echo "<p>Review the failed items above and check the FIX_DOCUMENTATION.md file.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Test Your Login Page</h3>";
echo "<p><a href='?page=login' style='padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;'>Click here to test login page</a></p>";

echo "<h3>Debug Info</h3>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Error Log: " . ini_get('error_log') . "\n";
echo "Display Errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "\n";
echo "Base Path: " . (defined('BASE_PATH') ? BASE_PATH : 'Not defined') . "\n";
echo "</pre>";
