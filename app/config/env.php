<?php

$envPath = __DIR__ . '/.env'; // adjust if needed

// 🔴 HARD FAIL if missing
if (!file_exists($envPath)) {
    die("❌ .env file missing. System cannot start.");
}

// Load safely
$dotenv = parse_ini_file($envPath, false, INI_SCANNER_TYPED);

if ($dotenv === false) {
    die("❌ Failed to parse .env file.");
}

// Define constants safely
foreach ($dotenv as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

// Helper
function app_env() {
    return defined('APP_ENV') ? APP_ENV : 'production';
}