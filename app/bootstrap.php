<?php
/**
 * Application Bootstrap
 * 
 * Centralizes all boot-time setup. Included from public/index.php.
 * WHY: Separating boot logic from routing makes testing, CLI scripts,
 * and maintenance much easier. All production concerns (error handling,
 * logging, session config) are configured here.
 */

// Load Composer autoloader if available (vendor may not exist yet)
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

define('BASE_PATH', dirname(__DIR__)); // Points to project root (above public/)

// Legacy config still works because old Config class is needed by legacy code
require_once __DIR__ . '/config/config.php';

// Initialize logger after config is loaded so typed properties are available
if (class_exists('\NepalPay\Core\Logger')) {
    \NepalPay\Core\Logger::initialize(BASE_PATH);
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/session.php';
require_once __DIR__ . '/helpers/helpers.php';
require_once __DIR__ . '/helpers/lang.php';

require_once __DIR__ . '/helpers/csrf.php';

Session::init();

