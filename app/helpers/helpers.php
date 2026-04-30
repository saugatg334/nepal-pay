<?php
/**
 * Global Helper Functions
 * These are loaded via Composer's "files" autoload.
 * WARNING: Keep these pure functions (no dependencies on classes)
 */

function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate cryptographically secure UUID v4
 * WHY: mt_rand() is predictable. random_int() is CSPRNG-7
 */
function generateUUID() {
    $data = random_bytes(16);
    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Generate unique transaction ID
 * WHY: Predictable IDs enable timestamp guessing and enumeration.
 * random_int() adds entropy.
 */
function generateTransactionId() {
    return 'TXN' . date('YmdHis') . random_int(1000, 9999);
}

/**
 * Generate wallet number from phone
 * WHY: Simple transformation, not security-critical
 */
function generateWalletNumber($phone) {
    $prefix = 'NP';
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    return $prefix . $cleanPhone;
}

/**
 * Generate merchant code
 * WHY: Predictable codes could allow merchant ID guessing.
 * Use random_int for security.
 */
function generateMerchantCode() {
    return 'MER' . date('Ymd') . random_int(1000, 9999);
}

function formatCurrency($amount, $currency = 'NPR') {
    return $currency . ' ' . number_format($amount, 2);
}

function redirect($url) {
    session_write_close();
    header("Location: {$url}");
    exit;
}

function jsonResponse($data, $status = 200) {
    session_write_close();
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function flash($message, $type = 'success') {
    Session::init();
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type = 'success') {
    Session::init();
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function hasFlash($type = 'success') {
    Session::init();
    return isset($_SESSION['flash'][$type]);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^9[78]\d{8}$/', $phone);
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}