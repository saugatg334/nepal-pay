<?php
/**
 * Debug Logger Helper
 * Provides comprehensive logging for debugging payment flow and user actions
 */

class DebugLogger {
    private static $logFile = __DIR__ . '/../../logs/debug.log';
    private static $paymentLogFile = __DIR__ . '/../../logs/payment_flow.log';
    private static $initialized = false;

    /**
     * Initialize logs directory if needed
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }

        $logsDir = dirname(self::$logFile);
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0755, true);
        }

        self::$initialized = true;
    }

    /**
     * Log a debug message
     */
    public static function log($message, $context = [], $level = 'INFO') {
        self::init();

        $timestamp = date('Y-m-d H:i:s.u');
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'N/A';
        $ip = self::getClientIP();

        $contextStr = !empty($context) ? PHP_EOL . json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '';

        $logEntry = "[{$timestamp}] [{$level}] [USER:{$user_id}] [IP:{$ip}] {$message}{$contextStr}" . PHP_EOL;

        @file_put_contents(self::$logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Log payment flow events
     */
    public static function logPayment($event, $data = []) {
        self::init();

        $timestamp = date('Y-m-d H:i:s.u');
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'N/A';
        $ip = self::getClientIP();

        $logEntry = [
            'timestamp' => $timestamp,
            'event' => $event,
            'user_id' => $user_id,
            'ip' => $ip,
            'data' => $data
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES) . PHP_EOL;

        @file_put_contents(self::$paymentLogFile, $logLine, FILE_APPEND);
    }

    /**
     * Log button clicks and user interactions
     */
    public static function logButtonClick($buttonId, $additionalData = []) {
        self::logPayment('BUTTON_CLICK', array_merge(['button_id' => $buttonId], $additionalData));
        self::log("Button clicked: {$buttonId}", $additionalData, 'DEBUG');
    }

    /**
     * Log form submission
     */
    public static function logFormSubmission($formId, $formData = []) {
        // Sanitize sensitive data
        $sanitizedData = self::sanitizeFormData($formData);
        self::logPayment('FORM_SUBMISSION', array_merge(['form_id' => $formId], $sanitizedData));
        self::log("Form submitted: {$formId}", $sanitizedData, 'DEBUG');
    }

    /**
     * Log payment attempt
     */
    public static function logPaymentAttempt($user_id, $biller_id, $amount, $method, $reference = '') {
        $data = [
            'user_id' => $user_id,
            'biller_id' => $biller_id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $reference
        ];
        self::logPayment('PAYMENT_ATTEMPT_START', $data);
        self::log("Payment attempt started", $data, 'INFO');
    }

    /**
     * Log payment validation
     */
    public static function logPaymentValidation($user_id, $validation_checks = []) {
        $data = array_merge(['user_id' => $user_id], $validation_checks);
        self::logPayment('PAYMENT_VALIDATION', $data);
        self::log("Payment validation", $data, 'DEBUG');
    }

    /**
     * Log balance check
     */
    public static function logBalanceCheck($user_id, $required_amount, $current_balance, $passed = true) {
        $data = [
            'user_id' => $user_id,
            'required_amount' => $required_amount,
            'current_balance' => $current_balance,
            'check_passed' => $passed
        ];
        self::logPayment('BALANCE_CHECK', $data);
        $status = $passed ? 'PASSED' : 'FAILED';
        self::log("Balance check [{$status}]", $data, $passed ? 'DEBUG' : 'WARNING');
    }

    /**
     * Log wallet update
     */
    public static function logWalletUpdate($user_id, $amount_change, $old_balance, $new_balance) {
        $data = [
            'user_id' => $user_id,
            'amount_change' => $amount_change,
            'old_balance' => $old_balance,
            'new_balance' => $new_balance
        ];
        self::logPayment('WALLET_UPDATE', $data);
        self::log("Wallet updated", $data, 'INFO');
    }

    /**
     * Log transaction recording
     */
    public static function logTransactionRecord($txn_id, $user_id, $amount, $type, $status) {
        $data = [
            'txn_id' => $txn_id,
            'user_id' => $user_id,
            'amount' => $amount,
            'type' => $type,
            'status' => $status
        ];
        self::logPayment('TRANSACTION_RECORD', $data);
        self::log("Transaction recorded: {$txn_id}", $data, 'INFO');
    }

    /**
     * Log payment success
     */
    public static function logPaymentSuccess($txn_id, $user_id, $biller_id, $amount, $method) {
        $data = [
            'txn_id' => $txn_id,
            'user_id' => $user_id,
            'biller_id' => $biller_id,
            'amount' => $amount,
            'method' => $method
        ];
        self::logPayment('PAYMENT_SUCCESS', $data);
        self::log("Payment successful", $data, 'INFO');
    }

    /**
     * Log payment error
     */
    public static function logPaymentError($error_message, $user_id = null, $payment_context = []) {
        $data = array_merge([
            'error' => $error_message,
            'user_id' => $user_id ?? (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'N/A')
        ], $payment_context);
        self::logPayment('PAYMENT_ERROR', $data);
        self::log("Payment error: {$error_message}", $data, 'ERROR');
    }

    /**
     * Log API request
     */
    public static function logAPIRequest($endpoint, $method, $params = []) {
        $sanitizedParams = self::sanitizeFormData($params);
        $data = [
            'endpoint' => $endpoint,
            'method' => $method,
            'params' => $sanitizedParams
        ];
        self::logPayment('API_REQUEST', $data);
        self::log("API request: {$method} {$endpoint}", $sanitizedParams, 'DEBUG');
    }

    /**
     * Log API response
     */
    public static function logAPIResponse($endpoint, $status_code, $response = []) {
        $data = [
            'endpoint' => $endpoint,
            'status_code' => $status_code,
            'response_summary' => is_array($response) ? json_encode($response) : (string)$response
        ];
        self::logPayment('API_RESPONSE', $data);
        self::log("API response: {$endpoint} [{$status_code}]", $data, 'DEBUG');
    }

    /**
     * Get client IP address
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'N/A';
    }

    /**
     * Sanitize form data by removing/masking sensitive fields
     */
    private static function sanitizeFormData($data) {
        $sanitized = [];
        $sensitiveFields = ['password', 'pin', 'card', 'security'];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            $isSensitive = false;

            foreach ($sensitiveFields as $sensitive) {
                if (strpos($lowerKey, $sensitive) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get debug logs
     */
    public static function getLogs($lines = 100, $type = 'general') {
        self::init();
        $logFile = ($type === 'payment') ? self::$paymentLogFile : self::$logFile;

        if (!file_exists($logFile)) {
            return [];
        }

        $allLines = file($logFile);
        return array_slice($allLines, -$lines);
    }

    /**
     * Clear logs
     */
    public static function clearLogs($type = 'general') {
        self::init();
        $logFile = ($type === 'payment') ? self::$paymentLogFile : self::$logFile;

        if (file_exists($logFile)) {
            @unlink($logFile);
        }
    }

    /**
     * Get payment flow summary
     */
    public static function getPaymentFlowSummary($txn_id = null) {
        self::init();

        if (!file_exists(self::$paymentLogFile)) {
            return [];
        }

        $lines = file(self::$paymentLogFile);
        $summary = [];

        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            if (!$entry) continue;

            if ($txn_id && isset($entry['data']['txn_id']) && $entry['data']['txn_id'] !== $txn_id) {
                continue;
            }

            $summary[] = $entry;
        }

        return $summary;
    }
}
?>
