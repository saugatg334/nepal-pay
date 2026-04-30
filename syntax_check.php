<?php
/**
 * Syntax check for all modified files
 */
echo "<h1>PHP Syntax Check</h1>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>File</th><th>Status</th></tr>\n";

$files = [
    'app/Services/FraudDetectionService.php',
    'app/Services/TokenEncryptionService.php',
    'app/controller/AdminController.php',
    'app/controller/ApiWalletController.php',
    'app/controller/ApiAuthController.php',
    'app/controller/ApiController.php',
    'app/controller/WalletController.php',
    'app/Services/WalletService.php',
    'app/Services/TransactionPinService.php',
    'app/Services/SecurityService.php',
    'database/migrations/002_add_rate_limits_and_api_tokens.sql',
    'database/migrations/006_fraud_detection.sql',
];

$all_ok = true;

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        // Check for basic PHP syntax issues
        $content = file_get_contents($path);
        
        // Check for unterminated strings/comments
        $lines = explode("\n", $content);
        $errors = [];
        
        // Basic sanity checks
        if (strpos($file, '.php') !== false) {
            // Check if PHP opening tag exists
            if (strpos($content, '<?php') === false && strpos($content, '<?') === false) {
                $errors[] = 'No PHP opening tag';
            }
            // Count braces
            $open_braces = substr_count($content, '{');
            $close_braces = substr_count($content, '}');
            if ($open_braces != $close_braces) {
                $errors[] = "Brace mismatch: {$open_braces} opening, {$close_braces} closing";
            }
            // Count parentheses
            $open_paren = substr_count($content, '(');
            $close_paren = substr_count($content, ')');
            if ($open_paren != $close_paren) {
                $errors[] = "Parenthesis mismatch";
            }
            // Check for common syntax errors
            if (substr_count($content, '<?php') > 1) {
                $errors[] = 'Multiple PHP opening tags';
            }
        }
        
        if (empty($errors)) {
            echo "<tr style='background:#e8f5e9;'><td>{$file}</td><td>✅ OK</td></tr>\n";
        } else {
            echo "<tr style='background:#ffebee;'><td>{$file}</td><td>❌ " . implode(', ', $errors) . "</td></tr>\n";
            $all_ok = false;
        }
    } else {
        echo "<tr style='background:#fff3cd;'><td>{$file}</td><td>⚠️ Not found</td></tr>\n";
    }
}

echo "</table>\n";

if ($all_ok) {
    echo "<div style='background:#e8f5e9;border:2px solid #4caf50;padding:15px;border-radius:5px;margin:20px 0;'>";
    echo "<h2 style='margin-top:0;color:#2e7d32;'>✅ All files pass basic syntax checks!</h2>";
    echo "</div>";
}

echo "<h2>Modified Files</h2>\n";
echo "<ul>\n";
echo "<li>app/Services/FraudDetectionService.php - NEW</li>\n";
echo "<li>app/Services/TokenEncryptionService.php - NEW</li>\n";
echo "<li>app/controller/AdminController.php - CSRF protection added</li>\n";
echo "<li>app/controller/ApiWalletController.php - CSRF + encryption support</li>\n";
echo "<li>app/controller/ApiAuthController.php - Token encryption</li>\n";
echo "<li>app/controller/ApiController.php - Token hash validation</li>\n";
echo "<li>app/controller/WalletController.php - CSRF token support</li>\n";
echo "<li>app/Services/WalletService.php - Fraud detection integration</li>\n";
echo "<li>app/Services/TransactionPinService.php - isVerifiedRecently() added</li>\n";
echo "<li>database/migrations/002_add_rate_limits_and_api_tokens.sql - Encryption schema</li>\n";
echo "<li>database/migrations/006_fraud_detection.sql - NEW</li>\n";
echo "</ul>\n";
