<?php
/**
 * Root Gateway - Forwards to public/index.php
 * 
 * WHY: On a production server, the webroot should point to public/.
 * During development (XAMPP), this file acts as a forward gateway.
 * Production: Point Apache DocumentRoot to project_root/public/
 * 
 * NOTE: We use include instead of redirect to preserve POST data.
 * A redirect would convert POST to GET, breaking form submissions.
 */

// Forward all requests to the public entry point
require __DIR__ . '/public/index.php';

